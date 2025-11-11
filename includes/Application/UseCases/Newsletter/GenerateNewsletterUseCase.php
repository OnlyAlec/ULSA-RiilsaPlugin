<?php

declare(strict_types=1);

/**
 * Generate Newsletter Use Case
 *
 * @package RIILSA\Application\UseCases\Newsletter
 * @since 3.1.0
 */

namespace RIILSA\Application\UseCases\Newsletter;

use RIILSA\Application\DTOs\NewsletterGenerationDTO;
use RIILSA\Application\DTOs\NewsletterGenerationResultDTO;
use RIILSA\Application\Services\TemplateGenerationService;
use RIILSA\Domain\Entities\Newsletter;
use RIILSA\Domain\Entities\News;
use RIILSA\Domain\Repositories\NewsletterRepositoryInterface;
use RIILSA\Domain\Repositories\NewsRepositoryInterface;
use RIILSA\Domain\Services\NewsletterContentService;

/**
 * Use case for generating newsletters
 * 
 * Pattern: Use Case Pattern
 * This class orchestrates the newsletter generation process
 */
class GenerateNewsletterUseCase
{
    /**
     * Constructor
     */
    public function __construct(
        private readonly NewsletterRepositoryInterface $newsletterRepository,
        private readonly NewsRepositoryInterface $newsRepository,
        private readonly NewsletterContentService $contentService,
        private readonly TemplateGenerationService $templateService
    ) {
    }
    
    /**
     * Execute the use case
     *
     * @param NewsletterGenerationDTO $dto
     * @return NewsletterGenerationResultDTO
     */
    public function execute(NewsletterGenerationDTO $dto): NewsletterGenerationResultDTO
    {
        try {
            // Validate input
            if (!$dto->hasValidNewsIds()) {
                return NewsletterGenerationResultDTO::failure(
                    ['Invalid news IDs provided']
                );
            }
            
            // Fetch news items
            $newsItems = $this->newsRepository->findByIds($dto->newsIds);
            
            if (empty($newsItems)) {
                return NewsletterGenerationResultDTO::failure(
                    ['No valid news items found']
                );
            }
            
            // Create or get newsletter
            $newsletter = $this->getOrCreateNewsletter($dto);
            
            // Categorize news items
            $categorizedNews = $this->contentService->categorizeNewsItems(
                $newsItems,
                $dto->newsIds
            );
            
            // Add categorized news to newsletter
            foreach ($categorizedNews as $category => $items) {
                $newsletter->addCategorizedNews($category, $items);
            }
            
            // Generate HTML content
            $html = $this->templateService->generateNewsletterHtml($newsletter);
            $newsletter->setHtmlContent($html);
            
            // Validate newsletter content
            $validation = $this->contentService->validateNewsletterContent($newsletter);
            if (!$validation['valid']) {
                return NewsletterGenerationResultDTO::failure($validation['errors']);
            }
            
            // Save if requested
            if ($dto->updateDatabase) {
                $newsletter = $this->newsletterRepository->save($newsletter);
                
                // Update news items with newsletter association
                $this->newsRepository->updateNewsletterAssociation(
                    $dto->newsIds,
                    $newsletter->getNumber()
                );
            }
            
            // Get statistics
            $statistics = $this->contentService->getContentStatistics($newsletter);
            
            return NewsletterGenerationResultDTO::success(
                html: $html,
                newsletterId: $newsletter->getId() ?? 0,
                statistics: $statistics
            );
            
        } catch (\Exception $e) {
            debugLog('Newsletter generation error: ' . $e->getMessage(), 'error');
            
            return NewsletterGenerationResultDTO::failure([
                'Newsletter generation failed: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get existing newsletter or create new one
     *
     * @param NewsletterGenerationDTO $dto
     * @return Newsletter
     * @throws \RuntimeException
     */
    private function getOrCreateNewsletter(NewsletterGenerationDTO $dto): Newsletter
    {
        // Check if newsletter already exists
        $existing = $this->newsletterRepository->findByNumber($dto->newsletterNumber);
        
        if ($existing) {
            // Check if it can be edited
            if (!$existing->canEdit()) {
                throw new \RuntimeException(sprintf(
                    'Newsletter #%d cannot be edited in its current status: %s',
                    $existing->getNumber(),
                    $existing->getStatus()->label()
                ));
            }
            
            // Update header text
            $existing = new Newsletter(
                $existing->getNumber(),
                $dto->headerText,
                $dto->newsIds
            );
            $existing->setId($existing->getId());
            
            return $existing;
        }
        
        // Create new newsletter
        return new Newsletter(
            $dto->newsletterNumber,
            $dto->headerText,
            $dto->newsIds
        );
    }
}
