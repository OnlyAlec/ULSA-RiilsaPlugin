<?php

declare(strict_types=1);

/**
 * WordPress News Repository Implementation
 *
 * @package RIILSA\Infrastructure\Repositories
 * @since 3.1.0
 */

namespace RIILSA\Infrastructure\Repositories;

use RIILSA\Domain\Entities\News;
use RIILSA\Domain\Repositories\NewsRepositoryInterface;
use RIILSA\Domain\ValueObjects\PostStatus;
use RIILSA\Domain\ValueObjects\DateRange;

/**
 * WordPress implementation of News repository
 * 
 * Pattern: Repository Pattern
 * This class implements news persistence using WordPress APIs
 */
class WordPressNewsRepository implements NewsRepositoryInterface
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
    public function findById(int $id): ?News
    {
        $post = get_post($id);
        
        if (!$post || $post->post_type !== RIILSA_POST_TYPE_NEWS) {
            return null;
        }
        
        $meta = get_post_meta($post->ID);
        return News::createFromWordPressPost($post, $meta);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        
        $args = [
            'post_type' => RIILSA_POST_TYPE_NEWS,
            'posts_per_page' => -1,
            'post_status' => 'any',
            'post__in' => $ids,
            'orderby' => 'post__in',
        ];
        
        $query = new \WP_Query($args);
        
        return array_map(function($post) {
            $meta = get_post_meta($post->ID);
            return News::createFromWordPressPost($post, $meta);
        }, $query->posts);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByExternalId(string $externalId): ?News
    {
        $args = [
            'post_type' => RIILSA_POST_TYPE_NEWS,
            'posts_per_page' => 1,
            'post_status' => 'any',
            'meta_key' => 'id',
            'meta_value' => $externalId,
        ];
        
        $query = new \WP_Query($args);
        
        if (!$query->have_posts()) {
            return null;
        }
        
        $meta = get_post_meta($query->posts[0]->ID);
        return News::createFromWordPressPost($query->posts[0], $meta);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findAll(
        array $criteria = [],
        array $orderBy = ['createdAt' => 'DESC'],
        ?int $limit = null,
        int $offset = 0
    ): array {
        $args = [
            'post_type' => RIILSA_POST_TYPE_NEWS,
            'posts_per_page' => $limit ?? -1,
            'offset' => $offset,
            'post_status' => 'any',
        ];
        
        $args = $this->applyCriteria($args, $criteria);
        $args = $this->applyOrderBy($args, $orderBy);
        
        $query = new \WP_Query($args);
        
        return array_map(function($post) {
            $meta = get_post_meta($post->ID);
            return News::createFromWordPressPost($post, $meta);
        }, $query->posts);
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
            'post_type' => RIILSA_POST_TYPE_NEWS,
            'posts_per_page' => $limit ?? -1,
            'offset' => $offset,
            'post_status' => $postStatus->toWordPress(),
        ];
        
        $query = new \WP_Query($args);
        
        return array_map(function($post) {
            $meta = get_post_meta($post->ID);
            return News::createFromWordPressPost($post, $meta);
        }, $query->posts);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByNewsletterNumber(int $newsletterNumber): array
    {
        $args = [
            'post_type' => RIILSA_POST_TYPE_NEWS,
            'posts_per_page' => -1,
            'post_status' => 'any',
            'meta_key' => 'newsletterNumber',
            'meta_value' => $newsletterNumber,
        ];
        
        $query = new \WP_Query($args);
        
        return array_map(function($post) {
            $meta = get_post_meta($post->ID);
            return News::createFromWordPressPost($post, $meta);
        }, $query->posts);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByResearchLine(
        string $researchLine,
        ?int $limit = null,
        int $offset = 0
    ): array {
        $args = [
            'post_type' => RIILSA_POST_TYPE_NEWS,
            'posts_per_page' => $limit ?? -1,
            'offset' => $offset,
            'post_status' => 'any',
            'tax_query' => [
                [
                    'taxonomy' => RIILSA_TAXONOMY_AREA,
                    'field' => 'name',
                    'terms' => $researchLine,
                ],
            ],
        ];
        
        $query = new \WP_Query($args);
        
        return array_map(function($post) {
            $meta = get_post_meta($post->ID);
            return News::createFromWordPressPost($post, $meta);
        }, $query->posts);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByDateRange(
        DateRange $dateRange,
        ?PostStatus $postStatus = null,
        ?int $limit = null,
        int $offset = 0
    ): array {
        $args = [
            'post_type' => RIILSA_POST_TYPE_NEWS,
            'posts_per_page' => $limit ?? -1,
            'offset' => $offset,
            'post_status' => $postStatus ? $postStatus->toWordPress() : 'any',
            'date_query' => [
                [
                    'after' => $dateRange->getStartDate()->format('Y-m-d'),
                    'before' => $dateRange->getEndDate()->format('Y-m-d'),
                    'inclusive' => true,
                ],
            ],
        ];
        
        $query = new \WP_Query($args);
        
        return array_map(function($post) {
            $meta = get_post_meta($post->ID);
            return News::createFromWordPressPost($post, $meta);
        }, $query->posts);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->findByDateRange(
            DateRange::lastDays(30),
            PostStatus::PUBLISHED,
            $limit
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function findAvailableForNewsletter(?int $limit = null): array
    {
        return $this->findByPostStatus(PostStatus::PUBLISHED, $limit);
    }
    
    /**
     * {@inheritdoc}
     */
    public function search(
        string $keyword,
        array $searchFields = ['title', 'content', 'bullets'],
        ?int $limit = null,
        int $offset = 0
    ): array {
        $args = [
            'post_type' => RIILSA_POST_TYPE_NEWS,
            'posts_per_page' => $limit ?? -1,
            'offset' => $offset,
            'post_status' => 'any',
            's' => $keyword,
        ];
        
        $query = new \WP_Query($args);
        
        return array_map(function($post) {
            $meta = get_post_meta($post->ID);
            return News::createFromWordPressPost($post, $meta);
        }, $query->posts);
    }
    
    /**
     * {@inheritdoc}
     */
    public function save(News $news): News
    {
        $postData = [
            'post_type' => RIILSA_POST_TYPE_NEWS,
            'post_title' => $news->getTitle(),
            'post_content' => $news->getContent(),
            'post_status' => $news->getPostStatus()->toWordPress(),
            'post_author' => get_current_user_id() ?: 1,
        ];
        
        if ($news->getId()) {
            $postData['ID'] = $news->getId();
            $postId = wp_update_post($postData);
        } else {
            $postId = wp_insert_post($postData);
        }
        
        if (is_wp_error($postId)) {
            throw new \RuntimeException('Failed to save news: ' . $postId->get_error_message());
        }
        
        $news->setId($postId);
        
        // Save meta fields
        $this->saveNewsMeta($news);
        
        return $news;
    }
    
    /**
     * {@inheritdoc}
     */
    public function saveMultiple(array $newsItems): array
    {
        $saved = [];
        
        foreach ($newsItems as $news) {
            $saved[] = $this->save($news);
        }
        
        return $saved;
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete(News $news): bool
    {
        if (!$news->getId()) {
            return false;
        }
        
        return $this->deleteById($news->getId());
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
    public function existsByExternalId(string $externalId): bool
    {
        $count = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->wpdb->postmeta} pm
             INNER JOIN {$this->wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = 'id' AND pm.meta_value = %s
             AND p.post_type = %s AND p.post_status != 'trash'",
            $externalId,
            RIILSA_POST_TYPE_NEWS
        ));
        
        return (int)$count > 0;
    }
    
    /**
     * {@inheritdoc}
     */
    public function count(array $criteria = []): int
    {
        $args = [
            'post_type' => RIILSA_POST_TYPE_NEWS,
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
    public function countPublished(): int
    {
        return $this->count(['post_status' => PostStatus::PUBLISHED]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function countByNewsletter(int $newsletterNumber): int
    {
        return $this->count(['newsletter_number' => $newsletterNumber]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function updateNewsletterAssociation(array $newsIds, int $newsletterNumber): int
    {
        $updated = 0;
        
        foreach ($newsIds as $newsId) {
            update_post_meta($newsId, 'newsletterNumber', $newsletterNumber);
            $updated++;
        }
        
        return $updated;
    }
    
    /**
     * {@inheritdoc}
     */
    public function removeNewsletterAssociation(int $newsletterNumber): int
    {
        $newsItems = $this->findByNewsletterNumber($newsletterNumber);
        $updated = 0;
        
        foreach ($newsItems as $news) {
            delete_post_meta($news->getId(), 'newsletterNumber');
            $updated++;
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
     * Save news meta fields
     *
     * @param News $news
     * @return void
     */
    private function saveNewsMeta(News $news): void
    {
        $metaFields = [
            'id' => $news->getExternalId(),
            'bullets' => $news->getBullets(),
            'contactInfo' => $news->getContactInfo(),
            'newsletterNumber' => $news->getNewsletterNumber(),
            'position' => $news->getPosition(),
        ];
        
        foreach ($metaFields as $key => $value) {
            if ($value !== null) {
                update_post_meta($news->getId(), $key, $value);
            }
        }
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
        if (isset($criteria['post_status'])) {
            $args['post_status'] = $criteria['post_status']->toWordPress();
        }
        
        if (isset($criteria['newsletter_number'])) {
            $args['meta_key'] = 'newsletterNumber';
            $args['meta_value'] = $criteria['newsletter_number'];
        }
        
        if (isset($criteria['research_line'])) {
            $args['tax_query'][] = [
                'taxonomy' => RIILSA_TAXONOMY_AREA,
                'field' => 'name',
                'terms' => $criteria['research_line'],
            ];
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
            case 'updatedAt':
                $args['orderby'] = 'modified';
                break;
            default:
                $args['orderby'] = 'date';
        }
        
        $args['order'] = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        
        return $args;
    }
}
