<?php

declare(strict_types=1);

/**
 * Subscription Confirmation DTO
 *
 * @package RIILSA\Application\DTOs
 * @since 3.1.0
 */

namespace RIILSA\Application\DTOs;

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
