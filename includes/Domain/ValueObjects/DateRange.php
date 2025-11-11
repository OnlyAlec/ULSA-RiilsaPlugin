<?php

declare(strict_types=1);

/**
 * Date Range Value Object
 *
 * @package RIILSA\Domain\ValueObjects
 * @since 3.1.0
 */

namespace RIILSA\Domain\ValueObjects;

use DateTimeInterface;
use DateTimeImmutable;
use DateInterval;
use InvalidArgumentException;

/**
 * Date range value object
 * 
 * Pattern: Value Object Pattern
 * This immutable object represents a date range with start and end dates
 */
final class DateRange
{
    /**
     * Start date
     *
     * @var DateTimeImmutable
     */
    private readonly DateTimeImmutable $startDate;
    
    /**
     * End date
     *
     * @var DateTimeImmutable
     */
    private readonly DateTimeImmutable $endDate;
    
    /**
     * Create a new DateRange instance
     *
     * @param DateTimeInterface $startDate
     * @param DateTimeInterface $endDate
     * @throws InvalidArgumentException If end date is before start date
     */
    public function __construct(DateTimeInterface $startDate, DateTimeInterface $endDate)
    {
        $start = DateTimeImmutable::createFromInterface($startDate);
        $end = DateTimeImmutable::createFromInterface($endDate);
        
        if ($end < $start) {
            throw new InvalidArgumentException('End date must be after or equal to start date');
        }
        
        $this->startDate = $start;
        $this->endDate = $end;
    }
    
    /**
     * Create from date strings
     *
     * @param string $startDate
     * @param string $endDate
     * @param string $format
     * @return self
     * @throws InvalidArgumentException
     */
    public static function fromStrings(
        string $startDate,
        string $endDate,
        string $format = 'Y-m-d'
    ): self {
        $start = DateTimeImmutable::createFromFormat($format, $startDate);
        $end = DateTimeImmutable::createFromFormat($format, $endDate);
        
        if (!$start || !$end) {
            throw new InvalidArgumentException('Invalid date format');
        }
        
        return new self($start, $end);
    }
    
    /**
     * Create from timestamps
     *
     * @param int $startTimestamp
     * @param int $endTimestamp
     * @return self
     */
    public static function fromTimestamps(int $startTimestamp, int $endTimestamp): self
    {
        return new self(
            (new DateTimeImmutable())->setTimestamp($startTimestamp),
            (new DateTimeImmutable())->setTimestamp($endTimestamp)
        );
    }
    
    /**
     * Create for the current month
     *
     * @return self
     */
    public static function currentMonth(): self
    {
        $start = new DateTimeImmutable('first day of this month 00:00:00');
        $end = new DateTimeImmutable('last day of this month 23:59:59');
        
        return new self($start, $end);
    }
    
    /**
     * Create for the last N days
     *
     * @param int $days
     * @return self
     */
    public static function lastDays(int $days): self
    {
        $end = new DateTimeImmutable('today 23:59:59');
        $start = $end->sub(new DateInterval("P{$days}D"))->setTime(0, 0, 0);
        
        return new self($start, $end);
    }
    
    /**
     * Get the start date
     *
     * @return DateTimeImmutable
     */
    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }
    
    /**
     * Get the end date
     *
     * @return DateTimeImmutable
     */
    public function getEndDate(): DateTimeImmutable
    {
        return $this->endDate;
    }
    
    /**
     * Check if a date is within the range
     *
     * @param DateTimeInterface $date
     * @return bool
     */
    public function contains(DateTimeInterface $date): bool
    {
        $compareDate = DateTimeImmutable::createFromInterface($date);
        return $compareDate >= $this->startDate && $compareDate <= $this->endDate;
    }
    
    /**
     * Check if the range contains the current date
     *
     * @return bool
     */
    public function containsNow(): bool
    {
        return $this->contains(new DateTimeImmutable());
    }
    
    /**
     * Check if the range has passed
     *
     * @return bool
     */
    public function hasPassed(): bool
    {
        return $this->endDate < new DateTimeImmutable();
    }
    
    /**
     * Check if the range is in the future
     *
     * @return bool
     */
    public function isFuture(): bool
    {
        return $this->startDate > new DateTimeImmutable();
    }
    
    /**
     * Check if the range is active (contains current date)
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->containsNow();
    }
    
    /**
     * Get the duration in days
     *
     * @return int
     */
    public function getDurationInDays(): int
    {
        return $this->startDate->diff($this->endDate)->days + 1;
    }
    
    /**
     * Check if two date ranges overlap
     *
     * @param DateRange $other
     * @return bool
     */
    public function overlaps(DateRange $other): bool
    {
        return $this->startDate <= $other->endDate && $this->endDate >= $other->startDate;
    }
    
    /**
     * Check if two date ranges are equal
     *
     * @param DateRange $other
     * @return bool
     */
    public function equals(DateRange $other): bool
    {
        return $this->startDate == $other->startDate && $this->endDate == $other->endDate;
    }
    
    /**
     * Format the date range as a string
     *
     * @param string $format
     * @param string $separator
     * @return string
     */
    public function format(string $format = 'Y-m-d', string $separator = ' - '): string
    {
        return $this->startDate->format($format) . $separator . $this->endDate->format($format);
    }
    
    /**
     * String representation
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->format();
    }
    
    /**
     * Convert to array
     *
     * @return array{start: string, end: string}
     */
    public function toArray(): array
    {
        return [
            'start' => $this->startDate->format('Y-m-d H:i:s'),
            'end' => $this->endDate->format('Y-m-d H:i:s'),
        ];
    }
    
    /**
     * Serialize to JSON
     *
     * @return array{start: string, end: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
