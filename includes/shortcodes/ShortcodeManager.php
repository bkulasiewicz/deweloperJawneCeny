<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode Manager - Enhanced with Dependency Injection
 * Handles registration and management of plugin shortcodes with proper DI support
 */
class ShortcodeManager {

    public function __construct() {
        $this->initializeShortcodeHooks();
        new PriceHistoryModal(); // Initialize global modal
        Logger::info('ShortcodeManager: Initialized');
    }

    /**
     * Initialize WordPress shortcode hooks
     */
    private function initializeShortcodeHooks() {
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
        // Register resources list shortcode with prefix
        add_shortcode('jawneceny_resources_list', [ResourcesListShortcode::class, 'handle']);

        // Backwards compatibility: Keep old shortcode name as deprecated
        add_shortcode('resources_list', [ResourcesListShortcode::class, 'handle']);

        // Register resource single shortcode with prefix
        add_shortcode('jawneceny_resource_single', [ResourceSingleShortcode::class, 'handle']);
    }

    /**
     * Enqueue shortcode CSS and JavaScript assets
     */
    public function enqueue_shortcode_assets() {
        // Resources list CSS
        wp_enqueue_style(
            'jawneceny-shortcode-resources-list-css',
            JAWNECENY_PLUGIN_URL . 'assets/blocks/resources-list-block.css',
            [],
            JAWNECENY_VERSION
        );

        // Set priority to ensure CSS loads early
        wp_styles()->add_data('jawneceny-shortcode-resources-list-css', 'priority', 'high');

        // Resource cards CSS (for card display mode)
        wp_enqueue_style(
            'resource-cards-css',
            JAWNECENY_PLUGIN_URL . 'assets/blocks/resource-cards.css',
            [],
            JAWNECENY_VERSION
        );

        wp_styles()->add_data('resource-cards-css', 'priority', 'high');

        // Resource single CSS
        wp_enqueue_style(
            'jawneceny-shortcode-resource-single-css',
            JAWNECENY_PLUGIN_URL . 'assets/blocks/resource-single.css',
            [],
            JAWNECENY_VERSION
        );

        wp_styles()->add_data('jawneceny-shortcode-resource-single-css', 'priority', 'high');

        // Price History Modal CSS (shared across all views)
        wp_enqueue_style(
            'price-history-modal-css',
            JAWNECENY_PLUGIN_URL . 'assets/blocks/price-history-modal.css',
            [],
            JAWNECENY_VERSION
        );

        wp_styles()->add_data('price-history-modal-css', 'priority', 'high');

        // JavaScript (shared for both list and single - modal functionality)
        wp_enqueue_script(
            'jawneceny-shortcode-resources-list-js',
            JAWNECENY_PLUGIN_URL . 'assets/blocks/resources-list-widget.js',
            ['jquery'],
            JAWNECENY_VERSION,
            true
        );

        wp_localize_script('jawneceny-shortcode-resources-list-js', 'jawnecenyResourcesListAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
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
        remove_shortcode('jawneceny_resources_list');
        remove_shortcode('resources_list'); // Backwards compatibility
        remove_shortcode('jawneceny_resource_single');
    }
}