<?php

declare(strict_types=1);

/**
 * Create Call Use Case
 *
 * @package RIILSA\Application\UseCases\ContentManager
 * @since 3.1.0
 */

namespace RIILSA\Application\UseCases\ContentManager;

use RIILSA\Domain\Entities\Call;
use RIILSA\Domain\Repositories\CallRepositoryInterface;
use RIILSA\Domain\ValueObjects\PostStatus;

/**
 * Use case for creating a call/announcement
 * 
 * Pattern: Use Case Pattern
 * This class handles the creation of a call from Excel data
 */
class CreateCallUseCase
{
    /**
     * Constructor
     */
    public function __construct(
        private readonly CallRepositoryInterface $callRepository
    ) {
    }
    
    /**
     * Execute the use case
     *
     * @param array $data Excel row data
     * @param array $options Additional options
     * @return Call
     * @throws \Exception
     */
    public function execute(array $data, array $options = []): Call
    {
        try {
            // Create call entity from Excel data
            $call = Call::createFromExcel($data);
            
            // Set post status (default to pending)
            $postStatus = $options['postStatus'] ?? PostStatus::PENDING;
            $call->updatePostStatus($postStatus);
            
            // Save to repository
            $savedCall = $this->callRepository->save($call);
            
            // Handle taxonomy terms
            $this->handleTaxonomies($savedCall);
            
            // Schedule status update if call is active
            if ($savedCall->isOpen()) {
                $this->scheduleStatusUpdate($savedCall);
            }
            
            // Log successful creation
            debugLog(sprintf(
                'Call created: ID=%d, Title="%s", Status=%s, Closes=%s',
                $savedCall->getId(),
                $savedCall->getTitle(),
                $savedCall->getCallStatus()->value,
                $savedCall->getClosingDate()->format('Y-m-d')
            ), 'info');
            
            return $savedCall;
            
        } catch (\Exception $e) {
            debugLog('Failed to create call: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * Handle taxonomy assignments
     *
     * @param Call $call
     * @return void
     */
    private function handleTaxonomies(Call $call): void
    {
        // Handle call status taxonomy
        $statusTerm = $call->getCallStatus()->toTaxonomyTerm();
        $this->assignTaxonomyTerm(
            $call->getId(),
            $statusTerm,
            RIILSA_TAXONOMY_STATUS
        );
    }
    
    /**
     * Assign taxonomy term to post
     *
     * @param int $postId
     * @param string $termName
     * @param string $taxonomy
     * @return void
     */
    private function assignTaxonomyTerm(int $postId, string $termName, string $taxonomy): void
    {
        $term = get_term_by('name', $termName, $taxonomy);
        
        if (!$term) {
            // Create term if it doesn't exist
            $termData = wp_insert_term($termName, $taxonomy);
            
            if (is_wp_error($termData)) {
                debugLog("Failed to create term '{$termName}': " . $termData->get_error_message(), 'warning');
                return;
            }
            
            $termId = $termData['term_id'];
        } else {
            $termId = $term->term_id;
        }
        
        // Assign term to post
        $result = wp_set_post_terms($postId, [$termId], $taxonomy);
        
        if (is_wp_error($result)) {
            debugLog("Failed to assign term '{$termName}' to post {$postId}: " . $result->get_error_message(), 'warning');
        }
    }
    
    /**
     * Schedule automatic status update
     *
     * @param Call $call
     * @return void
     */
    private function scheduleStatusUpdate(Call $call): void
    {
        // Schedule event for the day after closing date
        $scheduleTime = $call->getClosingDate()->modify('+1 day')->getTimestamp();
        
        // Use WordPress cron to schedule the update
        if (!wp_next_scheduled('riilsa_update_call_status', [$call->getId()])) {
            wp_schedule_single_event(
                $scheduleTime,
                'riilsa_update_call_status',
                [$call->getId()]
            );
            
            debugLog(sprintf(
                'Scheduled status update for call %d on %s',
                $call->getId(),
                date('Y-m-d H:i:s', $scheduleTime)
            ), 'info');
        }
    }
}
