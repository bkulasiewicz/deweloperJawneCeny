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
        $error_message = null;
        
        try {
            // Generate CSV
            $csv_result = $this->csvUseCase->execute();
            if (!$csv_result['success']) {
                $error_message = $csv_result['error'] ?? 'Błąd generowania pliku CSV';
                $this->historyUseCase->execute(PublicationStatus::Error, $error_message, $trigger_type);
                return $csv_result;
            }
            
            
            // Create submission files (XML + MD5) based on CSV
            $submission_result = $this->submissionUseCase->createSubmissionFiles($csv_result['csv']['url'] ?? null);
            if (!$submission_result['success']) {
                $error_message = $submission_result['error'] ?? 'Błąd generowania plików XML/MD5';
                $this->historyUseCase->execute(PublicationStatus::Error, $error_message, $trigger_type);
                return $submission_result;
            }
            
            // Log success to history
            $this->historyUseCase->execute(
                PublicationStatus::Success,
                'Pliki zostały wygenerowane pomyślnie',
                $trigger_type
            );
            
            return [
                'success' => true,
                'message' => 'Pliki zostały wygenerowane i przygotowane do zgłoszenia na dane.gov.pl',
                'csv' => $csv_result['csv'],
                'xml' => $submission_result['files']
            ];
            
        } catch (Exception $e) {
            $error_message = 'Wyjątek: ' . $e->getMessage();
            $this->historyUseCase->execute(PublicationStatus::Error, $error_message, $trigger_type);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}