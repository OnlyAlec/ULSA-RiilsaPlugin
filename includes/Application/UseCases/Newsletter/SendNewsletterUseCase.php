<?php

declare(strict_types=1);

/**
 * Send Newsletter Use Case
 *
 * @package RIILSA\Application\UseCases\Newsletter
 * @since 3.1.0
 */

namespace RIILSA\Application\UseCases\Newsletter;

use RIILSA\Application\DTOs\NewsletterSendDTO;
use RIILSA\Application\DTOs\NewsletterSendResultDTO;
use RIILSA\Domain\Repositories\NewsletterRepositoryInterface;
use RIILSA\Domain\Repositories\SubscriberRepositoryInterface;
use RIILSA\Domain\ValueObjects\NewsletterStatus;

/**
 * Use case for sending newsletters
 * 
 * Pattern: Use Case Pattern
 * This class orchestrates the newsletter sending process
 */
class SendNewsletterUseCase
{
    /**
     * Mail service interface
     *
     * @var mixed
     */
    private $mailService;
    
    /**
     * Constructor
     */
    public function __construct(
        private readonly NewsletterRepositoryInterface $newsletterRepository,
        private readonly SubscriberRepositoryInterface $subscriberRepository,
        mixed $mailService // Will be injected from container
    ) {
        $this->mailService = $mailService;
    }
    
    /**
     * Execute the use case
     *
     * @param NewsletterSendDTO $dto
     * @return NewsletterSendResultDTO
     */
    public function execute(NewsletterSendDTO $dto): NewsletterSendResultDTO
    {
        try {
            // Get newsletter
            $newsletter = $this->newsletterRepository->findById($dto->newsletterId);
            
            if (!$newsletter) {
                return NewsletterSendResultDTO::failure(
                    ['Newsletter not found'],
                    0,
                    0
                );
            }
            
            // Validate newsletter can be sent
            if (!$newsletter->canSend()) {
                return NewsletterSendResultDTO::failure(
                    [sprintf(
                        'Newsletter cannot be sent in status: %s',
                        $newsletter->getStatus()->label()
                    )],
                    0,
                    0
                );
            }
            
            // Handle scheduled send
            if ($dto->isScheduled()) {
                if (!$dto->isScheduledTimeValid()) {
                    return NewsletterSendResultDTO::failure(
                        ['Scheduled time must be in the future'],
                        0,
                        0
                    );
                }
                
                $newsletter->schedule($dto->scheduledAt);
                $this->newsletterRepository->save($newsletter);
                
                return NewsletterSendResultDTO::success(
                    0,
                    0,
                    ['status' => 'scheduled', 'scheduledAt' => $dto->scheduledAt->format('Y-m-d H:i:s')]
                );
            }
            
            // Lock newsletter to prevent concurrent sends
            if (!$this->newsletterRepository->lockForSending($dto->newsletterId)) {
                return NewsletterSendResultDTO::failure(
                    ['Newsletter is already being sent'],
                    0,
                    0
                );
            }
            
            try {
                // Mark as sending
                $newsletter->markAsSending();
                $this->newsletterRepository->save($newsletter);
                
                // Get recipients
                $recipients = $this->getRecipients($dto->recipientFilters);
                
                if (empty($recipients)) {
                    throw new \RuntimeException('No recipients found');
                }
                
                // Send newsletter
                $sendResult = $this->sendToRecipients(
                    $newsletter,
                    $dto->html,
                    $recipients
                );
                
                // Update newsletter status
                if ($sendResult['success']) {
                    $newsletter->markAsSent($sendResult['statistics']);
                } else {
                    $newsletter->markAsFailed($sendResult['error'] ?? 'Unknown error');
                }
                
                $this->newsletterRepository->save($newsletter);
                
                // Unlock newsletter
                $this->newsletterRepository->unlockAfterSending($dto->newsletterId);
                
                return $sendResult['success']
                    ? NewsletterSendResultDTO::success(
                        $sendResult['recipientCount'],
                        $sendResult['sentCount'],
                        $sendResult['statistics']
                    )
                    : NewsletterSendResultDTO::failure(
                        [$sendResult['error'] ?? 'Send failed'],
                        $sendResult['recipientCount'],
                        $sendResult['sentCount']
                    );
                
            } catch (\Exception $e) {
                // Ensure newsletter is unlocked on error
                $this->newsletterRepository->unlockAfterSending($dto->newsletterId);
                throw $e;
            }
            
        } catch (\Exception $e) {
            debugLog('Newsletter send error: ' . $e->getMessage(), 'error');
            
            return NewsletterSendResultDTO::failure(
                ['Newsletter send failed: ' . $e->getMessage()],
                0,
                0
            );
        }
    }
    
    /**
     * Get recipients based on filters
     *
     * @param array $filters
     * @return array
     */
    private function getRecipients(array $filters): array
    {
        $dependencyIds = $filters['dependencies'] ?? null;
        
        // Get all active subscribers who can receive emails
        $subscribers = $this->subscriberRepository->findRecipients($dependencyIds);
        
        // Apply additional filters if needed
        if (!empty($filters['limit'])) {
            $subscribers = array_slice($subscribers, 0, (int)$filters['limit']);
        }
        
        return $subscribers;
    }
    
    /**
     * Send newsletter to recipients
     *
     * @param Newsletter $newsletter
     * @param string $html
     * @param array $recipients
     * @return array
     */
    private function sendToRecipients($newsletter, string $html, array $recipients): array
    {
        $recipientCount = count($recipients);
        $sentCount = 0;
        $errors = [];
        $statistics = [
            'recipients' => $recipientCount,
            'sent' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        try {
            // Prepare campaign data
            $listIds = $this->getListIdsFromRecipients($recipients);
            $tag = (string)$newsletter->getNumber();
            
            // Create and send campaign via mail service
            $campaignResult = $this->mailService->createAndSendCampaign(
                $listIds,
                $html,
                $tag,
                $newsletter->getSubject()
            );
            
            if ($campaignResult['success']) {
                $sentCount = $recipientCount; // Assume all sent if campaign created
                $statistics['sent'] = $sentCount;
                $statistics['campaignId'] = $campaignResult['campaignId'] ?? null;
            } else {
                $errors[] = $campaignResult['error'] ?? 'Campaign creation failed';
                $statistics['failed'] = $recipientCount;
            }
            
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
            $statistics['failed'] = $recipientCount;
        }
        
        $statistics['errors'] = $errors;
        
        return [
            'success' => $sentCount > 0,
            'recipientCount' => $recipientCount,
            'sentCount' => $sentCount,
            'statistics' => $statistics,
            'error' => !empty($errors) ? implode('; ', $errors) : null
        ];
    }
    
    /**
     * Get list IDs from recipients
     *
     * @param array $recipients
     * @return array
     */
    private function getListIdsFromRecipients(array $recipients): array
    {
        $dependencyIds = array_unique(array_map(
            fn($recipient) => $recipient->getDependencyId(),
            $recipients
        ));
        
        // Convert dependency IDs to mail service list IDs
        // This would need to be implemented based on your mail service integration
        return $dependencyIds;
    }
}
