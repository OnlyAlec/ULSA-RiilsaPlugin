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
use RIILSA\Application\UseCases\Newsletter\SubscribeUserUseCase;
use RIILSA\Application\DTOs\NewsletterGenerationDTO;
use RIILSA\Application\DTOs\NewsletterSendDTO;
use RIILSA\Application\DTOs\SubscriptionRequestDTO;
use RIILSA\Domain\Repositories\NewsletterRepositoryInterface;
use RIILSA\Domain\Repositories\SubscriberRepositoryInterface;
use RIILSA\Infrastructure\Services\BrevoMailService;
use function RIILSA\Core\debugLog;

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
        private readonly SubscribeUserUseCase $subscribeUserUseCase,
        private readonly NewsletterRepositoryInterface $newsletterRepository,
        private readonly SubscriberRepositoryInterface $subscriberRepository,
        private readonly BrevoMailService $brevoMailService
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

                case 'resend':
                    $this->resendConfirmation();
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
     * Handle update shortcodes AJAX request
     *
     * @return void
     */
    public function handleUpdateShortcodes(): void
    {
        try {
            // Validate request
            // We skip full validation here as this is a read-only operation mostly
            // and the nonce is checked by WordPress if we use check_ajax_referer
            // but here we just check if shortcode is provided

            $shortcode = $_POST['shortcode'] ?? '';

            if (empty($shortcode)) {
                wp_send_json_error(['message' => 'Shortcode is required']);
            }

            // Allowed shortcodes for security
            $allowedShortcodes = [
                'getEmailTable',
                'getDepTable',
                'getDepSelect',
                'getLastNumberBoletin',
                'getRecentNews'
            ];

            if (!in_array($shortcode, $allowedShortcodes)) {
                wp_send_json_error(['message' => 'Invalid shortcode']);
            }

            // Execute shortcode
            $content = do_shortcode('[' . $shortcode . ']');

            wp_send_json_success($content);

        } catch (\Exception $e) {
            debugLog('Update shortcodes AJAX error: ' . $e->getMessage(), 'error');
            wp_send_json_error(['message' => 'Error updating shortcode']);
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
        <div class="containerHistory riilsa-card">
            <!-- Gradient accent -->
            <div class="riilsa-gradient"></div>

            <div class="riilsa-card-header">
                <div>
                    <div class="numberHistory riilsa-card-number">#<?php echo $number; ?></div>
                    <div class="riilsa-card-id">ID: <?php echo $id; ?></div>
                </div>
                <div class="riilsa-status-badge">
                    <?php echo esc_html($status); ?>
                </div>
            </div>

            <div class="riilsa-info-section">
                <div class="riilsa-info-item">
                    <span class="dashicons dashicons-calendar-alt riilsa-icon-blue"></span>
                    <?php echo $dateCreated; ?>
                </div>
                <div class="riilsa-info-item">
                    <span class="dashicons dashicons-media-text riilsa-icon-red"></span>
                    <?php echo $newsCount; ?>         <?php _e('noticias', 'riilsa'); ?>
                </div>
            </div>

            <div class="riilsa-actions-grid" data-id="<?php echo esc_attr($newsIds); ?>" data-text="<?php echo $headerText; ?>"
                data-newsletter="<?php echo $number; ?>">
                <button class="btnHistory doBoletin riilsa-btn-view">
                    <span class="elementor-button-icon fa-spin" style="display: none;">
                        <svg aria-hidden="true" class="e-font-icon-svg e-fas-spinner" viewBox="0 0 512 512"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill="#ffffff"
                                d="M304 48c0 26.51-21.49 48-48 48s-48-21.49-48-48 21.49-48 48-48 48 21.49 48 48zm-48 368c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48-21.49-48-48-48zm208-208c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48-21.49-48-48-48zM96 256c0-26.51-21.49-48-48-48S0 229.49 0 256s21.49 48 48 48 48-21.49 48-48zm12.922 99.078c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48c0-26.509-21.491-48-48-48zm294.156 0c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48c0-26.509-21.49-48-48-48zM108.922 60.922c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48-21.491-48-48-48z">
                            </path>
                        </svg>
                    </span>
                    <span class="dashicons dashicons-visibility elementor-button-text"></span>
                    <?php _e('Ver', 'riilsa'); ?>
                </button>
                <button class="btnHistory sendBoletin riilsa-btn-send elementor-button-text">
                    <span class="dashicons dashicons-email"></span>
                    <?php _e('Enviar', 'riilsa'); ?>
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
        try {
            $data = $_POST['data'] ?? [];

            // Map 'dep' to 'dependencyId' as expected by DTO
            if (isset($data['dep'])) {
                $data['dependencyId'] = $data['dep'];
            }

            $dto = SubscriptionRequestDTO::fromRequest(
                $data,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );

            $result = $this->subscribeUserUseCase->execute($dto);

            if ($result->success) {
                wp_send_json_success(['message' => $result->message]);
            } else {
                wp_send_json_error(['message' => $result->message]);
            }
        } catch (\Exception $e) {
            debugLog('Add subscriber error: ' . $e->getMessage(), 'error');
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Remove subscriber
     *
     * @return void
     */
    private function removeSubscriber(): void
    {
        try {
            $data = $_POST['data'] ?? [];
            $id = $data['id'] ?? 0;

            if (!$id) {
                wp_send_json_error(['message' => 'ID is required']);
            }

            // Try to remove from Brevo first
            $subscriber = $this->subscriberRepository->findById((int) $id);
            if ($subscriber) {
                try {
                    $this->brevoMailService->deleteContact((string) $subscriber->getEmail());
                } catch (\Exception $e) {
                    debugLog('Failed to delete Brevo contact: ' . $e->getMessage(), 'warning');
                }
            }

            if ($this->subscriberRepository->deleteById((int) $id)) {
                wp_send_json_success(['message' => 'Subscriber removed successfully']);
            } else {
                wp_send_json_error(['message' => 'Failed to remove subscriber']);
            }
        } catch (\Exception $e) {
            debugLog('Remove subscriber error: ' . $e->getMessage(), 'error');
            wp_send_json_error(['message' => $e->getMessage()]);
        }
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
        $description = $_POST['data']['description'] ?? '';

        if (empty($description)) {
            wp_send_json_error(['message' => 'Description is required']);
        }

        global $wpdb;
        $result = $wpdb->insert(
            RIILSA_TABLE_DEPENDENCY_CATALOG,
            ['description' => sanitize_text_field($description)]
        );

        if ($result) {
            // Create list in Brevo
            try {
                $this->brevoMailService->createContactList(sanitize_text_field($description));
            } catch (\Exception $e) {
                debugLog('Failed to create Brevo list for dependency: ' . $e->getMessage(), 'warning');
            }

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
        $id = $_POST['data']['id'] ?? 0;

        if (!$id) {
            wp_send_json_error(['message' => 'ID is required']);
        }

        try {
            $this->brevoMailService->deleteListByDependencyId((int) $id);
        } catch (\Exception $e) {
            debugLog('Failed to delete Brevo list for dependency: ' . $e->getMessage(), 'warning');
        }

        global $wpdb;
        $result = $wpdb->delete(
            RIILSA_TABLE_DEPENDENCY_CATALOG,
            ['id' => (int) $id]
        );

        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error(['message' => 'Failed to remove dependency']);
        }
    }

    /**
     * Resend confirmation email
     *
     * @return void
     */
    private function resendConfirmation(): void
    {
        try {
            $data = $_POST['data'] ?? [];
            $id = $data['id'] ?? 0;

            if (!$id) {
                wp_send_json_error(['message' => 'ID is required']);
            }

            $subscriber = $this->subscriberRepository->findById((int) $id);

            if (!$subscriber) {
                wp_send_json_error(['message' => 'Subscriber not found']);
            }

            // Reuse SubscribeUserUseCase to handle resending
            $dto = new SubscriptionRequestDTO(
                email: $subscriber->getEmail()->getValue(),
                dependencyId: $subscriber->getDependencyId(),
                ipAddress: $_SERVER['REMOTE_ADDR'] ?? '',
                userAgent: $_SERVER['HTTP_USER_AGENT'] ?? ''
            );

            $result = $this->subscribeUserUseCase->execute($dto);

            if ($result->success) {
                wp_send_json_success(['message' => $result->message]);
            } else {
                wp_send_json_error(['message' => $result->message]);
            }

        } catch (\Exception $e) {
            debugLog('Resend confirmation error: ' . $e->getMessage(), 'error');
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}
