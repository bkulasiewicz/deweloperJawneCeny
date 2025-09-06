<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Strona główna (dashboard)
 */
class DashboardPage {
    
    private $developerRepository;
    private $investmentRepository;
    private $resourceRepository;
    private $generateFilesUseCase;
    private $automationTile;
    private $historyTile;
    
    public function __construct() {
        $this->developerRepository = new DeveloperRepository();
        $this->investmentRepository = new InvestmentRepository();
        $this->resourceRepository = new ResourceRepository();
        $this->generateFilesUseCase = new GenerateFilesUseCase();
        
        // Initialize dashboard tiles
        $this->automationTile = new AutomationTile();
        $this->historyTile = new HistoryTile();
        
        add_action('wp_ajax_ujc_manual_generation', [$this, 'ajax_manual_generation']);
        add_action('wp_ajax_download_logs', [$this, 'ajax_download_logs']);
    }
    
    public function ajax_manual_generation() {
        try {
            check_ajax_referer('ujc_admin_nonce', 'nonce');
            if (!current_user_can('manage_options')) wp_send_json_error('Brak uprawnień');
            
            $result = $this->generateFilesUseCase->execute(TriggerType::Manual);
            
            if ($result['success']) {
                wp_send_json_success($result['message']);
            } else {
                wp_send_json_error($result['message']);
            }
        } catch (Exception $e) {
            wp_send_json_error('Błąd generowania: ' . $e->getMessage());
        }
    }
    
    public function ajax_download_logs() {
        check_ajax_referer('ujc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień administratora');
            return;
        }
        
        try {
            $log_content = Logger::getLogs();
            
            if (empty($log_content)) {
                wp_send_json_error('Brak logów do pobrania');
                return;
            }
            
            wp_send_json_success([
                'logs' => $log_content,
                'filename' => 'ujc-logs-' . gmdate('Y-m-d-H-i-s') . '.txt'
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error('Błąd pobierania logów: ' . $e->getMessage());
        }
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
                        <p><a href="<?php echo esc_url(admin_url('admin.php?page=ujc-developer')); ?>" class="button button-primary">Dodaj dane dostawcy</a></p>
                    <?php elseif (!$investment): ?>
                        <p><a href="<?php echo esc_url(admin_url('admin.php?page=ujc-resources')); ?>" class="button button-primary">Dodaj inwestycję</a></p>
                    <?php elseif ($resources_count === 0): ?>
                        <p><a href="<?php echo esc_url(admin_url('admin.php?page=ujc-resources')); ?>" class="button button-primary">Dodaj zasoby</a></p>
                    <?php else: ?>
                        <p>
                            <button type="button" class="button button-primary" onclick="manualGeneration()" id="quick-generation-btn">
                                🔄 Generuj manualnie
                            </button>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=ujc-publication')); ?>" class="button button-secondary" style="margin-left: 10px;">
                                📁 Publikacja danych
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
                
                
                <?php $this->automationTile->render(); ?>
                
                <?php $this->historyTile->render(); ?>
                
                <?php
                // Renderuj DEV Console jeśli dostępna
                if (class_exists('DevConsoleTile')) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dev Console output contains safe inline JavaScript
                    echo DevConsoleTile::render_console_tile();
                }
                ?>
            </div>
            
            <!-- Minimalistyczny przycisk logów w prawym dolnym rogu -->
            <div id="ujc-logs-btn" style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
                <button type="button" onclick="downloadLogs()" style="
                    background: #f6f7f7; 
                    border: 1px solid #dcdcde; 
                    color: #646970; 
                    font-size: 11px; 
                    padding: 4px 8px; 
                    border-radius: 3px; 
                    cursor: pointer;
                    opacity: 0.6;
                    transition: opacity 0.2s;
                " onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'">
                    Pobierz logi
                </button>
            </div>
            
            <script>
            
            function manualGeneration() {
                const button = document.getElementById('quick-generation-btn');
                const originalText = button.textContent;
                button.textContent = '⏳ Generowanie...';
                button.disabled = true;
                
                const nonce = typeof ujc_ajax !== 'undefined' ? ujc_ajax.nonce : '<?php echo esc_attr(wp_create_nonce('ujc_admin_nonce')); ?>';
                
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
            
            function downloadLogs() {
                const nonce = typeof ujc_ajax !== 'undefined' ? ujc_ajax.nonce : '<?php echo esc_attr(wp_create_nonce('ujc_admin_nonce')); ?>';
                
                jQuery.post(ajaxurl, {
                    action: 'download_logs',
                    nonce: nonce
                }, function(response) {
                    if (response.success) {
                        // Utwórz i pobierz plik
                        const blob = new Blob([response.data.logs], { type: 'text/plain' });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = response.data.filename;
                        a.click();
                        URL.revokeObjectURL(url);
                    }
                }).fail(function(xhr, status, error) {
                    console.error('Log download error:', error);
                });
            }
            </script>
        </div>
        <?php
    }
    
    
    
    private function get_resources_count() {
        $resources = $this->resourceRepository->readAll();
        return count($resources);
    }
}