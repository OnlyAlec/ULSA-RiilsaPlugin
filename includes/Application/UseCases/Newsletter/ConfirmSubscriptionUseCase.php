<?php

declare(strict_types=1);

/**
 * Confirm Subscription Use Case
 *
 * @package RIILSA\Application\UseCases\Newsletter
 * @since 3.1.0
 */

namespace RIILSA\Application\UseCases\Newsletter;

use RIILSA\Application\DTOs\SubscriptionConfirmationDTO;
use RIILSA\Application\DTOs\SubscriptionResultDTO;
use RIILSA\Domain\ValueObjects\Email;
use RIILSA\Domain\Repositories\SubscriberRepositoryInterface;

/**
 * Use case for confirming newsletter subscriptions
 * 
 * Pattern: Use Case Pattern
 * This class handles the subscription confirmation process
 */
class ConfirmSubscriptionUseCase
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
        private readonly SubscriberRepositoryInterface $subscriberRepository,
        mixed $mailService // Will be injected from container
    ) {
        $this->mailService = $mailService;
    }
    
    /**
     * Execute the use case
     *
     * @param SubscriptionConfirmationDTO $dto
     * @return SubscriptionResultDTO
     */
    public function execute(SubscriptionConfirmationDTO $dto): SubscriptionResultDTO
    {
        try {
            // Validate token format
            if (!$dto->isTokenFormatValid()) {
                return SubscriptionResultDTO::failure(
                    'Invalid confirmation link.',
                    ['Invalid token format']
                );
            }
            
            // Find subscriber by email
            $email = Email::fromString($dto->email);
            $subscriber = $this->subscriberRepository->findByEmail($email);
            
            if (!$subscriber) {
                return SubscriptionResultDTO::failure(
                    'Subscription not found.',
                    ['No subscriber found with this email']
                );
            }
            
            // Check if already confirmed
            if ($subscriber->isConfirmed()) {
                return SubscriptionResultDTO::success(
                    'Your subscription is already confirmed.',
                    null
                );
            }
            
            // Validate token
            if (!$subscriber->isTokenValid($dto->token)) {
                return SubscriptionResultDTO::failure(
                    'Invalid or expired confirmation link.',
                    ['Token mismatch or expired']
                );
            }
            
            // Confirm subscription
            $subscriber->confirm($dto->token);
            
            // Save to database
            $this->subscriberRepository->save($subscriber);
            
            // Update in mail service
            try {
                $this->mailService->confirmContact($email->getValue());
            } catch (\Exception $e) {
                debugLog('Failed to confirm contact in mail service: ' . $e->getMessage(), 'warning');
                // Continue even if mail service fails
            }
            
            // Log successful confirmation
            debugLog(sprintf(
                'Subscription confirmed for %s',
                $email->getValue()
            ), 'info');
            
            return SubscriptionResultDTO::success(
                'Your subscription has been confirmed successfully!',
                null
            );
            
        } catch (\DomainException $e) {
            // Handle domain-specific exceptions
            return SubscriptionResultDTO::failure(
                $e->getMessage(),
                [$e->getMessage()]
            );
            
        } catch (\Exception $e) {
            debugLog('Subscription confirmation error: ' . $e->getMessage(), 'error');
            
            return SubscriptionResultDTO::failure(
                'Confirmation failed. Please try again later.',
                [$e->getMessage()]
            );
        }
    }
    
    /**
     * Handle unsubscribe request
     *
     * @param string $email
     * @param string $token
     * @param string $reason
     * @return SubscriptionResultDTO
     */
    public function unsubscribe(string $email, string $token, string $reason = ''): SubscriptionResultDTO
    {
        try {
            $emailVO = Email::fromString($email);
            $subscriber = $this->subscriberRepository->findByEmail($emailVO);
            
            if (!$subscriber) {
                return SubscriptionResultDTO::failure(
                    'Subscription not found.',
                    ['No subscriber found with this email']
                );
            }
            
            // For unsubscribe, we use a different token validation
            // This could be implemented as a separate unsubscribe token
            // For now, we'll allow unsubscribe if the subscriber is confirmed
            if (!$subscriber->isConfirmed()) {
                return SubscriptionResultDTO::failure(
                    'Cannot unsubscribe: subscription not confirmed.',
                    ['Subscription must be confirmed to unsubscribe']
                );
            }
            
            // Unsubscribe
            $subscriber->unsubscribe($reason);
            
            // Save to database
            $this->subscriberRepository->save($subscriber);
            
            // Update in mail service
            try {
                $this->mailService->unsubscribeContact($emailVO->getValue());
            } catch (\Exception $e) {
                debugLog('Failed to unsubscribe contact in mail service: ' . $e->getMessage(), 'warning');
                // Continue even if mail service fails
            }
            
            debugLog(sprintf(
                'Unsubscribed %s. Reason: %s',
                $emailVO->getValue(),
                $reason ?: 'Not specified'
            ), 'info');
            
            return SubscriptionResultDTO::success(
                'You have been successfully unsubscribed from our newsletter.',
                null
            );
            
        } catch (\Exception $e) {
            debugLog('Unsubscribe error: ' . $e->getMessage(), 'error');
            
            return SubscriptionResultDTO::failure(
                'Unsubscribe failed. Please try again later.',
                [$e->getMessage()]
            );
        }
    }
}
