<?php

declare(strict_types=1);

/**
 * Subscriber Entity
 *
 * @package RIILSA\Domain\Entities
 * @since 3.1.0
 */

namespace RIILSA\Domain\Entities;

use RIILSA\Domain\ValueObjects\Email;
use RIILSA\Domain\ValueObjects\SubscriberStatus;

/**
 * Subscriber entity
 * 
 * Pattern: Entity Pattern
 * This class represents a newsletter subscriber in the domain model
 */
class Subscriber
{
    /**
     * Subscriber ID
     *
     * @var int|null
     */
    private ?int $id = null;
    
    /**
     * Email address
     *
     * @var Email
     */
    private Email $email;
    
    /**
     * Dependency/department ID
     *
     * @var int
     */
    private int $dependencyId;
    
    /**
     * Subscriber status
     *
     * @var SubscriberStatus
     */
    private SubscriberStatus $status;
    
    /**
     * Confirmation token
     *
     * @var string|null
     */
    private ?string $confirmationToken = null;
    
    /**
     * Token expiration date
     *
     * @var \DateTimeImmutable|null
     */
    private ?\DateTimeImmutable $tokenExpiresAt = null;
    
    /**
     * External ID from Brevo
     *
     * @var string|null
     */
    private ?string $externalId = null;
    
    /**
     * IP address of subscription
     *
     * @var string|null
     */
    private ?string $ipAddress = null;
    
    /**
     * User agent of subscription
     *
     * @var string|null
     */
    private ?string $userAgent = null;
    
    /**
     * Subscription date
     *
     * @var \DateTimeImmutable
     */
    private \DateTimeImmutable $subscribedAt;
    
    /**
     * Confirmation date
     *
     * @var \DateTimeImmutable|null
     */
    private ?\DateTimeImmutable $confirmedAt = null;
    
    /**
     * Unsubscribe date
     *
     * @var \DateTimeImmutable|null
     */
    private ?\DateTimeImmutable $unsubscribedAt = null;
    
    /**
     * Last activity date
     *
     * @var \DateTimeImmutable|null
     */
    private ?\DateTimeImmutable $lastActivityAt = null;
    
    /**
     * Constructor
     *
     * @param Email $email
     * @param int $dependencyId
     */
    public function __construct(Email $email, int $dependencyId)
    {
        $this->email = $email;
        $this->dependencyId = $dependencyId;
        $this->status = SubscriberStatus::PENDING;
        $this->subscribedAt = new \DateTimeImmutable();
        $this->generateConfirmationToken();
    }
    
    /**
     * Create from database record
     *
     * @param array $data
     * @return self
     */
    public static function fromDatabaseRecord(array $data): self
    {
        $subscriber = new self(
            Email::fromString($data['email']),
            (int)$data['dependency_id']
        );
        
        $subscriber->id = (int)$data['id'];
        $subscriber->status = SubscriberStatus::from($data['status']);
        $subscriber->confirmationToken = $data['token'] ?? null;
        $subscriber->tokenExpiresAt = isset($data['token_expires_at']) 
            ? new \DateTimeImmutable($data['token_expires_at']) 
            : null;
        $subscriber->externalId = $data['external_id'] ?? null;
        $subscriber->ipAddress = $data['ip_address'] ?? null;
        $subscriber->userAgent = $data['user_agent'] ?? null;
        $subscriber->subscribedAt = new \DateTimeImmutable($data['subscribed_at']);
        $subscriber->confirmedAt = isset($data['confirmed_at']) 
            ? new \DateTimeImmutable($data['confirmed_at']) 
            : null;
        $subscriber->unsubscribedAt = isset($data['unsubscribed_at']) 
            ? new \DateTimeImmutable($data['unsubscribed_at']) 
            : null;
        $subscriber->lastActivityAt = isset($data['last_activity_at']) 
            ? new \DateTimeImmutable($data['last_activity_at']) 
            : null;
        
        return $subscriber;
    }
    
    /**
     * Generate a new confirmation token
     *
     * @return void
     */
    private function generateConfirmationToken(): void
    {
        $this->confirmationToken = bin2hex(random_bytes(32));
        $this->tokenExpiresAt = (new \DateTimeImmutable())->add(new \DateInterval('P7D')); // 7 days
    }
    
    // Getters
    
    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getEmail(): Email
    {
        return $this->email;
    }
    
    public function getDependencyId(): int
    {
        return $this->dependencyId;
    }
    
    public function getStatus(): SubscriberStatus
    {
        return $this->status;
    }
    
    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }
    
    public function getTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->tokenExpiresAt;
    }
    
    public function getExternalId(): ?string
    {
        return $this->externalId;
    }
    
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }
    
    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }
    
    public function getSubscribedAt(): \DateTimeImmutable
    {
        return $this->subscribedAt;
    }
    
    public function getConfirmedAt(): ?\DateTimeImmutable
    {
        return $this->confirmedAt;
    }
    
    public function getUnsubscribedAt(): ?\DateTimeImmutable
    {
        return $this->unsubscribedAt;
    }
    
    public function getLastActivityAt(): ?\DateTimeImmutable
    {
        return $this->lastActivityAt;
    }
    
    // Setters and business logic
    
    public function setId(int $id): void
    {
        $this->id = $id;
    }
    
    public function setExternalId(string $externalId): void
    {
        $this->externalId = $externalId;
    }
    
    public function setSubscriptionMetadata(string $ipAddress, string $userAgent): void
    {
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
    }
    
    /**
     * Confirm the subscription
     *
     * @param string $token
     * @return void
     * @throws \DomainException
     */
    public function confirm(string $token): void
    {
        if ($this->status !== SubscriberStatus::PENDING) {
            throw new \DomainException('Subscription is not pending confirmation');
        }
        
        if ($this->confirmationToken !== $token) {
            throw new \DomainException('Invalid confirmation token');
        }
        
        if ($this->tokenExpiresAt && $this->tokenExpiresAt < new \DateTimeImmutable()) {
            throw new \DomainException('Confirmation token has expired');
        }
        
        $this->status = SubscriberStatus::CONFIRMED;
        $this->confirmedAt = new \DateTimeImmutable();
        $this->confirmationToken = null;
        $this->tokenExpiresAt = null;
        $this->updateLastActivity();
    }
    
    /**
     * Unsubscribe
     *
     * @param string $reason
     * @return void
     */
    public function unsubscribe(string $reason = ''): void
    {
        if ($this->status === SubscriberStatus::UNSUBSCRIBED) {
            return; // Already unsubscribed
        }
        
        $this->status = SubscriberStatus::UNSUBSCRIBED;
        $this->unsubscribedAt = new \DateTimeImmutable();
        $this->updateLastActivity();
    }
    
    /**
     * Mark as bounced
     *
     * @return void
     */
    public function markAsBounced(): void
    {
        $this->status = SubscriberStatus::BOUNCED;
        $this->updateLastActivity();
    }
    
    /**
     * Block the subscriber
     *
     * @return void
     */
    public function block(): void
    {
        $this->status = SubscriberStatus::BLOCKED;
        $this->updateLastActivity();
    }
    
    /**
     * Resubscribe (if allowed)
     *
     * @return void
     * @throws \DomainException
     */
    public function resubscribe(): void
    {
        if (!in_array($this->status, [SubscriberStatus::UNSUBSCRIBED])) {
            throw new \DomainException('Cannot resubscribe from current status');
        }
        
        $this->status = SubscriberStatus::PENDING;
        $this->unsubscribedAt = null;
        $this->generateConfirmationToken();
        $this->updateLastActivity();
    }
    
    /**
     * Update last activity timestamp
     *
     * @return void
     */
    public function updateLastActivity(): void
    {
        $this->lastActivityAt = new \DateTimeImmutable();
    }
    
    /**
     * Get the confirmation URL
     *
     * @return string
     */
    public function getConfirmationUrl(): string
    {
        if (!$this->confirmationToken) {
            return '';
        }
        
        return add_query_arg([
            'action' => 'confirm',
            'token' => $this->confirmationToken,
            'email' => $this->email->getValue(),
        ], RIILSA_URL_CONFIRMATION);
    }
    
    /**
     * Check if the token is valid
     *
     * @param string $token
     * @return bool
     */
    public function isTokenValid(string $token): bool
    {
        if ($this->confirmationToken !== $token) {
            return false;
        }
        
        if ($this->tokenExpiresAt && $this->tokenExpiresAt < new \DateTimeImmutable()) {
            return false;
        }
        
        return true;
    }
    
    // Business logic queries
    
    public function isActive(): bool
    {
        return $this->status->isActive();
    }
    
    public function canReceiveEmails(): bool
    {
        return $this->status->canReceiveEmails();
    }
    
    public function isPending(): bool
    {
        return $this->status === SubscriberStatus::PENDING;
    }
    
    public function isConfirmed(): bool
    {
        return $this->status === SubscriberStatus::CONFIRMED;
    }
    
    /**
     * Convert to array for persistence
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email->getValue(),
            'dependency_id' => $this->dependencyId,
            'status' => $this->status->value,
            'token' => $this->confirmationToken,
            'token_expires_at' => $this->tokenExpiresAt?->format('Y-m-d H:i:s'),
            'external_id' => $this->externalId,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'subscribed_at' => $this->subscribedAt->format('Y-m-d H:i:s'),
            'confirmed_at' => $this->confirmedAt?->format('Y-m-d H:i:s'),
            'unsubscribed_at' => $this->unsubscribedAt?->format('Y-m-d H:i:s'),
            'last_activity_at' => $this->lastActivityAt?->format('Y-m-d H:i:s'),
        ];
    }
}
