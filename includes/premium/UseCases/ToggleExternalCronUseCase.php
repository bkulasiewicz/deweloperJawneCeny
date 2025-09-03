<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Toggle External Cron (cron-job.org) UseCase
 * Handles registration/unregistration with cron-job.org and validation
 */
class ToggleExternalCronUseCase {
    
    private $registerUseCase;
    private $unregisterUseCase;
    private $developerRepository;
    private $investmentRepository;  
    private $resourceRepository;
    
    public function __construct() {
        $this->registerUseCase = new RegisterExternalCronUseCase();
        $this->unregisterUseCase = new UnregisterExternalCronUseCase();
        $this->developerRepository = new DeveloperRepository();
        $this->investmentRepository = new InvestmentRepository();
        $this->resourceRepository = new ResourceRepository();
    }
    
    /**
     * Execute external cron toggle
     * 
     * @param bool $enable True to enable, false to disable
     * @param string $schedule Schedule key for activation (only used when enabling)
     * @return array ['success' => bool, 'message' => string]
     */
    public function execute($enable, $schedule = '24hour') {
        try {
            
            // Validate prerequisites before enabling
            if ($enable) {
                $validation_result = $this->validateBeforeEnable();
                if (!$validation_result['success']) {
                    error_log("UJC ToggleExternalCronUseCase: validation failed - " . $validation_result['message']);
                    return $validation_result;
                }
            }
            
            if ($enable) {
                // Enable external cron - register with cron-job.org using provided schedule
                $result = $this->registerUseCase->execute($schedule);
                
                return $result;
            } else {
                // Disable external cron - unregister from cron-job.org
                $result = $this->unregisterUseCase->execute();
                
                return $result;
            }
            
        } catch (Exception $e) {
            error_log('UJC ToggleExternalCronUseCase Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Błąd serwera: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate prerequisites before enabling external cron
     * 
     * @return array ['success' => bool, 'message' => string]
     */
    private function validateBeforeEnable() {
        $errors = [];
        
        // Check if developer data exists
        $developer = $this->developerRepository->read();
        if (!$developer) {
            $errors[] = 'Brak danych dewelopera. Uzupełnij dane w zakładce "Dane Dostawcy"';
        }
        
        // Check if investment data exists
        $investment = $this->investmentRepository->read();
        if (!$investment) {
            $errors[] = 'Brak danych inwestycji. Uzupełnij dane inwestycji';
        }
        
        // Check if resources exist
        $resources = $this->resourceRepository->readAll();
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
                'message' => '❌ Nie można aktywować External Cron:<br>• ' . implode('<br>• ', $errors)
            ];
        }
        
        return ['success' => true];
    }
    
    /**
     * Get current external cron status for UI
     * 
     * @return array Status information
     */
    public static function getStatus() {
        return [
            'enabled' => ExternalCronController::is_external_cron_enabled(),
            'job_id' => get_option('ujc_cronjoborg_job_id'),
            'endpoint' => get_site_url() . '/wp-json/ujc/v1/external-cron'
        ];
    }
}