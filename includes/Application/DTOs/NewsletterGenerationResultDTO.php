<?php

declare(strict_types=1);

/**
 * Newsletter Generation Result DTO
 *
 * @package RIILSA\Application\DTOs
 * @since 3.1.0
 */

namespace RIILSA\Application\DTOs;

/**
 * Newsletter generation result DTO
 */
final class NewsletterGenerationResultDTO
{
    /**
     * Constructor
     *
     * @param bool $success
     * @param string $html
     * @param int|null $newsletterId
     * @param array $statistics
     * @param array $errors
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $html,
        public readonly ?int $newsletterId,
        public readonly array $statistics,
        public readonly array $errors = []
    ) {
    }
    
    /**
     * Create a success result
     *
     * @param string $html
     * @param int $newsletterId
     * @param array $statistics
     * @return self
     */
    public static function success(
        string $html,
        int $newsletterId,
        array $statistics
    ): self {
        return new self(
            success: true,
            html: $html,
            newsletterId: $newsletterId,
            statistics: $statistics,
            errors: []
        );
    }
    
    /**
     * Create a failure result
     *
     * @param array $errors
     * @return self
     */
    public static function failure(array $errors): self
    {
        return new self(
            success: false,
            html: '',
            newsletterId: null,
            statistics: [],
            errors: $errors
        );
    }
    
    /**
     * Check if the generation was successful
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->success && !empty($this->html);
    }
    
    /**
     * Get error messages as string
     *
     * @param string $separator
     * @return string
     */
    public function getErrorMessage(string $separator = ', '): string
    {
        return implode($separator, $this->errors);
    }
    
    /**
     * Convert to array for API response
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'html' => $this->html,
            'newsletterId' => $this->newsletterId,
            'statistics' => $this->statistics,
            'errors' => $this->errors,
        ];
    }
}
