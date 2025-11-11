<?php

declare(strict_types=1);

/**
 * WordPress Project Repository Implementation
 *
 * @package RIILSA\Infrastructure\Repositories
 * @since 3.1.0
 */

namespace RIILSA\Infrastructure\Repositories;

use RIILSA\Domain\Entities\Project;
use RIILSA\Domain\Repositories\ProjectRepositoryInterface;
use RIILSA\Domain\ValueObjects\PostStatus;
use RIILSA\Domain\ValueObjects\ProjectStatus;
use RIILSA\Domain\ValueObjects\DateRange;
use RIILSA\Domain\ValueObjects\Email;

/**
 * WordPress implementation of Project repository
 * 
 * Pattern: Repository Pattern
 * This class implements project persistence using WordPress APIs
 */
class WordPressProjectRepository implements ProjectRepositoryInterface
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
    public function findById(int $id): ?Project
    {
        $post = get_post($id);
        
        if (!$post || $post->post_type !== RIILSA_POST_TYPE_PROJECT) {
            return null;
        }
        
        return $this->createProjectFromPost($post);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByExternalId(string $externalId): ?Project
    {
        $args = [
            'post_type' => RIILSA_POST_TYPE_PROJECT,
            'posts_per_page' => 1,
            'post_status' => 'any',
            'meta_key' => 'id',
            'meta_value' => $externalId,
        ];
        
        $query = new \WP_Query($args);
        
        if (!$query->have_posts()) {
            return null;
        }
        
        return $this->createProjectFromPost($query->posts[0]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findAll(
        array $criteria = [],
        array $orderBy = [],
        ?int $limit = null,
        int $offset = 0
    ): array {
        $args = [
            'post_type' => RIILSA_POST_TYPE_PROJECT,
            'posts_per_page' => $limit ?? -1,
            'offset' => $offset,
            'post_status' => 'any',
        ];
        
        // Apply criteria
        $args = $this->applyCriteria($args, $criteria);
        
        // Apply ordering
        if (!empty($orderBy)) {
            $args = $this->applyOrderBy($args, $orderBy);
        } else {
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        }
        
        $query = new \WP_Query($args);
        
        return array_map([$this, 'createProjectFromPost'], $query->posts);
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
            'post_type' => RIILSA_POST_TYPE_PROJECT,
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
        
        return array_map([$this, 'createProjectFromPost'], $query->posts);
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
            'post_type' => RIILSA_POST_TYPE_PROJECT,
            'posts_per_page' => $limit ?? -1,
            'offset' => $offset,
            'post_status' => $postStatus->toWordPress(),
        ];
        
        $query = new \WP_Query($args);
        
        return array_map([$this, 'createProjectFromPost'], $query->posts);
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
            'post_type' => RIILSA_POST_TYPE_PROJECT,
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
        
        return array_map([$this, 'createProjectFromPost'], $query->posts);
    }
    
    /**
     * {@inheritdoc}
     */
    public function search(
        string $keyword,
        array $searchFields = ['title', 'objective', 'summary'],
        ?int $limit = null,
        int $offset = 0
    ): array {
        $args = [
            'post_type' => RIILSA_POST_TYPE_PROJECT,
            'posts_per_page' => $limit ?? -1,
            'offset' => $offset,
            'post_status' => 'any',
            's' => $keyword,
        ];
        
        $query = new \WP_Query($args);
        
        return array_map([$this, 'createProjectFromPost'], $query->posts);
    }
    
    /**
     * {@inheritdoc}
     */
    public function save(Project $project): Project
    {
        $postData = [
            'post_type' => RIILSA_POST_TYPE_PROJECT,
            'post_title' => $project->getTitle(),
            'post_content' => '',
            'post_status' => $project->getPostStatus()->toWordPress(),
            'post_author' => get_current_user_id() ?: 1,
        ];
        
        if ($project->getId()) {
            $postData['ID'] = $project->getId();
            $postId = wp_update_post($postData);
        } else {
            $postId = wp_insert_post($postData);
        }
        
        if (is_wp_error($postId)) {
            throw new \RuntimeException('Failed to save project: ' . $postId->get_error_message());
        }
        
        $project->setId($postId);
        
        // Save meta fields
        $this->saveProjectMeta($project);
        
        return $project;
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete(Project $project): bool
    {
        if (!$project->getId()) {
            return false;
        }
        
        return $this->deleteById($project->getId());
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
            RIILSA_POST_TYPE_PROJECT
        ));
        
        return (int)$count > 0;
    }
    
    /**
     * {@inheritdoc}
     */
    public function count(array $criteria = []): int
    {
        $args = [
            'post_type' => RIILSA_POST_TYPE_PROJECT,
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
    public function countActive(): int
    {
        return $this->count([
            'project_status' => ProjectStatus::CURRENT,
        ]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function countExpired(): int
    {
        return $this->count([
            'project_status' => ProjectStatus::EXPIRED,
        ]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function updateExpiredStatuses(): int
    {
        $projects = $this->findByStatus(ProjectStatus::CURRENT);
        $updated = 0;
        
        foreach ($projects as $project) {
            $project->updateProjectStatus();
            
            if ($project->getProjectStatus() === ProjectStatus::EXPIRED) {
                $this->save($project);
                $this->updateProjectStatusTaxonomy($project);
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
     * Create Project entity from WordPress post
     *
     * @param \WP_Post $post
     * @return Project
     */
    private function createProjectFromPost(\WP_Post $post): Project
    {
        $meta = get_post_meta($post->ID);
        
        $data = [
            'externalId' => $meta['id'][0] ?? '',
            'title' => $post->post_title,
            'objective' => $meta['objetivo'][0] ?? '',
            'authorName' => $meta['nombre'][0] ?? '',
            'authorEmail' => $meta['correo'][0] ?? '',
            'university' => $meta['universidad'][0] ?? '',
            'country' => $meta['pais'][0] ?? '',
            'knowledgeArea' => $meta['area'][0] ?? '',
            'researchLine' => $meta['linea'][0] ?? $this->getProjectResearchLine($post->ID),
            'startDate' => $meta['fecha_inicio'][0] ?? '',
            'endDate' => $meta['fecha_termino'][0] ?? '',
            'websiteUrl' => $meta['pagina'][0] ?? null,
            'sdg' => $meta['ods'][0] ?? null,
            'expectedResults' => $meta['resultados'][0] ?? null,
            'summary' => $meta['resumen'][0] ?? null,
            'problemDescription' => $meta['problematica'][0] ?? null,
            'targetAudience' => $meta['quien'][0] ?? null,
            'featuredImageUrl' => get_the_post_thumbnail_url($post->ID, 'full') ?: null,
            'postStatus' => PostStatus::tryFromWordPress($post->post_status) ?? PostStatus::DRAFT,
            'createdAt' => new \DateTimeImmutable($post->post_date),
            'updatedAt' => new \DateTimeImmutable($post->post_modified),
        ];
        
        $project = new Project($data);
        $project->setId($post->ID);
        
        return $project;
    }
    
    /**
     * Save project meta fields
     *
     * @param Project $project
     * @return void
     */
    private function saveProjectMeta(Project $project): void
    {
        $metaFields = [
            'id' => $project->getExternalId(),
            'objetivo' => $project->getObjective(),
            'nombre' => $project->getAuthorName(),
            'correo' => $project->getAuthorEmail()->getValue(),
            'universidad' => $project->getUniversity(),
            'pais' => $project->getCountry(),
            'area' => $project->getKnowledgeArea(),
            'linea' => $project->getResearchLine(),
            'fecha_inicio' => $project->getDateRange()->getStartDate()->format('Y-m-d'),
            'fecha_termino' => $project->getDateRange()->getEndDate()->format('Y-m-d'),
            'pagina' => $project->getWebsiteUrl(),
            'ods' => $project->getSdg(),
            'resultados' => $project->getExpectedResults(),
            'resumen' => $project->getSummary(),
            'problematica' => $project->getProblemDescription(),
            'quien' => $project->getTargetAudience(),
        ];
        
        foreach ($metaFields as $key => $value) {
            if ($value !== null) {
                update_post_meta($project->getId(), $key, $value);
            }
        }
    }
    
    /**
     * Get project research line from taxonomy
     *
     * @param int $postId
     * @return string
     */
    private function getProjectResearchLine(int $postId): string
    {
        $terms = wp_get_post_terms($postId, RIILSA_TAXONOMY_AREA);
        
        if (!empty($terms) && !is_wp_error($terms)) {
            return $terms[0]->name;
        }
        
        return '';
    }
    
    /**
     * Update project status taxonomy
     *
     * @param Project $project
     * @return void
     */
    private function updateProjectStatusTaxonomy(Project $project): void
    {
        wp_set_post_terms(
            $project->getId(),
            [$project->getProjectStatus()->toTaxonomyTerm()],
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
        if (isset($criteria['project_status'])) {
            $args['tax_query'][] = [
                'taxonomy' => RIILSA_TAXONOMY_STATUS,
                'field' => 'name',
                'terms' => $criteria['project_status']->toTaxonomyTerm(),
            ];
        }
        
        if (isset($criteria['post_status'])) {
            $args['post_status'] = $criteria['post_status']->toWordPress();
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
