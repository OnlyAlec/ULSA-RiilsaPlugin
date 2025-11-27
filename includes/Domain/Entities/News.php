<?php

declare(strict_types=1);

/**
 * News Entity
 *
 * @package RIILSA\Domain\Entities
 * @since 3.1.0
 */

namespace RIILSA\Domain\Entities;

use RIILSA\Domain\ValueObjects\PostStatus;

/**
 * News entity
 * 
 * Pattern: Entity Pattern
 * This class represents a news item in the domain model
 */
class News
{
    /**
     * News ID
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
     * News title
     *
     * @var string
     */
    private string $title;

    /**
     * Two bullet points
     *
     * @var string
     */
    private string $bullets;

    /**
     * News body content
     *
     * @var string
     */
    private string $content;

    /**
     * Contact information
     *
     * @var string|null
     */
    private ?string $contactInfo = null;

    /**
     * Newsletter number this news belongs to
     *
     * @var int|null
     */
    private ?int $newsletterNumber = null;

    /**
     * Featured image URL
     *
     * @var string|null
     */
    private ?string $featuredImageUrl = null;

    /**
     * Research line (LGAC)
     *
     * @var string|null
     */
    private ?string $researchLine = null;

    /**
     * Position in newsletter (highlight, normal, grid)
     *
     * @var string
     */
    private string $position = 'normal';

    /**
     * Post status
     *
     * @var PostStatus
     */
    private PostStatus $postStatus;

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
     * Publication date
     *
     * @var \DateTimeImmutable|null
     */
    private ?\DateTimeImmutable $publishedAt = null;

    /**
     * Constructor
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        // Handle internal ID
        $internalId = $data['id'] ?? null;
        if (is_array($internalId)) {
            $this->id = isset($internalId[0]) && is_numeric($internalId[0]) ? (int) $internalId[0] : null;
        } elseif (is_numeric($internalId)) {
            $this->id = (int) $internalId;
        } else {
            $this->id = null;
        }

        // Handle external ID
        $externalId = $data['externalId'] ?? $data['id'] ?? '';
        $this->externalId = is_array($externalId) ? (string) ($externalId[0] ?? '') : (string) $externalId;

        $this->title = is_array($data['title']) ? (string) ($data['title'][0] ?? '') : (string) $data['title'];

        $bullets = $data['bullets'] ?? '';
        if (is_array($bullets)) {
            $this->bullets = implode("\n", $bullets);
        } else {
            $this->bullets = (string) $bullets;
        }

        $this->content = $data['content'] ?? '';

        // Optional fields
        $contactInfo = $data['contactInfo'] ?? null;
        if (is_array($contactInfo)) {
            $this->contactInfo = implode("\n", $contactInfo);
        } else {
            $this->contactInfo = $contactInfo !== null ? (string) $contactInfo : null;
        }

        $this->newsletterNumber = isset($data['newsletterNumber']) ? (int) $data['newsletterNumber'] : null;

        $featuredImageUrl = $data['featuredImageUrl'] ?? null;
        if (is_array($featuredImageUrl)) {
            $this->featuredImageUrl = isset($featuredImageUrl[0]) ? (string) $featuredImageUrl[0] : null;
        } else {
            $this->featuredImageUrl = $featuredImageUrl !== null ? (string) $featuredImageUrl : null;
        }

        $researchLine = $data['researchLine'] ?? null;
        if (is_array($researchLine)) {
            $this->researchLine = isset($researchLine[0]) ? (string) $researchLine[0] : null;
        } else {
            $this->researchLine = $researchLine !== null ? (string) $researchLine : null;
        }

        $position = $data['position'] ?? 'normal';
        if (is_array($position)) {
            $this->position = isset($position[0]) ? (string) $position[0] : 'normal';
        } else {
            $this->position = (string) $position;
        }

        // Status fields
        $this->postStatus = $data['postStatus'] ?? PostStatus::PENDING;

        // Dates
        $this->createdAt = $data['createdAt'] ?? new \DateTimeImmutable();
        $this->updatedAt = $data['updatedAt'] ?? null;
        $this->publishedAt = $data['publishedAt'] ?? null;
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
            'bullets' => $excelData['bullets'] ?? '',
            'content' => $excelData['cuerpo'] ?? '',
            'contactInfo' => $excelData['datos'] ?? null,
            'newsletterNumber' => $excelData['numero'] ?? null,
            'featuredImageUrl' => $excelData['imagen'] ?? null,
            'researchLine' => $excelData['linea'] ?? null,
            'createdAt' => isset($excelData['marca'])
                ? new \DateTimeImmutable($excelData['marca'])
                : new \DateTimeImmutable(),
        ]);
    }

    /**
     * Create from WordPress post
     *
     * @param \WP_Post $post
     * @param array $meta
     * @return self
     */
    public static function createFromWordPressPost(\WP_Post $post, array $meta = []): self
    {
        $data = [
            'id' => $post->ID,
            'externalId' => $meta['id'] ?? $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'bullets' => $meta['bullets'] ?? '',
            'contactInfo' => $meta['contactInfo'] ?? null,
            'newsletterNumber' => $meta['newsletterNumber'] ?? null,
            'featuredImageUrl' => get_the_post_thumbnail_url($post->ID, 'full') ?: null,
            'researchLine' => self::extractResearchLine($post->ID),
            'position' => $meta['position'] ?? 'normal',
            'postStatus' => PostStatus::tryFromWordPress($post->post_status) ?? PostStatus::DRAFT,
            'createdAt' => new \DateTimeImmutable($post->post_date),
            'updatedAt' => new \DateTimeImmutable($post->post_modified),
            'publishedAt' => $post->post_status === 'publish'
                ? new \DateTimeImmutable($post->post_date)
                : null,
        ];

        return new self($data);
    }

    /**
     * Extract research line from post taxonomies
     *
     * @param int $postId
     * @return string|null
     */
    private static function extractResearchLine(int $postId): ?string
    {
        $terms = wp_get_post_terms($postId, RIILSA_TAXONOMY_AREA);
        if (!empty($terms) && !is_wp_error($terms)) {
            return $terms[0]->name;
        }
        return null;
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

    public function getBullets(): string
    {
        return $this->bullets;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getContactInfo(): ?string
    {
        return $this->contactInfo;
    }

    public function getNewsletterNumber(): ?int
    {
        return $this->newsletterNumber;
    }

    public function getFeaturedImageUrl(): ?string
    {
        return $this->featuredImageUrl;
    }

    public function getResearchLine(): ?string
    {
        return $this->researchLine;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function getPostStatus(): PostStatus
    {
        return $this->postStatus;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    /**
     * Get the excerpt for newsletter display
     *
     * @param int $length
     * @return string
     */
    public function getExcerpt(int $length = 150): string
    {
        if (!empty($this->bullets)) {
            return $this->bullets;
        }

        $content = strip_tags($this->content);
        if (strlen($content) <= $length) {
            return $content;
        }

        return substr($content, 0, $length) . '...';
    }

    /**
     * Get the URL for the news item
     *
     * @return string
     */
    public function getUrl(): string
    {
        if ($this->id) {
            return get_permalink($this->id);
        }
        return '';
    }

    // Setters for mutable properties

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setPosition(string $position): void
    {
        if (!in_array($position, ['highlight', 'normal', 'grid'])) {
            throw new \InvalidArgumentException("Invalid position: {$position}");
        }
        $this->position = $position;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updatePostStatus(PostStatus $status): void
    {
        $this->postStatus = $status;
        $this->updatedAt = new \DateTimeImmutable();

        if ($status === PostStatus::PUBLISHED && !$this->publishedAt) {
            $this->publishedAt = new \DateTimeImmutable();
        }
    }

    public function assignToNewsletter(int $newsletterNumber): void
    {
        $this->newsletterNumber = $newsletterNumber;
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Business logic

    public function isPublished(): bool
    {
        return $this->postStatus === PostStatus::PUBLISHED;
    }

    public function canBeAddedToNewsletter(): bool
    {
        return $this->isPublished();
    }

    public function isInNewsletter(): bool
    {
        return $this->newsletterNumber !== null;
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
            'bullets' => $this->bullets,
            'content' => $this->content,
            'contactInfo' => $this->contactInfo,
            'newsletterNumber' => $this->newsletterNumber,
            'featuredImageUrl' => $this->featuredImageUrl,
            'researchLine' => $this->researchLine,
            'position' => $this->position,
            'postStatus' => $this->postStatus->value,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'publishedAt' => $this->publishedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
