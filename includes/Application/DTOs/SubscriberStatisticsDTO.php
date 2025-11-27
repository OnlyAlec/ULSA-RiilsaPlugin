<?php

declare(strict_types=1);

/**
 * Subscriber Statistics DTO
 *
 * @package RIILSA\Application\DTOs
 * @since 3.1.0
 */

namespace RIILSA\Application\DTOs;

/**
 * Subscriber statistics DTO
 */
final class SubscriberStatisticsDTO
{
    /**
     * Constructor
     *
     * @param int $total
     * @param int $confirmed
     * @param int $pending
     * @param int $unsubscribed
     * @param int $bounced
     * @param int $blocked
     * @param array $byDependency
     * @param array $growth
     */
    public function __construct(
        public readonly int $total,
        public readonly int $confirmed,
        public readonly int $pending,
        public readonly int $unsubscribed,
        public readonly int $bounced,
        public readonly int $blocked,
        public readonly array $byDependency = [],
        public readonly array $growth = []
    ) {
    }
    
    /**
     * Create from statistics data
     *
     * @param array $data
     * @return self
     */
    public static function fromStatistics(array $data): self
    {
        return new self(
            total: $data['total'] ?? 0,
            confirmed: $data['confirmed'] ?? 0,
            pending: $data['pending'] ?? 0,
            unsubscribed: $data['unsubscribed'] ?? 0,
            bounced: $data['bounced'] ?? 0,
            blocked: $data['blocked'] ?? 0,
            byDependency: $data['byDependency'] ?? [],
            growth: $data['growth'] ?? []
        );
    }
    
    /**
     * Get active subscriber count
     *
     * @return int
     */
    public function getActiveCount(): int
    {
        return $this->confirmed;
    }
    
    /**
     * Get inactive subscriber count
     *
     * @return int
     */
    public function getInactiveCount(): int
    {
        return $this->total - $this->confirmed;
    }
    
    /**
     * Get confirmation rate
     *
     * @return float
     */
    public function getConfirmationRate(): float
    {
        if ($this->total === 0) {
            return 0.0;
        }
        
        return $this->confirmed / $this->total;
    }
    
    /**
     * Get churn rate
     *
     * @return float
     */
    public function getChurnRate(): float
    {
        if ($this->total === 0) {
            return 0.0;
        }
        
        return ($this->unsubscribed + $this->bounced) / $this->total;
    }
    
    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'confirmed' => $this->confirmed,
            'pending' => $this->pending,
            'unsubscribed' => $this->unsubscribed,
            'bounced' => $this->bounced,
            'blocked' => $this->blocked,
            'active' => $this->getActiveCount(),
            'inactive' => $this->getInactiveCount(),
            'confirmationRate' => $this->getConfirmationRate(),
            'churnRate' => $this->getChurnRate(),
            'byDependency' => $this->byDependency,
            'growth' => $this->growth,
        ];
    }
}
