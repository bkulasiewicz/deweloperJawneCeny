<?php

if (!defined('ABSPATH')) {
    exit;
}

class GenerateCSVFileUseCase {
    
    private $developerRepository;
    private $investmentRepository;
    private $resourceRepository;
    private $priceHistoryRepository;
    private $csvFormatter;
    private $fileManager;
    
    public function __construct() {
        $this->developerRepository = new DeveloperRepository();
        $this->investmentRepository = new InvestmentRepository();
        $this->resourceRepository = new ResourceRepository();
        $this->priceHistoryRepository = new PriceHistoryRepository();
        $this->csvFormatter = new CSVFormatter();
        $this->fileManager = new FileManager();
    }
    
    /**
     * Execute CSV file generation
     */
    public function execute() {
        Logger::info('GenerateCSV: Starting CSV generation process...');
        
        try {
            // Validation
            Logger::info('GenerateCSV: Starting validation...');
            $validation_errors = $this->validateBeforeGeneration();
            if (!empty($validation_errors)) {
                $error_msg = implode('. ', $validation_errors);
                Logger::error('GenerateCSV: Validation FAILED: ' . $error_msg);
                return [
                    'success' => false,
                    'error' => $error_msg
                ];
            }
            Logger::success('GenerateCSV: Validation SUCCESS');
            
            // Get data
            Logger::info('GenerateCSV: Fetching data from repositories...');
            $developer = $this->developerRepository->read();
            Logger::info('GenerateCSV: Developer data loaded');
            
            $investment = $this->investmentRepository->read();
            Logger::info('GenerateCSV: Investment data loaded');
            
            $resources = $this->resourceRepository->readAll();
            Logger::info('GenerateCSV: Resources loaded - count: ' . count($resources));
            
            Logger::info('GenerateCSV: Getting price history for resources...');
            $priceHistory = $this->getPriceHistoryForResources($resources);
            Logger::info('GenerateCSV: Price history loaded - count: ' . count($priceHistory));
            
            Logger::info('GenerateCSV: Data fetched - Resources count: ' . count($resources));
            
            // Generate CSV content
            Logger::info('GenerateCSV: Generating CSV content...');
            $csvRows = $this->csvFormatter->generate($developer, $investment, $resources, $priceHistory);
            Logger::info('GenerateCSV: CSV rows generated - count: ' . count($csvRows));
            
            // Generate filename
            $filename = $this->fileManager->generateCSVFilename($developer);
            Logger::info('GenerateCSV: Generated filename: ' . $filename);
            
            // Save CSV to file
            Logger::info('GenerateCSV: Saving CSV to file...');
            $csv_result = $this->fileManager->saveCSV($csvRows, $filename);
            
            Logger::success('GenerateCSV: File saved successfully: ' . ($csv_result['filepath'] ?? 'unknown path'));
            
            Logger::success('GenerateCSV: Returning SUCCESS response');
            
            return [
                'success' => true,
                'message' => 'Plik CSV został wygenerowany pomyślnie',
                'csv' => $csv_result
            ];
            
        } catch (Exception $e) {
            $error_msg = $this->getDetailedErrorMessage($e);
            Logger::error('GenerateCSV: EXCEPTION caught: ' . $error_msg);
            
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
     * Get price history for all resources
     */
    private function getPriceHistoryForResources(array $resources): array {
        $priceHistory = [];
        foreach ($resources as $resource) {
            Logger::info('GenerateCSV: Getting price history for resource ID: ' . $resource->id);
            try {
                $priceHistory[$resource->id] = $this->priceHistoryRepository->getCurrentPricesForResource($resource->id);
                Logger::info('GenerateCSV: Price history loaded for resource ID: ' . $resource->id);
            } catch (Exception $e) {
                Logger::error('GenerateCSV: Failed to get price history for resource ID: ' . $resource->id . ' - ' . $e->getMessage());
                throw $e;
            }
        }
        Logger::info('GenerateCSV: All price histories loaded');
        return $priceHistory;
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