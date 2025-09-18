<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * DEV Console - narzędzia deweloperskie
 * Dostępne tylko gdy WP_DEBUG=true
 */
class DevConsoleTile {
    
    private $resetDatabaseUseCase;
    
    public function __construct(ResetDatabaseUseCase $resetDatabaseUseCase) {
        $this->resetDatabaseUseCase = $resetDatabaseUseCase;
        // Inicjalizuj tylko w trybie deweloperskim
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_ajax_ujc_dev_clear_table', [$this, 'ajax_clear_table']);
            add_action('wp_ajax_dev_trigger_fallback', [$this, 'ajax_trigger_fallback']);
            add_action('wp_ajax_ujc_dev_clear_logs', [$this, 'ajax_clear_logs']);
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
        
        check_ajax_referer('ujc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień administratora');
            return;
        }
        
        $table_type = sanitize_text_field($_POST['table_type'] ?? '');
        
        try {
            switch ($table_type) {
                case 'developer':
                    $this->resetDatabaseUseCase->resetDeveloperData();
                    wp_send_json_success('Dane dostawcy zostały usunięte');
                    break;
                    
                case 'investment':
                    $this->resetDatabaseUseCase->resetInvestmentData();
                    wp_send_json_success('Dane inwestycji zostały usunięte');
                    break;
                    
                case 'resources':
                    Logger::info('UJC DEV: Starting resources cleanup');
                    $result = $this->resetDatabaseUseCase->resetAllData();
                    wp_send_json_success($result[0]);
                    break;
                    
                case 'publication_history':
                    $result = $this->resetDatabaseUseCase->resetPublicationHistoryData();
                    wp_send_json_success($result);
                    break;
                    
                case 'xml_resource':
                    $result = $this->resetDatabaseUseCase->resetXmlResourceData();
                    wp_send_json_success($result);
                    break;
                    
                case 'all':
                    UJC_Schema_Manager::drop_tables();
                    UJC_Schema_Manager::create_tables();
                    wp_send_json_success('CAŁA BAZA ZOSTAŁA ZRESETOWANA!');
                    break;
                    
                default:
                    wp_send_json_error('Nieprawidłowy typ tabeli: ' . $table_type);
            }
        } catch (Exception $e) {
            Logger::error('UJC DEV Console Error: ' . $e->getMessage());
            wp_send_json_error('Błąd bazy danych: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler do czyszczenia logów UJC
     */
    public function ajax_clear_logs() {
        check_ajax_referer('ujc_admin_nonce', 'nonce');
        
        try {
            Logger::clearLogs();
            wp_send_json_success('Logi UJC zostały wyczyszczone');
        } catch (Exception $e) {
            wp_send_json_error('Błąd czyszczenia logów: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler do ręcznego uruchomienia fallback cron
     */
    public function ajax_trigger_fallback() {
        // Podwójne sprawdzenie bezpieczeństwa
        if (!self::is_available()) {
            wp_send_json_error('Funkcja dostępna tylko w trybie deweloperskim');
            return;
        }
        
        check_ajax_referer('ujc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień administratora');
            return;
        }
        
        try {
            // Sprawdź czy premium fallback controller jest dostępny
            if (!class_exists('WpCronFallbackController')) {
                wp_send_json_error('WpCronFallbackController nie jest dostępny (premium feature)');
                return;
            }
            
            Logger::info('DEV Console: Manual fallback trigger started');
            $result = WpCronFallbackController::trigger_fallback_manually();
            
            if ($result['success']) {
                $message = "Fallback wykonany pomyślnie: {$result['message']} (akcja: {$result['action_taken']})";
                Logger::success('DEV Console: ' . $message);
                wp_send_json_success($message);
            } else {
                $error = "Błąd fallback: {$result['message']}";
                Logger::error('DEV Console: ' . $error);
                wp_send_json_error($error);
            }
            
        } catch (Exception $e) {
            Logger::error('DEV Console Fallback Error: ' . $e->getMessage());
            wp_send_json_error('Błąd serwera: ' . $e->getMessage());
        }
    }
    
    /**
     * Renderuje kafelek DEV Console
     */
    public function render_console_tile() {
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
                <?php if (class_exists('WpCronFallbackController')): ?>
                <h3>Debugowanie:</h3>
                <button type="button" class="button button-primary" onclick="triggerFallback()" style="margin: 5px; background-color: #d63638;">
                    🚨 Uruchom Fallback Cron
                </button>
                <button type="button" class="button button-secondary" onclick="clearLogs()" style="margin: 5px;">
                    Wyczyść logi
                </button>
                <p><small>WP_DEBUG: <?php echo defined('WP_DEBUG') && WP_DEBUG ? 'ON' : 'OFF'; ?></small></p>
                <?php endif; ?>
                
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
                <button type="button" class="button button-secondary" onclick="confirmClearTable('publication_history', 'historię publikacji')" style="margin: 5px;">
                    🗑️ Usuń historię publikacji
                </button>
                <button type="button" class="button button-secondary" onclick="confirmClearTable('xml_resource', 'XML Resources')" style="margin: 5px;">
                    🗑️ Usuń XML Resources
                </button>
                
                <hr style="margin: 20px 0;">
                
                <button type="button" class="button" onclick="confirmClearTable('all', 'WSZYSTKIE DANE')" style="background-color: #d63638; color: white; border-color: #d63638; margin: 5px;">
                    ⚠️ RESETUJ CAŁĄ BAZĘ
                </button>
            </div>
        </div>
        
        <script>
        console.log('DEV Console script loaded');
        console.log('WP_DEBUG status:', '<?php echo defined('WP_DEBUG') && WP_DEBUG ? 'ON' : 'OFF'; ?>');
        
        
        function triggerFallback() {
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = '⏳ Uruchamianie fallback...';
            button.disabled = true;
            
            const nonce = typeof ujc_ajax !== 'undefined' ? ujc_ajax.nonce : '<?php echo esc_attr(wp_create_nonce('ujc_admin_nonce')); ?>';
            
            jQuery.post(ajaxurl, {
                action: 'dev_trigger_fallback',
                nonce: nonce
            }, function(response) {
                if (response.success) {
                    alert('✅ ' + response.data);
                } else {
                    alert('❌ Błąd: ' + (response.data || 'Nieznany błąd'));
                }
            }).fail(function(xhr, status, error) {
                console.error('Trigger Fallback AJAX Error:', xhr, status, error);
                alert('❌ Błąd połączenia: ' + error);
            }).always(function() {
                button.textContent = originalText;
                button.disabled = false;
            });
        }

        function clearLogs() {
            const nonce = typeof ujc_ajax !== 'undefined' ? ujc_ajax.nonce : '<?php echo esc_attr(wp_create_nonce('ujc_admin_nonce')); ?>';
            
            jQuery.post(ajaxurl, {
                action: 'ujc_dev_clear_logs',
                nonce: nonce
            }, function(response) {
                if (response.success) {
                    alert('✅ ' + response.data);
                } else {
                    alert('❌ Błąd: ' + (response.data || 'Nieznany błąd'));
                }
            }).fail(function(xhr, status, error) {
                console.error('Clear Logs AJAX Error:', xhr, status, error);
                alert('❌ Błąd połączenia: ' + error);
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
            const nonce = typeof ujc_ajax !== 'undefined' ? ujc_ajax.nonce : '<?php echo esc_attr(wp_create_nonce('ujc_admin_nonce')); ?>';
            
            jQuery.post(ajaxurl, {
                action: 'ujc_dev_clear_table',
                table_type: type,
                nonce: nonce
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