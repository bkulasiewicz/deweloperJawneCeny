<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register External Cron UseCase
 * Handles registration with cron-job.org service
 */
class RegisterExternalCronUseCase {
    
    /**
     * Execute external cron registration
     * 
     * @param string $schedule Schedule key (e.g., '1min', '15min', '1hour', '24hour')
     * @return array ['success' => bool, 'message' => string, 'job_id' => string|null]
     */
    public static function execute($schedule = '24hour') {
        try {
            // Validate prerequisites
            $validation_result = self::validatePrerequisites();
            if (!$validation_result['success']) {
                return $validation_result;
            }
            
            // Prepare job data
            $site_url = get_site_url();
            $endpoint = $site_url . '/wp-json/ujc/v1/external-cron';
            $domain = parse_url($site_url, PHP_URL_HOST);
            $title = 'UJC-' . $domain;
            
            // Get schedule configuration
            $repository = new ExternalCronRepository();
            $available_schedules = $repository->getAvailableSchedules();
            
            if (!array_key_exists($schedule, $available_schedules)) {
                return [
                    'success' => false,
                    'message' => 'Nieprawidłowy harmonogram: ' . $schedule,
                    'job_id' => null
                ];
            }
            
            $schedule_config = $available_schedules[$schedule];
            
            // Register with cron-job.org
            $result = $repository->createJob($endpoint, $schedule_config, $title);
            
            if ($result['success']) {
                // Save configuration locally
                update_option('ujc_cronjoborg_job_id', $result['job_id']);
                update_option('ujc_external_cron_enabled', true);
                update_option('ujc_external_cron_schedule', $schedule);
                
                // Disable WordPress cron
                wp_clear_scheduled_hook('ujc_generate_files_cycle');
                
                error_log("UJC: Registered with cron-job.org, Job ID: {$result['job_id']}, Schedule: {$schedule}");
                
                return [
                    'success' => true,
                    'message' => '✅ External Cron został zarejestrowany! Harmonogram: ' . $schedule_config['interval_text'],
                    'job_id' => $result['job_id']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => '❌ Nie udało się zarejestrować w cron-job.org: ' . $result['message'],
                    'job_id' => null
                ];
            }
            
        } catch (Exception $e) {
            error_log('UJC RegisterExternalCronUseCase Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Błąd serwera: ' . $e->getMessage(),
                'job_id' => null
            ];
        }
    }
    
    /**
     * Validate prerequisites before registration
     * 
     * @return array ['success' => bool, 'message' => string]
     */
    private static function validatePrerequisites() {
        $errors = [];
        
        // Check if external cron is already enabled
        if (ExternalCronController::is_external_cron_enabled()) {
            $errors[] = 'External Cron jest już aktywny';
        }
        
        // Check if developer data exists
        $developer_repository = new DeveloperRepository();
        $developer = $developer_repository->read();
        if (!$developer) {
            $errors[] = 'Brak danych dewelopera. Uzupełnij dane w zakładce "Dane Dostawcy"';
        }
        
        // Check if investment data exists
        $investment_repository = new InvestmentRepository();
        $investment = $investment_repository->read();
        if (!$investment) {
            $errors[] = 'Brak danych inwestycji. Uzupełnij dane inwestycji';
        }
        
        // Check if resources exist
        $resource_repository = new ResourceRepository();
        $resources = $resource_repository->readAll();
        if (empty($resources)) {
            $errors[] = 'Brak nieruchomości. Dodaj przynajmniej jedną nieruchomość w zakładce "Zasoby"';
        }
        
        // Check file directory permissions
        $upload_dir = wp_upload_dir();
        $ujc_dir = $upload_dir['basedir'] . '/ujc-data';
        if (!is_dir($ujc_dir) || !is_writable($ujc_dir)) {
            $errors[] = 'Katalog plików nie istnieje lub nie ma uprawnień do zapisu';
        }
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => '❌ Nie można zarejestrować External Cron:<br>• ' . implode('<br>• ', $errors)
            ];
        }
        
        return ['success' => true];
    }
}