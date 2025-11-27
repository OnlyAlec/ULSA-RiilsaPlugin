<?php

declare(strict_types=1);

/**
 * Call Repository Interface
 *
 * @package RIILSA\Domain\Repositories
 * @since 3.1.0
 */

namespace RIILSA\Domain\Repositories;

use RIILSA\Domain\Entities\Call;
use RIILSA\Domain\ValueObjects\PostStatus;
use RIILSA\Domain\ValueObjects\ProjectStatus;

/**
 * Repository interface for Call entities
 * 
 * Pattern: Repository Pattern
 * This interface defines the contract for call/announcement data persistence
 */
interface CallRepositoryInterface
{
    /**
     * Find a call by ID
     *
     * @param int $id
     * @return Call|null
     */
    public function findById(int $id): ?Call;
    
    /**
     * Find a call by external ID
     *
     * @param string $externalId
     * @return Call|null
     */
    public function findByExternalId(string $externalId): ?Call;
    
    /**
     * Get all calls
     *
     * @param array $criteria Optional filter criteria
     * @param array $orderBy Optional ordering
     * @param int|null $limit
     * @param int $offset
     * @return array<Call>
     */
    public function findAll(
        array $criteria = [],
        array $orderBy = ['closingDate' => 'ASC'],
        ?int $limit = null,
        int $offset = 0
    ): array;
    
    /**
     * Get calls by status
     *
     * @param ProjectStatus $status
     * @param int|null $limit
     * @param int $offset
     * @return array<Call>
     */
    public function findByStatus(
        ProjectStatus $status,
        ?int $limit = null,
        int $offset = 0
    ): array;
    
    /**
     * Get calls by post status
     *
     * @param PostStatus $postStatus
     * @param int|null $limit
     * @param int $offset
     * @return array<Call>
     */
    public function findByPostStatus(
        PostStatus $postStatus,
        ?int $limit = null,
        int $offset = 0
    ): array;
    
    /**
     * Get open calls (current)
     *
     * @param int|null $limit
     * @param int $offset
     * @return array<Call>
     */
    public function findOpen(?int $limit = null, int $offset = 0): array;
    
    /**
     * Get calls closing soon (within N days)
     *
     * @param int $days Number of days
     * @param int|null $limit
     * @return array<Call>
     */
    public function findClosingSoon(int $days = 7, ?int $limit = null): array;
    
    /**
     * Get expired calls
     *
     * @param int|null $limit
     * @param int $offset
     * @return array<Call>
     */
    public function findExpired(?int $limit = null, int $offset = 0): array;
    
    /**
     * Search calls by keyword
     *
     * @param string $keyword
     * @param array $searchFields Fields to search in
     * @param int|null $limit
     * @param int $offset
     * @return array<Call>
     */
    public function search(
        string $keyword,
        array $searchFields = ['title', 'description', 'contact'],
        ?int $limit = null,
        int $offset = 0
    ): array;
    
    /**
     * Save a call (insert or update)
     *
     * @param Call $call
     * @return Call The saved call with ID set
     * @throws \RuntimeException If save fails
     */
    public function save(Call $call): Call;
    
    /**
     * Delete a call
     *
     * @param Call $call
     * @return bool
     * @throws \RuntimeException If delete fails
     */
    public function delete(Call $call): bool;
    
    /**
     * Delete a call by ID
     *
     * @param int $id
     * @return bool
     * @throws \RuntimeException If delete fails
     */
    public function deleteById(int $id): bool;
    
    /**
     * Check if call exists by title
     *
     * @param string $title
     * @return bool
     */
    public function existsByTitle(string $title): bool;
    
    /**
     * Check if call exists by external ID
     *
     * @param string $externalId
     * @return bool
     */
    public function existsByExternalId(string $externalId): bool;
    
    /**
     * Count calls by criteria
     *
     * @param array $criteria
     * @return int
     */
    public function count(array $criteria = []): int;
    
    /**
     * Count open calls
     *
     * @return int
     */
    public function countOpen(): int;
    
    /**
     * Count expired calls
     *
     * @return int
     */
    public function countExpired(): int;
    
    /**
     * Update call statuses based on dates
     *
     * @return int Number of calls updated
     */
    public function updateExpiredStatuses(): int;
    
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
