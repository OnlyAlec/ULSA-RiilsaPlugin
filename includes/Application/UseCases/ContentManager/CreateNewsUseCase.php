<?php

declare(strict_types=1);

/**
 * Create News Use Case
 *
 * @package RIILSA\Application\UseCases\ContentManager
 * @since 3.1.0
 */

namespace RIILSA\Application\UseCases\ContentManager;

use RIILSA\Domain\Entities\News;
use RIILSA\Domain\Repositories\NewsRepositoryInterface;
use RIILSA\Domain\ValueObjects\PostStatus;

/**
 * Use case for creating a news item
 * 
 * Pattern: Use Case Pattern
 * This class handles the creation of a news item from Excel data
 */
class CreateNewsUseCase
{
    /**
     * Constructor
     */
    public function __construct(
        private readonly NewsRepositoryInterface $newsRepository
    ) {
    }
    
    /**
     * Execute the use case
     *
     * @param array $data Excel row data
     * @param array $options Additional options
     * @return News
     * @throws \Exception
     */
    public function execute(array $data, array $options = []): News
    {
        try {
            // Create news entity from Excel data
            $news = News::createFromExcel($data);
            
            // Set post status (default to pending)
            $postStatus = $options['postStatus'] ?? PostStatus::PENDING;
            $news->updatePostStatus($postStatus);
            
            // Determine position if not set
            if (empty($news->getPosition()) && isset($options['autoPosition'])) {
                $position = $this->determinePosition($news);
                $news->setPosition($position);
            }
            
            // Save to repository
            $savedNews = $this->newsRepository->save($news);
            
            // Handle featured image if present
            if (!empty($data['imagen'])) {
                $this->handleFeaturedImage($savedNews, $data['imagen']);
            }
            
            // Handle taxonomy terms
            $this->handleTaxonomies($savedNews);
            
            // Log successful creation
            debugLog(sprintf(
                'News created: ID=%d, Title="%s", Newsletter=%s',
                $savedNews->getId(),
                $savedNews->getTitle(),
                $savedNews->getNewsletterNumber() ?? 'none'
            ), 'info');
            
            return $savedNews;
            
        } catch (\Exception $e) {
            debugLog('Failed to create news: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * Determine news position based on content
     *
     * @param News $news
     * @return string
     */
    private function determinePosition(News $news): string
    {
        // If it has an image and substantial content, it could be highlight
        if ($news->getFeaturedImageUrl() && strlen($news->getContent()) > 500) {
            return 'highlight';
        }
        
        // If it has bullets or is shorter, it's good for grid
        if (!empty($news->getBullets()) || strlen($news->getContent()) < 200) {
            return 'grid';
        }
        
        // Default to normal
        return 'normal';
    }
    
    /**
     * Handle featured image processing
     *
     * @param News $news
     * @param string $imageUrl
     * @return void
     */
    private function handleFeaturedImage(News $news, string $imageUrl): void
    {
        try {
            $imageUrl = $this->processGoogleDriveUrl($imageUrl);
            
            if (!$imageUrl) {
                return;
            }
            
            // Download and attach image to post
            $attachmentId = $this->downloadAndAttachImage(
                $imageUrl,
                $news->getId(),
                $news->getTitle()
            );
            
            if ($attachmentId && !is_wp_error($attachmentId)) {
                set_post_thumbnail($news->getId(), $attachmentId);
                debugLog("Featured image set for news {$news->getId()}", 'info');
            }
            
        } catch (\Exception $e) {
            debugLog("Failed to set featured image: " . $e->getMessage(), 'warning');
            // Don't fail the whole process for image issues
        }
    }
    
    /**
     * Process Google Drive URL
     *
     * @param string $url
     * @return string|null
     */
    private function processGoogleDriveUrl(string $url): ?string
    {
        $url = trim($url);
        
        if (empty($url)) {
            return null;
        }
        
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        
        // Check if it's a Google Drive URL
        if (preg_match(RIILSA_REGEX_GOOGLE_DRIVE, $url, $matches)) {
            $fileId = $matches[1] ?? null;
            if ($fileId) {
                return RIILSA_URL_GOOGLE_DRIVE_DOWNLOAD . $fileId;
            }
        }
        
        // Return original URL if not Google Drive
        return $url;
    }
    
    /**
     * Download and attach image to post
     *
     * @param string $imageUrl
     * @param int $postId
     * @param string $postTitle
     * @return int|false Attachment ID or false on failure
     */
    private function downloadAndAttachImage(string $imageUrl, int $postId, string $postTitle): int|false
    {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Use WordPress function to handle the download
        $attachmentId = media_sideload_image($imageUrl, $postId, $postTitle, 'id');
        
        if (is_wp_error($attachmentId)) {
            debugLog('Image download error: ' . $attachmentId->get_error_message(), 'error');
            return false;
        }
        
        return $attachmentId;
    }
    
    /**
     * Handle taxonomy assignments
     *
     * @param News $news
     * @return void
     */
    private function handleTaxonomies(News $news): void
    {
        // Handle research line (LGAC)
        if (!empty($news->getResearchLine())) {
            $this->assignTaxonomyTerm(
                $news->getId(),
                $news->getResearchLine(),
                RIILSA_TAXONOMY_AREA
            );
        }
        
        // Handle newsletter taxonomy if assigned
        if ($news->getNewsletterNumber()) {
            $newsletterTerm = sprintf('Boletín %d', $news->getNewsletterNumber());
            $this->assignNewsletterTerm(
                $news->getId(),
                $newsletterTerm,
                $news->getNewsletterNumber()
            );
        }
    }
    
    /**
     * Assign taxonomy term to post
     *
     * @param int $postId
     * @param string $termName
     * @param string $taxonomy
     * @return void
     */
    private function assignTaxonomyTerm(int $postId, string $termName, string $taxonomy): void
    {
        $term = get_term_by('name', $termName, $taxonomy);
        
        if (!$term) {
            // Create term if it doesn't exist
            $termData = wp_insert_term($termName, $taxonomy);
            
            if (is_wp_error($termData)) {
                debugLog("Failed to create term '{$termName}': " . $termData->get_error_message(), 'warning');
                return;
            }
            
            $termId = $termData['term_id'];
        } else {
            $termId = $term->term_id;
        }
        
        // Assign term to post
        $result = wp_set_post_terms($postId, [$termId], $taxonomy);
        
        if (is_wp_error($result)) {
            debugLog("Failed to assign term '{$termName}' to post {$postId}: " . $result->get_error_message(), 'warning');
        }
    }
    
    /**
     * Assign newsletter term to post
     *
     * @param int $postId
     * @param string $termName
     * @param int $newsletterNumber
     * @return void
     */
    private function assignNewsletterTerm(int $postId, string $termName, int $newsletterNumber): void
    {
        $term = get_term_by('name', $termName, RIILSA_TAXONOMY_NEWSLETTER);
        
        if (!$term) {
            // Create term under parent
            $termData = wp_insert_term($termName, RIILSA_TAXONOMY_NEWSLETTER, [
                'parent' => RIILSA_NEWSLETTER_PARENT_ID,
                'slug' => 'boletin-' . $newsletterNumber,
            ]);
            
            if (is_wp_error($termData)) {
                debugLog("Failed to create newsletter term '{$termName}': " . $termData->get_error_message(), 'warning');
                return;
            }
            
            $termId = $termData['term_id'];
            
            // Add newsletter number as term meta
            update_term_meta($termId, 'newsletter_number', $newsletterNumber);
        } else {
            $termId = $term->term_id;
        }
        
        // Assign term to post
        $result = wp_set_post_terms($postId, [$termId], RIILSA_TAXONOMY_NEWSLETTER);
        
        if (is_wp_error($result)) {
            debugLog("Failed to assign newsletter term to post {$postId}: " . $result->get_error_message(), 'warning');
        }
    }
}
