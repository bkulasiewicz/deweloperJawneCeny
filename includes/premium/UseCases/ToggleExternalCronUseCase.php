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
    
    public function __construct(
        RegisterExternalCronUseCase $registerUseCase,
        UnregisterExternalCronUseCase $unregisterUseCase,
        DeveloperRepository $developerRepository,
        InvestmentRepository $investmentRepository,
        ResourceRepository $resourceRepository
    ) {
        $this->registerUseCase = $registerUseCase;
        $this->unregisterUseCase = $unregisterUseCase;
        $this->developerRepository = $developerRepository;
        $this->investmentRepository = $investmentRepository;
        $this->resourceRepository = $resourceRepository;
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
            Logger::info("UJC ToggleExternalCronUseCase: Starting execute() with enable=" . ($enable ? 'true' : 'false') . ", schedule=" . $schedule);
            
            // Validate prerequisites before enabling
            if ($enable) {
                $validation_result = $this->validateBeforeEnable();
                if (!$validation_result['success']) {
                    Logger::error("UJC ToggleExternalCronUseCase: validation failed - " . $validation_result['message']);
                    return $validation_result;
                }
                Logger::info("UJC ToggleExternalCronUseCase: validation passed");
            }
            
            if ($enable) {
                // Enable external cron - register with cron-job.org using provided schedule
                Logger::info("UJC ToggleExternalCronUseCase: calling registerUseCase->execute()");
                $result = $this->registerUseCase->execute($schedule);
                Logger::info("UJC ToggleExternalCronUseCase: registerUseCase returned - success: " . ($result['success'] ? 'true' : 'false') . ", message: " . $result['message']);
                
                return $result;
            } else {
                // Disable external cron - unregister from cron-job.org
                Logger::info("UJC ToggleExternalCronUseCase: calling unregisterUseCase->execute()");
                $result = $this->unregisterUseCase->execute();
                Logger::info("UJC ToggleExternalCronUseCase: unregisterUseCase returned - success: " . ($result['success'] ? 'true' : 'false') . ", message: " . $result['message']);
                
                return $result;
            }
            
        } catch (Exception $e) {
            Logger::error('UJC ToggleExternalCronUseCase Error: ' . $e->getMessage());
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
        
        
        $upload_dir = wp_upload_dir();
        $ujc_dir = $upload_dir['basedir'] . '/ujc-data';
        global $wp_filesystem;
        if (!$wp_filesystem->is_dir($ujc_dir) || !$wp_filesystem->is_writable($ujc_dir)) {
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
            'job_id' => get_option('jawneceny_cronjoborg_job_id'),
            'endpoint' => get_site_url() . '/wp-json/ujc/v1/external-cron'
        ];
    }
}