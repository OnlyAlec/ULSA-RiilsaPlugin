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
            updateDatabase: filter_var($data['updateDB'] ?? true, FILTER_VALIDATE_BOOLEAN),
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

