<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * DEV Console - narzędzia deweloperskie
 * Dostępne tylko gdy WP_DEBUG=true
 */
class UJC_Dev_Console {
    
    public function __construct() {
        // Inicjalizuj tylko w trybie deweloperskim
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_ajax_ujc_dev_clear_table', [$this, 'ajax_clear_table']);
            add_action('wp_ajax_ujc_dev_download_logs', [$this, 'ajax_download_logs']);
            }
    }
    
    /**
     * Sprawdź czy DEV Console jest dostępna
     */
    public static function is_available() {
        return defined('WP_DEBUG') && WP_DEBUG;
    }
    
    /**
     * AJAX handler do czyszczenia tabel
     */
    public function ajax_clear_table() {
        // Podwójne sprawdzenie bezpieczeństwa
        if (!self::is_available()) {
            wp_send_json_error('Funkcja dostępna tylko w trybie deweloperskim');
            return;
        }
        
        check_ajax_referer('ujc_admin_nonce', 'ujc_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień administratora');
            return;
        }
        
        $table_type = sanitize_text_field($_POST['table_type'] ?? '');
        
        $resetUseCase = new ResetDatabaseUseCase();
        
        try {
            switch ($table_type) {
                case 'developer':
                    $resetUseCase->resetDeveloperData();
                    wp_send_json_success('Dane dostawcy zostały usunięte');
                    break;
                    
                case 'investment':
                    $resetUseCase->resetInvestmentData();
                    wp_send_json_success('Dane inwestycji zostały usunięte');
                    break;
                    
                case 'resources':
                    error_log('UJC DEV: Starting resources cleanup');
                    $result = $resetUseCase->resetAllData();
                    wp_send_json_success($result[0]);
                    break;
                    
                case 'all':
                    UJC_Schema_Manager::drop_tables();
                    UJC_Database_Versioning::force_recreate_tables();
                    wp_send_json_success('CAŁA BAZA ZOSTAŁA ZRESETOWANA!');
                    break;
                    
                default:
                    wp_send_json_error('Nieprawidłowy typ tabeli: ' . $table_type);
            }
        } catch (Exception $e) {
            error_log('UJC DEV Console Error: ' . $e->getMessage());
            wp_send_json_error('Błąd bazy danych: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler do pobierania logów
     */
    public function ajax_download_logs() {
        // Podwójne sprawdzenie bezpieczeństwa
        if (!self::is_available()) {
            wp_send_json_error('Funkcja dostępna tylko w trybie deweloperskim');
            return;
        }
        
        check_ajax_referer('ujc_admin_nonce', 'ujc_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień administratora');
            return;
        }
        
        $log_content = $this->get_debug_logs();
        
        if (empty($log_content)) {
            wp_send_json_error('Brak logów do pobrania lub nie można odczytać pliku logów');
            return;
        }
        
        // Zwróć logi jako tekst do pobrania przez frontend
        wp_send_json_success([
            'logs' => $log_content,
            'filename' => 'ujc-debug-logs-' . date('Y-m-d-H-i-s') . '.txt'
        ]);
    }
    
    /**
     * Pobiera logi debugowania
     */
    private function get_debug_logs() {
        $logs = [];
        
        // Możliwe lokalizacje logów WordPress
        $possible_log_files = [
            WP_CONTENT_DIR . '/debug.log',
            ABSPATH . 'wp-content/debug.log',
            ini_get('error_log'),
            '/var/log/apache2/error.log',
            '/var/log/nginx/error.log'
        ];
        
        // Przeszukaj logi w poszukiwaniu wpisów UJC
        foreach ($possible_log_files as $log_file) {
            if ($log_file && file_exists($log_file) && is_readable($log_file)) {
                $content = file_get_contents($log_file);
                if ($content !== false) {
                    // Znajdź linie zawierające UJC
                    $lines = explode("\n", $content);
                    foreach ($lines as $line) {
                        if (stripos($line, 'UJC:') !== false || stripos($line, 'ujc_') !== false) {
                            $logs[] = $line;
                        }
                    }
                    break; // Jeśli znaleźliśmy logi, przerwij
                }
            }
        }
        
        // Jeśli nie ma logów UJC, pokaż ostatnie 100 linii z głównego logu
        if (empty($logs)) {
            foreach ($possible_log_files as $log_file) {
                if ($log_file && file_exists($log_file) && is_readable($log_file)) {
                    $content = file_get_contents($log_file);
                    if ($content !== false) {
                        $lines = explode("\n", $content);
                        $logs = array_slice($lines, -100); // Ostatnie 100 linii
                        array_unshift($logs, "=== OSTATNIE 100 LINII LOGÓW (brak specyficznych logów UJC) ===");
                        break;
                    }
                }
            }
        } else {
            array_unshift($logs, "=== LOGI UJC z " . date('Y-m-d H:i:s') . " ===");
        }
        
        return implode("\n", $logs);
    }
    
    
    /**
     * Renderuje kafelek DEV Console
     */
    public static function render_console_tile() {
        if (!self::is_available()) {
            return '';
        }
        
        
        ob_start();
        ?>
        <div class="ujc-dev-console">
            <h2 style="margin-top: 0;">🔧 DEV Console</h2>
            <p style="color: #d63638; font-size: 12px; margin-bottom: 15px;">
                <strong>⚠️ Tryb deweloperski</strong> - Te funkcje są dostępne tylko gdy WP_DEBUG=true
            </p>
            
            <div class="ujc-dev-actions" style="flex-grow: 1;">
                <h3>Debugowanie:</h3>
                <button type="button" class="button button-primary" onclick="downloadLogs()" style="margin: 5px; background-color: #2271b1;">
                    📋 Pobierz logi debugowania
                </button>
                
                <h3 style="margin-top: 20px;">Czyszczenie tabel:</h3>
                <button type="button" class="button button-secondary" onclick="confirmClearTable('developer', 'dane dostawcy')" style="margin: 5px;">
                    🗑️ Usuń dane dostawcy
                </button>
                <button type="button" class="button button-secondary" onclick="confirmClearTable('investment', 'dane inwestycji')" style="margin: 5px;">
                    🗑️ Usuń dane inwestycji
                </button>
                <button type="button" class="button button-secondary" onclick="confirmClearTable('resources', 'wszystkie zasoby')" style="margin: 5px;">
                    🗑️ Usuń wszystkie zasoby
                </button>
                
                <hr style="margin: 20px 0;">
                
                <button type="button" class="button" onclick="confirmClearTable('all', 'WSZYSTKIE DANE')" style="background-color: #d63638; color: white; border-color: #d63638; margin: 5px;">
                    ⚠️ RESETUJ CAŁĄ BAZĘ
                </button>
            </div>
        </div>
        
        <script>
        
        function downloadLogs() {
            const nonce = typeof ujc_ajax !== 'undefined' ? ujc_ajax.nonce : '<?php echo wp_create_nonce('ujc_admin_nonce'); ?>';
            
            // Pokaż loading
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = '⏳ Pobieranie logów...';
            button.disabled = true;
            
            jQuery.post(ajaxurl, {
                action: 'ujc_dev_download_logs',
                ujc_nonce: nonce
            }, function(response) {
                if (response.success) {
                    // Utwórz plik do pobrania
                    const blob = new Blob([response.data.logs], { type: 'text/plain' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = response.data.filename;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    
                    alert('✅ Logi zostały pobrane');
                } else {
                    alert('❌ Błąd: ' + (response.data || 'Nie można pobrać logów'));
                }
            }).fail(function(xhr, status, error) {
                console.error('Download logs AJAX Error:', xhr, status, error);
                alert('❌ Błąd połączenia: ' + error);
            }).always(function() {
                button.textContent = originalText;
                button.disabled = false;
            });
        }
        
        function confirmClearTable(type, description) {
            const message = `Czy na pewno chcesz usunąć ${description}?\n\nTa operacja jest nieodwracalna!`;
            
            if (confirm(message)) {
                const secondConfirm = `OSTATNIE OSTRZEŻENIE!\n\nUsuwasz: ${description}\n\nKliknij OK aby kontynuować.`;
                if (confirm(secondConfirm)) {
                    clearTableData(type);
                }
            }
        }

        function clearTableData(type) {
            const nonce = typeof ujc_ajax !== 'undefined' ? ujc_ajax.nonce : '<?php echo wp_create_nonce('ujc_admin_nonce'); ?>';
            
            jQuery.post(ajaxurl, {
                action: 'ujc_dev_clear_table',
                table_type: type,
                ujc_nonce: nonce
            }, function(response) {
                if (response.success) {
                    alert('✅ ' + response.data);
                    location.reload();
                } else {
                    alert('❌ Błąd: ' + (response.data || 'Nieznany błąd'));
                }
            }).fail(function(xhr, status, error) {
                console.error('DEV Console AJAX Error:', xhr, status, error);
                alert('❌ Błąd połączenia: ' + error);
            });
        }
        </script>
        <?php
        return ob_get_clean();
    }
}