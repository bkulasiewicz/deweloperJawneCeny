<?php

if (!defined('ABSPATH')) {
    exit;
}

class ImportResourcesUseCase {
    
    private $createUseCase;
    private $updateUseCase;
    private $getByIdUseCase;
    
    public function __construct(
        CreateResourceUseCase $createUseCase,
        UpdateResourceUseCase $updateUseCase,
        GetResourceByIdUseCase $getByIdUseCase
    ) {
        $this->createUseCase = $createUseCase;
        $this->updateUseCase = $updateUseCase;
        $this->getByIdUseCase = $getByIdUseCase;
    }
    
    /**
     * Importuje dane z pliku CSV
     * FUNKCJONALNOŚĆ TYMCZASOWO WYŁĄCZONA - niezgodność z nową strukturą enum
     */
    public function execute($file_data) {
        throw new Exception('Import CSV jest tymczasowo wyłączony z powodu refaktoryzacji enum');
        
        /* WYŁĄCZONO - wymaga aktualizacji do nowej struktury enum
        // Walidacja pliku
        if (!isset($file_data['tmp_name']) || $file_data['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Błąd podczas przesyłania pliku');
        }
        
        $file_extension = strtolower(pathinfo($file_data['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'csv') {
            throw new Exception('Obsługiwany tylko format CSV');
        }
        
        return $this->importFromCsv($file_data['tmp_name']);
        */
    }
    
    /* WYŁĄCZONO - wymaga aktualizacji do nowej struktury enum
    private function importFromCsv($file_path) {
        $imported = 0;
        $updated = 0;
        
        global $wp_filesystem;
        
        // Read entire file content using WordPress filesystem
        if (!$wp_filesystem->exists($file_path)) {
            throw new Exception('Plik CSV nie istnieje');
        }
        
        $file_content = $wp_filesystem->get_contents($file_path);
        if ($file_content === false) {
            throw new Exception('Nie można odczytać pliku CSV');
        }
        
        // Split into lines
        $lines = explode("\n", $file_content);
        if (empty($lines)) {
            throw new Exception('Plik CSV jest pusty');
        }
        
        // Get first line to detect separator
        $first_line = $lines[0];
        $separator = $this->detect_csv_separator($first_line);
        
        // Parse header line
        $headers = str_getcsv($first_line, $separator);
        
        // Process data lines (skip header)
        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;
            
            $data = str_getcsv($line, $separator);
            if (count($data) < 4) continue;
            
            $resource_data = $this->clean_csv_row($data);
            
            if (!$resource_data) {
                continue;
            }
            
            $resource_id = $resource_data['nr_lokalu']; // nr_lokalu to faktycznie ID
            $existing_resource = $this->getByIdUseCase->execute($resource_id);
            
            $formData = new ResourceFormData(
                rodzaj_nieruchomosci: PropertyType::from($resource_data['rodzaj_nieruchomosci']),
                nr_lokalu: $resource_data['nr_lokalu'],
                powierzchnia_uzytkowa: $resource_data['powierzchnia_uzytkowa'],
                cena_m2: $resource_data['cena_m2'],
                cena_calkowita: $resource_data['cena_calkowita'] ?? 0.0,
                cena_z_dodatkami: $resource_data['cena_z_dodatkami'] ?? 0.0,
                status: ResourceStatus::from($resource_data['status'])
            );
            
            if ($existing_resource) {
                $result = $this->updateUseCase->execute($formData, $resource_id);
                if ($result->isSuccess) $updated++;
            } else {
                $result = $this->createUseCase->execute($formData);
                if ($result->isSuccess) $imported++;
            }
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
    */
    
    /* WYŁĄCZONO - wymaga aktualizacji do nowej struktury enum
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
        $rodzaj = trim($data[1]) ?: 'Lokal mieszkalny'; // PROBLEM: używa display text zamiast database key
        $powierzchnia = $this->clean_decimal($data[2] ?? '');
        $cena_m2 = !empty($data[3]) ? $this->clean_decimal($data[3]) : null;
        $cena_calkowita = isset($data[4]) && !empty($data[4]) ? $this->clean_decimal($data[4]) : null;
        
        if (empty($nr_lokalu) || $powierzchnia <= 0) {
            return false;
        }
        
        // PROBLEM: sprawdza display text zamiast database keys
        if (!in_array($rodzaj, ['Lokal mieszkalny', 'Dom jednorodzinny'])) {
            $rodzaj = 'Lokal mieszkalny';
        }
        
        $resource_data = [
            'nr_lokalu' => $nr_lokalu,
            'rodzaj_nieruchomosci' => $rodzaj, // PROBLEM: zwraca display text
            'powierzchnia_uzytkowa' => $powierzchnia,
            'cena_m2' => $cena_m2,
            'cena_calkowita' => $cena_calkowita ?? 0.0,
            'cena_z_dodatkami' => 0.0,
            'status' => 'dostepny' // PROBLEM: display text zamiast 'available'
        ];
        
        return $resource_data;
    }
    
    private function clean_decimal($value) {
        if (empty($value)) return 0;
        
        $clean = preg_replace('/[^\d,.-]/', '', trim($value));
        $clean = str_replace(',', '.', $clean);
        
        return floatval($clean);
    }
    */
}