<?php

if (!defined('ABSPATH')) {
    exit;
}

class GenerateXMLFileUseCase {
    
    private $xmlFormatter;
    private $fileManager;
    
    public function __construct() {
        $this->xmlFormatter = new XMLFormatter();
        $this->fileManager = new FileManager();
    }
    
    /**
     * Execute XML file generation
     * 
     * @param string|null $csv_url URL to CSV file for reference
     */
    public function execute($csv_url = null) {
        try {
            // Validation
            $validation_errors = $this->validateBeforeGeneration();
            if (!empty($validation_errors)) {
                return [
                    'success' => false,
                    'error' => implode('. ', $validation_errors)
                ];
            }
            
            // Generate XML content
            $xmlContent = $this->xmlFormatter->generateXML($csv_url);
            
            // Generate filename
            $filename = $this->fileManager->generateXMLFilename();
            
            // Save XML to file (with MD5)
            $xml_result = $this->fileManager->saveXML($xmlContent, $filename);
            
            return [
                'success' => true,
                'message' => 'Plik XML został wygenerowany pomyślnie',
                'xml' => $xml_result
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
        
        // Check directory using FileManager
        try {
            $this->fileManager->ensureDirectoryExists();
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
        
        return $errors;
    }
    
    /**
     * Get detailed XML error description
     */
    private function getXmlErrorDetails() {
        try {
            $this->fileManager->ensureDirectoryExists();
        } catch (Exception $e) {
            return "Błąd XML: " . $e->getMessage();
        }
        
        return "Błąd XML: Nieznany błąd podczas tworzenia pliku XML lub MD5";
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