<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Orchestrates CSV generation and dane.gov.pl submission files creation
 * Uses GenerateCSVFileUseCase and CreateDaneGovSubmissionFilesUseCase
 */
class GenerateFilesUseCase {
    
    private $csvUseCase;
    private $submissionUseCase;
    private $historyUseCase;
    
    public function __construct() {
        $this->csvUseCase = new GenerateCSVFileUseCase();
        $this->submissionUseCase = new CreateDaneGovSubmissionFilesUseCase();
        $this->historyUseCase = new AddPublicationHistoryUseCase();
    }
    
    /**
     * Main method generating CSV and XML files
     * 
     * @param TriggerType $trigger_type Type of trigger
     */
    public function execute(TriggerType $trigger_type) {
        $csv_filename = null;
        $error_message = null;
        
        try {
            // Generate CSV
            $csv_result = $this->csvUseCase->execute();
            if (!$csv_result['success']) {
                $error_message = $csv_result['error'] ?? 'Błąd generowania pliku CSV';
                $this->historyUseCase->execute(PublicationStatus::Error, $error_message, $trigger_type, null);
                return $csv_result;
            }
            
            $csv_filename = $csv_result['csv']['filename'] ?? null;
            
            // Create submission files (XML + MD5) based on CSV
            $submission_result = $this->submissionUseCase->createSubmissionFiles($csv_result['csv']['url'] ?? null);
            if (!$submission_result['success']) {
                $error_message = $submission_result['error'] ?? 'Błąd generowania plików XML/MD5';
                $this->historyUseCase->execute(PublicationStatus::Error, $error_message, $trigger_type, $csv_filename);
                return $submission_result;
            }
            
            // Log success to history
            $this->historyUseCase->execute(
                PublicationStatus::Success,
                'Pliki zostały wygenerowane pomyślnie',
                $trigger_type,
                $csv_filename
            );
            
            return [
                'success' => true,
                'message' => 'Pliki zostały wygenerowane i przygotowane do zgłoszenia na dane.gov.pl',
                'csv' => $csv_result['csv'],
                'xml' => $submission_result['files']
            ];
            
        } catch (Exception $e) {
            $error_message = 'Wyjątek: ' . $e->getMessage();
            $this->historyUseCase->execute(PublicationStatus::Error, $error_message, $trigger_type, $csv_filename);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}