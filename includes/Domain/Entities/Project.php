<?php

declare(strict_types=1);

/**
 * Project Entity
 *
 * @package RIILSA\Domain\Entities
 * @since 3.1.0
 */

namespace RIILSA\Domain\Entities;

use RIILSA\Domain\ValueObjects\DateRange;
use RIILSA\Domain\ValueObjects\Email;
use RIILSA\Domain\ValueObjects\PostStatus;
use RIILSA\Domain\ValueObjects\ProjectStatus;

/**
 * Project entity
 * 
 * Pattern: Entity Pattern
 * This class represents a project in the domain model
 */
class Project
{
    /**
     * Project ID
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
     * Project title
     *
     * @var string
     */
    private string $title;
    
    /**
     * Project objective
     *
     * @var string
     */
    private string $objective;
    
    /**
     * Author name
     *
     * @var string
     */
    private string $authorName;
    
    /**
     * Author email
     *
     * @var Email
     */
    private Email $authorEmail;
    
    /**
     * University
     *
     * @var string
     */
    private string $university;
    
    /**
     * Country
     *
     * @var string
     */
    private string $country;
    
    /**
     * Knowledge area
     *
     * @var string
     */
    private string $knowledgeArea;
    
    /**
     * Research line (LGAC)
     *
     * @var string
     */
    private string $researchLine;
    
    /**
     * Project date range
     *
     * @var DateRange
     */
    private DateRange $dateRange;
    
    /**
     * Website URL
     *
     * @var string|null
     */
    private ?string $websiteUrl = null;
    
    /**
     * SDG (Sustainable Development Goals)
     *
     * @var string|null
     */
    private ?string $sdg = null;
    
    /**
     * Expected results
     *
     * @var string|null
     */
    private ?string $expectedResults = null;
    
    /**
     * Summary
     *
     * @var string|null
     */
    private ?string $summary = null;
    
    /**
     * Problem description
     *
     * @var string|null
     */
    private ?string $problemDescription = null;
    
    /**
     * Target audience
     *
     * @var string|null
     */
    private ?string $targetAudience = null;
    
    /**
     * Featured image URL
     *
     * @var string|null
     */
    private ?string $featuredImageUrl = null;
    
    /**
     * Post status
     *
     * @var PostStatus
     */
    private PostStatus $postStatus;
    
    /**
     * Project status
     *
     * @var ProjectStatus
     */
    private ProjectStatus $projectStatus;
    
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
        $this->externalId = $data['externalId'];
        $this->title = $data['title'];
        $this->objective = $data['objective'];
        $this->authorName = $data['authorName'];
        $this->authorEmail = Email::fromString($data['authorEmail']);
        $this->university = $data['university'];
        $this->country = $data['country'];
        $this->knowledgeArea = $data['knowledgeArea'];
        $this->researchLine = $data['researchLine'];
        $this->dateRange = $data['dateRange'] instanceof DateRange 
            ? $data['dateRange'] 
            : DateRange::fromStrings($data['startDate'], $data['endDate']);
        
        // Optional fields
        $this->websiteUrl = $data['websiteUrl'] ?? null;
        $this->sdg = $data['sdg'] ?? null;
        $this->expectedResults = $data['expectedResults'] ?? null;
        $this->summary = $data['summary'] ?? null;
        $this->problemDescription = $data['problemDescription'] ?? null;
        $this->targetAudience = $data['targetAudience'] ?? null;
        $this->featuredImageUrl = $data['featuredImageUrl'] ?? null;
        
        // Status fields
        $this->postStatus = $data['postStatus'] ?? PostStatus::PENDING;
        $this->projectStatus = $this->determineProjectStatus();
        
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
            'externalId' => (string)($excelData['id'] ?? ''),
            'title' => $excelData['titulo'] ?? '',
            'objective' => $excelData['objetivo'] ?? '',
            'authorName' => $excelData['nombre'] ?? '',
            'authorEmail' => $excelData['correo'] ?? '',
            'university' => $excelData['universidad'] ?? '',
            'country' => $excelData['pais'] ?? '',
            'knowledgeArea' => $excelData['area'] ?? '',
            'researchLine' => $excelData['linea'] ?? '',
            'startDate' => $excelData['fecha_inicio'] ?? '',
            'endDate' => $excelData['fecha_termino'] ?? '',
            'websiteUrl' => $excelData['pagina'] ?? null,
            'sdg' => $excelData['ods'] ?? null,
            'expectedResults' => $excelData['resultados'] ?? null,
            'summary' => $excelData['resumen'] ?? null,
            'problemDescription' => $excelData['problematica'] ?? null,
            'targetAudience' => $excelData['quien'] ?? null,
            'featuredImageUrl' => $excelData['imagen'] ?? null,
        ]);
    }
    
    /**
     * Determine project status based on date range
     *
     * @return ProjectStatus
     */
    private function determineProjectStatus(): ProjectStatus
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
    
    public function getObjective(): string
    {
        return $this->objective;
    }
    
    public function getAuthorName(): string
    {
        return $this->authorName;
    }
    
    public function getAuthorEmail(): Email
    {
        return $this->authorEmail;
    }
    
    public function getUniversity(): string
    {
        return $this->university;
    }
    
    public function getCountry(): string
    {
        return $this->country;
    }
    
    public function getKnowledgeArea(): string
    {
        return $this->knowledgeArea;
    }
    
    public function getResearchLine(): string
    {
        return $this->researchLine;
    }
    
    public function getDateRange(): DateRange
    {
        return $this->dateRange;
    }
    
    public function getWebsiteUrl(): ?string
    {
        return $this->websiteUrl;
    }
    
    public function getSdg(): ?string
    {
        return $this->sdg;
    }
    
    public function getExpectedResults(): ?string
    {
        return $this->expectedResults;
    }
    
    public function getSummary(): ?string
    {
        return $this->summary;
    }
    
    public function getProblemDescription(): ?string
    {
        return $this->problemDescription;
    }
    
    public function getTargetAudience(): ?string
    {
        return $this->targetAudience;
    }
    
    public function getFeaturedImageUrl(): ?string
    {
        return $this->featuredImageUrl;
    }
    
    public function getPostStatus(): PostStatus
    {
        return $this->postStatus;
    }
    
    public function getProjectStatus(): ProjectStatus
    {
        return $this->projectStatus;
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
    
    public function updateProjectStatus(): void
    {
        $this->projectStatus = $this->determineProjectStatus();
        $this->updatedAt = new \DateTimeImmutable();
    }
    
    // Business logic
    
    public function isActive(): bool
    {
        return $this->projectStatus === ProjectStatus::CURRENT;
    }
    
    public function isExpired(): bool
    {
        return $this->projectStatus === ProjectStatus::EXPIRED;
    }
    
    public function isPublished(): bool
    {
        return $this->postStatus === PostStatus::PUBLISHED;
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
            'objective' => $this->objective,
            'authorName' => $this->authorName,
            'authorEmail' => $this->authorEmail->getValue(),
            'university' => $this->university,
            'country' => $this->country,
            'knowledgeArea' => $this->knowledgeArea,
            'researchLine' => $this->researchLine,
            'startDate' => $this->dateRange->getStartDate()->format('Y-m-d'),
            'endDate' => $this->dateRange->getEndDate()->format('Y-m-d'),
            'websiteUrl' => $this->websiteUrl,
            'sdg' => $this->sdg,
            'expectedResults' => $this->expectedResults,
            'summary' => $this->summary,
            'problemDescription' => $this->problemDescription,
            'targetAudience' => $this->targetAudience,
            'featuredImageUrl' => $this->featuredImageUrl,
            'postStatus' => $this->postStatus->value,
            'projectStatus' => $this->projectStatus->value,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
