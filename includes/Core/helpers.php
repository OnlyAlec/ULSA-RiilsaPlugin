<?php

declare(strict_types=1);

/**
 * RIILSA Plugin Helper Functions
 *
 * @package RIILSA\Core
 * @since 3.1.0
 */

namespace RIILSA\Core;

use RIILSA\Core\Container;

/**
 * Get the dependency injection container instance
 *
 * @return Container
 */
function container(): Container {
    return Container::getInstance();
}

/**
 * Resolve a class from the dependency injection container
 *
 * @template T
 * @param class-string<T> $class
 * @return T
 */
function resolve(string $class): mixed {
    return container()->get($class);
}

/**
 * Check if the plugin is in debug mode
 *
 * @return bool
 */
function isDebugMode(): bool {
    return defined('WP_DEBUG') && WP_DEBUG;
}

/**
 * Log a message if debug mode is enabled
 *
 * @param mixed $message
 * @param string $level
 * @return void
 */
function debugLog(mixed $message, string $level = 'info'): void {
    if (!isDebugMode()) {
        return;
    }
    
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    
    error_log(sprintf('[RIILSA %s] %s: %s', date('Y-m-d H:i:s'), strtoupper($level), $message));
}

/**
 * Get the plugin base path
 *
 * @param string $path
 * @return string
 */
function pluginPath(string $path = ''): string {
    return RIILSA_PLUGIN_DIR . ltrim($path, '/\\');
}

/**
 * Get the plugin base URL
 *
 * @param string $path
 * @return string
 */
function pluginUrl(string $path = ''): string {
    return RIILSA_PLUGIN_URL . ltrim($path, '/\\');
}

/**
 * Get the plugin version
 *
 * @return string
 */
function pluginVersion(): string {
    return RIILSA_VERSION;
}

/**
 * Sanitize and validate an email address
 *
 * @param string $email
 * @return string|false
 */
function sanitizeEmail(string $email): string|false {
    $email = sanitize_email($email);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
}

/**
 * Format a date according to WordPress settings
 *
 * @param string|\DateTimeInterface $date
 * @param string $format
 * @return string
 */
function formatDate(string|\DateTimeInterface $date, string $format = ''): string {
    if (is_string($date)) {
        $date = new \DateTime($date);
    }
    
    if (empty($format)) {
        $format = get_option('date_format') . ' ' . get_option('time_format');
    }
    
    return wp_date($format, $date->getTimestamp());
}

/**
 * Create a nonce for AJAX requests
 *
 * @param string $action
 * @return string
 */
function createNonce(string $action): string {
    return wp_create_nonce('riilsa_' . $action);
}

/**
 * Verify a nonce for AJAX requests
 *
 * @param string $nonce
 * @param string $action
 * @return bool
 */
function verifyNonce(string $nonce, string $action): bool {
    return wp_verify_nonce($nonce, 'riilsa_' . $action) !== false;
}
