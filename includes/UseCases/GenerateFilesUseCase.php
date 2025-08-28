<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Orchestrates CSV generation and dane.gov.pl submission files creation
 * Uses GenerateCSVFileUseCase and CreateDaneGovSubmissionFilesUseCase
 */
class GenerateFilesUseCase {
    
    private static $csvUseCase;
    private static $submissionUseCase;
    
    /**
     * Main method generating CSV and XML files
     */
    public static function execute() {
        try {
            // Initialize use cases
            if (!self::$csvUseCase) {
                self::$csvUseCase = new GenerateCSVFileUseCase();
            }
            if (!self::$submissionUseCase) {
                self::$submissionUseCase = new CreateDaneGovSubmissionFilesUseCase();
            }
            
            // Generate CSV
            $csv_result = self::$csvUseCase->execute();
            if (!$csv_result['success']) {
                return $csv_result;
            }
            
            // Create submission files (XML + MD5) based on CSV
            $submission_result = self::$submissionUseCase->createSubmissionFiles($csv_result['csv']['url'] ?? null);
            if (!$submission_result['success']) {
                return $submission_result;
            }
            
            return [
                'success' => true,
                'message' => 'Pliki zostały wygenerowane i przygotowane do zgłoszenia na dane.gov.pl',
                'csv' => $csv_result['csv'],
                'xml' => $submission_result['files']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}