<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Strona główna (dashboard)
 */
class UJC_Dashboard_Page {
    
    public function __construct() {
        add_action('wp_ajax_ujc_toggle_automation', [$this, 'ajax_toggle_automation']);
    }
    
    public function ajax_toggle_automation() {
        check_ajax_referer('ujc_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Brak uprawnień');
        
        require_once UJC_PLUGIN_DIR . 'includes/UseCases/ToggleAutomationUseCase.php';
        
        $result = ToggleAutomationUseCase::execute($_POST['enabled']);
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    
    public function render() {
        $developer_repo = new DeveloperRepository();
        $investment_repo = new InvestmentRepository();
        $developer = $developer_repo->read();
        $investment = $investment_repo->read();
        $properties_count = $this->get_properties_count();
        
        ?>
        <div class="wrap">
            <h1>Pulpit - DeweloperJawneCeny</h1>
            
            <div class="ujc-dashboard">
                <div class="ujc-quick-actions">
                    <h2>Szybkie Akcje</h2>
                    <?php if (!$developer): ?>
                        <p><a href="<?php echo admin_url('admin.php?page=ujc-developer'); ?>" class="button button-primary">Dodaj dane dostawcy</a></p>
                    <?php elseif (!$investment): ?>
                        <p><a href="<?php echo admin_url('admin.php?page=ujc-properties'); ?>" class="button button-primary">Dodaj inwestycję</a></p>
                    <?php elseif ($properties_count === 0): ?>
                        <p><a href="<?php echo admin_url('admin.php?page=ujc-properties'); ?>" class="button button-primary">Dodaj zasoby</a></p>
                    <?php else: ?>
                        <p>
                            <button type="button" class="button button-primary" onclick="manualGeneration()" id="quick-generation-btn">
                                🔄 Generuj pliki XML/CSV
                            </button>
                            <a href="<?php echo admin_url('admin.php?page=ujc-publication'); ?>" class="button button-secondary" style="margin-left: 10px;">
                                📁 Publikacja danych
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="ujc-automation-control">
                    <h2>Automatyzacja</h2>
                    <?php
                    $automation_enabled = get_option('ujc_automation_enabled', true);
                    $current_interval = get_option('ujc_generation_interval', '24hours');
                    $last_generation_status = get_option('ujc_last_generation_status', null);
                    $last_generation_time = get_option('ujc_last_generation_time', null);
                    $next_scheduled = wp_next_scheduled('ujc_generate_files_cycle');
                    
                    $interval_labels = [
                        '1min' => 'co minutę',
                        '15min' => 'co 15 minut', 
                        '1hour' => 'co godzinę',
                        '24hours' => 'codziennie o północy UTC'
                    ];
                    ?>
                    <div style="margin: 15px 0; padding: 15px; background: #f0f0f1; border-radius: 4px;">
                        <p><strong>Status automatycznego zgłaszania cen:</strong> 
                            <span style="color: <?php echo $automation_enabled ? '#007600' : '#d63638'; ?>;">
                                <?php echo $automation_enabled ? '✅ Włączone' : '❌ Wyłączone'; ?>
                            </span>
                        </p>
                        
                        <?php if ($automation_enabled): ?>
                        <p><strong>Częstotliwość generowania:</strong> <?php echo $interval_labels[$current_interval] ?? $current_interval; ?></p>
                        
                        <?php if ($next_scheduled): ?>
                        <p><strong>Następne generowanie:</strong> 
                            <span style="color: #0073aa;">
                                <?php echo DateHelper::formatTimestampForUser($next_scheduled); ?>
                            </span>
                        </p>
                        <?php endif; ?>
                        
                        <?php if ($last_generation_time): ?>
                        <p><strong>Ostatnia akcja:</strong> 
                            <?php echo DateHelper::formatTimestampForUser($last_generation_time); ?>
                            <?php if ($last_generation_status): ?>
                                <?php if ($last_generation_status === 'success'): ?>
                                    <span style="color: #007600;">✅ Sukces</span>
                                <?php else: ?>
                                    <span style="color: #d63638;">❌ Błąd</span>
                                    <br><small style="color: #666;"><?php echo esc_html($last_generation_status); ?></small>
                                <?php endif; ?>
                            <?php endif; ?>
                        </p>
                        <?php endif; ?>
                        <?php endif; ?>
                        
                        <div style="margin-top: 15px;">
                            <button type="button" 
                                    class="button <?php echo $automation_enabled ? 'button-secondary' : 'button-primary'; ?>" 
                                    onclick="toggleAutomation(<?php echo $automation_enabled ? 'false' : 'true'; ?>)"
                                    id="automation-toggle-btn">
                                <?php echo $automation_enabled ? '⏸️ Wyłącz automatyzację' : '▶️ Włącz automatyzację'; ?>
                            </button>
                            
                        </div>
                    </div>
                </div>
                
                <div class="ujc-sharing-history">
                    <h2>Historia Udostępniania</h2>
                    <?php $this->render_sharing_history(); ?>
                </div>
                
                <?php
                // Renderuj DEV Console jeśli dostępna
                if (class_exists('UJC_Dev_Console')) {
                    echo UJC_Dev_Console::render_console_tile();
                }
                ?>
            </div>
            
            <script>
            function toggleAutomation(enable) {
                const button = document.getElementById('automation-toggle-btn');
                const originalText = button.textContent;
                button.textContent = '⏳ Zmienianie...';
                button.disabled = true;
                
                const ajaxUrl = typeof ujc_ajax !== 'undefined' ? ujc_ajax.ajax_url : ajaxurl;
                const nonce = typeof ujc_ajax !== 'undefined' ? ujc_ajax.nonce : '<?php echo wp_create_nonce('ujc_admin_nonce'); ?>';
                
                jQuery.post(ajaxUrl, {
                    action: 'ujc_toggle_automation',
                    enabled: enable,
                    nonce: nonce
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('❌ Błąd: ' + (response.data || 'Nieznany błąd'));
                        button.textContent = originalText;
                        button.disabled = false;
                    }
                }).fail(function(xhr, status, error) {
                    console.error('Toggle automation AJAX Error:', xhr, status, error);
                    alert('❌ Błąd połączenia: ' + error);
                    button.textContent = originalText;
                    button.disabled = false;
                });
            }
            
            function manualGeneration() {
                const button = document.getElementById('quick-generation-btn');
                const originalText = button.textContent;
                button.textContent = '⏳ Generowanie...';
                button.disabled = true;
                
                const nonce = typeof ujc_ajax !== 'undefined' ? ujc_ajax.nonce : '<?php echo wp_create_nonce('ujc_admin_nonce'); ?>';
                
                jQuery.post(ajaxurl, {
                    action: 'ujc_manual_generation',
                    nonce: nonce
                }, function(response) {
                    if (response.success) {
                        alert('✅ ' + response.data);
                        location.reload();
                    } else {
                        alert('❌ Błąd: ' + (response.data || 'Nieznany błąd'));
                        button.textContent = originalText;
                        button.disabled = false;
                    }
                }).fail(function(xhr, status, error) {
                    console.error('Manual generation AJAX Error:', xhr, status, error);
                    alert('❌ Błąd połączenia: ' + error);
                    button.textContent = originalText;
                    button.disabled = false;
                });
            }
            </script>
            
            <!-- Wersja wtyczki w lewym dolnym rogu -->
            <div style="position: fixed; bottom: 20px; left: 20px; background: #fff; padding: 10px 15px; border: 1px solid #ccd0d4; border-radius: 4px; font-size: 12px; color: #666; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <strong>DeweloperJawneCeny</strong><br>
                Wersja: <?php echo esc_html(defined('UJC_VERSION') ? UJC_VERSION : '1.0.0'); ?>
            </div>
        </div>
        <?php
    }
    
    private function render_sharing_history() {
        // Pobierz historię generowania z opcji WordPress
        $settings_repo = new SettingsRepository();
        $history = $settings_repo->getGenerationHistory();
        
        if (empty($history)) {
            echo '<div style="padding: 15px; background: #f0f0f1; border-radius: 4px; text-align: center; color: #666;">';
            echo '<p>Brak historii udostępniania danych.<br><small>Historia będzie widoczna po pierwszym generowaniu plików.</small></p>';
            echo '</div>';
            return;
        }
        
        // Sortuj od najnowszych
        usort($history, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        // Pokaż ostatnie 10 wpisów
        $history = array_slice($history, 0, 10);
        
        echo '<div style="background: #f0f0f1; border-radius: 4px; padding: 15px;">';
        echo '<div class="ujc-history-list">';
        
        foreach ($history as $entry) {
            $status_class = $entry['status'] === 'success' ? 'success' : 'error';
            $status_icon = $entry['status'] === 'success' ? '✅' : '❌';
            $type_label = isset($entry['type']) ? $entry['type'] : 'automatyczne';
            
            echo '<div class="ujc-history-item" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #ddd;">';
            echo '<div>';
            echo '<span style="font-weight: 500;">' . DateHelper::formatTimestampForUser($entry['timestamp']) . '</span>';
            echo '<small style="margin-left: 10px; color: #666;">(' . esc_html($type_label) . ')</small>';
            if ($entry['status'] !== 'success') {
                echo '<br><small style="color: #d63638;">' . esc_html($entry['message'] ?? 'Błąd generowania') . '</small>';
            }
            echo '</div>';
            echo '<div>';
            echo '<span style="font-size: 16px;">' . $status_icon . '</span>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '<div style="text-align: center; margin-top: 10px;">';
        echo '<small style="color: #666;">Wyświetlane ostatnie 10 wpisów</small>';
        echo '</div>';
        echo '</div>';
    }
    
    private function get_properties_count() {
        $repository = new ResourceRepository();
        $resources = $repository->readAll();
        return count($resources);
    }
}