<?php

declare(strict_types=1);

/**
 * Newsletter Entity
 *
 * @package RIILSA\Domain\Entities
 * @since 3.1.0
 */

namespace RIILSA\Domain\Entities;

use RIILSA\Domain\ValueObjects\NewsletterStatus;

/**
 * Newsletter entity
 * 
 * Pattern: Entity Pattern
 * This class represents a newsletter in the domain model
 */
class Newsletter
{
    /**
     * Newsletter ID
     *
     * @var int|null
     */
    private ?int $id = null;
    
    /**
     * Newsletter number
     *
     * @var int
     */
    private int $number;
    
    /**
     * Header text
     *
     * @var string
     */
    private string $headerText;
    
    /**
     * Collection of news IDs
     *
     * @var array<int>
     */
    private array $newsIds = [];
    
    /**
     * Categorized news items
     *
     * @var array<string, array<News>>
     */
    private array $categorizedNews = [];
    
    /**
     * Generated HTML content
     *
     * @var string|null
     */
    private ?string $htmlContent = null;
    
    /**
     * Newsletter status
     *
     * @var NewsletterStatus
     */
    private NewsletterStatus $status;
    
    /**
     * Scheduled send date
     *
     * @var \DateTimeImmutable|null
     */
    private ?\DateTimeImmutable $scheduledAt = null;
    
    /**
     * Actual send date
     *
     * @var \DateTimeImmutable|null
     */
    private ?\DateTimeImmutable $sentAt = null;
    
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
     * Send statistics
     *
     * @var array
     */
    private array $statistics = [
        'recipients' => 0,
        'sent' => 0,
        'opened' => 0,
        'clicked' => 0,
        'bounced' => 0,
        'unsubscribed' => 0,
    ];
    
    /**
     * Constructor
     *
     * @param int $number
     * @param string $headerText
     * @param array<int> $newsIds
     */
    public function __construct(int $number, string $headerText, array $newsIds = [])
    {
        $this->number = $number;
        $this->headerText = $headerText;
        $this->newsIds = array_map('intval', $newsIds);
        $this->status = NewsletterStatus::DRAFT;
        $this->createdAt = new \DateTimeImmutable();
    }
    
    /**
     * Create from database record
     *
     * @param array $data
     * @return self
     */
    public static function fromDatabaseRecord(array $data): self
    {
        $newsletter = new self(
            (int)$data['number'],
            $data['text_header'],
            explode(',', $data['news_collection'] ?? '')
        );
        
        $newsletter->id = (int)$data['id'];
        $newsletter->status = NewsletterStatus::from((int)$data['id_status']);
        $newsletter->createdAt = new \DateTimeImmutable($data['date_created']);
        $newsletter->updatedAt = isset($data['date_updated']) 
            ? new \DateTimeImmutable($data['date_updated']) 
            : null;
        $newsletter->scheduledAt = isset($data['scheduled_at']) 
            ? new \DateTimeImmutable($data['scheduled_at']) 
            : null;
        $newsletter->sentAt = isset($data['sent_at']) 
            ? new \DateTimeImmutable($data['sent_at']) 
            : null;
        
        // Load statistics if available
        if (isset($data['statistics'])) {
            $newsletter->statistics = json_decode($data['statistics'], true) ?? [];
        }
        
        return $newsletter;
    }
    
    // Getters
    
    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getNumber(): int
    {
        return $this->number;
    }
    
    public function getHeaderText(): string
    {
        return $this->headerText;
    }
    
    public function getNewsIds(): array
    {
        return $this->newsIds;
    }
    
    public function getCategorizedNews(): array
    {
        return $this->categorizedNews;
    }
    
    public function getHtmlContent(): ?string
    {
        return $this->htmlContent;
    }
    
    public function getStatus(): NewsletterStatus
    {
        return $this->status;
    }
    
    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }
    
    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }
    
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
    
    public function getStatistics(): array
    {
        return $this->statistics;
    }
    
    /**
     * Get the title for display
     *
     * @return string
     */
    public function getTitle(): string
    {
        return sprintf(__('Newsletter #%d', 'riilsa'), $this->number);
    }
    
    /**
     * Get the subject line for email
     *
     * @return string
     */
    public function getSubject(): string
    {
        return sprintf(__('Newsletter #%d RIILSA', 'riilsa'), $this->number);
    }
    
    // Setters and business logic
    
    public function setId(int $id): void
    {
        $this->id = $id;
    }
    
    /**
     * Add categorized news
     *
     * @param string $category (highlight, normal, grid)
     * @param array<News> $newsItems
     * @return void
     */
    public function addCategorizedNews(string $category, array $newsItems): void
    {
        if (!in_array($category, ['highlight', 'normal', 'grid'])) {
            throw new \InvalidArgumentException("Invalid news category: {$category}");
        }
        
        $this->categorizedNews[$category] = $newsItems;
        $this->updatedAt = new \DateTimeImmutable();
    }
    
    /**
     * Set the generated HTML content
     *
     * @param string $html
     * @return void
     */
    public function setHtmlContent(string $html): void
    {
        $this->htmlContent = $html;
        $this->updatedAt = new \DateTimeImmutable();
    }
    
    /**
     * Schedule the newsletter
     *
     * @param \DateTimeInterface $scheduledDate
     * @return void
     * @throws \DomainException
     */
    public function schedule(\DateTimeInterface $scheduledDate): void
    {
        if (!$this->status->canEdit()) {
            throw new \DomainException('Cannot schedule newsletter in current status');
        }
        
        if ($scheduledDate <= new \DateTime()) {
            throw new \InvalidArgumentException('Scheduled date must be in the future');
        }
        
        $this->scheduledAt = \DateTimeImmutable::createFromInterface($scheduledDate);
        $this->status = NewsletterStatus::SCHEDULED;
        $this->updatedAt = new \DateTimeImmutable();
    }
    
    /**
     * Mark as sending
     *
     * @return void
     * @throws \DomainException
     */
    public function markAsSending(): void
    {
        if (!$this->status->canSend()) {
            throw new \DomainException('Cannot send newsletter in current status');
        }
        
        $this->status = NewsletterStatus::SENDING;
        $this->updatedAt = new \DateTimeImmutable();
    }
    
    /**
     * Mark as sent
     *
     * @param array $statistics
     * @return void
     */
    public function markAsSent(array $statistics = []): void
    {
        $this->status = NewsletterStatus::SENT;
        $this->sentAt = new \DateTimeImmutable();
        $this->statistics = array_merge($this->statistics, $statistics);
        $this->updatedAt = new \DateTimeImmutable();
    }
    
    /**
     * Mark as failed
     *
     * @param string $reason
     * @return void
     */
    public function markAsFailed(string $reason = ''): void
    {
        $this->status = NewsletterStatus::FAILED;
        $this->updatedAt = new \DateTimeImmutable();
        
        // Could store failure reason in statistics
        $this->statistics['failure_reason'] = $reason;
    }
    
    /**
     * Cancel the newsletter
     *
     * @return void
     * @throws \DomainException
     */
    public function cancel(): void
    {
        if (!$this->status->canCancel()) {
            throw new \DomainException('Cannot cancel newsletter in current status');
        }
        
        $this->status = NewsletterStatus::CANCELLED;
        $this->updatedAt = new \DateTimeImmutable();
    }
    
    /**
     * Update statistics
     *
     * @param array $statistics
     * @return void
     */
    public function updateStatistics(array $statistics): void
    {
        $this->statistics = array_merge($this->statistics, $statistics);
        $this->updatedAt = new \DateTimeImmutable();
    }
    
    // Business logic queries
    
    public function canEdit(): bool
    {
        return $this->status->canEdit();
    }
    
    public function canSend(): bool
    {
        return $this->status->canSend() && !empty($this->htmlContent);
    }
    
    public function isSent(): bool
    {
        return $this->status === NewsletterStatus::SENT;
    }
    
    public function isScheduled(): bool
    {
        return $this->status === NewsletterStatus::SCHEDULED;
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
            'number' => $this->number,
            'id_status' => $this->status->value,
            'status' => $this->status->label(),
            'news_collection' => implode(',', $this->newsIds),
            'text_header' => $this->headerText,
            'html_content' => $this->htmlContent,
            'scheduled_at' => $this->scheduledAt?->format('Y-m-d H:i:s'),
            'sent_at' => $this->sentAt?->format('Y-m-d H:i:s'),
            'date_created' => $this->createdAt->format('Y-m-d H:i:s'),
            'date_updated' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'statistics' => json_encode($this->statistics),
        ];
    }
}
