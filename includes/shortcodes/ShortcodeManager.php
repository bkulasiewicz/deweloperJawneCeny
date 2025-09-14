<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode Manager
 * Handles registration and management of plugin shortcodes
 */
class ShortcodeManager {
    
    public function __construct() {
        add_action('init', [$this, 'register_shortcodes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_shortcode_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_shortcode_assets']);
        // Also enqueue on wp_head with higher priority to ensure CSS loads before shortcode rendering
        add_action('wp_head', [$this, 'enqueue_shortcode_assets'], 5);
    }
    
    /**
     * Register all plugin shortcodes
     */
    public function register_shortcodes() {
        // Register resources list shortcode
        add_shortcode('resources_list', [ResourcesListShortcode::class, 'handle']);
    }

    /**
     * Enqueue shortcode CSS and JavaScript assets
     */
    public function enqueue_shortcode_assets() {
        wp_enqueue_style(
            'ujc-shortcode-resources-list-css',
            PLUGIN_URL . 'assets/blocks/resources-list-block.css',
            [],
            VERSION
        );

        // Set priority to ensure CSS loads early
        wp_styles()->add_data('ujc-shortcode-resources-list-css', 'priority', 'high');

        wp_enqueue_script(
            'ujc-shortcode-resources-list-js',
            PLUGIN_URL . 'assets/blocks/resources-list-widget.js',
            ['jquery'],
            VERSION,
            true
        );

        wp_localize_script('ujc-shortcode-resources-list-js', 'resourcesListAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('resources_list_nonce'),
            'strings' => [
                'loading' => 'Ładowanie...',
                'error' => 'Wystąpił błąd podczas ładowania historii cen.',
                'no_history' => 'Brak historii cen dla tego zasobu.'
            ]
        ]);
    }
    
    /**
     * Remove all plugin shortcodes (for cleanup/deactivation)
     */
    public static function remove_shortcodes() {
        remove_shortcode('resources_list');
    }
}