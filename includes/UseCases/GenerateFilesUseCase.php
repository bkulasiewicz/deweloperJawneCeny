<?php

if (!defined('ABSPATH')) {
    exit;
}

class GenerateFilesUseCase {
    
    private static $csv_generator;
    private static $xml_generator;
    
    /**
     * Główna metoda generująca pliki CSV i XML
     */
    public static function execute() {
        try {
            // Inicjalizacja generatorów
            if (!self::$csv_generator) {
                self::$csv_generator = new UJC_CSV_Generator();
            }
            if (!self::$xml_generator) {
                self::$xml_generator = new UJC_XML_Generator();
            }
            
            // Walidacja przed generowaniem
            $validation_errors = self::validate_before_generation();
            if (!empty($validation_errors)) {
                return [
                    'success' => false,
                    'error' => implode('. ', $validation_errors)
                ];
            }
            
            // Generowanie CSV
            $csv_result = self::$csv_generator->generate_daily_csv();
            if (!$csv_result) {
                throw new Exception(self::get_csv_error_details());
            }
            
            // Generowanie XML na podstawie CSV
            $xml_result = self::$xml_generator->generate_xml($csv_result['url']);
            if (!$xml_result) {
                throw new Exception(self::get_xml_error_details());
            }
            
            return [
                'success' => true,
                'message' => 'Pliki zostały wygenerowane i opublikowane pomyślnie',
                'csv' => $csv_result,
                'xml' => $xml_result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => self::get_detailed_error_message($e)
            ];
        }
    }
    
    /**
     * Walidacja przed generowaniem
     */
    private static function validate_before_generation() {
        $errors = [];
        
        $developer_repo = new UJC_Developer_Repository();
        if (!$developer_repo->read()) {
            $errors[] = "Brak danych dewelopera. Uzupełnij dane w zakładce 'Dane Dostawcy'";
        }
        
        $resource_repo = new UJC_Resource_Repository();
        $properties = $resource_repo->readAll();
        if (empty($properties)) {
            $errors[] = "Brak nieruchomości do eksportu. Dodaj nieruchomości w zakładce 'Zasoby'";
        }
        
        // Sprawdzenie katalogu
        $upload_dir = wp_upload_dir();
        $ujc_dir = $upload_dir['basedir'] . '/ujc-data';
        
        if (!is_dir($ujc_dir) && !wp_mkdir_p($ujc_dir)) {
            $errors[] = "Nie można utworzyć katalogu " . $ujc_dir;
        } elseif (!is_writable($ujc_dir)) {
            $errors[] = "Brak uprawnień do zapisu w katalogu " . $ujc_dir;
        }
        
        return $errors;
    }
    
    /**
     * Szczegółowy opis błędu dla CSV
     */
    private static function get_csv_error_details() {
        $developer_repo = new UJC_Developer_Repository();
        $resource_repo = new UJC_Resource_Repository();
        $upload_dir = wp_upload_dir();
        $ujc_dir = $upload_dir['basedir'] . '/ujc-data';
        
        if (!$developer_repo->read()) {
            return "Błąd CSV: Brak danych dewelopera";
        }
        
        if (empty($resource_repo->readAll())) {
            return "Błąd CSV: Brak nieruchomości do eksportu";
        }
        
        if (!is_writable($ujc_dir)) {
            return "Błąd CSV: Brak uprawnień do zapisu w katalogu " . $ujc_dir;
        }
        
        return "Błąd CSV: Nieznany błąd podczas tworzenia pliku";
    }
    
    /**
     * Szczegółowy opis błędu dla XML
     */
    private static function get_xml_error_details() {
        $upload_dir = wp_upload_dir();
        $ujc_dir = $upload_dir['basedir'] . '/ujc-data';
        
        if (!is_dir($ujc_dir)) {
            return "Błąd XML: Katalog " . $ujc_dir . " nie istnieje";
        }
        
        if (!is_writable($ujc_dir)) {
            return "Błąd XML: Brak uprawnień do zapisu w katalogu " . $ujc_dir;
        }
        
        return "Błąd XML: Nieznany błąd podczas tworzenia pliku XML lub MD5";
    }
    
    /**
     * Tworzy szczegółowy opis błędu na podstawie wyjątku
     */
    private static function get_detailed_error_message(Exception $e) {
        $message = $e->getMessage();
        
        // Sprawdź błędy związane z uprawnieniami
        if (strpos($message, 'Permission denied') !== false || strpos($message, 'permission') !== false) {
            return "Błąd uprawnień: " . $message;
        }
        
        // Sprawdź błędy dyskowe
        if (strpos($message, 'No space left') !== false || strpos($message, 'disk full') !== false) {
            return "Błąd dysku: Brak miejsca na dysku";
        }
        
        // Sprawdź błędy bazy danych
        if (strpos($message, 'database') !== false || strpos($message, 'MySQL') !== false) {
            return "Błąd bazy danych: " . $message;
        }
        
        // Domyślny opis
        return $message;
    }
}