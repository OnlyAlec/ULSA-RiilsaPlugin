<?php

declare(strict_types=1);

/**
 * WordPress Call Repository Implementation
 *
 * @package RIILSA\Infrastructure\Repositories
 * @since 3.1.0
 */

namespace RIILSA\Infrastructure\Repositories;

use RIILSA\Domain\Entities\Call;
use RIILSA\Domain\Repositories\CallRepositoryInterface;
use RIILSA\Domain\ValueObjects\PostStatus;
use RIILSA\Domain\ValueObjects\ProjectStatus;
use RIILSA\Domain\ValueObjects\DateRange;

/**
 * WordPress implementation of Call repository
 * 
 * Pattern: Repository Pattern
 * This class implements call/announcement persistence using WordPress APIs
 */
class WordPressCallRepository implements CallRepositoryInterface
{
    /**
     * WordPress database interface
     *
     * @var \wpdb
     */
    private \wpdb $wpdb;
    
    /**
     * Constructor
     *
     * @param \wpdb $wpdb
     */
    public function __construct(\wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
    }
    
    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?Call
    {
        $post = get_post($id);
        
        if (!$post || $post->post_type !== RIILSA_POST_TYPE_CALL) {
            return null;
        }
        
        return $this->createCallFromPost($post);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByExternalId(string $externalId): ?Call
    {
        $args = [
            'post_type' => RIILSA_POST_TYPE_CALL,
            'posts_per_page' => 1,
            'post_status' => 'any',
            'meta_key' => 'id',
            'meta_value' => $externalId,
        ];
        
        $query = new \WP_Query($args);
        
        if (!$query->have_posts()) {
            return null;
        }
        
        return $this->createCallFromPost($query->posts[0]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findAll(
        array $criteria = [],
        array $orderBy = ['closingDate' => 'ASC'],
        ?int $limit = null,
        int $offset = 0
    ): array {
        $args = [
            'post_type' => RIILSA_POST_TYPE_CALL,
            'posts_per_page' => $limit ?? -1,
            'offset' => $offset,
            'post_status' => 'any',
        ];
        
        $args = $this->applyCriteria($args, $criteria);
        $args = $this->applyOrderBy($args, $orderBy);
        
        $query = new \WP_Query($args);
        
        return array_map([$this, 'createCallFromPost'], $query->posts);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByStatus(
        ProjectStatus $status,
        ?int $limit = null,
        int $offset = 0
    ): array {
        $args = [
            'post_type' => RIILSA_POST_TYPE_CALL,
            'posts_per_page' => $limit ?? -1,
            'offset' => $offset,
            'post_status' => 'any',
            'tax_query' => [
                [
                    'taxonomy' => RIILSA_TAXONOMY_STATUS,
                    'field' => 'name',
                    'terms' => $status->toTaxonomyTerm(),
                ],
            ],
        ];
        
        $query = new \WP_Query($args);
        
        return array_map([$this, 'createCallFromPost'], $query->posts);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByPostStatus(
        PostStatus $postStatus,
        ?int $limit = null,
        int $offset = 0
    ): array {
        $args = [
            'post_type' => RIILSA_POST_TYPE_CALL,
            'posts_per_page' => $limit ?? -1,
            'offset' => $offset,
            'post_status' => $postStatus->toWordPress(),
        ];
        
        $query = new \WP_Query($args);
        
        return array_map([$this, 'createCallFromPost'], $query->posts);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findOpen(?int $limit = null, int $offset = 0): array
    {
        return $this->findByStatus(ProjectStatus::CURRENT, $limit, $offset);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findClosingSoon(int $days = 7, ?int $limit = null): array
    {
        $today = new \DateTime();
        $futureDate = (clone $today)->modify("+{$days} days");
        
        // Get all open calls
        $openCalls = $this->findOpen();
        
        // Filter by closing date
        return array_filter($openCalls, function(Call $call) use ($today, $futureDate) {
            $closingDate = $call->getClosingDate();
            return $closingDate >= $today && $closingDate <= $futureDate;
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function findExpired(?int $limit = null, int $offset = 0): array
    {
        return $this->findByStatus(ProjectStatus::EXPIRED, $limit, $offset);
    }
    
    /**
     * {@inheritdoc}
     */
    public function search(
        string $keyword,
        array $searchFields = ['title', 'description', 'contact'],
        ?int $limit = null,
        int $offset = 0
    ): array {
        $args = [
            'post_type' => RIILSA_POST_TYPE_CALL,
            'posts_per_page' => $limit ?? -1,
            'offset' => $offset,
            'post_status' => 'any',
            's' => $keyword,
        ];
        
        $query = new \WP_Query($args);
        
        return array_map([$this, 'createCallFromPost'], $query->posts);
    }
    
    /**
     * {@inheritdoc}
     */
    public function save(Call $call): Call
    {
        $postData = [
            'post_type' => RIILSA_POST_TYPE_CALL,
            'post_title' => $call->getTitle(),
            'post_content' => $call->getDescription(),
            'post_status' => $call->getPostStatus()->toWordPress(),
            'post_author' => get_current_user_id() ?: 1,
        ];
        
        if ($call->getId()) {
            $postData['ID'] = $call->getId();
            $postId = wp_update_post($postData);
        } else {
            $postId = wp_insert_post($postData);
        }
        
        if (is_wp_error($postId)) {
            throw new \RuntimeException('Failed to save call: ' . $postId->get_error_message());
        }
        
        $call->setId($postId);
        
        // Save meta fields
        $this->saveCallMeta($call);
        
        return $call;
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete(Call $call): bool
    {
        if (!$call->getId()) {
            return false;
        }
        
        return $this->deleteById($call->getId());
    }
    
    /**
     * {@inheritdoc}
     */
    public function deleteById(int $id): bool
    {
        $result = wp_delete_post($id, true);
        return $result !== false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function existsByTitle(string $title): bool
    {
        $count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->wpdb->posts}
             WHERE post_title = %s
             AND post_type = %s AND post_status != 'trash'",
            $title,
            RIILSA_POST_TYPE_CALL
        ));
        
        return (int)$count > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function existsByExternalId(string $externalId): bool
    {
        $count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->wpdb->postmeta} pm
             INNER JOIN {$this->wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = 'id' AND pm.meta_value = %s
             AND p.post_type = %s AND p.post_status != 'trash'",
            $externalId,
            RIILSA_POST_TYPE_CALL
        ));
        
        return (int)$count > 0;
    }
    
    /**
     * {@inheritdoc}
     */
    public function count(array $criteria = []): int
    {
        $args = [
            'post_type' => RIILSA_POST_TYPE_CALL,
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids',
        ];
        
        $args = $this->applyCriteria($args, $criteria);
        $query = new \WP_Query($args);
        
        return $query->found_posts;
    }
    
    /**
     * {@inheritdoc}
     */
    public function countOpen(): int
    {
        return $this->count(['call_status' => ProjectStatus::CURRENT]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function countExpired(): int
    {
        return $this->count(['call_status' => ProjectStatus::EXPIRED]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function updateExpiredStatuses(): int
    {
        $calls = $this->findByStatus(ProjectStatus::CURRENT);
        $updated = 0;
        
        foreach ($calls as $call) {
            $call->updateCallStatus();
            
            if ($call->getCallStatus() === ProjectStatus::EXPIRED) {
                $this->save($call);
                $this->updateCallStatusTaxonomy($call);
                $updated++;
            }
        }
        
        return $updated;
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
     * Create Call entity from WordPress post
     *
     * @param \WP_Post $post
     * @return Call
     */
    private function createCallFromPost(\WP_Post $post): Call
    {
        $meta = get_post_meta($post->ID);
        
        $data = [
            'id' => $meta['id'][0] ?? '',
            'title' => $post->post_title,
            'contact' => $meta['contacto'][0] ?? '',
            'description' => $post->post_content,
            'publicationLink' => $meta['link'][0] ?? null,
            'openingDate' => $meta['apertura'][0] ?? '',
            'closingDate' => $meta['cierre'][0] ?? '',
            'postStatus' => PostStatus::tryFromWordPress($post->post_status) ?? PostStatus::DRAFT,
            'createdAt' => new \DateTimeImmutable($post->post_date),
            'updatedAt' => new \DateTimeImmutable($post->post_modified),
        ];
        
        $call = Call::createFromExcel($data);
        $call->setId($post->ID);
        
        return $call;
    }
    
    /**
     * Save call meta fields
     *
     * @param Call $call
     * @return void
     */
    private function saveCallMeta(Call $call): void
    {
        $metaFields = [
            'id' => $call->getExternalId(),
            'contacto' => $call->getContact(),
            'link' => $call->getPublicationLink(),
            'apertura' => $call->getOpeningDate()->format('Y-m-d'),
            'cierre' => $call->getClosingDate()->format('Y-m-d'),
        ];
        
        foreach ($metaFields as $key => $value) {
            if ($value !== null) {
                update_post_meta($call->getId(), $key, $value);
            }
        }
    }
    
    /**
     * Update call status taxonomy
     *
     * @param Call $call
     * @return void
     */
    private function updateCallStatusTaxonomy(Call $call): void
    {
        wp_set_post_terms(
            $call->getId(),
            [$call->getCallStatus()->toTaxonomyTerm()],
            RIILSA_TAXONOMY_STATUS
        );
    }
    
    /**
     * Apply criteria to query args
     *
     * @param array $args
     * @param array $criteria
     * @return array
     */
    private function applyCriteria(array $args, array $criteria): array
    {
        if (isset($criteria['call_status'])) {
            $args['tax_query'][] = [
                'taxonomy' => RIILSA_TAXONOMY_STATUS,
                'field' => 'name',
                'terms' => $criteria['call_status']->toTaxonomyTerm(),
            ];
        }
        
        if (isset($criteria['post_status'])) {
            $args['post_status'] = $criteria['post_status']->toWordPress();
        }
        
        return $args;
    }
    
    /**
     * Apply ordering to query args
     *
     * @param array $args
     * @param array $orderBy
     * @return array
     */
    private function applyOrderBy(array $args, array $orderBy): array
    {
        $field = key($orderBy);
        $direction = current($orderBy);
        
        switch ($field) {
            case 'title':
                $args['orderby'] = 'title';
                break;
            case 'createdAt':
                $args['orderby'] = 'date';
                break;
            case 'closingDate':
                $args['orderby'] = 'meta_value';
                $args['meta_key'] = 'cierre';
                $args['meta_type'] = 'DATE';
                break;
            default:
                $args['orderby'] = 'date';
        }
        
        $args['order'] = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        
        return $args;
    }
}
