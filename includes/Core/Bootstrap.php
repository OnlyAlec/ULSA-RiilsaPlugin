<?php

declare(strict_types=1);

/**
 * Plugin Bootstrap
 *
 * @package RIILSA\Core
 * @since 3.1.0
 */

namespace RIILSA\Core;

use RIILSA\Infrastructure\WordPress\HooksManager;
use RIILSA\Infrastructure\WordPress\PostTypeRegistrar;
use RIILSA\Infrastructure\WordPress\TaxonomyRegistrar;
use RIILSA\Infrastructure\WordPress\ShortcodeRegistrar;
use RIILSA\Infrastructure\Database\DatabaseManager;
use RIILSA\Presentation\Controllers\ContentManagerController;
use RIILSA\Presentation\Controllers\NewsletterController;
use RIILSA\Presentation\Controllers\SubscriptionController;

/**
 * Bootstrap class for initializing the plugin
 *
 * Pattern: Bootstrap Pattern
 * This class is responsible for initializing all plugin components
 * in the correct order and registering them with WordPress.
 */
class Bootstrap
{
    /**
     * The dependency injection container
     *
     * @var Container
     */
    private Container $container;

    /**
     * Plugin activation status
     *
     * @var bool
     */
    private bool $isActivated = false;

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
     * Initialize the plugin
     *
     * @return void
     */
    public function init(): void
    {
        if ($this->isActivated) {
            return;
        }

        $this->isActivated = true;

        // Load text domain for internationalization
        $this->loadTextDomain();

        // Check environment configuration
        $this->checkEnvironment();

        // Initialize core components
        $this->initializeCore();

        // Initialize infrastructure layer
        $this->initializeInfrastructure();

        // Initialize presentation layer
        $this->initializePresentation();

        // Register activation and deactivation hooks
        $this->registerLifecycleHooks();
    }

    /**
     * Check environment configuration
     *
     * @return void
     */
    private function checkEnvironment(): void
    {
        // Load environment variables
        if (file_exists(RIILSA_PLUGIN_DIR . '.env')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(RIILSA_PLUGIN_DIR);
            $dotenv->safeLoad();
        }

        $apiKey = $_ENV['API_KEY'] ?? null;

        if (!$apiKey && is_admin()) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-warning"><p>' . esc_html__('RIILSA Plugin: Brevo API key not configured. Email functionality is disabled.', 'riilsa') . '</p></div>';
            });
        }
    }

    /**
     * Load plugin text domain for translations
     *
     * @return void
     */
    private function loadTextDomain(): void
    {
        add_action('init', function () {
            load_plugin_textdomain(
                'riilsa',
                false,
                dirname(plugin_basename(RIILSA_PLUGIN_DIR . 'riilsa.php')) . '/languages'
            );
        });
    }

    /**
     * Initialize core components
     *
     * @return void
     */
    private function initializeCore(): void
    {
        // Initialize constants (already loaded)
        // Initialize any core services that need early initialization
    }

    /**
     * Initialize infrastructure layer components
     *
     * @return void
     */
    private function initializeInfrastructure(): void
    {
        // Initialize database manager
        $databaseManager = $this->container->make(DatabaseManager::class);
        $databaseManager->init();

        // Initialize WordPress integrations
        $hooksManager = $this->container->make(HooksManager::class);
        $hooksManager->register();

        // Register post types
        $postTypeRegistrar = $this->container->make(PostTypeRegistrar::class);
        add_action('init', [$postTypeRegistrar, 'register'], 0);

        // Register taxonomies
        $taxonomyRegistrar = $this->container->make(TaxonomyRegistrar::class);
        add_action('init', [$taxonomyRegistrar, 'register'], 0);

        // Register shortcodes
        $shortcodeRegistrar = $this->container->make(ShortcodeRegistrar::class);
        add_action('init', [$shortcodeRegistrar, 'register']);
    }

    /**
     * Initialize presentation layer components
     *
     * @return void
     */
    private function initializePresentation(): void
    {
        // Initialize controllers

        // Content Manager Controller
        $contentManagerController = $this->container->make(ContentManagerController::class);
        $contentManagerController->init();

        // Newsletter Controller
        $newsletterController = $this->container->make(NewsletterController::class);
        $newsletterController->init();

        // Subscription Controller
        $subscriptionController = $this->container->make(SubscriptionController::class);
        $subscriptionController->init();

        // Register assets
        $this->registerAssets();
    }

    /**
     * Register plugin assets (styles and scripts)
     *
     * @return void
     */
    private function registerAssets(): void
    {
        add_action('wp_enqueue_scripts', function () {
            // Frontend styles
            wp_register_style(
                'riilsa-main',
                pluginUrl('assets/css/riilsa-modal.css'),
                [],
                pluginVersion()
            );

            // Frontend scripts
            wp_register_script(
                'riilsa-modal',
                pluginUrl('assets/js/riilsa-modal.js'),
                ['jquery'],
                pluginVersion(),
                true
            );

            // Newsletter specific assets
            if (is_page('gestion-boletin')) {
                wp_enqueue_style(
                    'riilsa-newsletter-config',
                    pluginUrl('assets/css/newsletterConfig.css'),
                    ['riilsa-main'],
                    pluginVersion()
                );

                wp_enqueue_style(
                    'riilsa-newsletter-history',
                    pluginUrl('assets/css/newsletterHistory.css'),
                    ['riilsa-main'],
                    pluginVersion()
                );

                wp_enqueue_style(
                    'riilsa-newsletter-select',
                    pluginUrl('assets/css/newsletterSelection.css'),
                    ['riilsa-main'],
                    pluginVersion()
                );

                wp_enqueue_script(
                    'riilsa-newsletter-general',
                    pluginUrl('assets/js/newsletterGeneral.js'),
                    ['jquery'],
                    pluginVersion(),
                    true
                );

                wp_enqueue_script(
                    'riilsa-newsletter-selection',
                    pluginUrl('assets/js/newsletterSelection.js'),
                    ['jquery'],
                    pluginVersion(),
                    true
                );

                wp_enqueue_script(
                    'riilsa-newsletter-config',
                    pluginUrl('assets/js/newsletterConfig.js'),
                    ['jquery'],
                    pluginVersion(),
                    true
                );

                wp_enqueue_script(
                    'riilsa-newsletter-history',
                    pluginUrl('assets/js/newsletterHistory.js'),
                    ['jquery'],
                    pluginVersion(),
                    true
                );

                $brevoService = $this->container->get(\RIILSA\Infrastructure\Services\BrevoMailService::class);
                $isBrevoAvailable = $brevoService->isAvailable();

                // Localize script with AJAX data
                wp_localize_script('riilsa-newsletter-general', 'riilsa_ajax', [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => createNonce('newsletter_actions'),
                    'brevo_available' => $isBrevoAvailable,
                    'strings' => [
                        'processing' => __('Processing...', 'riilsa'),
                        'success' => __('Success', 'riilsa'),
                        'error' => __('Error', 'riilsa'),
                        'warning' => __('Warning', 'riilsa'),
                        'brevo_unavailable' => __('El servicio de envío de correos (Brevo) no está disponible en este momento.', 'riilsa')
                    ]
                ]);
            }

            // Confirmation page specific assets
            if (is_page('confirmacion-boletin')) {
                wp_enqueue_style(
                    'riilsa-confirmation',
                    pluginUrl('assets/css/confirmation-page.css'),
                    [],
                    pluginVersion()
                );
            }

            // Content Manager page specific assets
            if (is_page('gestor-de-contenido')) {
                wp_enqueue_script(
                    'riilsa-contentManager-general',
                    pluginUrl('assets/js/contentManager.js'),
                    ['jquery'],
                    pluginVersion(),
                    true
                );
            }
        });

        // Admin assets
        add_action('admin_enqueue_scripts', function () {
            // Chilling
        });
    }

    /**
     * Register plugin lifecycle hooks
     *
     * @return void
     */
    private function registerLifecycleHooks(): void
    {
        // Activation hook
        register_activation_hook(RIILSA_PLUGIN_DIR . 'riilsa.php', function () {
            $this->onActivation();
        });

        // Deactivation hook
        register_deactivation_hook(RIILSA_PLUGIN_DIR . 'riilsa.php', function () {
            $this->onDeactivation();
        });

        // Uninstall hook
        register_uninstall_hook(RIILSA_PLUGIN_DIR . 'riilsa.php', [__CLASS__, 'onUninstall']);
    }

    /**
     * Handle plugin activation
     *
     * @return void
     */
    private function onActivation(): void
    {
        // Create database tables
        $databaseManager = $this->container->make(DatabaseManager::class);
        $databaseManager->createTables();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Set plugin version
        update_option('riilsa_version', RIILSA_VERSION);

        debugLog('RIILSA plugin activated', 'info');
    }

    /**
     * Handle plugin deactivation
     *
     * @return void
     */
    private function onDeactivation(): void
    {
        // Clean up scheduled events
        wp_clear_scheduled_hook('riilsa_daily_cleanup');

        // Flush rewrite rules
        flush_rewrite_rules();

        debugLog('RIILSA plugin deactivated', 'info');
    }

    /**
     * Handle plugin uninstall
     *
     * @return void
     */
    public static function onUninstall(): void
    {
        // Only run if explicitly uninstalling
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            return;
        }

        // Remove options
        delete_option('riilsa_version');

        // Note: We don't remove database tables by default
        // to prevent accidental data loss
    }
}
