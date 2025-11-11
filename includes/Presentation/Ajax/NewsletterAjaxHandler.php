<?php

declare(strict_types=1);

/**
 * Newsletter AJAX Handler
 *
 * @package RIILSA\Presentation\Ajax
 * @since 3.1.0
 */

namespace RIILSA\Presentation\Ajax;

use RIILSA\Application\UseCases\Newsletter\GenerateNewsletterUseCase;
use RIILSA\Application\UseCases\Newsletter\SendNewsletterUseCase;
use RIILSA\Application\DTOs\NewsletterGenerationDTO;
use RIILSA\Application\DTOs\NewsletterSendDTO;
use RIILSA\Domain\Repositories\NewsletterRepositoryInterface;
use RIILSA\Domain\Repositories\SubscriberRepositoryInterface;

/**
 * AJAX handler for newsletter operations
 * 
 * Pattern: Handler Pattern
 * This class handles AJAX requests for newsletter management
 */
class NewsletterAjaxHandler
{
    /**
     * Constructor
     */
    public function __construct(
        private readonly GenerateNewsletterUseCase $generateNewsletterUseCase,
        private readonly SendNewsletterUseCase $sendNewsletterUseCase,
        private readonly NewsletterRepositoryInterface $newsletterRepository,
        private readonly SubscriberRepositoryInterface $subscriberRepository
    ) {
    }
    
    /**
     * Handle generate newsletter AJAX request
     *
     * @return void
     */
    public function handleGenerateNewsletter(): void
    {
        try {
            // Validate request
            $this->validateAjaxRequest();
            
            // Parse request data
            $requestData = $_POST['data'] ?? [];
            
            if (!is_array($requestData)) {
                throw new \InvalidArgumentException('Invalid request data');
            }
            
            // Create DTO from request
            $dto = NewsletterGenerationDTO::fromRequest($requestData);
            
            // Execute use case
            $result = $this->generateNewsletterUseCase->execute($dto);
            
            // Send response
            if ($result->isSuccessful()) {
                wp_send_json_success([
                    'html' => $result->html,
                    'newsletterId' => $result->newsletterId,
                    'statistics' => $result->statistics,
                ]);
            } else {
                wp_send_json_error([
                    'message' => $result->getErrorMessage(),
                    'errors' => $result->errors,
                ]);
            }
            
        } catch (\InvalidArgumentException $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        } catch (\Exception $e) {
            debugLog('Generate newsletter AJAX error: ' . $e->getMessage(), 'error');
            wp_send_json_error(['message' => 'An error occurred while generating the newsletter']);
        }
        
        wp_die();
    }
    
    /**
     * Handle send newsletter AJAX request
     *
     * @return void
     */
    public function handleSendNewsletter(): void
    {
        try {
            // Validate request
            $this->validateAjaxRequest();
            
            // Parse request data
            $requestData = $_POST['data'] ?? [];
            
            if (!is_array($requestData)) {
                throw new \InvalidArgumentException('Invalid request data');
            }
            
            // Create DTO from request
            $dto = NewsletterSendDTO::fromRequest($requestData);
            
            // Execute use case
            $result = $this->sendNewsletterUseCase->execute($dto);
            
            // Send response
            if ($result->success) {
                wp_send_json_success([
                    'recipientCount' => $result->recipientCount,
                    'sentCount' => $result->sentCount,
                    'statistics' => $result->statistics,
                ]);
            } else {
                wp_send_json_error([
                    'message' => implode(', ', $result->errors),
                    'errors' => $result->errors,
                ]);
            }
            
        } catch (\InvalidArgumentException $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        } catch (\Exception $e) {
            debugLog('Send newsletter AJAX error: ' . $e->getMessage(), 'error');
            wp_send_json_error(['message' => 'An error occurred while sending the newsletter']);
        }
        
        wp_die();
    }
    
    /**
     * Handle get history AJAX request
     *
     * @return void
     */
    public function handleGetHistory(): void
    {
        try {
            // Get all newsletters
            $newsletters = $this->newsletterRepository->findAll();
            
            if (empty($newsletters)) {
                echo '<div class="gridHistory">';
                echo '<p>' . __('No newsletters found.', 'riilsa') . '</p>';
                echo '</div>';
                wp_die();
            }
            
            // Render history grid
            echo '<div class="gridHistory">';
            
            foreach ($newsletters as $newsletter) {
                $this->renderNewsletterHistoryItem($newsletter);
            }
            
            echo '</div>';
            
        } catch (\Exception $e) {
            debugLog('Get history AJAX error: ' . $e->getMessage(), 'error');
            echo '<p>' . __('Error loading newsletter history.', 'riilsa') . '</p>';
        }
        
        wp_die();
    }
    
    /**
     * Handle control emails AJAX request
     *
     * @return void
     */
    public function handleControlEmails(): void
    {
        try {
            $action = $_POST['emailAction'] ?? '';
            
            switch ($action) {
                case 'list':
                    $this->listSubscribers();
                    break;
                    
                case 'add':
                    $this->addSubscriber();
                    break;
                    
                case 'remove':
                    $this->removeSubscriber();
                    break;
                    
                default:
                    wp_send_json_error(['message' => 'Invalid email action']);
            }
            
        } catch (\Exception $e) {
            debugLog('Control emails AJAX error: ' . $e->getMessage(), 'error');
            wp_send_json_error(['message' => 'Error managing emails']);
        }
        
        wp_die();
    }
    
    /**
     * Handle control dependencies AJAX request
     *
     * @return void
     */
    public function handleControlDependencies(): void
    {
        try {
            $action = $_POST['dependencyAction'] ?? '';
            
            switch ($action) {
                case 'list':
                    $this->listDependencies();
                    break;
                    
                case 'add':
                    $this->addDependency();
                    break;
                    
                case 'remove':
                    $this->removeDependency();
                    break;
                    
                default:
                    wp_send_json_error(['message' => 'Invalid dependency action']);
            }
            
        } catch (\Exception $e) {
            debugLog('Control dependencies AJAX error: ' . $e->getMessage(), 'error');
            wp_send_json_error(['message' => 'Error managing dependencies']);
        }
        
        wp_die();
    }
    
    /**
     * Validate AJAX request
     *
     * @return void
     * @throws \Exception
     */
    private function validateAjaxRequest(): void
    {
        // Check if it's an AJAX request
        if (!wp_doing_ajax()) {
            throw new \Exception('Not an AJAX request');
        }
        
        // Check user capability
        if (!current_user_can('edit_posts')) {
            throw new \Exception('Insufficient permissions');
        }
        
        // Verify nonce if present
        if (isset($_POST['nonce'])) {
            if (!wp_verify_nonce($_POST['nonce'], 'riilsa_newsletter_actions')) {
                throw new \Exception('Invalid security token');
            }
        }
    }
    
    /**
     * Render newsletter history item
     *
     * @param \RIILSA\Domain\Entities\Newsletter $newsletter
     * @return void
     */
    private function renderNewsletterHistoryItem(\RIILSA\Domain\Entities\Newsletter $newsletter): void
    {
        $newsCount = count($newsletter->getNewsIds());
        $status = $newsletter->getStatus()->label();
        $statusColor = $newsletter->getStatus()->color();
        $number = $newsletter->getNumber();
        $id = $newsletter->getId();
        $dateCreated = $newsletter->getCreatedAt()->format('Y-m-d H:i:s');
        $newsIds = implode(',', $newsletter->getNewsIds());
        $headerText = esc_attr($newsletter->getHeaderText());
        
        ?>
        <div class="containerHistory">
            <div class="topHistory">
                <h2 style="font-size: 24px; color: #fff;" class="numberHistory">
                    #<?php echo $number; ?>
                </h2>
                <div>
                    <p style="font-size: 18px; margin: 0; color:rgb(201, 201, 201);">
                        ID: <?php echo $id; ?>
                    </p>
                    <p style="font-size: 18px; margin: 0; color:rgb(201, 201, 201);">
                        <?php echo $dateCreated; ?>
                    </p>
                </div>
            </div>
            <div>
                <h2 style="font-size: 20px; color: <?php echo esc_attr($statusColor); ?>;">
                    Status: <?php echo esc_html($status); ?>
                </h2>
            </div>
            <div>
                <h2 style="font-size: 20px; color: #fff;">
                    <?php printf(__('News: %d', 'riilsa'), $newsCount); ?>
                </h2>
            </div>
            <div data-id="<?php echo esc_attr($newsIds); ?>" 
                 data-text="<?php echo $headerText; ?>" 
                 data-newsletter="<?php echo $number; ?>">
                <button class="btnHistory doBoletin" style="color: #fff; margin-bottom: 10px;">
                    <span class="elementor-button-text"><?php _e('View Newsletter', 'riilsa'); ?></span>
                </button>
                <button class="btnHistory sendBoletin" style="color: #fff; margin-bottom: 10px;">
                    <span class="elementor-button-text"><?php _e('Send Newsletter', 'riilsa'); ?></span>
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * List subscribers
     *
     * @return void
     */
    private function listSubscribers(): void
    {
        $subscribers = $this->subscriberRepository->findAll();
        wp_send_json_success(['subscribers' => array_map(fn($s) => $s->toArray(), $subscribers)]);
    }
    
    /**
     * Add subscriber
     *
     * @return void
     */
    private function addSubscriber(): void
    {
        // Implementation would use SubscribeUserUseCase
        wp_send_json_error(['message' => 'Not implemented yet']);
    }
    
    /**
     * Remove subscriber
     *
     * @return void
     */
    private function removeSubscriber(): void
    {
        // Implementation would delete subscriber
        wp_send_json_error(['message' => 'Not implemented yet']);
    }
    
    /**
     * List dependencies
     *
     * @return void
     */
    private function listDependencies(): void
    {
        global $wpdb;
        $dependencies = $wpdb->get_results(
            "SELECT * FROM " . RIILSA_TABLE_DEPENDENCY_CATALOG,
            ARRAY_A
        );
        wp_send_json_success(['dependencies' => $dependencies]);
    }
    
    /**
     * Add dependency
     *
     * @return void
     */
    private function addDependency(): void
    {
        $description = $_POST['description'] ?? '';
        
        if (empty($description)) {
            wp_send_json_error(['message' => 'Description is required']);
        }
        
        global $wpdb;
        $result = $wpdb->insert(
            RIILSA_TABLE_DEPENDENCY_CATALOG,
            ['description' => sanitize_text_field($description)]
        );
        
        if ($result) {
            wp_send_json_success(['id' => $wpdb->insert_id]);
        } else {
            wp_send_json_error(['message' => 'Failed to add dependency']);
        }
    }
    
    /**
     * Remove dependency
     *
     * @return void
     */
    private function removeDependency(): void
    {
        $id = $_POST['id'] ?? 0;
        
        if (!$id) {
            wp_send_json_error(['message' => 'ID is required']);
        }
        
        global $wpdb;
        $result = $wpdb->delete(
            RIILSA_TABLE_DEPENDENCY_CATALOG,
            ['id' => (int)$id]
        );
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error(['message' => 'Failed to remove dependency']);
        }
    }
}
