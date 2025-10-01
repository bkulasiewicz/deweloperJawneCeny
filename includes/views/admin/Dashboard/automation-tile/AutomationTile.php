<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Automation Tile Component
 * Handles display and functionality of automation controls
 */
class AutomationTile {
    
    private $externalCronRepository;
    private $externalCronController;
    private $toggleExternalCronUseCase;
    
    public function __construct(
        ?ExternalCronRepository $externalCronRepository = null,
        ?ExternalCronController $externalCronController = null,
        ?ToggleExternalCronUseCase $toggleExternalCronUseCase = null
    ) {
        $this->externalCronRepository = $externalCronRepository;
        $this->externalCronController = $externalCronController;
        $this->toggleExternalCronUseCase = $toggleExternalCronUseCase;

        if ($this->toggleExternalCronUseCase) {
            add_action('wp_ajax_jawneceny_toggle_external_cron', [$this, 'ajax_toggle_external_cron']);
        }

        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }
    
    /**
     * Enqueue JavaScript and CSS assets
     */
    public function enqueue_assets() {
        // Only enqueue on admin pages where this component is used
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'toplevel_page_ujc-dashboard') {
            return;
        }

        wp_enqueue_script(
            'ujc-automation-tile',
            plugins_url('automation-tile/AutomationTile.js', __FILE__),
            ['jquery'],
            '1.0.0',
            true
        );

        // Localize script with nonce for AJAX calls
        wp_localize_script('ujc-automation-tile', 'ujc_automation_ajax', [
            'nonce' => wp_create_nonce('ujc_admin_nonce')
        ]);
    }

    /**
     * Render the complete automation tile
     */
    public function render() {
        if (!PremiumHelper::is_premium()) {
            $this->render_freemium_tile();
            return;
        }
        
        ?>
        <div class="automation-control">
            <h2>Automatyzacja</h2>
            <?php $this->render_external_cron_control(); ?>
        </div>
        <?php
    }
    
    /**
     * Render freemium version of automation tile
     */
    private function render_freemium_tile() {
        ?>
        <div class="automation-control">
            <h2>Automatyzacja</h2>
            <p><?php 
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- PremiumHelper returns safe HTML with controlled content
                echo PremiumHelper::get_upgrade_message(); 
            ?></p>
            <p><small style="color: #666;">Automatyzuj proces zgodności z ustawą o jawności cen mieszkań.</small></p>
        </div>
        <?php
    }
    
    /**
     * Render external cron controls
     */
    private function render_external_cron_control() {
        $external_cron_enabled = ExternalCronController::is_external_cron_enabled();
        $current_schedule = $this->externalCronController->get_current_schedule();
        $available_schedules = $this->externalCronRepository->getAvailableSchedules();
        $frequency_text = $available_schedules[$current_schedule]['interval_text'] ?? 'Nieznany';
        
        ?>
        <p><strong>Status:</strong> 
            <span style="color: <?php echo $external_cron_enabled ? '#007600' : '#d63638'; ?>;">
                <?php echo $external_cron_enabled ? '✅ Aktywny' : '❌ Nieaktywny'; ?>
            </span>
        </p>
        
        <?php if ($external_cron_enabled): ?>
            <p><strong>Częstotliwość:</strong> <?php echo esc_html($frequency_text); ?></p>
        <?php endif; ?>
            
        <div style="margin-top: 15px;">
            <button type="button" 
                    class="button <?php echo $external_cron_enabled ? 'button-secondary' : 'button-primary'; ?>" 
                    onclick="toggleExternalCron(<?php echo $external_cron_enabled ? 'false' : 'true'; ?>)"
                    id="external-cron-toggle-btn">
                <?php echo $external_cron_enabled ? '⏸️ Wyłącz External Cron' : '▶️ Włącz External Cron'; ?>
            </button>
            
            <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                <div style="margin-top: 10px; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
                    <small style="color: #856404;"><strong>⚠️ DEV MODE:</strong> Częstotliwość dla External Cron</small>
                    <div style="margin-top: 5px;">
                        <?php 
                        $available_schedules = $this->externalCronRepository->getAvailableSchedules();
                        ?>
                        <select id="external-cron-schedule-select" style="margin-right: 10px;">
                            <?php foreach ($available_schedules as $schedule_key => $schedule_data): ?>
                                <option value="<?php echo esc_attr($schedule_key); ?>" <?php echo ($schedule_key === '24hour') ? 'selected' : ''; ?>>
                                    <?php echo esc_html($schedule_data['interval_text']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: #666;">Zostanie zastosowana przy aktywacji</small>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for toggling external cron
     */
    public function ajax_toggle_external_cron() {
        if (!$this->toggleExternalCronUseCase) {
            wp_send_json_error('Funkcja dostępna tylko w wersji premium');
            return;
        }
        
        check_ajax_referer('ujc_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Brak uprawnień');
        
        // Proper boolean conversion - handle JavaScript boolean false correctly
        $enable_raw = $_POST['enable'] ?? '';
        
        // JavaScript boolean false can be sent as string "false" or actual boolean false
        if ($enable_raw === false || $enable_raw === 'false' || $enable_raw === '0' || $enable_raw === 0) {
            $enable = false;
        } else {
            $enable = (bool)$enable_raw;
        }
        
        // Get schedule parameter for activation (only used when enabling)
        $schedule = sanitize_text_field($_POST['schedule'] ?? '24hour');
        
        $result = $this->toggleExternalCronUseCase->execute($enable, $schedule);
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
}