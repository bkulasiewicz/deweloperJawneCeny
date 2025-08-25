<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Klasa obsługująca import zasobów z plików CSV
 * Format: nr_lokalu;rodzaj_nieruchomosci;powierzchnia_uzytkowa;cena_m2;cena_calkowita
 */
class UJC_Resource_Importer {
    
    public function __construct() {
        add_action('wp_ajax_ujc_import_resources', [$this, 'ajax_import_resources']);
    }
    
    /**
     * AJAX: Importuje zasobów z pliku CSV
     */
    public function ajax_import_resources() {
        check_ajax_referer('ujc_admin_nonce', 'ujc_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
        }
        
        // Sprawdź czy plik został wysłany
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('Błąd podczas przesyłania pliku');
        }
        
        $file = $_FILES['import_file'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Sprawdź format pliku - tylko CSV
        if ($file_extension !== 'csv') {
            wp_send_json_error('Obsługiwany tylko format CSV');
        }
        
        try {
            $result = $this->import_from_csv($file['tmp_name']);
            
            if ($result['imported'] > 0) {
                $message = "Zaimportowano {$result['imported']} zasobów";
                if ($result['updated'] > 0) {
                    $message .= " (zaktualizowano {$result['updated']})";
                }
                
                wp_send_json_success([
                    'imported' => $result['imported'],
                    'updated' => $result['updated'],
                    'message' => $message
                ]);
            } else {
                wp_send_json_error('Nie znaleziono żadnych danych do zaimportowania');
            }
            
        } catch (Exception $e) {
            error_log('UJC Import Error: ' . $e->getMessage());
            wp_send_json_error('Błąd importu: ' . $e->getMessage());
        }
    }
    
    /**
     * Importuje dane z pliku CSV
     * Format: nr_lokalu;rodzaj_nieruchomosci;powierzchnia_uzytkowa;cena_m2;cena_calkowita
     */
    private function import_from_csv($file_path) {
        $imported = 0;
        $updated = 0;
        
        if (($handle = fopen($file_path, 'r')) !== FALSE) {
            // Wykryj separator
            $first_line = fgets($handle);
            rewind($handle);
            $separator = $this->detect_csv_separator($first_line);
            
            // Pomiń nagłówki (pierwsza linia)
            $headers = fgetcsv($handle, 1000, $separator);
            
            while (($data = fgetcsv($handle, 1000, $separator)) !== FALSE) {
                if (count($data) < 4) continue; // Minimum 4 kolumny wymagane
                
                // Wyczyść i zwaliduj dane
                $resource_data = $this->clean_csv_row($data);
                
                if (!$resource_data) {
                    continue; // Pomiń nieprawidłowe wiersze
                }
                
                // Sprawdź czy zasób już istnieje (po nr_lokalu)
                if ($this->resource_exists($resource_data['nr_lokalu'])) {
                    // Aktualizuj istniejący
                    $this->update_existing_resource($resource_data);
                    $updated++;
                } else {
                    // Dodaj nowy zasób
                    $this->create_new_resource($resource_data);
                    $imported++;
                }
            }
            
            fclose($handle);
        }
        
        return [
            'imported' => $imported,
            'updated' => $updated,
            'total' => $imported + $updated
        ];
    }
    
    /**
     * Wykrywa separator CSV
     */
    private function detect_csv_separator($line) {
        $separators = [';', ',', '\t'];
        $separator_counts = [];
        
        foreach ($separators as $sep) {
            $separator_counts[$sep] = substr_count($line, $sep);
        }
        
        // Zwróć separator z największą liczbą wystąpień
        return array_search(max($separator_counts), $separator_counts) ?: ';';
    }
    
    /**
     * Czyści i waliduje wiersz CSV
     * Format: nr_lokalu;rodzaj_nieruchomosci;powierzchnia_uzytkowa;cena_m2;cena_calkowita
     */
    private function clean_csv_row($data) {
        $nr_lokalu = trim($data[0]);
        $rodzaj = trim($data[1]) ?: 'Lokal mieszkalny';
        $powierzchnia = $this->clean_decimal($data[2] ?? '');
        $cena_m2 = $this->clean_decimal($data[3] ?? '');
        $cena_calkowita = isset($data[4]) && !empty($data[4]) ? $this->clean_decimal($data[4]) : null;
        
        // Walidacja podstawowych pól
        if (empty($nr_lokalu) || $powierzchnia <= 0 || $cena_m2 <= 0) {
            return false;
        }
        
        // Walidacja rodzaju nieruchomości
        if (!in_array($rodzaj, ['Lokal mieszkalny', 'Dom jednorodzinny'])) {
            $rodzaj = 'Lokal mieszkalny'; // Domyślne
        }
        
        return [
            'nr_lokalu' => $nr_lokalu,
            'rodzaj_nieruchomosci' => $rodzaj,
            'powierzchnia_uzytkowa' => $powierzchnia,
            'cena_m2' => $cena_m2,
            'cena_calkowita' => $cena_calkowita
        ];
    }
    
    /**
     * Czyści wartości dziesiętne (usuwa spacje, zamienia przecinki)
     */
    private function clean_decimal($value) {
        if (empty($value)) return 0;
        
        // Usuń białe znaki i inne znaki niż cyfry, przecinki, kropki
        $clean = preg_replace('/[^\d,.-]/', '', trim($value));
        
        // Zamień przecinek na kropkę
        $clean = str_replace(',', '.', $clean);
        
        return floatval($clean);
    }
    
    /**
     * Sprawdza czy zasób istnieje po numerze lokalu
     */
    private function resource_exists($nr_lokalu) {
        global $wpdb;
        $table = $wpdb->prefix . 'ujc_resources';
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE nr_lokalu = %s",
            $nr_lokalu
        ));
        
        return $exists > 0;
    }
    
    /**
     * Aktualizuje istniejący zasób
     */
    private function update_existing_resource($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'ujc_resources';
        
        $update_data = [
            'rodzaj_nieruchomosci' => $data['rodzaj_nieruchomosci'],
            'powierzchnia_uzytkowa' => $data['powierzchnia_uzytkowa'],
            'cena_m2' => $data['cena_m2'],
            'data_cena_m2' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        $update_format = ['%s', '%f', '%f', '%s', '%s'];
        
        if ($data['cena_calkowita'] !== null && $data['cena_calkowita'] > 0) {
            $update_data['cena_calkowita'] = $data['cena_calkowita'];
            $update_data['data_cena_calkowita'] = current_time('mysql');
            $update_format[] = '%f';
            $update_format[] = '%s';
        }
        
        $wpdb->update(
            $table,
            $update_data,
            ['nr_lokalu' => $data['nr_lokalu']],
            $update_format,
            ['%s']
        );
    }
    
    /**
     * Tworzy nowy zasób
     */
    private function create_new_resource($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'ujc_resources';
        
        $resource_data = [
            'rodzaj_nieruchomosci' => $data['rodzaj_nieruchomosci'],
            'nr_lokalu' => $data['nr_lokalu'],
            'powierzchnia_uzytkowa' => $data['powierzchnia_uzytkowa'],
            'cena_m2' => $data['cena_m2'],
            'data_cena_m2' => current_time('mysql'),
            'status' => 'dostepny',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        $format = ['%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s'];
        
        if ($data['cena_calkowita'] !== null && $data['cena_calkowita'] > 0) {
            $resource_data['cena_calkowita'] = $data['cena_calkowita'];
            $resource_data['data_cena_calkowita'] = current_time('mysql');
            $format[] = '%f';
            $format[] = '%s';
        }
        
        $wpdb->insert($table, $resource_data, $format);
    }
}