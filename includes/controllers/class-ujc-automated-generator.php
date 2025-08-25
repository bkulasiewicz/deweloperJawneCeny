<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Automatyczny Generator XML/CSV zgodny z wymaganiami ustawy o jawności cen
 * Implementuje pełną specyfikację dane.gov.pl z cyklicznym działaniem
 */
class UJC_Automated_Generator {
    
    private $csv_generator;
    private $xml_generator;
    private $intervals = [
        '1min' => 60,
        '15min' => 900,
        '1hour' => 3600,
        '24hours' => 86400
    ];
    
    public function __construct() {
        $this->csv_generator = new UJC_CSV_Generator();
        $this->xml_generator = new UJC_XML_Generator();
        
        add_action('init', [$this, 'init_automation']);
        add_filter('wp_ajax_ujc_set_interval', [$this, 'ajax_set_interval']);
        add_filter('wp_ajax_nopriv_ujc_set_interval', [$this, 'ajax_set_interval']);
        add_filter('cron_schedules', [$this, 'add_custom_cron_schedules']);
    }
    
    /**
     * Inicjalizuje cykliczną automatyzację - domyślnie północ UTC dla klientów
     */
    public function init_automation() {
        $current_interval = get_option('ujc_generation_interval', '24hours');
        
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
        $current_interval = get_option('ujc_generation_interval', '24hours');
        
        if ($current_interval === '24hours') {
            $utc_timezone = new DateTimeZone('UTC');
            $now_utc = new DateTime('now', $utc_timezone);
            $midnight_utc = new DateTime('tomorrow midnight', $utc_timezone);
            return $midnight_utc->getTimestamp();
        }
        
        return time();
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
        
        update_option('ujc_generation_interval', $interval);
        
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
        return get_option('ujc_automation_enabled', true);
    }
    
    public function set_automation_enabled($enabled) {
        update_option('ujc_automation_enabled', (bool)$enabled);
    }
    
    /**
     * Główna metoda - generuje wszystkie wymagane pliki automatycznie
     * Cykliczne działanie z możliwością wyłączenia przez klienta
     */
    public function generate_all_files() {
        if (!$this->is_automation_enabled()) {
            update_option('ujc_last_generation_status', 'Automatyzacja wyłączona przez użytkownika');
            update_option('ujc_last_generation_time', time());
            
            // Dodaj informację o wyłączonej automatyzacji
            $this->add_to_history('disabled', 'automatyczne', 'Automatyzacja wyłączona przez użytkownika');
            
            error_log('UJC: Automatyczne generowanie wyłączone przez klienta');
            return false;
        }
        try {
            $csv_result = $this->csv_generator->generate_daily_csv();
            if (!$csv_result) {
                throw new Exception('Błąd generowania CSV');
            }
            
            $xml_result = $this->xml_generator->generate_xml($csv_result['url']);
            if (!$xml_result) {
                throw new Exception('Błąd generowania XML');
            }
            
            // Zapisz status sukcesu
            update_option('ujc_last_generation_status', 'success');
            update_option('ujc_last_generation_time', time());
            
            // Dodaj do historii
            $this->add_to_history('success', 'automatyczne', 'Pliki wygenerowane pomyślnie');
            
            error_log('UJC: Pliki wygenerowane automatycznie o ' . date('Y-m-d H:i:s'));
            
            return [
                'success' => true,
                'csv' => $csv_result,
                'xml' => $xml_result,
                'message' => 'Pliki wygenerowane automatycznie'
            ];
            
        } catch (Exception $e) {
            // Przygotuj szczegółowy opis błędu
            $detailed_error = $this->get_detailed_error_message($e);
            
            // Zapisz status błędu
            update_option('ujc_last_generation_status', $detailed_error);
            update_option('ujc_last_generation_time', time());
            
            // Dodaj błąd do historii
            $this->add_to_history('error', 'automatyczne', $detailed_error);
            
            error_log('UJC: Błąd automatycznego generowania: ' . $detailed_error);
            return new WP_Error('generation_failed', 'Błąd generowania: ' . $detailed_error);
        }
    }
    
    /**
     * Dodaje wpis do historii generowania
     */
    public function add_to_history($status, $type, $message) {
        $history = get_option('ujc_generation_history', []);
        
        // Dodaj nowy wpis
        $history[] = [
            'timestamp' => time(),
            'status' => $status,
            'type' => $type,
            'message' => $message
        ];
        
        // Ogranicz historię do 50 ostatnich wpisów
        if (count($history) > 50) {
            $history = array_slice($history, -50);
        }
        
        update_option('ujc_generation_history', $history);
    }
    
    /**
     * Publiczna metoda do generowania plików - używana przez wszystkie komponenty
     */
    public function generate_files_manual($source = 'manual') {
        try {
            $csv_result = $this->csv_generator->generate_daily_csv();
            if (!$csv_result) {
                throw new Exception('Błąd generowania CSV');
            }
            
            $xml_result = $this->xml_generator->generate_xml($csv_result['url']);
            if (!$xml_result) {
                throw new Exception('Błąd generowania XML');
            }
            
            // Zapisz status sukcesu
            update_option('ujc_last_generation_status', 'success (' . $source . ')');
            update_option('ujc_last_generation_time', time());
            
            // Dodaj do historii
            $this->add_to_history('success', $source, 'Pliki wygenerowane pomyślnie');
            
            return [
                'success' => true,
                'message' => 'Pliki zostały wygenerowane i opublikowane pomyślnie',
                'csv' => $csv_result,
                'xml' => $xml_result
            ];
            
        } catch (Exception $e) {
            // Przygotuj szczegółowy opis błędu
            $detailed_error = $this->get_detailed_error_message($e);
            
            // Zapisz status błędu
            update_option('ujc_last_generation_status', $detailed_error . ' (' . $source . ')');
            update_option('ujc_last_generation_time', time());
            
            // Dodaj błąd do historii
            $this->add_to_history('error', $source, $detailed_error);
            
            error_log('UJC: Błąd generowania (' . $source . '): ' . $detailed_error);
            
            return [
                'success' => false,
                'error' => $detailed_error
            ];
        }
    }
    
    /**
     * Tworzy szczegółowy opis błędu na podstawie wyjątku
     */
    private function get_detailed_error_message(Exception $e) {
        $message = $e->getMessage();
        
        // Sprawdź czy to błąd generowania CSV
        if (strpos($message, 'Błąd generowania CSV') !== false) {
            $developer_repo = new UJC_Developer_Repository();
            $developer = $developer_repo->read();
            $price_history_repo = new UJC_Price_History_Repository();
            $properties = $price_history_repo->readForExport();
            
            if (!$developer) {
                return "Błąd CSV: Brak danych dewelopera. Uzupełnij dane dewelopera w zakładce 'Dane Dostawcy'.";
            }
            
            if (empty($properties)) {
                return "Błąd CSV: Brak nieruchomości do eksportu. Dodaj nieruchomości w zakładce 'Zasoby'.";
            }
            
            $upload_dir = wp_upload_dir();
            $ujc_dir = $upload_dir['basedir'] . '/ujc-data';
            
            if (!is_dir($ujc_dir) && !wp_mkdir_p($ujc_dir)) {
                return "Błąd CSV: Nie można utworzyć katalogu " . $ujc_dir . ". Sprawdź uprawnienia do zapisu.";
            }
            
            if (!is_writable($ujc_dir)) {
                return "Błąd CSV: Brak uprawnień do zapisu w katalogu " . $ujc_dir . ". Sprawdź uprawnienia plików.";
            }
            
            return "Błąd CSV: Nieznany błąd podczas tworzenia pliku. Sprawdź logi serwera.";
        }
        
        // Sprawdź czy to błąd generowania XML
        if (strpos($message, 'Błąd generowania XML') !== false) {
            $upload_dir = wp_upload_dir();
            $ujc_dir = $upload_dir['basedir'] . '/ujc-data';
            
            if (!is_dir($ujc_dir)) {
                return "Błąd XML: Katalog " . $ujc_dir . " nie istnieje.";
            }
            
            if (!is_writable($ujc_dir)) {
                return "Błąd XML: Brak uprawnień do zapisu w katalogu " . $ujc_dir . ". Sprawdź uprawnienia plików.";
            }
            
            return "Błąd XML: Nieznany błąd podczas tworzenia pliku XML lub MD5. Sprawdź logi serwera.";
        }
        
        // Sprawdź błędy związane z uprawnieniami
        if (strpos($message, 'Permission denied') !== false || strpos($message, 'permission') !== false) {
            return "Błąd uprawnień: " . $message . ". Sprawdź uprawnienia plików i katalogów.";
        }
        
        // Sprawdź błędy dyskowe
        if (strpos($message, 'No space left') !== false || strpos($message, 'disk full') !== false) {
            return "Błąd dysku: Brak miejsca na dysku. " . $message;
        }
        
        // Sprawdź błędy PHP
        if (strpos($message, 'Fatal error') !== false) {
            return "Błąd PHP: " . $message . ". Sprawdź konfigurację PHP i logi błędów.";
        }
        
        // Sprawdź błędy bazy danych
        if (strpos($message, 'database') !== false || strpos($message, 'MySQL') !== false) {
            return "Błąd bazy danych: " . $message . ". Sprawdź połączenie z bazą danych.";
        }
        
        // Domyślny szczegółowy opis
        return "Błąd: " . $message . " (Czas: " . date('Y-m-d H:i:s') . ")";
    }
    
}