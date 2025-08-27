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
    private $intervals = [
        '1min' => 60,
        '15min' => 900,
        '1hour' => 3600,
        '24hours' => 86400
    ];
    
    public function __construct() {
        $this->settings_repository = new SettingsRepository();
        
        add_action('init', [$this, 'init_automation']);
        add_filter('cron_schedules', [$this, 'add_custom_cron_schedules']);
    }
    
    /**
     * Inicjalizuje cykliczną automatyzację - domyślnie północ UTC dla klientów
     */
    public function init_automation() {
        $current_interval = $this->settings_repository->getGenerationInterval();
        
        if (!wp_next_scheduled('ujc_generate_files_cycle')) {
            $next_run = $this->calculate_next_utc_midnight();
            wp_schedule_event($next_run, $this->get_cron_schedule($current_interval), 'ujc_generate_files_cycle');
        }
        
        add_action('ujc_generate_files_cycle', [$this, 'generate_all_files']);
    }
    
    /**
     * Oblicza czas następnej północy UTC dla klientów produkcyjnych
     */
    public function calculate_next_utc_midnight() {
        $utc_timezone = new DateTimeZone('UTC');
        $midnight_utc = new DateTime('tomorrow midnight', $utc_timezone);
        return $midnight_utc->getTimestamp();
    }
    
    /**
     * Ustawia interwał generowania dla dev console
     */
    public function ajax_set_interval() {
        if (!current_user_can('manage_options')) {
            wp_die('Brak uprawnień');
        }
        
        $interval = sanitize_text_field($_POST['interval'] ?? '24hours');
        
        if (!array_key_exists($interval, $this->intervals)) {
            wp_die('Nieprawidłowy interwał');
        }
        
        $this->settings_repository->setGenerationInterval($interval);
        
        wp_clear_scheduled_hook('ujc_generate_files_cycle');
        
        $next_run = ($interval === '24hours') ? 
            $this->calculate_next_utc_midnight() : 
            time();
            
        wp_schedule_event($next_run, $this->get_cron_schedule($interval), 'ujc_generate_files_cycle');
        
        wp_send_json_success(['message' => 'Interwał ustawiony: ' . $interval]);
    }
    
    /**
     * Konwertuje interwał na schedule dla WordPress
     */
    public function get_cron_schedule($interval) {
        switch($interval) {
            case '1min':
                return 'ujc_every_minute';
            case '15min':
                return 'ujc_every_15_minutes';
            case '1hour':
                return 'hourly';
            case '24hours':
                return 'ujc_daily_utc';
            default:
                return 'ujc_daily_utc';
        }
    }
    
    /**
     * Dodaje niestandardowe harmonogramy cron
     */
    public function add_custom_cron_schedules($schedules) {
        $schedules['ujc_every_minute'] = [
            'interval' => 60,
            'display' => 'Co minutę'
        ];
        
        $schedules['ujc_every_15_minutes'] = [
            'interval' => 900,
            'display' => 'Co 15 minut'
        ];
        
        $schedules['ujc_daily_utc'] = [
            'interval' => 86400,
            'display' => 'Codziennie o północy UTC'
        ];
        
        return $schedules;
    }
    
    /**
     * Dodaje switch on/off dla klientów
     */
    public function is_automation_enabled() {
        return $this->settings_repository->isAutomationEnabled();
    }
    
    public function set_automation_enabled($enabled) {
        $this->settings_repository->setAutomationEnabled($enabled);
    }
    
    /**
     * Zarządzaj cron job na podstawie stanu automatyzacji
     */
    public function toggle_cron_job($enabled) {
        if ($enabled) {
            // Włącz automatyzację - dodaj cron job jeśli nie istnieje
            if (!wp_next_scheduled('ujc_generate_files_cycle')) {
                $current_interval = $this->settings_repository->getGenerationInterval();
                
                $next_run = ($current_interval === '24hours') ? 
                    $this->calculate_next_utc_midnight() : 
                    time();
                    
                wp_schedule_event($next_run, $this->get_cron_schedule($current_interval), 'ujc_generate_files_cycle');
            }
        } else {
            // Wyłącz automatyzację - usuń cron job
            wp_clear_scheduled_hook('ujc_generate_files_cycle');
        }
    }
    
    /**
     * Główna metoda - generuje wszystkie wymagane pliki automatycznie
     */
    public function generate_all_files() {
        if (!$this->is_automation_enabled()) {
            $this->settings_repository->setLastGenerationStatus('Automatyzacja wyłączona');
            $this->settings_repository->setLastGenerationTime();
            $this->add_to_history('disabled', 'Automatyzacja wyłączona');
            error_log('UJC: Automatyczne generowanie wyłączone');
            return false;
        }
        
        // Wywołaj UseCase do generowania
        $result = GenerateFilesUseCase::execute();
        
        // Zapisz status
        if ($result['success']) {
            $this->settings_repository->setLastGenerationStatus('success');
            $this->settings_repository->setLastGenerationTime();
            $this->add_to_history('success', 'Pliki wygenerowane pomyślnie');
            error_log('UJC: Pliki wygenerowane o ' . date('Y-m-d H:i:s'));
        } else {
            $this->settings_repository->setLastGenerationStatus($result['error']);
            $this->settings_repository->setLastGenerationTime();
            $this->add_to_history('error', $result['error']);
            error_log('UJC: Błąd generowania: ' . $result['error']);
        }
        
        return $result;
    }
    
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