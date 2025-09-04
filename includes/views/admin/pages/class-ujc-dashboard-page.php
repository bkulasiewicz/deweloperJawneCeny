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
    private $generateFilesUseCase;
    private $automationTile;
    private $historyTile;
    
    public function __construct() {
        $this->developerRepository = new DeveloperRepository();
        $this->investmentRepository = new InvestmentRepository();
        $this->settingsRepository = new SettingsRepository();
        $this->resourceRepository = new ResourceRepository();
        $this->generateFilesUseCase = new GenerateFilesUseCase();
        
        // Initialize dashboard tiles
        $this->automationTile = new AutomationTile();
        $this->historyTile = new HistoryTile();
        
        add_action('wp_ajax_ujc_manual_generation', [$this, 'ajax_manual_generation']);
    }
    
    public function ajax_manual_generation() {
        check_ajax_referer('ujc_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Brak uprawnień');
        
        $result = $this->generateFilesUseCase->execute(TriggerType::Manual);
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
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
                if (class_exists('UJC_Dev_Console')) {
                    echo wp_kses_post(UJC_Dev_Console::render_console_tile());
                }
                ?>
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
            </script>
        </div>
        <?php
    }
    
    
    
    private function get_resources_count() {
        $resources = $this->resourceRepository->readAll();
        return count($resources);
    }
}