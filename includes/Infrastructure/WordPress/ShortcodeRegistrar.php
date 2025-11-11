<?php

declare(strict_types=1);

/**
 * Shortcode Registrar
 *
 * @package RIILSA\Infrastructure\WordPress
 * @since 3.1.0
 */

namespace RIILSA\Infrastructure\WordPress;

use RIILSA\Core\Container;
use RIILSA\Infrastructure\Services\PhpSpreadsheetExcelService;
use RIILSA\Domain\Repositories\NewsletterRepositoryInterface;
use RIILSA\Domain\Repositories\NewsRepositoryInterface;

/**
 * Shortcode registration handler
 * 
 * Pattern: Registry Pattern
 * This class handles registration of custom shortcodes
 */
class ShortcodeRegistrar
{
    /**
     * Dependency injection container
     *
     * @var Container
     */
    private Container $container;
    
    /**
     * Constructor
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    
    /**
     * Register all custom shortcodes
     *
     * @return void
     */
    public function register(): void
    {
        add_shortcode('downloadLastExcel', [$this, 'downloadLastExcel']);
        add_shortcode('getLastNumberBoletin', [$this, 'getLastNewsletterNumber']);
        add_shortcode('getRecentNews', [$this, 'getRecentNews']);
    }
    
    /**
     * Shortcode: Download last Excel file
     *
     * @param array $atts Shortcode attributes
     * @return string URL to the file or '#' if not found
     */
    public function downloadLastExcel(array $atts = []): string
    {
        $atts = shortcode_atts([
            'type' => ''
        ], $atts, 'downloadLastExcel');
        
        $type = sanitize_text_field($atts['type']);
        $validTypes = ['Convocatorias', 'Proyectos', 'Noticias'];
        
        if (!in_array($type, $validTypes)) {
            return '#';
        }
        
        try {
            $excelService = $this->container->get(PhpSpreadsheetExcelService::class);
            $fileUrl = $excelService->getLastUploadedFile($type);
            
            return $fileUrl ?? '#';
            
        } catch (\Exception $e) {
            debugLog('Error getting last Excel file: ' . $e->getMessage(), 'error');
            return '#';
        }
    }
    
    /**
     * Shortcode: Get last newsletter number
     *
     * @param array $atts Shortcode attributes
     * @return int Last newsletter number
     */
    public function getLastNewsletterNumber(array $atts = []): int
    {
        try {
            $newsletterRepo = $this->container->get(NewsletterRepositoryInterface::class);
            return $newsletterRepo->getLastNewsletterNumber();
            
        } catch (\Exception $e) {
            debugLog('Error getting last newsletter number: ' . $e->getMessage(), 'error');
            return 0;
        }
    }
    
    /**
     * Shortcode: Get recent news
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function getRecentNews(array $atts = []): string
    {
        $atts = shortcode_atts([
            'count' => 10,
            'show_excerpt' => true,
        ], $atts, 'getRecentNews');
        
        try {
            $newsRepo = $this->container->get(NewsRepositoryInterface::class);
            $newsItems = $newsRepo->findRecent((int)$atts['count']);
            
            if (empty($newsItems)) {
                return '<p>' . __('No recent news available.', 'riilsa') . '</p>';
            }
            
            // Enqueue Elementor styles if available
            if (did_action('elementor/loaded')) {
                \Elementor\Plugin::instance()->frontend->enqueue_styles();
                wp_enqueue_style('elementor-frontend');
                wp_enqueue_style('elementor-global');
            }
            
            ob_start();
            
            foreach ($newsItems as $news) {
                // Use Elementor template if available
                if (defined('RIILSA_TEMPLATE_LAST_NEWS_ID') && did_action('elementor/loaded')) {
                    echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display(
                        RIILSA_TEMPLATE_LAST_NEWS_ID,
                        true
                    );
                    echo '<hr>';
                } else {
                    // Fallback to simple HTML
                    $this->renderNewsItem($news, $atts);
                }
            }
            
            return ob_get_clean();
            
        } catch (\Exception $e) {
            debugLog('Error getting recent news: ' . $e->getMessage(), 'error');
            return '<p>' . __('Error loading news.', 'riilsa') . '</p>';
        }
    }
    
    /**
     * Render a news item
     *
     * @param \RIILSA\Domain\Entities\News $news
     * @param array $atts
     * @return void
     */
    private function renderNewsItem(\RIILSA\Domain\Entities\News $news, array $atts): void
    {
        ?>
        <article class="riilsa-news-item">
            <h3>
                <a href="<?php echo esc_url($news->getUrl()); ?>">
                    <?php echo esc_html($news->getTitle()); ?>
                </a>
            </h3>
            
            <?php if ($news->getFeaturedImageUrl()): ?>
                <div class="riilsa-news-image">
                    <img src="<?php echo esc_url($news->getFeaturedImageUrl()); ?>" 
                         alt="<?php echo esc_attr($news->getTitle()); ?>">
                </div>
            <?php endif; ?>
            
            <?php if ($atts['show_excerpt']): ?>
                <div class="riilsa-news-excerpt">
                    <?php echo wp_kses_post($news->getExcerpt()); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($news->getResearchLine()): ?>
                <div class="riilsa-news-meta">
                    <span class="research-line"><?php echo esc_html($news->getResearchLine()); ?></span>
                </div>
            <?php endif; ?>
        </article>
        <?php
    }
}
