<?php

declare(strict_types=1);

/**
 * News Repository Interface
 *
 * @package RIILSA\Domain\Repositories
 * @since 3.1.0
 */

namespace RIILSA\Domain\Repositories;

use RIILSA\Domain\Entities\News;
use RIILSA\Domain\ValueObjects\PostStatus;
use RIILSA\Domain\ValueObjects\DateRange;

/**
 * Repository interface for News entities
 * 
 * Pattern: Repository Pattern
 * This interface defines the contract for news data persistence
 */
interface NewsRepositoryInterface
{
    /**
     * Find a news item by ID
     *
     * @param int $id
     * @return News|null
     */
    public function findById(int $id): ?News;
    
    /**
     * Find news items by IDs
     *
     * @param array<int> $ids
     * @return array<News>
     */
    public function findByIds(array $ids): array;
    
    /**
     * Find a news item by external ID
     *
     * @param string $externalId
     * @return News|null
     */
    public function findByExternalId(string $externalId): ?News;
    
    /**
     * Get all news items
     *
     * @param array $criteria Optional filter criteria
     * @param array $orderBy Optional ordering
     * @param int|null $limit
     * @param int $offset
     * @return array<News>
     */
    public function findAll(
        array $criteria = [],
        array $orderBy = ['createdAt' => 'DESC'],
        ?int $limit = null,
        int $offset = 0
    ): array;
    
    /**
     * Get news by post status
     *
     * @param PostStatus $postStatus
     * @param int|null $limit
     * @param int $offset
     * @return array<News>
     */
    public function findByPostStatus(
        PostStatus $postStatus,
        ?int $limit = null,
        int $offset = 0
    ): array;
    
    /**
     * Get news by newsletter number
     *
     * @param int $newsletterNumber
     * @return array<News>
     */
    public function findByNewsletterNumber(int $newsletterNumber): array;
    
    /**
     * Get news by research line
     *
     * @param string $researchLine
     * @param int|null $limit
     * @param int $offset
     * @return array<News>
     */
    public function findByResearchLine(
        string $researchLine,
        ?int $limit = null,
        int $offset = 0
    ): array;
    
    /**
     * Get news within a date range
     *
     * @param DateRange $dateRange
     * @param PostStatus|null $postStatus
     * @param int|null $limit
     * @param int $offset
     * @return array<News>
     */
    public function findByDateRange(
        DateRange $dateRange,
        ?PostStatus $postStatus = null,
        ?int $limit = null,
        int $offset = 0
    ): array;
    
    /**
     * Get recent news (last month)
     *
     * @param int $limit
     * @return array<News>
     */
    public function findRecent(int $limit = 10): array;
    
    /**
     * Get news available for newsletter
     *
     * @param int|null $limit
     * @return array<News>
     */
    public function findAvailableForNewsletter(?int $limit = null): array;
    
    /**
     * Search news by keyword
     *
     * @param string $keyword
     * @param array $searchFields Fields to search in
     * @param int|null $limit
     * @param int $offset
     * @return array<News>
     */
    public function search(
        string $keyword,
        array $searchFields = ['title', 'content', 'bullets'],
        ?int $limit = null,
        int $offset = 0
    ): array;
    
    /**
     * Save a news item (insert or update)
     *
     * @param News $news
     * @return News The saved news with ID set
     * @throws \RuntimeException If save fails
     */
    public function save(News $news): News;
    
    /**
     * Save multiple news items
     *
     * @param array<News> $newsItems
     * @return array<News> The saved news items with IDs set
     * @throws \RuntimeException If save fails
     */
    public function saveMultiple(array $newsItems): array;
    
    /**
     * Delete a news item
     *
     * @param News $news
     * @return bool
     * @throws \RuntimeException If delete fails
     */
    public function delete(News $news): bool;
    
    /**
     * Delete a news item by ID
     *
     * @param int $id
     * @return bool
     * @throws \RuntimeException If delete fails
     */
    public function deleteById(int $id): bool;
    
    /**
     * Check if news exists by title
     *
     * @param string $title
     * @return bool
     */
    public function existsByTitle(string $title): bool;
    
    /**
     * Check if a news item exists by external ID
     *
     * @param string $externalId
     * @return bool
     */
    public function existsByExternalId(string $externalId): bool;
    
    /**
     * Count news by criteria
     *
     * @param array $criteria
     * @return int
     */
    public function count(array $criteria = []): int;
    
    /**
     * Count published news
     *
     * @return int
     */
    public function countPublished(): int;
    
    /**
     * Count news by newsletter
     *
     * @param int $newsletterNumber
     * @return int
     */
    public function countByNewsletter(int $newsletterNumber): int;
    
    /**
     * Update newsletter association for news items
     *
     * @param array<int> $newsIds
     * @param int $newsletterNumber
     * @return int Number of items updated
     */
    public function updateNewsletterAssociation(array $newsIds, int $newsletterNumber): int;
    
    /**
     * Remove newsletter association for news items
     *
     * @param int $newsletterNumber
     * @return int Number of items updated
     */
    public function removeNewsletterAssociation(int $newsletterNumber): int;
    
    /**
     * Begin a transaction
     *
     * @return void
     */
    public function beginTransaction(): void;
    
    /**
     * Commit a transaction
     *
     * @return void
     */
    public function commit(): void;
    
    /**
     * Rollback a transaction
     *
     * @return void
     */
    public function rollback(): void;
}
