<?php

declare(strict_types=1);

/**
 * Subscriber Status Value Object
 *
 * @package RIILSA\Domain\ValueObjects
 * @since 3.1.0
 */

namespace RIILSA\Domain\ValueObjects;

/**
 * Subscriber status enum
 */
enum SubscriberStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case UNSUBSCRIBED = 'unsubscribed';
    case BOUNCED = 'bounced';
    case BLOCKED = 'blocked';

    /**
     * Check if the status represents an active subscriber
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this === self::CONFIRMED;
    }
}
