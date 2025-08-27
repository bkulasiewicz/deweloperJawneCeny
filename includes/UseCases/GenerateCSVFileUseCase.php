<?php

if (!defined('ABSPATH')) {
    exit;
}

class GenerateCSVFileUseCase {
    
    private $developerRepository;
    private $resourceRepository;
    private $csvFormatter;
    private $fileManager;
    
    public function __construct() {
        $this->developerRepository = new DeveloperRepository();
        $this->resourceRepository = new ResourceRepository();
        $this->csvFormatter = new CSVFormatter();
        $this->fileManager = new FileManager();
    }
    
    /**
     * Execute CSV file generation
     */
    public function execute() {
        try {
            // Validation
            $validation_errors = $this->validateBeforeGeneration();
            if (!empty($validation_errors)) {
                return [
                    'success' => false,
                    'error' => implode('. ', $validation_errors)
                ];
            }
            
            // Get data
            $developer = $this->developerRepository->read();
            $resources = $this->resourceRepository->readAll();
            
            // Generate CSV content
            $csvRows = $this->csvFormatter->generate($developer, $resources);
            
            // Generate filename
            $filename = $this->fileManager->generateCSVFilename($developer);
            
            // Save CSV to file
            $csv_result = $this->fileManager->saveCSV($csvRows, $filename);
            
            return [
                'success' => true,
                'message' => 'Plik CSV został wygenerowany pomyślnie',
                'csv' => $csv_result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $this->getDetailedErrorMessage($e)
            ];
        }
    }
    
    /**
     * Validate before generation
     */
    private function validateBeforeGeneration() {
        $errors = [];
        
        if (!$this->developerRepository->read()) {
            $errors[] = "Brak danych dewelopera. Uzupełnij dane w zakładce 'Dane Dostawcy'";
        }
        
        $resources = $this->resourceRepository->readAll();
        if (empty($resources)) {
            $errors[] = "Brak nieruchomości do eksportu. Dodaj nieruchomości w zakładce 'Zasoby'";
        }
        
        // Check directory using FileManager
        try {
            $this->fileManager->ensureDirectoryExists();
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
        
        return $errors;
    }
    
    /**
     * Get detailed CSV error description
     */
    private function getCsvErrorDetails() {
        if (!$this->developerRepository->read()) {
            return "Błąd CSV: Brak danych dewelopera";
        }
        
        if (empty($this->resourceRepository->readAll())) {
            return "Błąd CSV: Brak nieruchomości do eksportu";
        }
        
        try {
            $this->fileManager->ensureDirectoryExists();
        } catch (Exception $e) {
            return "Błąd CSV: " . $e->getMessage();
        }
        
        return "Błąd CSV: Nieznany błąd podczas tworzenia pliku";
    }
    
    /**
     * Create detailed error message from exception
     */
    private function getDetailedErrorMessage(Exception $e) {
        $message = $e->getMessage();
        
        // Check permission errors
        if (strpos($message, 'Permission denied') !== false || strpos($message, 'permission') !== false) {
            return "Błąd uprawnień: " . $message;
        }
        
        // Check disk errors
        if (strpos($message, 'No space left') !== false || strpos($message, 'disk full') !== false) {
            return "Błąd dysku: Brak miejsca na dysku";
        }
        
        // Check database errors
        if (strpos($message, 'database') !== false || strpos($message, 'MySQL') !== false) {
            return "Błąd bazy danych: " . $message;
        }
        
        // Default description
        return $message;
    }
}