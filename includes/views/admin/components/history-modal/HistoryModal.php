<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modal historii cen zasobów
 */
class HistoryModal {

    private $getPriceHistoryUseCase;

    public function __construct(GetPriceHistoryUseCase $getPriceHistoryUseCase) {
        $this->getPriceHistoryUseCase = $getPriceHistoryUseCase;
        add_action('wp_ajax_jawneceny_get_resource_history', [$this, 'ajax_get_resource_history']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Enqueue JavaScript and CSS assets
     */
    public function enqueue_assets() {
        $viewPath = JAWNECENY_PLUGIN_URL . 'includes/views/admin/components/history-modal/';

        wp_enqueue_style(
            'history-modal',
            $viewPath . 'HistoryModal.css',
            [],
            JAWNECENY_VERSION
        );

        wp_enqueue_script(
            'history-modal',
            $viewPath . 'HistoryModal.js',
            ['jquery'],
            JAWNECENY_VERSION,
            true
        );

        wp_localize_script('history-modal', 'historyModalData', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('jawneceny_admin_nonce')
        ]);
    }

    /**
     * AJAX: Pobiera historię cen dla zasobu
     */
    public function ajax_get_resource_history() {
        check_ajax_referer('jawneceny_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
        }

        $resource_id = intval($_POST['resource_id'] ?? 0);
        $history = $this->getPriceHistoryUseCase->execute($resource_id);

        ob_start();
        if (empty($history)) {
            echo '<p>Brak historii zmian cen dla tego zasobu.</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Data zmiany ceny lokalu</th><th>Cena m²</th><th>Cena lokalu</th><th>Data zmiany ceny pełnej</th><th>Cena pełna</th></tr></thead><tbody>';
            foreach ($history as $record) {
                echo '<tr>';
                echo '<td>' . esc_html($record->data_zmiany) . '</td>';
                echo '<td>' . ($record->cena_m2 !== null ? number_format($record->cena_m2, 2, ',', ' ') . ' zł' : '—') . '</td>';
                echo '<td>' . number_format($record->cena_calkowita, 2, ',', ' ') . ' zł</td>';
                echo '<td>' . esc_html($record->data_cena_z_dodatkami) . '</td>';
                echo '<td>' . number_format($record->cena_z_dodatkami, 2, ',', ' ') . ' zł</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        $output = ob_get_clean();
        wp_send_json_success($output);
    }
}