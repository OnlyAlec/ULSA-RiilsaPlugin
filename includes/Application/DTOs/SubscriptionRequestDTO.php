<?php

declare(strict_types=1);

/**
 * Subscription Request DTO
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
