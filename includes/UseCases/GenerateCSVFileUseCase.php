<?php

if (!defined('ABSPATH')) {
    exit;
}

class GenerateCSVFileUseCase {
    
    private $developerRepository;
    private $investmentRepository;
    private $resourceRepository;
    private $csvFormatter;
    private $fileManager;
    
    public function __construct() {
        $this->developerRepository = new DeveloperRepository();
        $this->investmentRepository = new InvestmentRepository();
        $this->resourceRepository = new ResourceRepository();
        $this->csvFormatter = new CSVFormatter();
        $this->fileManager = new FileManager();
    }
    
    /**
     * Execute CSV file generation
     */
    public function execute() {
        error_log('GenerateCSV: Starting CSV generation process...');
        
        try {
            // Validation
            error_log('GenerateCSV: Starting validation...');
            $validation_errors = $this->validateBeforeGeneration();
            if (!empty($validation_errors)) {
                $error_msg = implode('. ', $validation_errors);
                error_log('GenerateCSV: Validation FAILED: ' . $error_msg);
                return [
                    'success' => false,
                    'error' => $error_msg
                ];
            }
            error_log('GenerateCSV: Validation SUCCESS');
            
            // Get data
            error_log('GenerateCSV: Fetching data from repositories...');
            $developer = $this->developerRepository->read();
            $investment = $this->investmentRepository->read();
            $resources = $this->resourceRepository->readAll();
            
            error_log('GenerateCSV: Data fetched - Resources count: ' . count($resources));
            
            // Generate CSV content
            error_log('GenerateCSV: Generating CSV content...');
            $csvRows = $this->csvFormatter->generate($developer, $investment, $resources);
            
            // Generate filename
            $filename = $this->fileManager->generateCSVFilename($developer);
            error_log('GenerateCSV: Generated filename: ' . $filename);
            
            // Save CSV to file
            error_log('GenerateCSV: Saving CSV to file...');
            $csv_result = $this->fileManager->saveCSV($csvRows, $filename);
            
            error_log('GenerateCSV: File saved successfully: ' . ($csv_result['filepath'] ?? 'unknown path'));
            
            error_log('GenerateCSV: Returning SUCCESS response');
            
            return [
                'success' => true,
                'message' => 'Plik CSV został wygenerowany pomyślnie',
                'csv' => $csv_result
            ];
            
        } catch (Exception $e) {
            $error_msg = $this->getDetailedErrorMessage($e);
            error_log('GenerateCSV: EXCEPTION caught: ' . $error_msg);
            
            return [
                'success' => false,
                'error' => $error_msg
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
        
        if (!$this->investmentRepository->read()) {
            $errors[] = "Brak danych inwestycji. Uzupełnij dane inwestycji w zakładce 'Zasoby'";
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