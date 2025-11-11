<?php

declare(strict_types=1);

/**
 * Newsletter Status Enumeration
 *
 * @package RIILSA\Domain\ValueObjects
 * @since 3.1.0
 */

namespace RIILSA\Domain\ValueObjects;

/**
 * Newsletter status enumeration
 * 
 * Pattern: Value Object Pattern (Enum)
 * This enum represents the possible statuses for newsletters in the system
 */
enum NewsletterStatus: int
{
    case DRAFT = 1;
    case SCHEDULED = 2;
    case SENDING = 3;
    case SENT = 4;
    case FAILED = 5;
    case CANCELLED = 6;
    
    /**
     * Get the display label
     *
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::DRAFT => __('Draft', 'riilsa'),
            self::SCHEDULED => __('Scheduled', 'riilsa'),
            self::SENDING => __('Sending', 'riilsa'),
            self::SENT => __('Sent', 'riilsa'),
            self::FAILED => __('Failed', 'riilsa'),
            self::CANCELLED => __('Cancelled', 'riilsa'),
        };
    }
    
    /**
     * Get the status color for UI
     *
     * @return string
     */
    public function color(): string
    {
        return match($this) {
            self::DRAFT => '#6c757d',      // gray
            self::SCHEDULED => '#17a2b8',   // info
            self::SENDING => '#ffc107',     // warning
            self::SENT => '#28a745',        // success
            self::FAILED => '#dc3545',      // danger
            self::CANCELLED => '#6c757d',   // gray
        };
    }
    
    /**
     * Check if the newsletter can be edited
     *
     * @return bool
     */
    public function canEdit(): bool
    {
        return in_array($this, [self::DRAFT, self::SCHEDULED], true);
    }
    
    /**
     * Check if the newsletter can be sent
     *
     * @return bool
     */
    public function canSend(): bool
    {
        return in_array($this, [self::DRAFT, self::SCHEDULED, self::FAILED], true);
    }
    
    /**
     * Check if the newsletter can be cancelled
     *
     * @return bool
     */
    public function canCancel(): bool
    {
        return in_array($this, [self::SCHEDULED, self::SENDING], true);
    }
    
    /**
     * Check if the newsletter is in a final state
     *
     * @return bool
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::SENT, self::CANCELLED], true);
    }
    
    /**
     * Get all active statuses
     *
     * @return array<self>
     */
    public static function activeStatuses(): array
    {
        return [self::DRAFT, self::SCHEDULED, self::SENDING];
    }
    
    /**
     * Get all completed statuses
     *
     * @return array<self>
     */
    public static function completedStatuses(): array
    {
        return [self::SENT, self::FAILED, self::CANCELLED];
    }
}

/**
 * Subscriber status enumeration
 */
enum SubscriberStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case UNSUBSCRIBED = 'unsubscribed';
    case BOUNCED = 'bounced';
    case BLOCKED = 'blocked';
    
    /**
     * Get the display label
     *
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => __('Pending Confirmation', 'riilsa'),
            self::CONFIRMED => __('Confirmed', 'riilsa'),
            self::UNSUBSCRIBED => __('Unsubscribed', 'riilsa'),
            self::BOUNCED => __('Bounced', 'riilsa'),
            self::BLOCKED => __('Blocked', 'riilsa'),
        };
    }
    
    /**
     * Check if the subscriber is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this === self::CONFIRMED;
    }
    
    /**
     * Check if the subscriber can receive emails
     *
     * @return bool
     */
    public function canReceiveEmails(): bool
    {
        return $this === self::CONFIRMED;
    }
    
    /**
     * Get the Brevo blacklist status
     *
     * @return bool
     */
    public function isBlacklisted(): bool
    {
        return !$this->isActive();
    }
}
