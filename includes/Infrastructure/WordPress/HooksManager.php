<?php

declare(strict_types=1);

/**
 * WordPress Hooks Manager
 *
 * @package RIILSA\Infrastructure\WordPress
 * @since 3.1.0
 */

namespace RIILSA\Infrastructure\WordPress;

use function RIILSA\Core\debugLog;

/**
 * Centralized hooks registration
 * 
 * Pattern: Manager Pattern
 * This class centralizes all WordPress hook registrations
 */
class HooksManager
{
    /**
     * Register all hooks
     *
     * @return void
     */
    public function register(): void
    {
        // Cron schedules
        add_filter('cron_schedules', [$this, 'addCronSchedules']);

        // Schedule cleanup events
        if (!wp_next_scheduled('riilsa_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'riilsa_daily_cleanup');
        }

        if (!wp_next_scheduled('riilsa_update_expired_statuses')) {
            wp_schedule_event(time(), 'daily', 'riilsa_update_expired_statuses');
        }

        // Register cron actions
        add_action('riilsa_daily_cleanup', [$this, 'dailyCleanup']);
        add_action('riilsa_update_expired_statuses', [$this, 'updateExpiredStatuses']);
        add_action('riilsa_update_call_status', [$this, 'updateCallStatus']);

        // Content filters
        add_filter('the_content', [$this, 'filterContent'], 10, 1);

        // Admin hooks
        if (is_admin()) {
            $this->registerAdminHooks();
        }
    }

    /**
     * Add custom cron schedules
     *
     * @param array $schedules
     * @return array
     */
    public function addCronSchedules(array $schedules): array
    {
        $schedules['weekly'] = [
            'interval' => WEEK_IN_SECONDS,
            'display' => __('Once Weekly', 'riilsa')
        ];

        return $schedules;
    }

    /**
     * Daily cleanup task
     *
     * @return void
     */
    public function dailyCleanup(): void
    {
        try {
            $container = \RIILSA\Core\Container::getInstance();

            // Clean up expired tokens
            $subscriberRepo = $container->get(\RIILSA\Domain\Repositories\SubscriberRepositoryInterface::class);

            // Get Brevo service
            $brevoService = null;
            if ($container->has(\RIILSA\Infrastructure\Services\BrevoMailService::class)) {
                $brevoService = $container->get(\RIILSA\Infrastructure\Services\BrevoMailService::class);
            }

            // Find expired subscribers
            $expiredSubscribers = $subscriberRepo->findWithExpiredTokens();
            $deleted = 0;

            foreach ($expiredSubscribers as $subscriber) {
                // Remove from Brevo if service is available
                if ($brevoService) {
                    try {
                        $brevoService->deleteContact((string) $subscriber->getEmail());
                    } catch (\Exception $e) {
                        debugLog('Failed to delete expired Brevo contact: ' . $e->getMessage(), 'warning');
                    }
                }

                if ($subscriberRepo->delete($subscriber)) {
                    $deleted++;
                }
            }

            debugLog("Daily cleanup: Deleted {$deleted} expired pending subscribers", 'info');

            // Clean up old temporary files
            $this->cleanupTempFiles();

        } catch (\Exception $e) {
            debugLog('Daily cleanup error: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Update expired project and call statuses
     *
     * @return void
     */
    public function updateExpiredStatuses(): void
    {
        try {
            $container = \RIILSA\Core\Container::getInstance();

            // Update projects
            $projectRepo = $container->get(\RIILSA\Domain\Repositories\ProjectRepositoryInterface::class);
            $projectsUpdated = $projectRepo->updateExpiredStatuses();

            // Update calls
            $callRepo = $container->get(\RIILSA\Domain\Repositories\CallRepositoryInterface::class);
            $callsUpdated = $callRepo->updateExpiredStatuses();

            debugLog(
                "Updated expired statuses: {$projectsUpdated} projects, {$callsUpdated} calls",
                'info'
            );

        } catch (\Exception $e) {
            debugLog('Update expired statuses error: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Update specific call status
     *
     * @param int $callId
     * @return void
     */
    public function updateCallStatus(int $callId): void
    {
        try {
            $container = \RIILSA\Core\Container::getInstance();
            $callRepo = $container->get(\RIILSA\Domain\Repositories\CallRepositoryInterface::class);

            $call = $callRepo->findById($callId);

            if ($call && $call->isOpen()) {
                $call->updateCallStatus();

                if ($call->isClosed()) {
                    $callRepo->save($call);
                    debugLog("Updated call status for call ID: {$callId}", 'info');
                }
            }

        } catch (\Exception $e) {
            debugLog('Update call status error: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Clean up temporary files
     *
     * @return void
     */
    private function cleanupTempFiles(): void
    {
        $uploadDir = wp_upload_dir();
        $cachePath = $uploadDir['basedir'] . '/riilsa-cache';

        if (!is_dir($cachePath)) {
            return;
        }

        // Delete files older than 7 days
        $cutoffTime = time() - (7 * DAY_IN_SECONDS);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cachePath)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getMTime() < $cutoffTime) {
                @unlink($file->getPathname());
            }
        }
    }

    /**
     * Filter post content
     *
     * @param string $content
     * @return string
     */
    public function filterContent(string $content): string
    {
        // Add any content filters here if needed
        return $content;
    }

    /**
     * Register admin-specific hooks
     *
     * @return void
     */
    private function registerAdminHooks(): void
    {
        // Add admin menu items if needed
        // add_action('admin_menu', [$this, 'registerAdminMenu']);

        // Add admin notices
        add_action('admin_notices', [$this, 'showAdminNotices']);
    }

    /**
     * Show admin notices
     *
     * @return void
     */
    public function showAdminNotices(): void
    {
        // Check for required plugins
        if (!defined('CBXPHPSPREADSHEET_PLUGIN_NAME')) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>RIILSA Plugin:</strong> ' .
                __('PhpSpreadsheet plugin is recommended for Excel processing functionality.', 'riilsa') .
                '</p>';
            echo '</div>';
        }
    }
}
