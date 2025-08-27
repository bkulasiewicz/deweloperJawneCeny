<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Legacy Use Case - orchestrates CSV and XML generation
 * Uses new GenerateCSVFileUseCase and GenerateXMLFileUseCase
 */
class GenerateFilesUseCase {
    
    private static $csvUseCase;
    private static $xmlUseCase;
    
    /**
     * Main method generating CSV and XML files
     */
    public static function execute() {
        try {
            // Initialize use cases
            if (!self::$csvUseCase) {
                self::$csvUseCase = new GenerateCSVFileUseCase();
            }
            if (!self::$xmlUseCase) {
                self::$xmlUseCase = new GenerateXMLFileUseCase();
            }
            
            // Generate CSV
            $csv_result = self::$csvUseCase->execute();
            if (!$csv_result['success']) {
                return $csv_result;
            }
            
            // Generate XML based on CSV
            $xml_result = self::$xmlUseCase->execute($csv_result['csv']['url'] ?? null);
            if (!$xml_result['success']) {
                return $xml_result;
            }
            
            return [
                'success' => true,
                'message' => 'Pliki zostały wygenerowane i opublikowane pomyślnie',
                'csv' => $csv_result['csv'],
                'xml' => $xml_result['xml']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}