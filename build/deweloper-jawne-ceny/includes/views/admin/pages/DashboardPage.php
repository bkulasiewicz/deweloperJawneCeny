<?php

namespace JawneCeny;

use JawneCeny_AdminPage;

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
    private $devConsoleTile;

    public function __construct(
        DeveloperRepository $developerRepository,
        InvestmentRepository $investmentRepository,
        ResourceRepository $resourceRepository,
        GenerateFilesUseCase $generateFilesUseCase,
        AutomationTile $automationTile,
        HistoryTile $historyTile,
        DevConsoleTile $devConsoleTile
    ) {
        $this->developerRepository = $developerRepository;
        $this->investmentRepository = $investmentRepository;
        $this->resourceRepository = $resourceRepository;
        $this->generateFilesUseCase = $generateFilesUseCase;
        $this->automationTile = $automationTile;
        $this->historyTile = $historyTile;
        $this->devConsoleTile = $devConsoleTile;

        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_jawneceny_manual_generation', [$this, 'ajax_manual_generation']);
        add_action('wp_ajax_jawneceny_download_logs', [$this, 'ajax_download_logs']);
    }
    
    public function ajax_manual_generation() {
        try {
            check_ajax_referer('jawneceny_admin_nonce', 'nonce');
            if (!current_user_can('manage_options')) wp_send_json_error('Brak uprawnień');
            
            $result = $this->generateFilesUseCase->execute(TriggerType::Manual);
            
            if ($result->isSuccess) {
                wp_send_json_success($result->message);
            } else {
                wp_send_json_error($result->message);
            }
        } catch (Exception $e) {
            wp_send_json_error('Błąd generowania: ' . $e->getMessage());
        }
    }
    
    public function ajax_download_logs() {
        check_ajax_referer('jawneceny_admin_nonce', 'nonce');
        
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

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'dashboard-page',
            JAWNECENY_PLUGIN_URL . 'includes/views/admin/pages/DashboardPage.js',
            ['jquery'],
            JAWNECENY_VERSION,
            true
        );

        wp_localize_script('dashboard-page', 'jawnecenyDashboardPageData', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('jawneceny_admin_nonce')
        ]);
    }

    public function render() {
        $developer = $this->developerRepository->read();
        $investment = $this->investmentRepository->read();
        $resources_count = $this->get_resources_count();
        
        ?>
        <div class="wrap">
            <h1>Pulpit - DeweloperJawneCeny</h1>
            
            <div class="ujc-dashboard">

                
                <?php $this->automationTile->render(); ?>
                
                <?php $this->historyTile->render(); ?>
                
                <?php
                // Renderuj DEV Console jeśli dostępna
                if (class_exists('DevConsoleTile')) {
                    // Allow onclick attribute for DEV Console buttons
                    $allowed_html = wp_kses_allowed_html('post');
                    $allowed_html['button']['onclick'] = true;
                    echo wp_kses($this->devConsoleTile->render_console_tile(), $allowed_html);
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
        </div>
        <?php
    }
    
    
    
    private function get_resources_count() {
        $resources = $this->resourceRepository->readAll();
        return count($resources);
    }
}