<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Update External Cron Schedule UseCase
 * Handles updating the schedule frequency for external cron via cron-job.org API
 */
class UpdateExternalCronScheduleUseCase {
    
    /**
     * Execute schedule update
     * 
     * @param string $schedule The schedule key (e.g., '1min', '15min', '1hour', '24hour')
     * @return array ['success' => bool, 'message' => string, 'data' => array|null]
     */
    public static function execute($schedule) {
        try {
            // Validate schedule parameter
            $schedule = sanitize_text_field($schedule);
            
            if (empty($schedule)) {
                return [
                    'success' => false,
                    'message' => 'Nie podano harmonogramu'
                ];
            }
            
            // Validate prerequisites
            $validation_result = self::validatePrerequisites($schedule);
            if (!$validation_result['success']) {
                return $validation_result;
            }
            
            // Get job ID
            $job_id = get_option('ujc_cronjoborg_job_id');
            
            // Get available schedules from repository
            $repository = new ExternalCronRepository();
            $available_schedules = $repository->getAvailableSchedules();
            
            // Update schedule via repository
            $schedule_config = $available_schedules[$schedule];
            $result = $repository->updateJobSchedule($job_id, $schedule_config);
            
            if ($result['success']) {
                // Save locally for reference
                update_option('ujc_external_cron_schedule', $schedule);
                
                return [
                    'success' => true,
                    'message' => 'Harmonogram zaktualizowany: ' . $available_schedules[$schedule]['interval_text'],
                    'data' => [
                        'schedule' => $schedule,
                        'job_id' => $job_id,
                        'interval_text' => $available_schedules[$schedule]['interval_text']
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Błąd podczas aktualizacji harmonogramu: ' . $result['message']
                ];
            }
            
        } catch (Exception $e) {
            error_log('UJC UpdateExternalCronScheduleUseCase Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Błąd serwera: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate prerequisites before updating schedule
     * 
     * @param string $schedule
     * @return array ['success' => bool, 'message' => string]
     */
    private static function validatePrerequisites($schedule) {
        $errors = [];
        
        // Check if external cron is enabled
        if (!ExternalCronController::is_external_cron_enabled()) {
            $errors[] = 'External Cron nie jest aktywny';
        }
        
        // Check if job ID exists
        $job_id = get_option('ujc_cronjoborg_job_id');
        if (!$job_id) {
            $errors[] = 'Brak zarejestrowanego job ID w cron-job.org';
        }
        
        // Check if schedule is valid
        $repository = new ExternalCronRepository();
        $available_schedules = $repository->getAvailableSchedules();
        if (!array_key_exists($schedule, $available_schedules)) {
            $errors[] = 'Nieprawidłowy harmonogram: ' . $schedule;
        }
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => '❌ Nie można zaktualizować harmonogramu:<br>• ' . implode('<br>• ', $errors)
            ];
        }
        
        return ['success' => true];
    }
    
    
    /**
     * Get current schedule status for UI
     * 
     * @return array Current schedule information
     */
    public static function getCurrentSchedule() {
        $repository = new ExternalCronRepository();
        $current_schedule = get_option('ujc_external_cron_schedule', '24hour');
        $available_schedules = $repository->getAvailableSchedules();
        
        return [
            'current' => $current_schedule,
            'available' => $available_schedules,
            'current_text' => $available_schedules[$current_schedule]['interval_text'] ?? 'Nieznany',
            'job_id' => get_option('ujc_cronjoborg_job_id'),
            'enabled' => ExternalCronController::is_external_cron_enabled()
        ];
    }
}