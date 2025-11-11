<?php

declare(strict_types=1);

/**
 * Subscription DTOs
 *
 * @package RIILSA\Application\DTOs
 * @since 3.1.0
 */

namespace RIILSA\Application\DTOs;

use RIILSA\Domain\ValueObjects\Email;

/**
 * Data Transfer Object for subscription requests
 * 
 * Pattern: DTO Pattern
 */
final class SubscriptionRequestDTO
{
    /**
     * Constructor
     *
     * @param string $email
     * @param int $dependencyId
     * @param string $ipAddress
     * @param string $userAgent
     * @param array $metadata
     */
    public function __construct(
        public readonly string $email,
        public readonly int $dependencyId,
        public readonly string $ipAddress,
        public readonly string $userAgent,
        public readonly array $metadata = []
    ) {
    }
    
    /**
     * Create from request data
     *
     * @param array $data
     * @param string $ipAddress
     * @param string $userAgent
     * @return self
     * @throws \InvalidArgumentException
     */
    public static function fromRequest(
        array $data,
        string $ipAddress,
        string $userAgent
    ): self {
        if (empty($data['email'])) {
            throw new \InvalidArgumentException('Email is required');
        }
        
        if (empty($data['dependencyId'])) {
            throw new \InvalidArgumentException('Dependency is required');
        }
        
        // Validate email format
        $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }
        
        return new self(
            email: $email,
            dependencyId: (int)$data['dependencyId'],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            metadata: $data['metadata'] ?? []
        );
    }
    
    /**
     * Get email as value object
     *
     * @return Email
     * @throws \InvalidArgumentException
     */
    public function getEmailValueObject(): Email
    {
        return Email::fromString($this->email);
    }
}

/**
 * Subscription confirmation DTO
 */
final class SubscriptionConfirmationDTO
{
    /**
     * Constructor
     *
     * @param string $email
     * @param string $token
     */
    public function __construct(
        public readonly string $email,
        public readonly string $token
    ) {
    }
    
    /**
     * Create from request data
     *
     * @param array $data
     * @return self
     * @throws \InvalidArgumentException
     */
    public static function fromRequest(array $data): self
    {
        if (empty($data['email'])) {
            throw new \InvalidArgumentException('Email is required');
        }
        
        if (empty($data['token'])) {
            throw new \InvalidArgumentException('Confirmation token is required');
        }
        
        return new self(
            email: filter_var($data['email'], FILTER_SANITIZE_EMAIL),
            token: trim($data['token'])
        );
    }
    
    /**
     * Validate token format
     *
     * @return bool
     */
    public function isTokenFormatValid(): bool
    {
        // Expecting a 64-character hex string (32 bytes)
        return preg_match('/^[a-f0-9]{64}$/i', $this->token) === 1;
    }
}

/**
 * Unsubscribe request DTO
 */
final class UnsubscribeRequestDTO
{
    /**
     * Constructor
     *
     * @param string $email
     * @param string $token
     * @param string $reason
     */
    public function __construct(
        public readonly string $email,
        public readonly string $token,
        public readonly string $reason = ''
    ) {
    }
    
    /**
     * Create from request data
     *
     * @param array $data
     * @return self
     * @throws \InvalidArgumentException
     */
    public static function fromRequest(array $data): self
    {
        if (empty($data['email'])) {
            throw new \InvalidArgumentException('Email is required');
        }
        
        if (empty($data['token'])) {
            throw new \InvalidArgumentException('Unsubscribe token is required');
        }
        
        return new self(
            email: filter_var($data['email'], FILTER_SANITIZE_EMAIL),
            token: trim($data['token']),
            reason: $data['reason'] ?? ''
        );
    }
}

/**
 * Subscription result DTO
 */
final class SubscriptionResultDTO
{
    /**
     * Constructor
     *
     * @param bool $success
     * @param string $message
     * @param string|null $confirmationUrl
     * @param array $errors
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?string $confirmationUrl = null,
        public readonly array $errors = []
    ) {
    }
    
    /**
     * Create a success result
     *
     * @param string $message
     * @param string|null $confirmationUrl
     * @return self
     */
    public static function success(
        string $message,
        ?string $confirmationUrl = null
    ): self {
        return new self(
            success: true,
            message: $message,
            confirmationUrl: $confirmationUrl,
            errors: []
        );
    }
    
    /**
     * Create a failure result
     *
     * @param string $message
     * @param array $errors
     * @return self
     */
    public static function failure(
        string $message,
        array $errors = []
    ): self {
        return new self(
            success: false,
            message: $message,
            confirmationUrl: null,
            errors: $errors
        );
    }
    
    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'confirmationUrl' => $this->confirmationUrl,
            'errors' => $this->errors,
        ];
    }
}

/**
 * Subscriber statistics DTO
 */
final class SubscriberStatisticsDTO
{
    /**
     * Constructor
     *
     * @param int $total
     * @param int $confirmed
     * @param int $pending
     * @param int $unsubscribed
     * @param int $bounced
     * @param int $blocked
     * @param array $byDependency
     * @param array $growth
     */
    public function __construct(
        public readonly int $total,
        public readonly int $confirmed,
        public readonly int $pending,
        public readonly int $unsubscribed,
        public readonly int $bounced,
        public readonly int $blocked,
        public readonly array $byDependency = [],
        public readonly array $growth = []
    ) {
    }
    
    /**
     * Create from statistics data
     *
     * @param array $data
     * @return self
     */
    public static function fromStatistics(array $data): self
    {
        return new self(
            total: $data['total'] ?? 0,
            confirmed: $data['confirmed'] ?? 0,
            pending: $data['pending'] ?? 0,
            unsubscribed: $data['unsubscribed'] ?? 0,
            bounced: $data['bounced'] ?? 0,
            blocked: $data['blocked'] ?? 0,
            byDependency: $data['byDependency'] ?? [],
            growth: $data['growth'] ?? []
        );
    }
    
    /**
     * Get active subscriber count
     *
     * @return int
     */
    public function getActiveCount(): int
    {
        return $this->confirmed;
    }
    
    /**
     * Get inactive subscriber count
     *
     * @return int
     */
    public function getInactiveCount(): int
    {
        return $this->total - $this->confirmed;
    }
    
    /**
     * Get confirmation rate
     *
     * @return float
     */
    public function getConfirmationRate(): float
    {
        if ($this->total === 0) {
            return 0.0;
        }
        
        return $this->confirmed / $this->total;
    }
    
    /**
     * Get churn rate
     *
     * @return float
     */
    public function getChurnRate(): float
    {
        if ($this->total === 0) {
            return 0.0;
        }
        
        return ($this->unsubscribed + $this->bounced) / $this->total;
    }
    
    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'confirmed' => $this->confirmed,
            'pending' => $this->pending,
            'unsubscribed' => $this->unsubscribed,
            'bounced' => $this->bounced,
            'blocked' => $this->blocked,
            'active' => $this->getActiveCount(),
            'inactive' => $this->getInactiveCount(),
            'confirmationRate' => $this->getConfirmationRate(),
            'churnRate' => $this->getChurnRate(),
            'byDependency' => $this->byDependency,
            'growth' => $this->growth,
        ];
    }
}
