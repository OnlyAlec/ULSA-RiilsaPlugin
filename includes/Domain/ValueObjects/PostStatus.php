<?php

declare(strict_types=1);

/**
 * Post Status Enumeration
 *
 * @package RIILSA\Domain\ValueObjects
 * @since 3.1.0
 */

namespace RIILSA\Domain\ValueObjects;

/**
 * Post status enumeration
 * 
 * Pattern: Value Object Pattern (Enum)
 * This enum represents the possible statuses for posts in the system
 */
enum PostStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case PUBLISHED = 'publish';
    case PRIVATE = 'private';
    case TRASH = 'trash';
    case AUTO_DRAFT = 'auto-draft';
    case INHERIT = 'inherit';
    
    /**
     * Get the WordPress status value
     *
     * @return string
     */
    public function toWordPress(): string
    {
        return $this->value;
    }
    
    /**
     * Get the display label
     *
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::DRAFT => __('Draft', 'riilsa'),
            self::PENDING => __('Pending Review', 'riilsa'),
            self::PUBLISHED => __('Published', 'riilsa'),
            self::PRIVATE => __('Private', 'riilsa'),
            self::TRASH => __('Trash', 'riilsa'),
            self::AUTO_DRAFT => __('Auto Draft', 'riilsa'),
            self::INHERIT => __('Inherited', 'riilsa'),
        };
    }
    
    /**
     * Check if the status is public
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this === self::PUBLISHED;
    }
    
    /**
     * Check if the status is editable
     *
     * @return bool
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::DRAFT, self::PENDING, self::PRIVATE], true);
    }
    
    /**
     * Create from WordPress post status
     *
     * @param string $status
     * @return self
     * @throws \ValueError If the status is invalid
     */
    public static function fromWordPress(string $status): self
    {
        return self::from($status);
    }
    
    /**
     * Try to create from WordPress post status
     *
     * @param string $status
     * @return self|null
     */
    public static function tryFromWordPress(string $status): ?self
    {
        return self::tryFrom($status);
    }
    
    /**
     * Get all public statuses
     *
     * @return array<self>
     */
    public static function publicStatuses(): array
    {
        return [self::PUBLISHED];
    }
    
    /**
     * Get all editable statuses
     *
     * @return array<self>
     */
    public static function editableStatuses(): array
    {
        return [self::DRAFT, self::PENDING, self::PRIVATE];
    }
}

/**
 * Project/Call specific status enumeration
 */
enum ProjectStatus: string
{
    case CURRENT = 'current';
    case EXPIRED = 'expired';
    
    /**
     * Get the display label
     *
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::CURRENT => __('Current', 'riilsa'),
            self::EXPIRED => __('Expired', 'riilsa'),
        };
    }
    
    /**
     * Get the taxonomy term name
     *
     * @return string
     */
    public function toTaxonomyTerm(): string
    {
        return match($this) {
            self::CURRENT => RIILSA_TERM_CURRENT,
            self::EXPIRED => RIILSA_TERM_EXPIRED,
        };
    }
    
    /**
     * Create from date range
     *
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @param \DateTimeInterface|null $referenceDate
     * @return self
     */
    public static function fromDateRange(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        ?\DateTimeInterface $referenceDate = null
    ): self {
        $referenceDate ??= new \DateTime();
        
        if ($referenceDate >= $startDate && $referenceDate <= $endDate) {
            return self::CURRENT;
        }
        
        return self::EXPIRED;
    }
}
