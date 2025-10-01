<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register External Cron UseCase
 * Handles registration with cron-job.org service
 */
class RegisterExternalCronUseCase {
    
    private $repository;
    private $developerRepository;
    private $investmentRepository;
    private $resourceRepository;
    
    public function __construct(
        ExternalCronRepository $repository,
        DeveloperRepository $developerRepository,
        InvestmentRepository $investmentRepository,
        ResourceRepository $resourceRepository
    ) {
        $this->repository = $repository;
        $this->developerRepository = $developerRepository;
        $this->investmentRepository = $investmentRepository;
        $this->resourceRepository = $resourceRepository;
    }
    
    /**
     * Execute external cron registration
     * 
     * @param string $schedule Schedule key (e.g., '1min', '15min', '1hour', '24hour')
     * @return array ['success' => bool, 'message' => string, 'job_id' => string|null]
     */
    public function execute($schedule = '24hour') {
        try {
            // Validate prerequisites
            $validation_result = $this->validatePrerequisites();
            if (!$validation_result['success']) {
                return $validation_result;
            }
            
            // Prepare job data
            $site_url = get_site_url();
            $endpoint = $site_url . '/wp-json/ujc/v1/external-cron';
            $domain = wp_parse_url($site_url, PHP_URL_HOST);
            
            // Get plugin version and license ID
            $plugin_version = defined('JAWNECENY_VERSION') ? JAWNECENY_VERSION : '2.0.10';
            $license_id = 'DJC8N5Q1WZ';
            
            // Create title with metadata: domain | version | license
            $title = $domain . ' | v' . $plugin_version . ' | ' . $license_id;
            
            // Get schedule configuration
            $available_schedules = $this->repository->getAvailableSchedules();
            
            if (!array_key_exists($schedule, $available_schedules)) {
                return [
                    'success' => false,
                    'message' => 'Nieprawidłowy harmonogram: ' . $schedule,
                    'job_id' => null
                ];
            }
            
            $schedule_config = $available_schedules[$schedule];
            
            // Register with cron-job.org
            $result = $this->repository->createJob($endpoint, $schedule_config, $title);
            
            if ($result['success']) {
                // Save configuration locally
                update_option('jawneceny_cronjoborg_job_id', $result['job_id']);
                update_option('jawneceny_external_cron_enabled', true);
                update_option('jawneceny_external_cron_schedule', $schedule);
                
                // Disable WordPress cron
                wp_clear_scheduled_hook('ujc_generate_files_cycle');
                
                Logger::success("UJC: Registered with cron-job.org, Job ID: {$result['job_id']}, Schedule: {$schedule}");
                
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
            Logger::error('UJC RegisterExternalCronUseCase Error: ' . $e->getMessage());
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
    private function validatePrerequisites() {
        global $wp_filesystem;
        $errors = [];
        
        // Check if external cron is already enabled
        if (ExternalCronController::is_external_cron_enabled()) {
            $errors[] = 'External Cron jest już aktywny';
        }
        
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
        if (!$wp_filesystem->is_dir($ujc_dir) || !$wp_filesystem->is_writable($ujc_dir)) {
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