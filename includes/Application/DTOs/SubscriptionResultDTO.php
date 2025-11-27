<?php

declare(strict_types=1);

/**
 * Subscription Result DTO
 *
 * @package RIILSA\Application\DTOs
 * @since 3.1.0
 */

namespace RIILSA\Application\DTOs;

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
