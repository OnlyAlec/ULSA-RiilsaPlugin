<?php

declare(strict_types=1);

/**
 * Subscriber Repository Interface
 *
 * @package RIILSA\Domain\Repositories
 * @since 3.1.0
 */

namespace RIILSA\Domain\Repositories;

use RIILSA\Domain\Entities\Subscriber;
use RIILSA\Domain\ValueObjects\Email;
use RIILSA\Domain\ValueObjects\SubscriberStatus;
use RIILSA\Domain\ValueObjects\DateRange;

/**
 * Repository interface for Subscriber entities
 * 
 * Pattern: Repository Pattern
 * This interface defines the contract for subscriber data persistence
 */
interface SubscriberRepositoryInterface
{
    /**
     * Find a subscriber by ID
     *
     * @param int $id
     * @return Subscriber|null
     */
    public function findById(int $id): ?Subscriber;
    
    /**
     * Find a subscriber by email
     *
     * @param Email $email
     * @return Subscriber|null
     */
    public function findByEmail(Email $email): ?Subscriber;
    
    /**
     * Find a subscriber by external ID (from Brevo)
     *
     * @param string $externalId
     * @return Subscriber|null
     */
    public function findByExternalId(string $externalId): ?Subscriber;
    
    /**
     * Find a subscriber by confirmation token
     *
     * @param string $token
     * @return Subscriber|null
     */
    public function findByToken(string $token): ?Subscriber;
    
    /**
     * Get all subscribers
     *
     * @param array $criteria Optional filter criteria
     * @param array $orderBy Optional ordering
     * @param int|null $limit
     * @param int $offset
     * @return array<Subscriber>
     */
    public function findAll(
        array $criteria = [],
        array $orderBy = ['subscribedAt' => 'DESC'],
        ?int $limit = null,
        int $offset = 0
    ): array;
    
    /**
     * Get subscribers by status
     *
     * @param SubscriberStatus $status
     * @param int|null $limit
     * @param int $offset
     * @return array<Subscriber>
     */
    public function findByStatus(
        SubscriberStatus $status,
        ?int $limit = null,
        int $offset = 0
    ): array;
    
    /**
     * Get subscribers by dependency
     *
     * @param int $dependencyId
     * @param SubscriberStatus|null $status
     * @param int|null $limit
     * @param int $offset
     * @return array<Subscriber>
     */
    public function findByDependency(
        int $dependencyId,
        ?SubscriberStatus $status = null,
        ?int $limit = null,
        int $offset = 0
    ): array;
    
    /**
     * Get active subscribers (confirmed)
     *
     * @param int|null $limit
     * @param int $offset
     * @return array<Subscriber>
     */
    public function findActive(?int $limit = null, int $offset = 0): array;
    
    /**
     * Get subscribers who can receive emails
     *
     * @param array<int>|null $dependencyIds Filter by dependencies
     * @return array<Subscriber>
     */
    public function findRecipients(?array $dependencyIds = null): array;
    
    /**
     * Get subscribers subscribed within a date range
     *
     * @param DateRange $dateRange
     * @param SubscriberStatus|null $status
     * @return array<Subscriber>
     */
    public function findBySubscriptionDate(
        DateRange $dateRange,
        ?SubscriberStatus $status = null
    ): array;
    
    /**
     * Get subscribers with expired tokens
     *
     * @return array<Subscriber>
     */
    public function findWithExpiredTokens(): array;
    
    /**
     * Search subscribers
     *
     * @param string $keyword
     * @param int|null $limit
     * @param int $offset
     * @return array<Subscriber>
     */
    public function search(
        string $keyword,
        ?int $limit = null,
        int $offset = 0
    ): array;
    
    /**
     * Save a subscriber (insert or update)
     *
     * @param Subscriber $subscriber
     * @return Subscriber The saved subscriber with ID set
     * @throws \RuntimeException If save fails
     */
    public function save(Subscriber $subscriber): Subscriber;
    
    /**
     * Delete a subscriber
     *
     * @param Subscriber $subscriber
     * @return bool
     * @throws \RuntimeException If delete fails
     */
    public function delete(Subscriber $subscriber): bool;
    
    /**
     * Delete a subscriber by ID
     *
     * @param int $id
     * @return bool
     * @throws \RuntimeException If delete fails
     */
    public function deleteById(int $id): bool;
    
    /**
     * Delete subscribers with expired tokens
     *
     * @return int Number of subscribers deleted
     */
    public function deleteExpiredPending(): int;
    
    /**
     * Check if a subscriber exists by email
     *
     * @param Email $email
     * @return bool
     */
    public function existsByEmail(Email $email): bool;
    
    /**
     * Count subscribers by criteria
     *
     * @param array $criteria
     * @return int
     */
    public function count(array $criteria = []): int;
    
    /**
     * Count subscribers by status
     *
     * @param SubscriberStatus $status
     * @return int
     */
    public function countByStatus(SubscriberStatus $status): int;
    
    /**
     * Count active subscribers
     *
     * @return int
     */
    public function countActive(): int;
    
    /**
     * Count subscribers by dependency
     *
     * @param int $dependencyId
     * @param SubscriberStatus|null $status
     * @return int
     */
    public function countByDependency(int $dependencyId, ?SubscriberStatus $status = null): int;
    
    /**
     * Get subscriber statistics
     *
     * @return array
     */
    public function getStatistics(): array;
    
    /**
     * Get subscriber growth over time
     *
     * @param DateRange $dateRange
     * @param string $groupBy 'day', 'week', or 'month'
     * @return array
     */
    public function getGrowthStatistics(DateRange $dateRange, string $groupBy = 'day'): array;
    
    /**
     * Batch update subscriber statuses
     *
     * @param array<int> $subscriberIds
     * @param SubscriberStatus $status
     * @return int Number of subscribers updated
     */
    public function batchUpdateStatus(array $subscriberIds, SubscriberStatus $status): int;
    
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
