<?php

declare(strict_types=1);

/**
 * Post Type Registrar
 *
 * @package RIILSA\Infrastructure\WordPress
 * @since 3.1.0
 */

namespace RIILSA\Infrastructure\WordPress;

/**
 * Post type registration handler
 * 
 * Pattern: Registry Pattern
 * This class handles registration of custom post types
 */
class PostTypeRegistrar
{
    /**
     * Register all custom post types
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerNewsPostType();
        $this->registerProjectPostType();
        $this->registerCallPostType();
    }
    
    /**
     * Register News post type
     *
     * @return void
     */
    private function registerNewsPostType(): void
    {
        $labels = [
            'name' => __('News', 'riilsa'),
            'singular_name' => __('News Item', 'riilsa'),
            'menu_name' => __('News', 'riilsa'),
            'add_new' => __('Add New', 'riilsa'),
            'add_new_item' => __('Add New News Item', 'riilsa'),
            'edit_item' => __('Edit News Item', 'riilsa'),
            'new_item' => __('New News Item', 'riilsa'),
            'view_item' => __('View News Item', 'riilsa'),
            'search_items' => __('Search News', 'riilsa'),
            'not_found' => __('No news found', 'riilsa'),
            'not_found_in_trash' => __('No news found in trash', 'riilsa'),
        ];
        
        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'noticias'],
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-media-text',
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
            'taxonomies' => [RIILSA_TAXONOMY_AREA, RIILSA_TAXONOMY_NEWSLETTER],
        ];
        
        register_post_type(RIILSA_POST_TYPE_NEWS, $args);
    }
    
    /**
     * Register Project post type
     *
     * @return void
     */
    private function registerProjectPostType(): void
    {
        $labels = [
            'name' => __('Projects', 'riilsa'),
            'singular_name' => __('Project', 'riilsa'),
            'menu_name' => __('Projects', 'riilsa'),
            'add_new' => __('Add New', 'riilsa'),
            'add_new_item' => __('Add New Project', 'riilsa'),
            'edit_item' => __('Edit Project', 'riilsa'),
            'new_item' => __('New Project', 'riilsa'),
            'view_item' => __('View Project', 'riilsa'),
            'search_items' => __('Search Projects', 'riilsa'),
            'not_found' => __('No projects found', 'riilsa'),
            'not_found_in_trash' => __('No projects found in trash', 'riilsa'),
        ];
        
        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'proyectos'],
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 6,
            'menu_icon' => 'dashicons-portfolio',
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'taxonomies' => [RIILSA_TAXONOMY_AREA, RIILSA_TAXONOMY_STATUS],
        ];
        
        register_post_type(RIILSA_POST_TYPE_PROJECT, $args);
    }
    
    /**
     * Register Call post type
     *
     * @return void
     */
    private function registerCallPostType(): void
    {
        $labels = [
            'name' => __('Calls', 'riilsa'),
            'singular_name' => __('Call', 'riilsa'),
            'menu_name' => __('Calls', 'riilsa'),
            'add_new' => __('Add New', 'riilsa'),
            'add_new_item' => __('Add New Call', 'riilsa'),
            'edit_item' => __('Edit Call', 'riilsa'),
            'new_item' => __('New Call', 'riilsa'),
            'view_item' => __('View Call', 'riilsa'),
            'search_items' => __('Search Calls', 'riilsa'),
            'not_found' => __('No calls found', 'riilsa'),
            'not_found_in_trash' => __('No calls found in trash', 'riilsa'),
        ];
        
        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'convocatorias'],
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 7,
            'menu_icon' => 'dashicons-megaphone',
            'supports' => ['title', 'editor', 'custom-fields'],
            'taxonomies' => [RIILSA_TAXONOMY_STATUS],
        ];
        
        register_post_type(RIILSA_POST_TYPE_CALL, $args);
    }
}
