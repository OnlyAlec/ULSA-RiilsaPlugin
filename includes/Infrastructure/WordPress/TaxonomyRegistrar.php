<?php

declare(strict_types=1);

/**
 * Taxonomy Registrar
 *
 * @package RIILSA\Infrastructure\WordPress
 * @since 3.1.0
 */

namespace RIILSA\Infrastructure\WordPress;

/**
 * Taxonomy registration handler
 * 
 * Pattern: Registry Pattern
 * This class handles registration of custom taxonomies
 */
class TaxonomyRegistrar
{
    /**
     * Register all custom taxonomies
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerAreaTaxonomy();
        $this->registerStatusTaxonomy();
        $this->registerNewsletterTaxonomy();
    }
    
    /**
     * Register Area (LGAC) taxonomy
     *
     * @return void
     */
    private function registerAreaTaxonomy(): void
    {
        $labels = [
            'name' => __('Research Areas', 'riilsa'),
            'singular_name' => __('Research Area', 'riilsa'),
            'search_items' => __('Search Research Areas', 'riilsa'),
            'all_items' => __('All Research Areas', 'riilsa'),
            'edit_item' => __('Edit Research Area', 'riilsa'),
            'update_item' => __('Update Research Area', 'riilsa'),
            'add_new_item' => __('Add New Research Area', 'riilsa'),
            'new_item_name' => __('New Research Area Name', 'riilsa'),
            'menu_name' => __('Research Areas', 'riilsa'),
        ];
        
        $args = [
            'labels' => $labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true,
            'show_tagcloud' => false,
            'rewrite' => ['slug' => 'area'],
        ];
        
        register_taxonomy(
            RIILSA_TAXONOMY_AREA,
            [RIILSA_POST_TYPE_NEWS, RIILSA_POST_TYPE_PROJECT],
            $args
        );
    }
    
    /**
     * Register Status taxonomy
     *
     * @return void
     */
    private function registerStatusTaxonomy(): void
    {
        $labels = [
            'name' => __('Statuses', 'riilsa'),
            'singular_name' => __('Status', 'riilsa'),
            'search_items' => __('Search Statuses', 'riilsa'),
            'all_items' => __('All Statuses', 'riilsa'),
            'edit_item' => __('Edit Status', 'riilsa'),
            'update_item' => __('Update Status', 'riilsa'),
            'add_new_item' => __('Add New Status', 'riilsa'),
            'new_item_name' => __('New Status Name', 'riilsa'),
            'menu_name' => __('Statuses', 'riilsa'),
        ];
        
        $args = [
            'labels' => $labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true,
            'show_tagcloud' => false,
            'rewrite' => ['slug' => 'estado'],
        ];
        
        register_taxonomy(
            RIILSA_TAXONOMY_STATUS,
            [RIILSA_POST_TYPE_PROJECT, RIILSA_POST_TYPE_CALL],
            $args
        );
        
        // Create default terms if they don't exist
        $this->ensureDefaultStatusTerms();
    }
    
    /**
     * Register Newsletter taxonomy
     *
     * @return void
     */
    private function registerNewsletterTaxonomy(): void
    {
        $labels = [
            'name' => __('Newsletters', 'riilsa'),
            'singular_name' => __('Newsletter', 'riilsa'),
            'search_items' => __('Search Newsletters', 'riilsa'),
            'all_items' => __('All Newsletters', 'riilsa'),
            'parent_item' => __('Parent Newsletter', 'riilsa'),
            'parent_item_colon' => __('Parent Newsletter:', 'riilsa'),
            'edit_item' => __('Edit Newsletter', 'riilsa'),
            'update_item' => __('Update Newsletter', 'riilsa'),
            'add_new_item' => __('Add New Newsletter', 'riilsa'),
            'new_item_name' => __('New Newsletter Name', 'riilsa'),
            'menu_name' => __('Newsletters', 'riilsa'),
        ];
        
        $args = [
            'labels' => $labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true,
            'show_tagcloud' => false,
            'rewrite' => ['slug' => 'boletin'],
        ];
        
        register_taxonomy(
            RIILSA_TAXONOMY_NEWSLETTER,
            [RIILSA_POST_TYPE_NEWS],
            $args
        );
    }
    
    /**
     * Ensure default status terms exist
     *
     * @return void
     */
    private function ensureDefaultStatusTerms(): void
    {
        $defaultTerms = [
            RIILSA_TERM_CURRENT,
            RIILSA_TERM_EXPIRED,
        ];
        
        foreach ($defaultTerms as $term) {
            if (!term_exists($term, RIILSA_TAXONOMY_STATUS)) {
                wp_insert_term($term, RIILSA_TAXONOMY_STATUS);
            }
        }
    }
}
