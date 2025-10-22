<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Price History Modal - Global Modal Component
 * Renders ONE modal instance via wp_footer hook to avoid duplicate ID conflicts
 * Used by: ResourceCardRenderer, ResourceTableRenderer, ResourceSingleRenderer
 */
class PriceHistoryModal {

    public function __construct() {
        add_action('wp_footer', [$this, 'render_modal'], 999);
        Logger::info('PriceHistoryModal: Initialized global modal');
    }

    /**
     * Render the global price history modal
     * Called via wp_footer hook - renders ONCE per page load
     */
    public function render_modal() {
        ?>
        <div id="price-history-modal" class="price-history-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Historia cen - <span id="modal-resource-name"></span></h3>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="history-loading">Ładowanie...</div>
                    <div id="history-content"></div>
                </div>
            </div>
        </div>
        <?php
    }
}
