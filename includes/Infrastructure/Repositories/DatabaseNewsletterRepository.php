<?php

declare(strict_types=1);

/**
 * Database Newsletter Repository Implementation
 *
 * @package RIILSA\Infrastructure\Repositories
 * @since 3.1.0
 */

namespace RIILSA\Infrastructure\Repositories;

use RIILSA\Domain\Entities\Newsletter;
use RIILSA\Domain\Repositories\NewsletterRepositoryInterface;
use RIILSA\Domain\ValueObjects\NewsletterStatus;
use RIILSA\Domain\ValueObjects\DateRange;

/**
 * Database implementation of Newsletter repository
 * 
 * Pattern: Repository Pattern
 * This class implements newsletter persistence using direct database access
 */
class DatabaseNewsletterRepository implements NewsletterRepositoryInterface
{
    /**
     * WordPress database interface
     *
     * @var \wpdb
     */
    private \wpdb $wpdb;

    /**
     * Table name
     *
     * @var string
     */
    private string $tableName;

    /**
     * Constructor
     *
     * @param \wpdb $wpdb
     */
    public function __construct(\wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
        $this->tableName = RIILSA_TABLE_NEWSLETTER_LOGS;
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?Newsletter
    {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->tableName} WHERE number = %d",
            $id
        );

        $row = $this->wpdb->get_row($sql, ARRAY_A);

        if (!$row) {
            return null;
        }

        return Newsletter::fromDatabaseRecord($row);
    }

    /**
     * {@inheritdoc}
     */
    public function findByNumber(int $number): ?Newsletter
    {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->tableName} WHERE number = %d",
            $number
        );

        $row = $this->wpdb->get_row($sql, ARRAY_A);

        if (!$row) {
            return null;
        }

        return Newsletter::fromDatabaseRecord($row);
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(
        array $criteria = [],
        array $orderBy = ['number' => 'DESC'],
        ?int $limit = null,
        int $offset = 0
    ): array {
        $sql = "SELECT * FROM {$this->tableName}";

        // Apply criteria (WHERE clause)
        $where = $this->buildWhereClauses($criteria);
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        // Apply ordering
        $sql .= $this->buildOrderByClause($orderBy);

        // Apply limit and offset
        if ($limit !== null) {
            $sql .= $this->wpdb->prepare(" LIMIT %d OFFSET %d", $limit, $offset);
        }

        $results = $this->wpdb->get_results($sql, ARRAY_A);

        return array_map(
            fn($row) => Newsletter::fromDatabaseRecord($row),
            $results ?? []
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findByStatus(
        NewsletterStatus $status,
        ?int $limit = null,
        int $offset = 0
    ): array {
        return $this->findAll(
            ['status' => $status],
            ['number' => 'DESC'],
            $limit,
            $offset
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findByStatuses(
        array $statuses,
        ?int $limit = null,
        int $offset = 0
    ): array {
        $statusValues = array_map(fn($s) => $s->value, $statuses);
        $placeholders = implode(',', array_fill(0, count($statusValues), '%d'));

        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->tableName} WHERE id_status IN ({$placeholders}) ORDER BY number DESC",
            ...$statusValues
        );

        if ($limit !== null) {
            $sql .= $this->wpdb->prepare(" LIMIT %d OFFSET %d", $limit, $offset);
        }

        $results = $this->wpdb->get_results($sql, ARRAY_A);

        return array_map(
            fn($row) => Newsletter::fromDatabaseRecord($row),
            $results ?? []
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findScheduledInRange(DateRange $dateRange): array
    {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->tableName} 
             WHERE id_status = %d 
             AND scheduled_at BETWEEN %s AND %s
             ORDER BY scheduled_at ASC",
            NewsletterStatus::SCHEDULED->value,
            $dateRange->getStartDate()->format('Y-m-d H:i:s'),
            $dateRange->getEndDate()->format('Y-m-d H:i:s')
        );

        $results = $this->wpdb->get_results($sql, ARRAY_A);

        return array_map(
            fn($row) => Newsletter::fromDatabaseRecord($row),
            $results ?? []
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findSentInRange(DateRange $dateRange): array
    {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->tableName} 
             WHERE id_status = %d 
             AND sent_at BETWEEN %s AND %s
             ORDER BY sent_at DESC",
            NewsletterStatus::SENT->value,
            $dateRange->getStartDate()->format('Y-m-d H:i:s'),
            $dateRange->getEndDate()->format('Y-m-d H:i:s')
        );

        $results = $this->wpdb->get_results($sql, ARRAY_A);

        return array_map(
            fn($row) => Newsletter::fromDatabaseRecord($row),
            $results ?? []
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getLastNewsletterNumber(): int
    {
        // Also check WordPress taxonomy for backward compatibility
        $boletines = get_terms([
            'taxonomy' => RIILSA_TAXONOMY_NEWSLETTER,
            'hide_empty' => false
        ]);

        $lastNumberFromTax = 0;
        foreach ($boletines as $boletin) {
            $number = intval(preg_replace('/\D/', '', $boletin->name));
            if ($number > $lastNumberFromTax) {
                $lastNumberFromTax = $number;
            }
        }

        // Check database table
        $lastNumberFromDb = (int) $this->wpdb->get_var(
            "SELECT MAX(number) FROM {$this->tableName}"
        );

        return max($lastNumberFromTax, $lastNumberFromDb);
    }

    /**
     * {@inheritdoc}
     */
    public function getNextNewsletterNumber(): int
    {
        return $this->getLastNewsletterNumber() + 1;
    }

    /**
     * {@inheritdoc}
     */
    public function save(Newsletter $newsletter): Newsletter
    {
        $data = [
            'number' => $newsletter->getNumber(),
            'id_status' => $newsletter->getStatus()->value,
            'news_collection' => implode(',', $newsletter->getNewsIds()),
            'text_header' => $newsletter->getHeaderText(),
            'html_content' => $newsletter->getHtmlContent(),
            'scheduled_at' => $newsletter->getScheduledAt()?->format('Y-m-d H:i:s'),
            'sent_at' => $newsletter->getSentAt()?->format('Y-m-d H:i:s'),
            'date_updated' => current_time('mysql'),
            'statistics' => json_encode($newsletter->getStatistics()),
        ];

        if ($newsletter->getId()) {
            // Update existing
            $result = $this->wpdb->update(
                $this->tableName,
                $data,
                ['id' => $newsletter->getId()],
                ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s'],
                ['%d']
            );

            if ($result === false) {
                throw new \RuntimeException('Failed to update newsletter: ' . $this->wpdb->last_error);
            }
        } else {
            // Insert new
            $data['date_created'] = current_time('mysql');

            $result = $this->wpdb->insert(
                $this->tableName,
                $data,
                ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
            );

            if ($result === false) {
                throw new \RuntimeException('Failed to insert newsletter: ' . $this->wpdb->last_error);
            }

            $newsletter->setId($this->wpdb->insert_id);
        }

        return $newsletter;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Newsletter $newsletter): bool
    {
        if (!$newsletter->getId()) {
            return false;
        }

        return $this->deleteById($newsletter->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById(int $id): bool
    {
        $result = $this->wpdb->delete(
            $this->tableName,
            ['id' => $id],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function existsByNumber(int $number): bool
    {
        $count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tableName} WHERE number = %d",
            $number
        ));

        return (int) $count > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function count(array $criteria = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->tableName}";

        $where = $this->buildWhereClauses($criteria);
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        return (int) $this->wpdb->get_var($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function countByStatus(NewsletterStatus $status): int
    {
        return $this->count(['status' => $status]);
    }

    /**
     * {@inheritdoc}
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->count(),
            'draft' => $this->countByStatus(NewsletterStatus::DRAFT),
            'scheduled' => $this->countByStatus(NewsletterStatus::SCHEDULED),
            'sending' => $this->countByStatus(NewsletterStatus::SENDING),
            'sent' => $this->countByStatus(NewsletterStatus::SENT),
            'failed' => $this->countByStatus(NewsletterStatus::FAILED),
            'cancelled' => $this->countByStatus(NewsletterStatus::CANCELLED),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function updateStatistics(int $newsletterId, array $statistics): bool
    {
        $result = $this->wpdb->update(
            $this->tableName,
            ['statistics' => json_encode($statistics)],
            ['id' => $newsletterId],
            ['%s'],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function findReadyToSend(): array
    {
        $now = current_time('mysql');

        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->tableName} 
             WHERE id_status = %d 
             AND scheduled_at <= %s
             ORDER BY scheduled_at ASC",
            NewsletterStatus::SCHEDULED->value,
            $now
        );

        $results = $this->wpdb->get_results($sql, ARRAY_A);

        return array_map(
            fn($row) => Newsletter::fromDatabaseRecord($row),
            $results ?? []
        );
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
     * Build WHERE clauses from criteria
     *
     * @param array $criteria
     * @return array
     */
    private function buildWhereClauses(array $criteria): array
    {
        $where = [];

        if (isset($criteria['status'])) {
            $where[] = $this->wpdb->prepare(
                'id_status = %d',
                $criteria['status']->value
            );
        }

        if (isset($criteria['number'])) {
            $where[] = $this->wpdb->prepare(
                'number = %d',
                $criteria['number']
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
            return ' ORDER BY number DESC';
        }

        $field = key($orderBy);
        $direction = current($orderBy);

        $column = match ($field) {
            'number' => 'number',
            'createdAt' => 'date_created',
            'sentAt' => 'sent_at',
            'scheduledAt' => 'scheduled_at',
            default => 'number',
        };

        $order = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        return " ORDER BY {$column} {$order}";
    }
}
