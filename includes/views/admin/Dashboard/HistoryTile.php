<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * History Tile Component
 * Displays recent publication history with consistent styling (date + status only)
 */
class HistoryTile {
    
    private $getPublicationHistoryUseCase;
    
    public function __construct() {
        $this->getPublicationHistoryUseCase = new GetPublicationHistoryUseCase();
    }
    
    /**
     * Render the complete history tile
     */
    public function render() {
        ?>
        <div class="automation-control">
            <h2>Historia Udostępniania</h2>
            <?php $this->render_history_list(); ?>
        </div>
        <?php
    }
    
    /**
     * Render simplified history entries list (date + status only)
     */
    private function render_history_list() {
        $history = $this->getPublicationHistoryUseCase->execute(7);
        
        if (empty($history)) {
            ?>
            <p style="text-align: center; color: #666; margin: 20px 0;">
                Brak historii udostępniania danych.<br>
                <small>Historia będzie widoczna po pierwszym generowaniu plików.</small>
            </p>
            <?php
            return;
        }
        
        ?>
        <div class="ujc-history-list">
            <?php foreach ($history as $entry): ?>
                <div class="ujc-history-item" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eee;">
                    <div>
                        <span style="font-weight: 500;">
                            <?php echo esc_html($entry->getFormattedDate()); ?>
                        </span>
                    </div>
                    <div>
                        <span style="font-size: 16px;">
                            <?php echo $entry->getStatusIcon(); ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div style="text-align: center; margin-top: 15px;">
            <small style="color: #666;">
                Wyświetlane ostatnie 7 wpisów
            </small>
        </div>
        <?php
    }
}