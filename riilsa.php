<?php

/**
 * RIILSA Plugin
 *
 * @package       RIILSA_Plugin
 * @author        Alexis Chacon Trujillo
 * Author URI:    https://github.com/OnlyAlec/
 *
 * @wordpress-plugin
 * Plugin Name:       RIILSA Plugin
 * Plugin URI:        https://www.riilsa.org
 * Description:       Custom functionality for the RIILSA website.
 * Version:           4.0.0
 * Author:            Alexis Chacon Trujillo
 * Requires PHP:      8.1.29
 * Requires at least: 6.7.0
 */

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('RIILSA_VERSION', '4.0.0');
define('RIILSA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RIILSA_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load Composer autoloader
require_once RIILSA_PLUGIN_DIR . 'vendor/autoload.php';

// Initialize the plugin using Clean Architecture
use RIILSA\Core\Bootstrap;
use RIILSA\Core\Container;
use RIILSA\Core\Constants;

/**
 * Main plugin instance
 *
 * Pattern: Singleton Pattern
 * This ensures only one instance of the plugin is loaded.
 */
final class RIILSA_Plugin
{
    /**
     * The singleton instance
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * The bootstrap instance
     *
     * @var Bootstrap
     */
    private Bootstrap $bootstrap;

    /**
     * Get the singleton instance
     *
     * @return self
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Private constructor
     */
    private function __construct()
    {
        $this->initialize();
    }

    /**
     * Initialize the plugin
     *
     * @return void
     */
    private function initialize(): void
    {
        // Initialize constants
        new Constants();

        // Get the dependency injection container
        $container = Container::getInstance();

        // Create and initialize bootstrap
        $this->bootstrap = new Bootstrap($container);

        // Initialize the plugin on plugins_loaded
        add_action('plugins_loaded', [$this, 'onPluginsLoaded']);
    }

    /**
     * Handle the plugins_loaded action
     *
     * @return void
     */
    public function onPluginsLoaded(): void
    {
        // Initialize the plugin
        $this->bootstrap->init();
    }

    /**
     * Prevent cloning
     *
     * @return void
     */
    private function __clone()
    {
        // Prevent cloning
    }

    /**
     * Prevent unserialization
     *
     * @return void
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}

/**
 * Get the main plugin instance
 *
 * @return RIILSA_Plugin
 */
function RIILSA(): RIILSA_Plugin
{
    return RIILSA_Plugin::instance();
}

// Initialize the plugin
RIILSA();
