<?php

declare(strict_types=1);

/**
 * Subscribe User Use Case
 *
 * @package RIILSA\Application\UseCases\Newsletter
 * @since 3.1.0
 */

namespace RIILSA\Application\UseCases\Newsletter;

use RIILSA\Application\DTOs\SubscriptionRequestDTO;
use RIILSA\Application\DTOs\SubscriptionResultDTO;
use RIILSA\Application\Services\TemplateGenerationService;
use RIILSA\Domain\Entities\Subscriber;
use RIILSA\Domain\Repositories\SubscriberRepositoryInterface;

/**
 * Use case for subscribing users to newsletter
 * 
 * Pattern: Use Case Pattern
 * This class handles the user subscription process
 */
class SubscribeUserUseCase
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
        private readonly TemplateGenerationService $templateService,
        mixed $mailService // Will be injected from container
    ) {
        $this->mailService = $mailService;
    }
    
    /**
     * Execute the use case
     *
     * @param SubscriptionRequestDTO $dto
     * @return SubscriptionResultDTO
     */
    public function execute(SubscriptionRequestDTO $dto): SubscriptionResultDTO
    {
        try {
            // Get email value object
            $email = $dto->getEmailValueObject();
            
            // Check if subscriber already exists
            $existingSubscriber = $this->subscriberRepository->findByEmail($email);
            
            if ($existingSubscriber) {
                return $this->handleExistingSubscriber($existingSubscriber);
            }
            
            // Create new subscriber
            $subscriber = new Subscriber($email, $dto->dependencyId);
            $subscriber->setSubscriptionMetadata($dto->ipAddress, $dto->userAgent);
            
            // Save to database
            $subscriber = $this->subscriberRepository->save($subscriber);
            
            // Create in mail service
            try {
                $externalId = $this->mailService->createContact(
                    $email->getValue(),
                    $dto->dependencyId
                );
                
                if ($externalId) {
                    $subscriber->setExternalId($externalId);
                    $this->subscriberRepository->save($subscriber);
                }
            } catch (\Exception $e) {
                debugLog('Failed to create contact in mail service: ' . $e->getMessage(), 'warning');
                // Continue with the process even if mail service fails
            }
            
            // Send confirmation email
            $this->sendConfirmationEmail($subscriber);
            
            return SubscriptionResultDTO::success(
                'Subscription successful. Please check your email to confirm.',
                $subscriber->getConfirmationUrl()
            );
            
        } catch (\Exception $e) {
            debugLog('Subscription error: ' . $e->getMessage(), 'error');
            
            return SubscriptionResultDTO::failure(
                'Subscription failed. Please try again later.',
                [$e->getMessage()]
            );
        }
    }
    
    /**
     * Handle existing subscriber
     *
     * @param Subscriber $subscriber
     * @return SubscriptionResultDTO
     */
    private function handleExistingSubscriber(Subscriber $subscriber): SubscriptionResultDTO
    {
        // If already confirmed
        if ($subscriber->isConfirmed()) {
            return SubscriptionResultDTO::success(
                'You are already subscribed to our newsletter.',
                null
            );
        }
        
        // If pending confirmation
        if ($subscriber->isPending()) {
            // Resend confirmation email if token is still valid
            if ($subscriber->getTokenExpiresAt() > new \DateTimeImmutable()) {
                $this->sendConfirmationEmail($subscriber);
                
                return SubscriptionResultDTO::success(
                    'A confirmation email has been resent to your address.',
                    $subscriber->getConfirmationUrl()
                );
            }
            
            // Token expired, allow resubscription
            try {
                $subscriber->resubscribe();
                $this->subscriberRepository->save($subscriber);
                $this->sendConfirmationEmail($subscriber);
                
                return SubscriptionResultDTO::success(
                    'Your subscription has been renewed. Please check your email to confirm.',
                    $subscriber->getConfirmationUrl()
                );
            } catch (\Exception $e) {
                return SubscriptionResultDTO::failure(
                    'Failed to renew subscription.',
                    [$e->getMessage()]
                );
            }
        }
        
        // If unsubscribed
        if ($subscriber->getStatus()->value === 'unsubscribed') {
            try {
                $subscriber->resubscribe();
                $this->subscriberRepository->save($subscriber);
                $this->sendConfirmationEmail($subscriber);
                
                return SubscriptionResultDTO::success(
                    'Welcome back! Please check your email to confirm your subscription.',
                    $subscriber->getConfirmationUrl()
                );
            } catch (\Exception $e) {
                return SubscriptionResultDTO::failure(
                    'Failed to resubscribe.',
                    [$e->getMessage()]
                );
            }
        }
        
        // Other statuses (bounced, blocked)
        return SubscriptionResultDTO::failure(
            'This email address cannot be subscribed at this time.',
            ['Email status: ' . $subscriber->getStatus()->label()]
        );
    }
    
    /**
     * Send confirmation email
     *
     * @param Subscriber $subscriber
     * @return void
     * @throws \RuntimeException
     */
    private function sendConfirmationEmail(Subscriber $subscriber): void
    {
        try {
            // Generate confirmation email HTML
            $html = $this->templateService->generateConfirmationEmail(
                $subscriber->getConfirmationUrl(),
                $subscriber->getEmail()->getValue()
            );
            
            // Send via mail service
            $this->mailService->sendTransactionalEmail(
                $subscriber->getEmail()->getValue(),
                [
                    'subject' => __('Confirm your subscription to RIILSA Newsletter', 'riilsa'),
                    'html' => $html,
                ]
            );
            
            debugLog(sprintf(
                'Confirmation email sent to %s',
                $subscriber->getEmail()->getValue()
            ), 'info');
            
        } catch (\Exception $e) {
            debugLog('Failed to send confirmation email: ' . $e->getMessage(), 'error');
            throw new \RuntimeException('Failed to send confirmation email');
        }
    }
}
