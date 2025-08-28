<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modal historii cen zasobów
 */
class UJC_History_Modal {
    
    public function __construct() {
        add_action('wp_ajax_ujc_get_resource_history', [$this, 'ajax_get_resource_history']);
    }
    
    /**
     * AJAX: Pobiera historię cen dla zasobu
     */
    public function ajax_get_resource_history() {
        check_ajax_referer('ujc_admin_nonce', 'ujc_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
        }
        
        $resource_id = intval($_POST['resource_id'] ?? 0);
        $price_history_repo = new PriceHistoryRepository();
        $history = $price_history_repo->readByResourceId($resource_id);
        
        ob_start();
        if (empty($history)) {
            echo '<p>Brak historii zmian cen dla tego zasobu.</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Data zmiany</th><th>Cena m²</th><th>Cena całkowita</th><th>Cena z dodatkami</th></tr></thead><tbody>';
            foreach ($history as $record) {
                echo '<tr>';
                echo '<td>' . DateHelper::formatForUser($record['data_zmiany']) . '</td>';
                echo '<td>' . number_format($record['cena_m2_new'], 2, ',', ' ') . ' zł</td>';
                echo '<td>' . ($record['cena_calkowita_new'] ? number_format($record['cena_calkowita_new'], 2, ',', ' ') . ' zł' : '-') . '</td>';
                echo '<td>' . ($record['cena_z_dodatkami_new'] ? number_format($record['cena_z_dodatkami_new'], 2, ',', ' ') . ' zł' : '-') . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        
        $output = ob_get_clean();
        wp_send_json_success($output);
    }
    
    /**
     * Renderuje JavaScript dla modala historii
     */
    public static function render_history_script() {
        ?>
        <script>
        // Funkcja historii cen - modal z inline styles i jQuery wrapper
        window.showResourceHistory = function(resourceId) {
            // Użyj jQuery wrapper aby uniknąć konfliktów z $
            jQuery(document).ready(function($) {
                // Usuń poprzedni modal jeśli istnieje
                $('#history-modal').remove();
                
                // Twórz modal dla historii - kompletne inline style
                var modalHtml = '<div id="history-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 10000; display: flex; align-items: center; justify-content: center;">' +
                    '<div style="background: white; padding: 30px; border-radius: 8px; max-width: 80%; max-height: 80%; overflow-y: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">' +
                    '<div style="border-bottom: 2px solid #ddd; padding-bottom: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">' +
                    '<h2 style="margin: 0; color: #333;">Historia Cen - Zasób #' + resourceId + '</h2>' +
                    '<span class="ujc-modal-close" style="font-size: 28px; cursor: pointer; color: #999; font-weight: bold;">&times;</span>' +
                    '</div>' +
                    '<div class="ujc-modal-body" style="min-height: 100px;">' +
                    '<p style="text-align: center; color: #666;">Ładowanie historii...</p>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
                
                $('body').append(modalHtml);
                
                // Załaduj dane
                $.post(typeof ujc_ajax !== 'undefined' ? ujc_ajax.ajax_url : ajaxurl, {
                    action: 'ujc_get_resource_history',
                    resource_id: resourceId,
                    ujc_nonce: '<?php echo wp_create_nonce('ujc_admin_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#history-modal .ujc-modal-body').html(response.data);
                    } else {
                        $('#history-modal .ujc-modal-body').html('<p>Błąd ładowania historii: ' + (response.data || 'Nieznany błąd') + '</p>');
                    }
                }).fail(function(xhr, status, error) {
                    $('#history-modal .ujc-modal-body').html('<p>Błąd połączenia z serwerem: ' + error + '</p>');
                });
                
                // Obsługa zamykania
                $(document).off('click', '#history-modal .ujc-modal-close, #history-modal').on('click', '#history-modal .ujc-modal-close, #history-modal', function(e) {
                    if (e.target === this) {
                        $('#history-modal').remove();
                    }
                });
            });
        };
        </script>
        <?php
    }
}