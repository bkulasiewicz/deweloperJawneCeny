<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

class BlocksManager {
    
    public function __construct() {
        add_action('init', [$this, 'register_blocks']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        
        // AJAX endpoints
        add_action('wp_ajax_jawneceny_get_price_history', [$this, 'ajax_get_price_history']);
        add_action('wp_ajax_nopriv_jawneceny_get_price_history', [$this, 'ajax_get_price_history']);
    }
    
    public function register_blocks() {
        if (function_exists('register_block_type')) {
            register_block_type('jawne-ceny/resources-list', [
                'render_callback' => [ResourcesListBlock::class, 'render']
            ]);

            register_block_type('jawne-ceny/resources-filters', [
                'render_callback' => [ResourcesFiltersBlock::class, 'render']
            ]);

            register_block_type('jawne-ceny/resource-single', [
                'render_callback' => [ResourceSingleBlock::class, 'render']
            ]);
        }
    }
    
    public function enqueue_editor_assets() {
        // Load compact color picker component first
        wp_enqueue_script(
            'compact-color-picker-js',
            JAWNECENY_PLUGIN_URL . 'assets/blocks/compact-color-picker.js',
            ['wp-element', 'wp-components'],
            JAWNECENY_VERSION
        );

        wp_enqueue_script(
            'resources-list-block-js',
            JAWNECENY_PLUGIN_URL . 'assets/blocks/resources-list-block.js',
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'compact-color-picker-js'],
            JAWNECENY_VERSION
        );

        wp_enqueue_script(
            'resources-filters-block-js',
            JAWNECENY_PLUGIN_URL . 'assets/blocks/resources-filters-block.js',
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'compact-color-picker-js'],
            JAWNECENY_VERSION
        );

        wp_enqueue_script(
            'resource-single-block-js',
            JAWNECENY_PLUGIN_URL . 'assets/blocks/resource-single-block.js',
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'compact-color-picker-js'],
            JAWNECENY_VERSION
        );
    }
    
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'resources-list-block-css',
            JAWNECENY_PLUGIN_URL . 'assets/blocks/resources-list-block.css',
            [],
            JAWNECENY_VERSION
        );

        wp_enqueue_style(
            'resource-cards-css',
            JAWNECENY_PLUGIN_URL . 'assets/blocks/resource-cards.css',
            [],
            JAWNECENY_VERSION
        );

        wp_enqueue_style(
            'resources-filters-block-css',
            JAWNECENY_PLUGIN_URL . 'assets/blocks/resources-filters-block.css',
            [],
            JAWNECENY_VERSION
        );

        wp_enqueue_script(
            'resources-list-widget-js',
            JAWNECENY_PLUGIN_URL . 'assets/blocks/resources-list-widget.js',
            [],
            JAWNECENY_VERSION,
            true
        );

        wp_enqueue_script(
            'jawneceny-frontend-filters-widget',
            JAWNECENY_PLUGIN_URL . 'assets/frontend-filters-widget.js',
            [],
            JAWNECENY_VERSION,
            true
        );

        wp_enqueue_script(
            'jawneceny-frontend-table-sorting',
            JAWNECENY_PLUGIN_URL . 'assets/frontend-table-sorting.js',
            ['jquery'],
            JAWNECENY_VERSION,
            true
        );

        wp_enqueue_script(
            'jawneceny-clickable-rows',
            JAWNECENY_PLUGIN_URL . 'assets/frontend-clickable-rows.js',
            ['jquery'],
            '1.0.1',
            true
        );

        wp_localize_script('resources-list-widget-js', 'jawnecenyResourcesListAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'strings' => [
                'loading' => 'Ładowanie...',
                'error' => 'Wystąpił błąd podczas ładowania historii cen.',
                'no_history' => 'Brak historii cen dla tego zasobu.'
            ]
        ]);
    }
    
    public function ajax_get_price_history() {
        // No nonce verification - price history is public information
        $resource_id = absint($_POST['resource_id'] ?? 0);

        if (!$resource_id) {
            wp_send_json_error([
                'message' => 'Nieprawidłowe ID zasobu',
                'code' => 'invalid_id'
            ]);
            return;
        }

        // Use DI Container for Use Cases
        $getPriceHistoryUseCase = DIContainer::get(GetPriceHistoryUseCase::class);
        $history = $getPriceHistoryUseCase->execute($resource_id);

        wp_send_json_success([
            'history' => $history,
            'count' => count($history)
        ]);
    }
}