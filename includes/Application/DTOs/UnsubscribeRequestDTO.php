<?php

declare(strict_types=1);

/**
 * Unsubscribe Request DTO
 *
 * @package RIILSA\Application\DTOs
 * @since 3.1.0
 */

namespace RIILSA\Application\DTOs;

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
