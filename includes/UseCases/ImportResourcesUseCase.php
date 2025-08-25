<?php

if (!defined('ABSPATH')) {
    exit;
}

class ImportResourcesUseCase {
    
    private $saveUseCase;
    private $getByIdUseCase;
    
    public function __construct() {
        $this->saveUseCase = new SaveResourceUseCase();
        $this->getByIdUseCase = new GetResourceByIdUseCase();
    }
    
    /**
     * Importuje dane z pliku CSV
     */
    public function execute($file_data) {
        // Walidacja pliku
        if (!isset($file_data['tmp_name']) || $file_data['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Błąd podczas przesyłania pliku');
        }
        
        $file_extension = strtolower(pathinfo($file_data['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'csv') {
            throw new Exception('Obsługiwany tylko format CSV');
        }
        
        return $this->importFromCsv($file_data['tmp_name']);
    }
    
    private function importFromCsv($file_path) {
        $imported = 0;
        $updated = 0;
        
        if (($handle = fopen($file_path, 'r')) !== FALSE) {
            $first_line = fgets($handle);
            rewind($handle);
            $separator = $this->detect_csv_separator($first_line);
            
            $headers = fgetcsv($handle, 1000, $separator);
            
            while (($data = fgetcsv($handle, 1000, $separator)) !== FALSE) {
                if (count($data) < 4) continue;
                
                $resource_data = $this->clean_csv_row($data);
                
                if (!$resource_data) {
                    continue;
                }
                
                $resource_id = $resource_data['nr_lokalu']; // nr_lokalu to faktycznie ID
                $existing_resource = $this->getByIdUseCase->execute($resource_id);
                
                if ($existing_resource) {
                    $result = $this->saveUseCase->execute($resource_data, $resource_id);
                    if ($result !== false) $updated++;
                } else {
                    $result = $this->saveUseCase->execute($resource_data);
                    if ($result !== false) $imported++;
                }
            }
            
            fclose($handle);
        }
        
        $result = [
            'imported' => $imported,
            'updated' => $updated,
            'total' => $imported + $updated
        ];
        
        if ($result['total'] === 0) {
            throw new Exception('Nie znaleziono żadnych danych do zaimportowania');
        }
        
        $message = "Zaimportowano {$result['imported']} zasobów";
        if ($result['updated'] > 0) {
            $message .= " (zaktualizowano {$result['updated']})";
        }
        $result['message'] = $message;
        
        return $result;
    }
    
    private function detect_csv_separator($line) {
        $separators = [';', ',', '\t'];
        $separator_counts = [];
        
        foreach ($separators as $sep) {
            $separator_counts[$sep] = substr_count($line, $sep);
        }
        
        return array_search(max($separator_counts), $separator_counts) ?: ';';
    }
    
    private function clean_csv_row($data) {
        $nr_lokalu = trim($data[0]);
        $rodzaj = trim($data[1]) ?: 'Lokal mieszkalny';
        $powierzchnia = $this->clean_decimal($data[2] ?? '');
        $cena_m2 = $this->clean_decimal($data[3] ?? '');
        $cena_calkowita = isset($data[4]) && !empty($data[4]) ? $this->clean_decimal($data[4]) : null;
        
        if (empty($nr_lokalu) || $powierzchnia <= 0 || $cena_m2 <= 0) {
            return false;
        }
        
        if (!in_array($rodzaj, ['Lokal mieszkalny', 'Dom jednorodzinny'])) {
            $rodzaj = 'Lokal mieszkalny';
        }
        
        $resource_data = [
            'nr_lokalu' => $nr_lokalu,
            'rodzaj_nieruchomosci' => $rodzaj,
            'powierzchnia_uzytkowa' => $powierzchnia,
            'cena_m2' => $cena_m2,
            'data_cena_m2' => current_time('mysql'),
            'status' => 'dostepny',
            'updated_at' => current_time('mysql')
        ];
        
        if ($cena_calkowita !== null && $cena_calkowita > 0) {
            $resource_data['cena_calkowita'] = $cena_calkowita;
            $resource_data['data_cena_calkowita'] = current_time('mysql');
        }
        
        return $resource_data;
    }
    
    private function clean_decimal($value) {
        if (empty($value)) return 0;
        
        $clean = preg_replace('/[^\d,.-]/', '', trim($value));
        $clean = str_replace(',', '.', $clean);
        
        return floatval($clean);
    }
}