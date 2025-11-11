<?php

declare(strict_types=1);

/**
 * Newsletter Generation DTO
 *
 * @package RIILSA\Application\DTOs
 * @since 3.1.0
 */

namespace RIILSA\Application\DTOs;

/**
 * Data Transfer Object for newsletter generation
 * 
 * Pattern: DTO Pattern
 * This class transfers newsletter generation data between layers
 */
final class NewsletterGenerationDTO
{
    /**
     * Constructor
     *
     * @param int $newsletterNumber
     * @param string $headerText
     * @param array<int> $newsIds
     * @param bool $updateDatabase
     * @param array $options Additional options
     */
    public function __construct(
        public readonly int $newsletterNumber,
        public readonly string $headerText,
        public readonly array $newsIds,
        public readonly bool $updateDatabase = true,
        public readonly array $options = []
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
        // Validate required fields
        if (empty($data['idNewsletter'])) {
            throw new \InvalidArgumentException('Newsletter number is required');
        }
        
        if (empty($data['text'])) {
            throw new \InvalidArgumentException('Header text is required');
        }
        
        if (empty($data['idNews']) || !is_array($data['idNews'])) {
            throw new \InvalidArgumentException('At least one news item must be selected');
        }
        
        return new self(
            newsletterNumber: (int)$data['idNewsletter'],
            headerText: trim($data['text']),
            newsIds: array_map('intval', $data['idNews']),
            updateDatabase: $data['updateDB'] ?? true,
            options: $data['options'] ?? []
        );
    }
    
    /**
     * Get the number of selected news items
     *
     * @return int
     */
    public function getNewsCount(): int
    {
        return count($this->newsIds);
    }
    
    /**
     * Check if news IDs are valid
     *
     * @return bool
     */
    public function hasValidNewsIds(): bool
    {
        foreach ($this->newsIds as $id) {
            if ($id <= 0) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Get a specific option value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getOption(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }
    
    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'newsletterNumber' => $this->newsletterNumber,
            'headerText' => $this->headerText,
            'newsIds' => $this->newsIds,
            'updateDatabase' => $this->updateDatabase,
            'options' => $this->options,
        ];
    }
}

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

/**
 * Newsletter send DTO
 */
final class NewsletterSendDTO
{
    /**
     * Constructor
     *
     * @param int $newsletterId
     * @param string $html
     * @param array $recipientFilters
     * @param \DateTimeInterface|null $scheduledAt
     */
    public function __construct(
        public readonly int $newsletterId,
        public readonly string $html,
        public readonly array $recipientFilters = [],
        public readonly ?\DateTimeInterface $scheduledAt = null
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
        if (empty($data['id'])) {
            throw new \InvalidArgumentException('Newsletter ID is required');
        }
        
        if (empty($data['html'])) {
            throw new \InvalidArgumentException('Newsletter HTML content is required');
        }
        
        $scheduledAt = null;
        if (!empty($data['scheduledAt'])) {
            $scheduledAt = new \DateTimeImmutable($data['scheduledAt']);
        }
        
        return new self(
            newsletterId: (int)$data['id'],
            html: $data['html'],
            recipientFilters: $data['filters'] ?? [],
            scheduledAt: $scheduledAt
        );
    }
    
    /**
     * Check if this is a scheduled send
     *
     * @return bool
     */
    public function isScheduled(): bool
    {
        return $this->scheduledAt !== null;
    }
    
    /**
     * Check if scheduled time is valid
     *
     * @return bool
     */
    public function isScheduledTimeValid(): bool
    {
        if (!$this->isScheduled()) {
            return true;
        }
        
        return $this->scheduledAt > new \DateTime();
    }
    
    /**
     * Get recipient filter by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getFilter(string $key, mixed $default = null): mixed
    {
        return $this->recipientFilters[$key] ?? $default;
    }
    
    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'newsletterId' => $this->newsletterId,
            'html' => $this->html,
            'recipientFilters' => $this->recipientFilters,
            'scheduledAt' => $this->scheduledAt?->format('Y-m-d H:i:s'),
        ];
    }
}

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
