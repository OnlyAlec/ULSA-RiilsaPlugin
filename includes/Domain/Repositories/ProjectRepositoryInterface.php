<?php

declare(strict_types=1);

/**
 * Project Repository Interface
 *
 * @package RIILSA\Domain\Repositories
 * @since 3.1.0
 */

namespace RIILSA\Domain\Repositories;

use RIILSA\Domain\Entities\Project;
use RIILSA\Domain\ValueObjects\PostStatus;
use RIILSA\Domain\ValueObjects\ProjectStatus;

/**
 * Repository interface for Project entities
 * 
 * Pattern: Repository Pattern
 * This interface defines the contract for project data persistence
 */
interface ProjectRepositoryInterface
{
    /**
     * Find a project by ID
     *
     * @param int $id
     * @return Project|null
     */
    public function findById(int $id): ?Project;
    
    /**
     * Find a project by external ID
     *
     * @param string $externalId
     * @return Project|null
     */
    public function findByExternalId(string $externalId): ?Project;
    
    /**
     * Get all projects
     *
     * @param array $criteria Optional filter criteria
     * @param array $orderBy Optional ordering
     * @param int|null $limit
     * @param int $offset
     * @return array<Project>
     */
    public function findAll(
        array $criteria = [],
        array $orderBy = [],
        ?int $limit = null,
        int $offset = 0
    ): array;
    
    /**
     * Get projects by status
     *
     * @param ProjectStatus $status
     * @param int|null $limit
     * @param int $offset
     * @return array<Project>
     */
    public function findByStatus(
        ProjectStatus $status,
        ?int $limit = null,
        int $offset = 0
    ): array;
    
    /**
     * Get projects by post status
     *
     * @param PostStatus $postStatus
     * @param int|null $limit
     * @param int $offset
     * @return array<Project>
     */
    public function findByPostStatus(
        PostStatus $postStatus,
        ?int $limit = null,
        int $offset = 0
    ): array;
    
    /**
     * Get projects by research line
     *
     * @param string $researchLine
     * @param int|null $limit
     * @param int $offset
     * @return array<Project>
     */
    public function findByResearchLine(
        string $researchLine,
        ?int $limit = null,
        int $offset = 0
    ): array;
    
    /**
     * Search projects by keyword
     *
     * @param string $keyword
     * @param array $searchFields Fields to search in
     * @param int|null $limit
     * @param int $offset
     * @return array<Project>
     */
    public function search(
        string $keyword,
        array $searchFields = ['title', 'objective', 'summary'],
        ?int $limit = null,
        int $offset = 0
    ): array;
    
    /**
     * Save a project (insert or update)
     *
     * @param Project $project
     * @return Project The saved project with ID set
     * @throws \RuntimeException If save fails
     */
    public function save(Project $project): Project;
    
    /**
     * Delete a project
     *
     * @param Project $project
     * @return bool
     * @throws \RuntimeException If delete fails
     */
    public function delete(Project $project): bool;
    
    /**
     * Delete a project by ID
     *
     * @param int $id
     * @return bool
     * @throws \RuntimeException If delete fails
     */
    public function deleteById(int $id): bool;
    
    /**
     * Check if project exists by title
     *
     * @param string $title
     * @return bool
     */
    public function existsByTitle(string $title): bool;
    
    /**
     * Check if project exists by external ID
     *
     * @param string $externalId
     * @return bool
     */
    public function existsByExternalId(string $externalId): bool;
    
    /**
     * Count projects by criteria
     *
     * @param array $criteria
     * @return int
     */
    public function count(array $criteria = []): int;
    
    /**
     * Get active projects count
     *
     * @return int
     */
    public function countActive(): int;
    
    /**
     * Get expired projects count
     *
     * @return int
     */
    public function countExpired(): int;
    
    /**
     * Update project statuses based on dates
     *
     * @return int Number of projects updated
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
