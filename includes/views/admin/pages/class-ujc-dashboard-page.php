<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Strona główna (dashboard)
 */
class UJC_Dashboard_Page {
    
    private $developerRepository;
    private $investmentRepository;
    private $settingsRepository;
    private $resourceRepository;
    private $automationTile;
    
    public function __construct() {
        $this->developerRepository = new DeveloperRepository();
        $this->investmentRepository = new InvestmentRepository();
        $this->settingsRepository = new SettingsRepository();
        $this->resourceRepository = new ResourceRepository();
        
        // Initialize automation tile
        $this->automationTile = new AutomationTile();
    }
    
    
    
    
    public function render() {
        $developer = $this->developerRepository->read();
        $investment = $this->investmentRepository->read();
        $resources_count = $this->get_resources_count();
        
        ?>
        <div class="wrap">
            <h1>Pulpit - DeweloperJawneCeny</h1>
            
            <div class="ujc-dashboard">
                <div class="ujc-quick-actions">
                    <h2>Szybkie Akcje</h2>
                    <?php if (!$developer): ?>
                        <p><a href="<?php echo admin_url('admin.php?page=ujc-developer'); ?>" class="button button-primary">Dodaj dane dostawcy</a></p>
                    <?php elseif (!$investment): ?>
                        <p><a href="<?php echo admin_url('admin.php?page=ujc-resources'); ?>" class="button button-primary">Dodaj inwestycję</a></p>
                    <?php elseif ($resources_count === 0): ?>
                        <p><a href="<?php echo admin_url('admin.php?page=ujc-resources'); ?>" class="button button-primary">Dodaj zasoby</a></p>
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
                
                
                <?php $this->automationTile->render(); ?>
                
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
            
            function toggleExternalCron(enable) {
                const button = document.getElementById('external-cron-toggle-btn');
                const originalText = button.textContent;
                button.textContent = '⏳ Przetwarzanie...';
                button.disabled = true;
                
                // Get selected schedule from debug mode select (if available)
                let schedule = '24hour'; // default
                const scheduleSelect = document.getElementById('external-cron-schedule-select');
                if (scheduleSelect && enable) {
                    schedule = scheduleSelect.value;
                }
                
                const ajaxUrl = typeof ujc_ajax !== 'undefined' ? ujc_ajax.ajax_url : ajaxurl;
                const nonce = typeof ujc_ajax !== 'undefined' ? ujc_ajax.nonce : '<?php echo wp_create_nonce('ujc_admin_nonce'); ?>';
                
                jQuery.post(ajaxUrl, {
                    action: 'ujc_toggle_external_cron',
                    enable: enable,
                    schedule: schedule,
                    nonce: nonce
                }, function(response) {
                    if (response.success) {
                        alert(response.data);
                        location.reload();
                    } else {
                        alert('❌ Błąd: ' + (response.data || 'Nieznany błąd'));
                        button.textContent = originalText;
                        button.disabled = false;
                    }
                }).fail(function(xhr, status, error) {
                    console.error('Toggle external cron AJAX Error:', xhr, status, error);
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
        $history = $this->settingsRepository->getGenerationHistory();
        
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
    
    
    private function get_resources_count() {
        $resources = $this->resourceRepository->readAll();
        return count($resources);
    }
}