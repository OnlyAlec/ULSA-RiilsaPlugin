<?php

declare(strict_types=1);

/**
 * Newsletter Send Result DTO
 *
 * @package RIILSA\Application\DTOs
 * @since 3.1.0
 */

namespace RIILSA\Application\DTOs;

/**
 * Newsletter send result DTO
 */
final class NewsletterSendResultDTO
{
    /**
     * Constructor
     *
     * @param bool $success
     * @param int $recipientCount
     * @param int $sentCount
     * @param array $errors
     * @param array $statistics
     */
    public function __construct(
        public readonly bool $success,
        public readonly int $recipientCount,
        public readonly int $sentCount,
        public readonly array $errors = [],
        public readonly array $statistics = []
    ) {
    }
    
    /**
     * Create a success result
     *
     * @param int $recipientCount
     * @param int $sentCount
     * @param array $statistics
     * @return self
     */
    public static function success(
        int $recipientCount,
        int $sentCount,
        array $statistics = []
    ): self {
        return new self(
            success: true,
            recipientCount: $recipientCount,
            sentCount: $sentCount,
            errors: [],
            statistics: $statistics
        );
    }
    
    /**
     * Create a failure result
     *
     * @param array $errors
     * @param int $recipientCount
     * @param int $sentCount
     * @return self
     */
    public static function failure(
        array $errors,
        int $recipientCount = 0,
        int $sentCount = 0
    ): self {
        return new self(
            success: false,
            recipientCount: $recipientCount,
            sentCount: $sentCount,
            errors: $errors,
            statistics: []
        );
    }
    
    /**
     * Get the failure rate
     *
     * @return float
     */
    public function getFailureRate(): float
    {
        if ($this->recipientCount === 0) {
            return 0.0;
        }
        
        return ($this->recipientCount - $this->sentCount) / $this->recipientCount;
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
            'recipientCount' => $this->recipientCount,
            'sentCount' => $this->sentCount,
            'failureRate' => $this->getFailureRate(),
            'errors' => $this->errors,
            'statistics' => $this->statistics,
        ];
    }
}
