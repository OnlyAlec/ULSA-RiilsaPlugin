<?php

declare(strict_types=1);

/**
 * Newsletter Repository Interface
 *
 * @package RIILSA\Domain\Repositories
 * @since 3.1.0
 */

namespace RIILSA\Domain\Repositories;

use RIILSA\Domain\Entities\Newsletter;
use RIILSA\Domain\ValueObjects\NewsletterStatus;
use RIILSA\Domain\ValueObjects\DateRange;

/**
 * Repository interface for Newsletter entities
 * 
 * Pattern: Repository Pattern
 * This interface defines the contract for newsletter data persistence
 */
interface NewsletterRepositoryInterface
{
    /**
     * Find a newsletter by ID
     *
     * @param int $id
     * @return Newsletter|null
     */
    public function findById(int $id): ?Newsletter;
    
    /**
     * Find a newsletter by number
     *
     * @param int $number
     * @return Newsletter|null
     */
    public function findByNumber(int $number): ?Newsletter;
    
    /**
     * Get all newsletters
     *
     * @param array $criteria Optional filter criteria
     * @param array $orderBy Optional ordering
     * @param int|null $limit
     * @param int $offset
     * @return array<Newsletter>
     */
    public function findAll(
        array $criteria = [],
        array $orderBy = ['number' => 'DESC'],
        ?int $limit = null,
        int $offset = 0
    ): array;
    
    /**
     * Get newsletters by status
     *
     * @param NewsletterStatus $status
     * @param int|null $limit
     * @param int $offset
     * @return array<Newsletter>
     */
    public function findByStatus(
        NewsletterStatus $status,
        ?int $limit = null,
        int $offset = 0
    ): array;
    
    /**
     * Get newsletters by multiple statuses
     *
     * @param array<NewsletterStatus> $statuses
     * @param int|null $limit
     * @param int $offset
     * @return array<Newsletter>
     */
    public function findByStatuses(
        array $statuses,
        ?int $limit = null,
        int $offset = 0
    ): array;
    
    /**
     * Get newsletters scheduled for a specific date range
     *
     * @param DateRange $dateRange
     * @return array<Newsletter>
     */
    public function findScheduledInRange(DateRange $dateRange): array;
    
    /**
     * Get newsletters sent within a date range
     *
     * @param DateRange $dateRange
     * @return array<Newsletter>
     */
    public function findSentInRange(DateRange $dateRange): array;
    
    /**
     * Get the last newsletter number
     *
     * @return int
     */
    public function getLastNewsletterNumber(): int;
    
    /**
     * Get the next available newsletter number
     *
     * @return int
     */
    public function getNextNewsletterNumber(): int;
    
    /**
     * Save a newsletter (insert or update)
     *
     * @param Newsletter $newsletter
     * @return Newsletter The saved newsletter with ID set
     * @throws \RuntimeException If save fails
     */
    public function save(Newsletter $newsletter): Newsletter;
    
    /**
     * Delete a newsletter
     *
     * @param Newsletter $newsletter
     * @return bool
     * @throws \RuntimeException If delete fails
     */
    public function delete(Newsletter $newsletter): bool;
    
    /**
     * Delete a newsletter by ID
     *
     * @param int $id
     * @return bool
     * @throws \RuntimeException If delete fails
     */
    public function deleteById(int $id): bool;
    
    /**
     * Check if a newsletter exists by number
     *
     * @param int $number
     * @return bool
     */
    public function existsByNumber(int $number): bool;
    
    /**
     * Count newsletters by criteria
     *
     * @param array $criteria
     * @return int
     */
    public function count(array $criteria = []): int;
    
    /**
     * Count newsletters by status
     *
     * @param NewsletterStatus $status
     * @return int
     */
    public function countByStatus(NewsletterStatus $status): int;
    
    /**
     * Get newsletter statistics
     *
     * @return array
     */
    public function getStatistics(): array;
    
    /**
     * Update newsletter statistics
     *
     * @param int $newsletterId
     * @param array $statistics
     * @return bool
     */
    public function updateStatistics(int $newsletterId, array $statistics): bool;
    
    /**
     * Get newsletters ready to be sent
     *
     * @return array<Newsletter>
     */
    public function findReadyToSend(): array;
    
    /**
     * Lock a newsletter for sending (prevent concurrent sends)
     *
     * @param int $newsletterId
     * @return bool
     */
    public function lockForSending(int $newsletterId): bool;
    
    /**
     * Unlock a newsletter after sending
     *
     * @param int $newsletterId
     * @return bool
     */
    public function unlockAfterSending(int $newsletterId): bool;
    
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
