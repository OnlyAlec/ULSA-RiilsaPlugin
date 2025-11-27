<?php

declare(strict_types=1);

/**
 * Database Subscriber Repository Implementation
 *
 * @package RIILSA\Infrastructure\Repositories
 * @since 3.1.0
 */

namespace RIILSA\Infrastructure\Repositories;

use RIILSA\Domain\Entities\Subscriber;
use RIILSA\Domain\Repositories\SubscriberRepositoryInterface;
use RIILSA\Domain\ValueObjects\Email;
use RIILSA\Domain\ValueObjects\SubscriberStatus;
use RIILSA\Domain\ValueObjects\DateRange;

/**
 * Database implementation of Subscriber repository
 * 
 * Pattern: Repository Pattern
 * This class implements subscriber persistence using direct database access
 */
class DatabaseSubscriberRepository implements SubscriberRepositoryInterface
{
    /**
     * WordPress database interface
     *
     * @var \wpdb
     */
    private \wpdb $wpdb;
    
    /**
     * Email table name
     *
     * @var string
     */
    private string $emailTable;
    
    /**
     * Token table name
     *
     * @var string
     */
    private string $tokenTable;
    
    /**
     * Constructor
     *
     * @param \wpdb $wpdb
     */
    public function __construct(\wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
        $this->emailTable = RIILSA_TABLE_EMAIL;
        $this->tokenTable = RIILSA_TABLE_EMAIL_TOKENS;
    }

    /**
     * Get common select fields mapped to entity properties
     * 
     * @return string
     */
    private function getSelectFields(): string
    {
        return "e.id, 
                e.email, 
                e.id_dependency as dependency_id, 
                CASE WHEN e.verified = 1 THEN 'confirmed' ELSE 'pending' END as status,
                e.id_brevo as external_id, 
                e.date_added as subscribed_at,
                e.date_opt_in as confirmed_at,
                t.token, 
                t.date_expire as token_expires_at";
    }
    
    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?Subscriber
    {
        $sql = $this->wpdb->prepare(
            "SELECT " . $this->getSelectFields() . "
             FROM {$this->emailTable} e
             LEFT JOIN {$this->tokenTable} t ON e.id = t.id_email
             WHERE e.id = %d",
            $id
        );
        
        $row = $this->wpdb->get_row($sql, ARRAY_A);
        
        if (!$row) {
            return null;
        }
        
        return Subscriber::fromDatabaseRecord($row);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByEmail(Email $email): ?Subscriber
    {
        $sql = $this->wpdb->prepare(
            "SELECT " . $this->getSelectFields() . "
             FROM {$this->emailTable} e
             LEFT JOIN {$this->tokenTable} t ON e.id = t.id_email
             WHERE e.email = %s",
            $email->getValue()
        );
        
        $row = $this->wpdb->get_row($sql, ARRAY_A);
        
        if (!$row) {
            return null;
        }
        
        return Subscriber::fromDatabaseRecord($row);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByExternalId(string $externalId): ?Subscriber
    {
        $sql = $this->wpdb->prepare(
            "SELECT " . $this->getSelectFields() . "
             FROM {$this->emailTable} e
             LEFT JOIN {$this->tokenTable} t ON e.id = t.id_email
             WHERE e.id_brevo = %s",
            $externalId
        );
        
        $row = $this->wpdb->get_row($sql, ARRAY_A);
        
        if (!$row) {
            return null;
        }
        
        return Subscriber::fromDatabaseRecord($row);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByToken(string $token): ?Subscriber
    {
        $sql = $this->wpdb->prepare(
            "SELECT " . $this->getSelectFields() . "
             FROM {$this->tokenTable} t
             INNER JOIN {$this->emailTable} e ON t.id_email = e.id
             WHERE t.token = %s",
            $token
        );
        
        $row = $this->wpdb->get_row($sql, ARRAY_A);
        
        if (!$row) {
            return null;
        }
        
        return Subscriber::fromDatabaseRecord($row);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findAll(
        array $criteria = [],
        array $orderBy = ['subscribedAt' => 'DESC'],
        ?int $limit = null,
        int $offset = 0
    ): array {
        $sql = "SELECT " . $this->getSelectFields() . "
                FROM {$this->emailTable} e
                LEFT JOIN {$this->tokenTable} t ON e.id = t.id_email";
        
        $where = $this->buildWhereClauses($criteria);
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $sql .= $this->buildOrderByClause($orderBy);
        
        if ($limit !== null) {
            $sql .= $this->wpdb->prepare(" LIMIT %d OFFSET %d", $limit, $offset);
        }
        
        $results = $this->wpdb->get_results($sql, ARRAY_A);
        
        return array_map(
            fn($row) => Subscriber::fromDatabaseRecord($row),
            $results ?? []
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByStatus(
        SubscriberStatus $status,
        ?int $limit = null,
        int $offset = 0
    ): array {
        return $this->findAll(
            ['status' => $status],
            ['subscribedAt' => 'DESC'],
            $limit,
            $offset
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByDependency(
        int $dependencyId,
        ?SubscriberStatus $status = null,
        ?int $limit = null,
        int $offset = 0
    ): array {
        $criteria = ['dependency_id' => $dependencyId];
        
        if ($status) {
            $criteria['status'] = $status;
        }
        
        return $this->findAll($criteria, ['subscribedAt' => 'DESC'], $limit, $offset);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findActive(?int $limit = null, int $offset = 0): array
    {
        return $this->findByStatus(SubscriberStatus::CONFIRMED, $limit, $offset);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findRecipients(?array $dependencyIds = null): array
    {
        $sql = "SELECT " . $this->getSelectFields() . "
                FROM {$this->emailTable} e
                LEFT JOIN {$this->tokenTable} t ON e.id = t.id_email
                WHERE e.verified = 1";
        
        $params = [];
        
        if ($dependencyIds !== null && !empty($dependencyIds)) {
            $placeholders = implode(',', array_fill(0, count($dependencyIds), '%d'));
            $sql .= " AND e.id_dependency IN ({$placeholders})";
            $params = array_merge($params, $dependencyIds);
        }
        
        if (!empty($params)) {
            $sql = $this->wpdb->prepare($sql, ...$params);
        }
        
        $results = $this->wpdb->get_results($sql, ARRAY_A);
        
        return array_map(
            fn($row) => Subscriber::fromDatabaseRecord($row),
            $results ?? []
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function findBySubscriptionDate(
        DateRange $dateRange,
        ?SubscriberStatus $status = null
    ): array {
        $criteria = [
            'date_range' => $dateRange,
        ];
        
        if ($status) {
            $criteria['status'] = $status;
        }
        
        return $this->findAll($criteria);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findWithExpiredTokens(): array
    {
        $sql = $this->wpdb->prepare(
            "SELECT " . $this->getSelectFields() . "
             FROM {$this->tokenTable} t
             INNER JOIN {$this->emailTable} e ON t.id_email = e.id
             WHERE t.date_expire < %s
             AND e.verified = 0",
            current_time('mysql')
        );
        
        $results = $this->wpdb->get_results($sql, ARRAY_A);
        
        return array_map(
            fn($row) => Subscriber::fromDatabaseRecord($row),
            $results ?? []
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function search(
        string $keyword,
        ?int $limit = null,
        int $offset = 0
    ): array {
        $sql = $this->wpdb->prepare(
            "SELECT " . $this->getSelectFields() . "
             FROM {$this->emailTable} e
             LEFT JOIN {$this->tokenTable} t ON e.id = t.id_email
             WHERE e.email LIKE %s
             ORDER BY e.date_added DESC",
            '%' . $this->wpdb->esc_like($keyword) . '%'
        );
        
        if ($limit !== null) {
            $sql .= $this->wpdb->prepare(" LIMIT %d OFFSET %d", $limit, $offset);
        }
        
        $results = $this->wpdb->get_results($sql, ARRAY_A);
        
        return array_map(
            fn($row) => Subscriber::fromDatabaseRecord($row),
            $results ?? []
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function save(Subscriber $subscriber): Subscriber
    {
        $emailData = [
            'email' => $subscriber->getEmail()->getValue(),
            'id_dependency' => $subscriber->getDependencyId(),
            'verified' => ($subscriber->getStatus() === SubscriberStatus::CONFIRMED) ? 1 : 0,
            'id_brevo' => $subscriber->getExternalId(),
            'date_added' => $subscriber->getSubscribedAt()->format('Y-m-d H:i:s'),
            'date_opt_in' => $subscriber->getConfirmedAt()?->format('Y-m-d H:i:s'),
        ];
        
        if ($subscriber->getId()) {
            // Update existing
            $result = $this->wpdb->update(
                $this->emailTable,
                $emailData,
                ['id' => $subscriber->getId()],
                ['%s', '%d', '%d', '%s', '%s', '%s'],
                ['%d']
            );
            
            if ($result === false) {
                throw new \RuntimeException('Failed to update subscriber: ' . $this->wpdb->last_error);
            }
        } else {
            // Insert new
            $result = $this->wpdb->insert(
                $this->emailTable,
                $emailData,
                ['%s', '%d', '%d', '%s', '%s', '%s']
            );
            
            if ($result === false) {
                throw new \RuntimeException('Failed to insert subscriber: ' . $this->wpdb->last_error);
            }
            
            $subscriber->setId($this->wpdb->insert_id);
        }
        
        // Handle token separately
        if ($subscriber->getConfirmationToken()) {
            $this->saveToken($subscriber);
        } else {
            $this->deleteToken($subscriber->getId());
        }
        
        return $subscriber;
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete(Subscriber $subscriber): bool
    {
        if (!$subscriber->getId()) {
            return false;
        }
        
        return $this->deleteById($subscriber->getId());
    }
    
    /**
     * {@inheritdoc}
     */
    public function deleteById(int $id): bool
    {
        // First delete token
        $this->deleteToken($id);
        
        $result = $this->wpdb->delete(
            $this->emailTable,
            ['id' => $id],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function deleteExpiredPending(): int
    {
        $expiredSubscribers = $this->findWithExpiredTokens();
        $deleted = 0;
        
        foreach ($expiredSubscribers as $subscriber) {
            if ($this->delete($subscriber)) {
                $deleted++;
            }
        }
        
        return $deleted;
    }
    
    /**
     * {@inheritdoc}
     */
    public function existsByEmail(Email $email): bool
    {
        $count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->emailTable} WHERE email = %s",
            $email->getValue()
        ));
        
        return (int)$count > 0;
    }
    
    /**
     * {@inheritdoc}
     */
    public function count(array $criteria = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->emailTable} e";
        
        $where = $this->buildWhereClauses($criteria);
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        return (int)$this->wpdb->get_var($sql);
    }
    
    /**
     * {@inheritdoc}
     */
    public function countByStatus(SubscriberStatus $status): int
    {
        return $this->count(['status' => $status]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function countActive(): int
    {
        return $this->countByStatus(SubscriberStatus::CONFIRMED);
    }
    
    /**
     * {@inheritdoc}
     */
    public function countByDependency(int $dependencyId, ?SubscriberStatus $status = null): int
    {
        $criteria = ['dependency_id' => $dependencyId];
        
        if ($status) {
            $criteria['status'] = $status;
        }
        
        return $this->count($criteria);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->count(),
            'confirmed' => $this->countByStatus(SubscriberStatus::CONFIRMED),
            'pending' => $this->countByStatus(SubscriberStatus::PENDING),
            'unsubscribed' => $this->countByStatus(SubscriberStatus::UNSUBSCRIBED),
            'bounced' => $this->countByStatus(SubscriberStatus::BOUNCED),
            'blocked' => $this->countByStatus(SubscriberStatus::BLOCKED),
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function getGrowthStatistics(DateRange $dateRange, string $groupBy = 'day'): array
    {
        $dateFormat = match($groupBy) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%U',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };
        
        $sql = $this->wpdb->prepare(
            "SELECT DATE_FORMAT(date_added, %s) as period,
                    COUNT(*) as count,
                    SUM(CASE WHEN verified = 1 THEN 1 ELSE 0 END) as confirmed
             FROM {$this->emailTable}
             WHERE date_added BETWEEN %s AND %s
             GROUP BY period
             ORDER BY period ASC",
            $dateFormat,
            $dateRange->getStartDate()->format('Y-m-d'),
            $dateRange->getEndDate()->format('Y-m-d')
        );
        
        return $this->wpdb->get_results($sql, ARRAY_A) ?? [];
    }
    
    /**
     * {@inheritdoc}
     */
    public function batchUpdateStatus(array $subscriberIds, SubscriberStatus $status): int
    {
        if (empty($subscriberIds)) {
            return 0;
        }
        
        $placeholders = implode(',', array_fill(0, count($subscriberIds), '%d'));
        $verified = ($status === SubscriberStatus::CONFIRMED) ? 1 : 0;
        
        $sql = $this->wpdb->prepare(
            "UPDATE {$this->emailTable}
             SET verified = %d
             WHERE id IN ({$placeholders})",
            $verified,
            ...$subscriberIds
        );
        
        return (int)$this->wpdb->query($sql);
    }
    
    /**
     * {@inheritdoc}
     */
    public function beginTransaction(): void
    {
        $this->wpdb->query('START TRANSACTION');
    }
    
    /**
     * {@inheritdoc}
     */
    public function commit(): void
    {
        $this->wpdb->query('COMMIT');
    }
    
    /**
     * {@inheritdoc}
     */
    public function rollback(): void
    {
        $this->wpdb->query('ROLLBACK');
    }
    
    /**
     * Save confirmation token
     *
     * @param Subscriber $subscriber
     * @return void
     */
    private function saveToken(Subscriber $subscriber): void
    {
        $tokenData = [
            'id_email' => $subscriber->getId(),
            'token' => $subscriber->getConfirmationToken(),
            'date_expire' => $subscriber->getTokenExpiresAt()->format('Y-m-d H:i:s'),
            'date_created' => current_time('mysql'),
        ];
        
        // Check if token exists
        $exists = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tokenTable} WHERE id_email = %d",
            $subscriber->getId()
        ));
        
        if ($exists) {
            $this->wpdb->update(
                $this->tokenTable,
                $tokenData,
                ['id_email' => $subscriber->getId()],
                ['%d', '%s', '%s', '%s'],
                ['%d']
            );
        } else {
            $this->wpdb->insert(
                $this->tokenTable,
                $tokenData,
                ['%d', '%s', '%s', '%s']
            );
        }
    }
    
    /**
     * Delete confirmation token
     *
     * @param int $subscriberId
     * @return void
     */
    private function deleteToken(int $subscriberId): void
    {
        $this->wpdb->delete(
            $this->tokenTable,
            ['id_email' => $subscriberId],
            ['%d']
        );
    }
    
    /**
     * Build WHERE clauses from criteria
     *
     * @param array $criteria
     * @return array
     */
    private function buildWhereClauses(array $criteria): array
    {
        $where = [];
        
        if (isset($criteria['status'])) {
            $verified = ($criteria['status'] === SubscriberStatus::CONFIRMED) ? 1 : 0;
            $where[] = $this->wpdb->prepare(
                'e.verified = %d',
                $verified
            );
        }
        
        if (isset($criteria['dependency_id'])) {
            $where[] = $this->wpdb->prepare(
                'e.id_dependency = %d',
                $criteria['dependency_id']
            );
        }
        
        if (isset($criteria['date_range'])) {
            $where[] = $this->wpdb->prepare(
                'e.date_added BETWEEN %s AND %s',
                $criteria['date_range']->getStartDate()->format('Y-m-d'),
                $criteria['date_range']->getEndDate()->format('Y-m-d')
            );
        }
        
        return $where;
    }
    
    /**
     * Build ORDER BY clause
     *
     * @param array $orderBy
     * @return string
     */
    private function buildOrderByClause(array $orderBy): string
    {
        if (empty($orderBy)) {
            return ' ORDER BY e.date_added DESC';
        }
        
        $field = key($orderBy);
        $direction = current($orderBy);
        
        $column = match($field) {
            'email' => 'e.email',
            'subscribedAt' => 'e.date_added',
            'confirmedAt' => 'e.date_opt_in',
            default => 'e.date_added',
        };
        
        $order = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        
        return " ORDER BY {$column} {$order}";
    }
}
