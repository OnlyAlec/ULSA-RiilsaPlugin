<?php

declare(strict_types=1);

/**
 * Call Entity
 *
 * @package RIILSA\Domain\Entities
 * @since 3.1.0
 */

namespace RIILSA\Domain\Entities;

use RIILSA\Domain\ValueObjects\DateRange;
use RIILSA\Domain\ValueObjects\PostStatus;
use RIILSA\Domain\ValueObjects\ProjectStatus;

/**
 * Call (Convocatoria) entity
 * 
 * Pattern: Entity Pattern
 * This class represents a call/announcement in the domain model
 */
class Call
{
    /**
     * Call ID
     *
     * @var int|null
     */
    private ?int $id = null;
    
    /**
     * External ID from source system
     *
     * @var string
     */
    private string $externalId;
    
    /**
     * Call title
     *
     * @var string
     */
    private string $title;
    
    /**
     * Contact information
     *
     * @var string
     */
    private string $contact;
    
    /**
     * Description
     *
     * @var string
     */
    private string $description;
    
    /**
     * Publication link
     *
     * @var string|null
     */
    private ?string $publicationLink = null;
    
    /**
     * Call date range (opening to closing)
     *
     * @var DateRange
     */
    private DateRange $dateRange;
    
    /**
     * Post status
     *
     * @var PostStatus
     */
    private PostStatus $postStatus;
    
    /**
     * Call status (current/expired)
     *
     * @var ProjectStatus
     */
    private ProjectStatus $callStatus;
    
    /**
     * Creation date
     *
     * @var \DateTimeImmutable
     */
    private \DateTimeImmutable $createdAt;
    
    /**
     * Last update date
     *
     * @var \DateTimeImmutable|null
     */
    private ?\DateTimeImmutable $updatedAt = null;
    
    /**
     * Constructor
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->externalId = (string)($data['externalId'] ?? $data['id'] ?? '');
        $this->title = $data['title'];
        $this->contact = $data['contact'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->publicationLink = $data['publicationLink'] ?? null;
        
        // Date range
        $this->dateRange = $data['dateRange'] instanceof DateRange 
            ? $data['dateRange'] 
            : DateRange::fromStrings($data['openingDate'], $data['closingDate']);
        
        // Status fields
        $this->postStatus = $data['postStatus'] ?? PostStatus::PENDING;
        $this->callStatus = $this->determineCallStatus();
        
        // Dates
        $this->createdAt = $data['createdAt'] ?? new \DateTimeImmutable();
        $this->updatedAt = $data['updatedAt'] ?? null;
    }
    
    /**
     * Create from Excel data
     *
     * @param array $excelData
     * @return self
     */
    public static function createFromExcel(array $excelData): self
    {
        return new self([
            'id' => $excelData['id'] ?? '',
            'title' => $excelData['titulo'] ?? '',
            'contact' => $excelData['contacto'] ?? '',
            'description' => $excelData['descripcion'] ?? '',
            'publicationLink' => $excelData['link'] ?? null,
            'openingDate' => $excelData['apertura'] ?? '',
            'closingDate' => $excelData['cierre'] ?? '',
            'createdAt' => isset($excelData['hora']) 
                ? new \DateTimeImmutable($excelData['hora']) 
                : new \DateTimeImmutable(),
        ]);
    }
    
    /**
     * Determine call status based on date range
     *
     * @return ProjectStatus
     */
    private function determineCallStatus(): ProjectStatus
    {
        return ProjectStatus::fromDateRange(
            $this->dateRange->getStartDate(),
            $this->dateRange->getEndDate()
        );
    }
    
    // Getters
    
    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getExternalId(): string
    {
        return $this->externalId;
    }
    
    public function getTitle(): string
    {
        return $this->title;
    }
    
    public function getContact(): string
    {
        return $this->contact;
    }
    
    public function getDescription(): string
    {
        return $this->description;
    }
    
    public function getPublicationLink(): ?string
    {
        return $this->publicationLink;
    }
    
    public function getDateRange(): DateRange
    {
        return $this->dateRange;
    }
    
    public function getOpeningDate(): \DateTimeImmutable
    {
        return $this->dateRange->getStartDate();
    }
    
    public function getClosingDate(): \DateTimeImmutable
    {
        return $this->dateRange->getEndDate();
    }
    
    public function getPostStatus(): PostStatus
    {
        return $this->postStatus;
    }
    
    public function getCallStatus(): ProjectStatus
    {
        return $this->callStatus;
    }
    
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
    
    // Setters for mutable properties
    
    public function setId(int $id): void
    {
        $this->id = $id;
    }
    
    public function updatePostStatus(PostStatus $status): void
    {
        $this->postStatus = $status;
        $this->updatedAt = new \DateTimeImmutable();
    }
    
    public function updateCallStatus(): void
    {
        $this->callStatus = $this->determineCallStatus();
        $this->updatedAt = new \DateTimeImmutable();
    }
    
    // Business logic
    
    public function isOpen(): bool
    {
        return $this->callStatus === ProjectStatus::CURRENT;
    }
    
    public function isClosed(): bool
    {
        return $this->callStatus === ProjectStatus::EXPIRED;
    }
    
    public function isPublished(): bool
    {
        return $this->postStatus === PostStatus::PUBLISHED;
    }
    
    /**
     * Get the number of days until closing
     *
     * @return int|null Returns null if already closed
     */
    public function getDaysUntilClosing(): ?int
    {
        if ($this->isClosed()) {
            return null;
        }
        
        $today = new \DateTimeImmutable('today');
        $closing = $this->getClosingDate();
        
        if ($closing < $today) {
            return null;
        }
        
        return $today->diff($closing)->days;
    }
    
    /**
     * Get a formatted deadline message
     *
     * @return string
     */
    public function getDeadlineMessage(): string
    {
        $days = $this->getDaysUntilClosing();
        
        if ($days === null) {
            return __('Call closed', 'riilsa');
        }
        
        if ($days === 0) {
            return __('Closes today', 'riilsa');
        }
        
        if ($days === 1) {
            return __('Closes tomorrow', 'riilsa');
        }
        
        return sprintf(
            _n('Closes in %d day', 'Closes in %d days', $days, 'riilsa'),
            $days
        );
    }
    
    /**
     * Convert to array for persistence
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'externalId' => $this->externalId,
            'title' => $this->title,
            'contact' => $this->contact,
            'description' => $this->description,
            'publicationLink' => $this->publicationLink,
            'openingDate' => $this->dateRange->getStartDate()->format('Y-m-d'),
            'closingDate' => $this->dateRange->getEndDate()->format('Y-m-d'),
            'postStatus' => $this->postStatus->value,
            'callStatus' => $this->callStatus->value,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
