<?php

declare(strict_types=1);

/**
 * Newsletter Send DTO
 *
 * @package RIILSA\Application\DTOs
 * @since 3.1.0
 */

namespace RIILSA\Application\DTOs;

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
