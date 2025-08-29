<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Unregister External Cron UseCase
 * Handles unregistration from cron-job.org service
 */
class UnregisterExternalCronUseCase {
    
    /**
     * Execute external cron unregistration
     * 
     * @return array ['success' => bool, 'message' => string]
     */
    public static function execute() {
        try {
            $job_id = get_option('ujc_cronjoborg_job_id');
            
            if (!$job_id) {
                // Clean up local settings even if no job ID
                self::cleanupLocalSettings();
                return [
                    'success' => true,
                    'message' => '✅ External Cron został dezaktywowany (brak job ID do usunięcia)'
                ];
            }
            
            // Unregister from cron-job.org
            $repository = new ExternalCronRepository();
            $result = $repository->deleteJob($job_id);
            
            // Clean up local settings regardless of API result
            self::cleanupLocalSettings();
            
            if ($result['success']) {
                error_log("UJC: Unregistered from cron-job.org, Job ID: {$job_id}");
                return [
                    'success' => true,
                    'message' => '✅ External Cron został pomyślnie dezaktywowany'
                ];
            } else {
                // Log error but still return success since local cleanup was done
                error_log('UJC: Failed to unregister from cron-job.org: ' . $result['message']);
                return [
                    'success' => true,
                    'message' => '✅ External Cron został dezaktywowany lokalnie (błąd usuwania z cron-job.org)'
                ];
            }
            
        } catch (Exception $e) {
            error_log('UJC UnregisterExternalCronUseCase Error: ' . $e->getMessage());
            // Still try to clean up local settings
            self::cleanupLocalSettings();
            return [
                'success' => false,
                'message' => 'Błąd serwera: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Clean up local WordPress options
     */
    private static function cleanupLocalSettings() {
        delete_option('ujc_cronjoborg_job_id');
        delete_option('ujc_external_cron_enabled');
        delete_option('ujc_external_cron_schedule');
        
        // Clear any related transients
        delete_transient('ujc_cronjoborg_ips');
        delete_transient('ujc_external_cron_rate');
    }
}