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
use RIILSA\Infrastructure\Services\BrevoMailService;
use RIILSA\Domain\Entities\Newsletter;
use function RIILSA\Core\debugLog;

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
     * @var BrevoMailService
     */
    private BrevoMailService $mailService;

    /**
     * Constructor
     */
    public function __construct(
        private readonly NewsletterRepositoryInterface $newsletterRepository,
        private readonly SubscriberRepositoryInterface $subscriberRepository,
        BrevoMailService $mailService
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
            $newsletter = $this->newsletterRepository->findByNumber($dto->newsletterId);

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
                    [
                        sprintf(
                            'Newsletter cannot be sent in status: %s',
                            $newsletter->getStatus()->label()
                        )
                    ],
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

            try {
                // Mark as sending
                $newsletter->markAsSending();
                $this->newsletterRepository->save($newsletter);

                // Get recipients
                $recipients = $this->getRecipients($dto->recipientFilters);

                if (empty($recipients)) {
                    throw new \RuntimeException('No recipients found');
                }

                // Check for split sending (limit 300)
                // Only if not already scheduled and has more than 300 recipients
                if (count($recipients) > 300 && !$dto->isScheduled()) {
                    $sendResult = $this->sendSplitBatches($newsletter, $dto->html, $recipients);
                } else {
                    // Standard send
                    $sendResult = $this->sendToRecipients(
                        $newsletter,
                        $dto->html,
                        $recipients,
                        $dto->scheduledAt
                    );
                }

                // Update newsletter status
                if ($sendResult['success']) {
                    // Check if it was a split batch with scheduling
                    $isSplitScheduled = isset($sendResult['statistics']['batches']['batch2']['status']) &&
                        $sendResult['statistics']['batches']['batch2']['status'] === 'scheduled';

                    if ($isSplitScheduled) {
                        $scheduledAtStr = $sendResult['statistics']['batches']['batch2']['scheduledAt'];
                        $scheduledAt = new \DateTimeImmutable($scheduledAtStr);

                        $newsletter->schedule($scheduledAt);
                        $newsletter->updateStatistics($sendResult['statistics']);
                    } else {
                        $newsletter->markAsSent($sendResult['statistics']);
                    }
                } else {
                    $newsletter->markAsFailed($sendResult['error'] ?? 'Unknown error');
                }

                $this->newsletterRepository->save($newsletter);

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
            $subscribers = array_slice($subscribers, 0, (int) $filters['limit']);
        }

        return $subscribers;
    }

    /**
     * Send newsletter to recipients
     *
     * @param Newsletter $newsletter
     * @param string $html
     * @param array $recipients
     * @param \DateTimeInterface|null $scheduledAt
     * @return array
     */
    private function sendToRecipients($newsletter, string $html, array $recipients, ?\DateTimeInterface $scheduledAt = null): array
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
            $tag = (string) $newsletter->getNumber();

            // Create and send campaign via mail service
            $campaignResult = $this->mailService->createAndSendCampaign(
                $listIds,
                $html,
                $tag,
                $newsletter->getSubject(),
                $scheduledAt
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
     * Send newsletter in split batches (limit 300)
     *
     * @param Newsletter $newsletter
     * @param string $html
     * @param array $recipients
     * @return array
     */
    private function sendSplitBatches($newsletter, string $html, array $recipients): array
    {
        $batchSize = 300;
        $batch1 = array_slice($recipients, 0, $batchSize);
        $batch2 = array_slice($recipients, $batchSize);

        $statistics = [
            'recipients' => count($recipients),
            'sent' => 0,
            'failed' => 0,
            'errors' => [],
            'batches' => []
        ];

        $errors = [];
        $success = false;

        try {
            // Create temporary lists
            $timestamp = date('YmdHis');
            $list1Name = sprintf('Newsletter #%d - Batch 1 - %s', $newsletter->getNumber(), $timestamp);
            $list2Name = sprintf('Newsletter #%d - Batch 2 - %s', $newsletter->getNumber(), $timestamp);

            $list1 = $this->mailService->createContactList($list1Name);
            $list2 = $this->mailService->createContactList($list2Name);

            if (empty($list1['id']) || empty($list2['id'])) {
                throw new \RuntimeException('Failed to create temporary lists for batch sending');
            }

            // Add contacts to lists
            $emails1 = array_map(fn($r) => $r->getEmail(), $batch1);
            $emails2 = array_map(fn($r) => $r->getEmail(), $batch2);

            $this->mailService->addContactsToList($list1['id'], $emails1);
            $this->mailService->addContactsToList($list2['id'], $emails2);

            // Send Batch 1 (Immediate)
            $result1 = $this->mailService->createAndSendCampaign(
                [$list1['id']],
                $html,
                (string) $newsletter->getNumber() . '_batch1',
                $newsletter->getSubject()
            );

            // Send Batch 2 (Scheduled for tomorrow)
            $tomorrow = new \DateTime('+1 day');
            // Set to 9:00 AM tomorrow if current time is late? Or just +24h?
            // Let's use +24h for simplicity as per requirement "next day"

            $result2 = $this->mailService->createAndSendCampaign(
                [$list2['id']],
                $html,
                (string) $newsletter->getNumber() . '_batch2',
                $newsletter->getSubject(),
                $tomorrow
            );

            // Process results
            if ($result1['success']) {
                $statistics['sent'] += count($batch1);
                $statistics['batches']['batch1'] = ['status' => 'sent', 'campaignId' => $result1['campaignId']];
            } else {
                $statistics['failed'] += count($batch1);
                $errors[] = 'Batch 1 failed: ' . ($result1['error'] ?? 'Unknown');
            }

            if ($result2['success']) {
                $statistics['sent'] += count($batch2); // Counted as sent because it's successfully scheduled
                $statistics['batches']['batch2'] = ['status' => 'scheduled', 'campaignId' => $result2['campaignId'], 'scheduledAt' => $tomorrow->format('Y-m-d H:i:s')];
            } else {
                $statistics['failed'] += count($batch2);
                $errors[] = 'Batch 2 failed: ' . ($result2['error'] ?? 'Unknown');
            }

            $success = $result1['success'] || $result2['success']; // Partial success is still success-ish

        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
            $statistics['failed'] = count($recipients);
        }

        $statistics['errors'] = $errors;

        return [
            'success' => $success,
            'recipientCount' => count($recipients),
            'sentCount' => $statistics['sent'],
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
