<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Automatyczny Generator XML/CSV zgodny z wymaganiami ustawy o jawności cen
 * Implementuje pełną specyfikację dane.gov.pl z cyklicznym działaniem
 */
class UJC_Automated_Generator {
    
    private $settings_repository;
    
    public function __construct() {
        $this->settings_repository = new SettingsRepository();
        
        // WordPress cron removed - using External Cron (cron-job.org) instead
        // Manual generation still available via generate_files_manual()
    }
    
    // All WordPress cron methods removed - replaced by External Cron (cron-job.org)
    
    /**
     * Dodaje switch on/off dla klientów
     */
    public function is_automation_enabled() {
        return $this->settings_repository->isAutomationEnabled();
    }
    
    public function set_automation_enabled($enabled) {
        $this->settings_repository->setAutomationEnabled($enabled);
    }
    
    // WordPress cron methods removed - External Cron (cron-job.org) handles scheduling
    
    const HISTORY_LIMIT = 50;
    
    /**
     * Dodaje wpis do historii generowania
     */
    public function add_to_history($status, $message) {
        $history = $this->settings_repository->getGenerationHistory();
        
        // Dodaj nowy wpis
        $history[] = [
            'timestamp' => time(),
            'status' => $status,
            'message' => $message
        ];
        
        // Ogranicz historię do ostatnich wpisów
        if (count($history) > self::HISTORY_LIMIT) {
            $history = array_slice($history, -self::HISTORY_LIMIT);
        }
        
        $this->settings_repository->setGenerationHistory($history);
    }
    
    /**
     * Publiczna metoda do generowania plików - używana przez wszystkie komponenty
     */
    public function generate_files_manual() {
        // Wywołaj UseCase do generowania
        $result = GenerateFilesUseCase::execute();
        
        // Zapisz status
        if ($result['success']) {
            $this->settings_repository->setLastGenerationStatus('success');
            $this->settings_repository->setLastGenerationTime();
            $this->add_to_history('success', 'Pliki wygenerowane pomyślnie');
            error_log('UJC: Pliki wygenerowane ręcznie o ' . date('Y-m-d H:i:s'));
        } else {
            $this->settings_repository->setLastGenerationStatus($result['error']);
            $this->settings_repository->setLastGenerationTime();
            $this->add_to_history('error', $result['error']);
            error_log('UJC: Błąd generowania: ' . $result['error']);
        }
        
        return $result;
    }
    
}