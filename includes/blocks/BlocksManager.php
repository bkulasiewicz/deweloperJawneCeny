<?php

if (!defined('ABSPATH')) {
    exit;
}

class BlocksManager {
    
    public function __construct() {
        add_action('init', [$this, 'register_blocks']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        
        // AJAX endpoints
        add_action('wp_ajax_get_resource_price_history', [$this, 'ajax_get_price_history']);
        add_action('wp_ajax_nopriv_get_resource_price_history', [$this, 'ajax_get_price_history']);
    }
    
    public function register_blocks() {
        if (function_exists('register_block_type')) {
            register_block_type('jawne-ceny/resources-list', [
                'render_callback' => [ResourcesListBlock::class, 'render']
            ]);
        }
    }
    
    public function enqueue_editor_assets() {
        wp_enqueue_script(
            'resources-list-block-js',
            PLUGIN_URL . 'assets/blocks/resources-list-block.js',
            ['wp-blocks', 'wp-element'],
            VERSION
        );
    }
    
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'resources-list-block-css',
            PLUGIN_URL . 'assets/blocks/resources-list-block.css',
            [],
            VERSION
        );
        
        wp_enqueue_script(
            'resources-list-widget-js',
            PLUGIN_URL . 'assets/blocks/resources-list-widget.js',
            [],
            VERSION,
            true
        );
        
        wp_localize_script('resources-list-widget-js', 'resourcesListAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('resources_list_nonce'),
            'strings' => [
                'loading' => 'Ładowanie...',
                'error' => 'Wystąpił błąd podczas ładowania historii cen.',
                'no_history' => 'Brak historii cen dla tego zasobu.'
            ]
        ]);
    }
    
    public function ajax_get_price_history() {
        check_ajax_referer('resources_list_nonce', 'nonce');
        
        $resource_id = intval($_POST['resource_id']);
        
        if (!$resource_id) {
            wp_die('Nieprawidłowe ID zasobu');
        }
        
        $priceHistoryRepo = new PriceHistoryRepository();
        $history = $priceHistoryRepo->readByResourceId($resource_id);
        
        wp_send_json_success([
            'history' => $history,
            'count' => count($history)
        ]);
    }
}