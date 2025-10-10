<?php

namespace JawneCeny;

use WP_REST_Response;
use WP_Error;
use Exception;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simple External Cron Controller for cron-job.org integration
 * Handles automatic registration and file generation with dev console control
 */
class ExternalCronController {
    
    private $repository;
    private $generateFilesUseCase;
    private $updateExternalCronScheduleUseCase;
    private $registerExternalCronUseCase;
    private $unregisterExternalCronUseCase;
    
    public function __construct(
        ExternalCronRepository $repository,
        GenerateFilesUseCase $generateFilesUseCase,
        UpdateExternalCronScheduleUseCase $updateExternalCronScheduleUseCase,
        RegisterExternalCronUseCase $registerExternalCronUseCase,
        UnregisterExternalCronUseCase $unregisterExternalCronUseCase
    ) {
        $this->repository = $repository;
        $this->generateFilesUseCase = $generateFilesUseCase;
        $this->updateExternalCronScheduleUseCase = $updateExternalCronScheduleUseCase;
        $this->registerExternalCronUseCase = $registerExternalCronUseCase;
        $this->unregisterExternalCronUseCase = $unregisterExternalCronUseCase;
        add_action('rest_api_init', [$this, 'register_rest_endpoint']);
        add_action('wp_ajax_jawneceny_update_external_cron_schedule', [$this, 'ajax_update_schedule']);
    }
    
    /**
     * Register REST API endpoint for external cron calls
     */
    public function register_rest_endpoint() {
        register_rest_route('ujc/v1', '/external-cron', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_external_request'],
            'permission_callback' => '__return_true'
        ]);
    }
    
    /**
     * Handle external cron request from cron-job.org
     */
    public function handle_external_request($request) {
        $start_time = microtime(true);
        $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        Logger::info('External cron request started from IP: ' . $client_ip);
        
        try {
            // Simple security check
            if (!$this->is_valid_cronjoborg_request()) {
                Logger::error('External cron access denied for IP: ' . $client_ip);
                return new WP_Error('access_denied', 'Access denied', ['status' => 401]);
            }
            
            // Rate limiting - max 3 calls per 5 minutes
            if (!$this->check_rate_limit()) {
                Logger::error('External cron rate limited for IP: ' . $client_ip);
                return new WP_Error('rate_limit', 'Too many requests', ['status' => 429]);
            }
            
            Logger::info('External cron starting file generation...');
            
            // Generate files using existing UseCase
            $result = $this->generateFilesUseCase->execute(TriggerType::ExternalCron);
            
            $execution_time = round(microtime(true) - $start_time, 2);
            
            Logger::info('External cron file generation completed in ' . $execution_time . 's: ' . ($result->isSuccess ? 'SUCCESS' : 'FAILED'));
            
            // Return simple response
            if ($result->isSuccess) {
                $csv_file = $result->csvFile?->filename;
                $xml_file = $result->xmlFile?->filename;
                
                Logger::success('External cron returning SUCCESS response - CSV: ' . ($csv_file ?? 'none') . ', XML: ' . ($xml_file ?? 'none'));
                
                return new WP_REST_Response([
                    'success' => true,
                    'message' => 'Files generated successfully',
                    'timestamp' => current_time('c'),
                    'execution_time' => $execution_time,
                    'domain' => wp_parse_url(get_site_url(), PHP_URL_HOST),
                    'files' => [
                        'csv' => $csv_file,
                        'xml' => $xml_file
                    ]
                ], 200);
            } else {
                Logger::error('External cron returning ERROR response: ' . $result->message);
                
                return new WP_Error('generation_failed', $result->message, [
                    'status' => 500,
                    'execution_time' => $execution_time
                ]);
            }
            
        } catch (Exception $e) {
            Logger::error('External cron EXCEPTION: ' . $e->getMessage());
            
            return new WP_Error('error', 'Error: ' . $e->getMessage(), [
                'status' => 500,
                'execution_time' => round(microtime(true) - $start_time, 2)
            ]);
        }
    }
    
    /**
     * Simple validation - TEMPORARILY DISABLED
     */
    private function is_valid_cronjoborg_request() {
        // TEMPORARILY DISABLED - Allow all requests to test timeout issue
        return true;
    }
    
    
    
    /**
     * Simple rate limiting
     */
    private function check_rate_limit() {
        $rate_key = 'ujc_external_cron_rate';
        $current_time = time();
        $rate_data = get_transient($rate_key);
        
        if (!$rate_data) {
            $rate_data = ['count' => 0, 'reset_time' => $current_time + 300];
        }
        
        // Reset if expired
        if ($current_time > $rate_data['reset_time']) {
            $rate_data = ['count' => 0, 'reset_time' => $current_time + 300];
        }
        
        // Check limit (max 3 requests per 5 minutes)
        if ($rate_data['count'] >= 3) {
            return false;
        }
        
        $rate_data['count']++;
        set_transient($rate_key, $rate_data, 300);
        
        return true;
    }
    
    
    /**
     * AJAX handler for updating cron schedule from dev console
     */
    public function ajax_update_schedule() {
        if (!current_user_can('manage_options')) {
            wp_die('Brak uprawnień');
        }
        
        check_ajax_referer('jawneceny_admin_nonce', 'nonce');
        
        $schedule = sanitize_text_field($_POST['schedule'] ?? '24hour');
        
        // Use UpdateExternalCronScheduleUseCase for consistency
        $result = $this->updateExternalCronScheduleUseCase->execute($schedule);
        
        if ($result['success']) {
            wp_send_json_success($result['data'] ?? $result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    
    /**
     * Get available schedules for dev console
     */
    public function get_available_schedules() {
        return $this->repository->getAvailableSchedules();
    }
    
    /**
     * Get current schedule
     */
    public function get_current_schedule() {
        return get_option('jawneceny_external_cron_schedule', '24hour');
    }
    
    /**
     * Auto-register with cron-job.org on activation
     *
     * @param string $schedule Schedule to use for registration
     * @return bool Success status
     */
    public static function register_with_cronjoborg($schedule = '24hour') {
        $registerUseCase = DIContainer::get(RegisterExternalCronUseCase::class);
        $result = $registerUseCase->execute($schedule);
        return $result['success'];
    }

    /**
     * Auto-unregister on deactivation
     */
    public static function unregister_from_cronjoborg() {
        $unregisterUseCase = DIContainer::get(UnregisterExternalCronUseCase::class);
        $result = $unregisterUseCase->execute();
        return $result['success'];
    }
    
    /**
     * Check if external cron is active
     */
    public static function is_external_cron_enabled() {
        return get_option('jawneceny_external_cron_enabled') && get_option('jawneceny_cronjoborg_job_id');
    }
    
    /**
     * Get external cron status for admin
     */
    public static function get_status() {
        return [
            'enabled' => self::is_external_cron_enabled(),
            'job_id' => get_option('jawneceny_cronjoborg_job_id'),
            'schedule' => get_option('jawneceny_external_cron_schedule', '24hour'),
            'endpoint' => get_site_url() . '/wp-json/ujc/v1/external-cron'
        ];
    }
}