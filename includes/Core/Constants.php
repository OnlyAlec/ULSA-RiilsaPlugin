<?php

declare(strict_types=1);

/**
 * Plugin Constants Definition
 *
 * @package RIILSA\Core
 * @since 3.1.0
 */

namespace RIILSA\Core;

/**
 * Constants class for defining plugin-wide constants
 * 
 * This class centralizes all constant definitions used throughout the plugin
 * to ensure consistency and maintainability.
 */
final class Constants
{
    /**
     * Constructor
     * 
     * Initializes all constant groups
     */
    public function __construct()
    {
        $this->definePostTypes();
        $this->defineTaxonomies();
        $this->defineACFFields();
        $this->defineMailSettings();
        $this->defineUrls();
        $this->defineTemplates();
        $this->defineDatabaseTables();
    }

    /**
     * Define post type constants
     *
     * @return void
     */
    private function definePostTypes(): void
    {
        // Custom post types
        define('RIILSA_POST_TYPE_NEWS', 'noticia');
        define('RIILSA_POST_TYPE_PROJECT', 'proyecto');
        define('RIILSA_POST_TYPE_CALL', 'convocatoria');
        
        // Legacy constants for backward compatibility
        define('NEWS_POST_TYPE', RIILSA_POST_TYPE_NEWS);
        define('PROYECT_POST_TYPE', RIILSA_POST_TYPE_PROJECT);
        define('CALLS_POST_TYPE', RIILSA_POST_TYPE_CALL);
    }

    /**
     * Define taxonomy constants
     *
     * @return void
     */
    private function defineTaxonomies(): void
    {
        // Custom taxonomies
        define('RIILSA_TAXONOMY_AREA', 'area');
        define('RIILSA_TAXONOMY_STATUS', 'estado');
        define('RIILSA_TAXONOMY_NEWSLETTER', 'boletin');
        
        // Taxonomy terms
        define('RIILSA_TERM_CURRENT', 'Vigente');
        define('RIILSA_TERM_EXPIRED', 'Caducado');
        
        // Newsletter parent category
        define('RIILSA_NEWSLETTER_PARENT_ID', 236);
        
        // Legacy constants for backward compatibility
        define('LGAC_TAXONOMY', RIILSA_TAXONOMY_AREA);
        define('STATUS_TAXONOMY', RIILSA_TAXONOMY_STATUS);
        define('NEWSLETTER_TAXONOMY', RIILSA_TAXONOMY_NEWSLETTER);
        define('CURRENT_TAXONOMY_TAG', RIILSA_TERM_CURRENT);
        define('EXPIRED_TAXONOMY_TAG', RIILSA_TERM_EXPIRED);
        define('NEWSLETTER_PARENT_ID', RIILSA_NEWSLETTER_PARENT_ID);
    }

    /**
     * Define ACF field constants
     *
     * @return void
     */
    private function defineACFFields(): void
    {
        // ACF field names
        define('RIILSA_ACF_NEWSLETTER', 'boletin');
        define('RIILSA_ACF_ODS', 'ods');
        
        // Legacy constants for backward compatibility
        define('ACF_TAG_BOLETIN', RIILSA_ACF_NEWSLETTER);
        define('ACF_TAG_ODS', RIILSA_ACF_ODS);
    }

    /**
     * Define mail settings constants
     *
     * @return void
     */
    private function defineMailSettings(): void
    {
        // Mail service settings
        define('RIILSA_MAIL_LIST_LIMIT', 30);
        define('RIILSA_MAIL_SENDER_NAME', 'Boletín RIILSA');
        define('RIILSA_MAIL_TOKEN_EXPIRATION', 'NOW() + INTERVAL 1 WEEK');
        
        // Legacy constants for backward compatibility
        define('LIMIT_LISTS', RIILSA_MAIL_LIST_LIMIT);
        define('EMAIL_NAME_SENDER', RIILSA_MAIL_SENDER_NAME);
        define('EXPIRATION_TIME_SQL', RIILSA_MAIL_TOKEN_EXPIRATION);
    }

    /**
     * Define URL constants
     *
     * @return void
     */
    private function defineUrls(): void
    {
        // Regular expressions
        define('RIILSA_REGEX_GOOGLE_DRIVE', '/(?:https?:\/\/)?(?:drive\.google\.com\/(?:file\/d\/|open\?id=)|docs\.google\.com\/(?:document|spreadsheets)\/d\/)([a-zA-Z0-9_-]+)/');
        
        // URLs
        define('RIILSA_URL_GOOGLE_DRIVE_DOWNLOAD', 'https://drive.google.com/uc?export=download&id=');
        define('RIILSA_URL_CONFIRMATION', site_url('confirmacion-boletin'));
        
        // Legacy constants for backward compatibility
        define('REGEX_GOOGLE_DRIVE', RIILSA_REGEX_GOOGLE_DRIVE);
        define('URL_GOOGLE_DRIVE', RIILSA_URL_GOOGLE_DRIVE_DOWNLOAD);
        define('URL_CONFIRMATION', RIILSA_URL_CONFIRMATION);
    }

    /**
     * Define database table constants
     *
     * @return void
     */
    private function defineDatabaseTables(): void
    {
        global $wpdb;
        
        // Custom tables
        define('RIILSA_TABLE_EMAIL', "{$wpdb->prefix}newsletter_email");
        define('RIILSA_TABLE_EMAIL_TOKENS', "{$wpdb->prefix}newsletter_email_tokens");
        define('RIILSA_TABLE_NEWSLETTER_LOGS', "{$wpdb->prefix}newsletter_logs");
        define('RIILSA_TABLE_DEPENDENCY_CATALOG', "{$wpdb->prefix}newsletter_dependency_catalog");
        
        // Legacy constants for backward compatibility
        define('TABLE_EMAIL', RIILSA_TABLE_EMAIL);
        define('TABLE_EMAIL_TOKENS', RIILSA_TABLE_EMAIL_TOKENS);
        define('TABLE_NEWSLETTER_LOGS', RIILSA_TABLE_NEWSLETTER_LOGS);
        define('TABLE_DEPENDENCY_CATALOG', RIILSA_TABLE_DEPENDENCY_CATALOG);
    }

    /**
     * Define template constants
     *
     * @return void
     */
    private function defineTemplates(): void
    {
        // Template IDs
        define('RIILSA_TEMPLATE_LAST_NEWS_ID', 60921);
        
        // Template paths
        define('RIILSA_PATH_TEMPLATE_NEWSLETTER', RIILSA_PLUGIN_DIR . 'assets/templates/Newsletter/');
        define('RIILSA_PATH_TEMPLATE_EMAIL', RIILSA_PLUGIN_DIR . 'assets/templates/Email/');
        define('RIILSA_PATH_TEMPLATE_PAGE', RIILSA_PLUGIN_DIR . 'assets/templates/Page/');
        
        // Specific template files
        define('RIILSA_PATH_TEMPLATE_BASE', RIILSA_PATH_TEMPLATE_NEWSLETTER . 'templateBaseBoletin.html');
        define('RIILSA_PATH_TEMPLATE_GRID', RIILSA_PATH_TEMPLATE_NEWSLETTER . 'templateGridBoletin.html');
        define('RIILSA_PATH_TEMPLATE_ITEM', RIILSA_PATH_TEMPLATE_NEWSLETTER . 'templateItemBoletin.html');
        define('RIILSA_PATH_TEMPLATE_HIGHLIGHT', RIILSA_PATH_TEMPLATE_NEWSLETTER . 'templateHighlightBoletin.html');
        define('RIILSA_PATH_TEMPLATE_NORMAL', RIILSA_PATH_TEMPLATE_NEWSLETTER . 'templateNormalBoletin.html');
        define('RIILSA_PATH_TEMPLATE_SPACE', RIILSA_PATH_TEMPLATE_NEWSLETTER . 'templateSpaceBoletin.html');
        
        // Email templates
        define('RIILSA_PATH_TEMPLATE_CONFIRM', RIILSA_PATH_TEMPLATE_EMAIL . 'templateConfirmBoletin.html');
        
        // Page templates
        define('RIILSA_PATH_PAGE_CONFIRM_OK', RIILSA_PATH_TEMPLATE_PAGE . 'page_confirm_ok.html');
        define('RIILSA_PATH_PAGE_CONFIRM_FAIL', RIILSA_PATH_TEMPLATE_PAGE . 'page_confirm_fail.html');
        
        // Legacy constants for backward compatibility
        define('TEMPLATE_LAST_NEWS', RIILSA_TEMPLATE_LAST_NEWS_ID);
        define('PATH_TEMPLATE_NEWSLETTER', RIILSA_PATH_TEMPLATE_NEWSLETTER);
        define('PATH_TEMPLATE_EMAIL', RIILSA_PATH_TEMPLATE_EMAIL);
        define('PATH_TEMPLATE_PAGE', RIILSA_PATH_TEMPLATE_PAGE);
        define('PATH_BASE', RIILSA_PATH_TEMPLATE_BASE);
        define('PATH_GRID', RIILSA_PATH_TEMPLATE_GRID);
        define('PATH_ITEM', RIILSA_PATH_TEMPLATE_ITEM);
        define('PATH_HIGHLIGHT', RIILSA_PATH_TEMPLATE_HIGHLIGHT);
        define('PATH_NORMAL', RIILSA_PATH_TEMPLATE_NORMAL);
        define('PATH_SPACE', RIILSA_PATH_TEMPLATE_SPACE);
        define('PATH_CONFIRM', RIILSA_PATH_TEMPLATE_CONFIRM);
        define('PATH_PAGE_CONFIRM_OK', RIILSA_PATH_PAGE_CONFIRM_OK);
        define('PATH_PAGE_CONFIRM_FAIL', RIILSA_PATH_PAGE_CONFIRM_FAIL);
    }
}
