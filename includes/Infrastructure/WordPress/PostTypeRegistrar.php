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
        // Register main menu
        add_action('admin_menu', [$this, 'registerMainMenu']);

        $this->registerNewsPostType();
        $this->registerProjectPostType();
        $this->registerCallPostType();
        $this->registerRevistaPostType();
    }

    /**
     * Register main admin menu
     *
     * @return void
     */
    public function registerMainMenu(): void
    {
        add_menu_page(
            __('RIILSA', 'riilsa'),
            __('RIILSA', 'riilsa'),
            'edit_posts', // Capability required
            'riilsa-main',
            function () {}, // Empty callback
            'dashicons-admin-site', // Icon
            5 // Position
        );

        add_submenu_page(
            'riilsa-main',
            __('Gestor de Contenido', 'riilsa'),
            __('Gestor de Contenido', 'riilsa'),
            'edit_posts',
            'riilsa-content-manager',
            function () {
                echo '<script>window.location.href = "' . home_url('/gestor-de-contenido') . '";</script>';
            }
        );

        add_submenu_page(
            'riilsa-main',
            __('Gestión Boletín', 'riilsa'),
            __('Gestión Boletín', 'riilsa'),
            'edit_posts',
            'riilsa-newsletter-manager',
            function () {
                echo '<script>window.location.href = "' . home_url('/gestion-boletin') . '";</script>';
            }
        );
    }

    /**
     * Register News post type
     *
     * @return void
     */
    private function registerNewsPostType(): void
    {
        $labels = [
            'name' => __('Noticias', 'riilsa'),
            'singular_name' => __('Noticia', 'riilsa'),
            'menu_name' => __('Noticias', 'riilsa'),
            'add_new' => __('Añadir nueva', 'riilsa'),
            'add_new_item' => __('Añadir nueva noticia', 'riilsa'),
            'edit_item' => __('Editar noticia', 'riilsa'),
            'new_item' => __('Nueva noticia', 'riilsa'),
            'view_item' => __('Ver noticia', 'riilsa'),
            'search_items' => __('Buscar noticias', 'riilsa'),
            'not_found' => __('No se encontraron noticias', 'riilsa'),
            'not_found_in_trash' => __('No se encontraron noticias en la papelera', 'riilsa'),
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'riilsa-main',
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
            'name' => __('Proyectos', 'riilsa'),
            'singular_name' => __('Proyecto', 'riilsa'),
            'menu_name' => __('Proyectos', 'riilsa'),
            'add_new' => __('Añadir nuevo', 'riilsa'),
            'add_new_item' => __('Añadir nuevo proyecto', 'riilsa'),
            'edit_item' => __('Editar proyecto', 'riilsa'),
            'new_item' => __('Nuevo proyecto', 'riilsa'),
            'view_item' => __('Ver proyecto', 'riilsa'),
            'search_items' => __('Buscar proyectos', 'riilsa'),
            'not_found' => __('No se encontraron proyectos', 'riilsa'),
            'not_found_in_trash' => __('No se encontraron proyectos en la papelera', 'riilsa'),
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'riilsa-main',
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
            'name' => __('Convocatorias', 'riilsa'),
            'singular_name' => __('Convocatoria', 'riilsa'),
            'menu_name' => __('Convocatorias', 'riilsa'),
            'add_new' => __('Añadir nueva', 'riilsa'),
            'add_new_item' => __('Añadir nueva convocatoria', 'riilsa'),
            'edit_item' => __('Editar convocatoria', 'riilsa'),
            'new_item' => __('Nueva convocatoria', 'riilsa'),
            'view_item' => __('Ver convocatoria', 'riilsa'),
            'search_items' => __('Buscar convocatorias', 'riilsa'),
            'not_found' => __('No se encontraron convocatorias', 'riilsa'),
            'not_found_in_trash' => __('No se encontraron convocatorias en la papelera', 'riilsa'),
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'riilsa-main',
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

    /**
     * Register Revista post type
     *
     * @return void
     */
    private function registerRevistaPostType(): void
    {
        $labels = [
            'name' => __('Revistas', 'riilsa'),
            'singular_name' => __('Revista', 'riilsa'),
            'menu_name' => __('Revistas', 'riilsa'),
            'add_new' => __('Añadir nueva', 'riilsa'),
            'add_new_item' => __('Añadir nueva revista', 'riilsa'),
            'edit_item' => __('Editar revista', 'riilsa'),
            'new_item' => __('Nueva revista', 'riilsa'),
            'view_item' => __('Ver revista', 'riilsa'),
            'search_items' => __('Buscar revistas', 'riilsa'),
            'not_found' => __('No se encontraron revistas', 'riilsa'),
            'not_found_in_trash' => __('No se encontraron revistas en la papelera', 'riilsa'),
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'riilsa-main',
            'show_in_rest' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'revistas'],
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 8,
            'menu_icon' => 'dashicons-book',
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'taxonomies' => [],
        ];

        register_post_type(RIILSA_POST_TYPE_REVISTA, $args);
    }
}
