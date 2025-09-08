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
        
        Logger::info('GenerateFiles started with trigger: ' . $trigger_type->value);
        
        try {
            // Generate CSV
            Logger::info('GenerateFiles: Starting CSV generation...');
            $csv_result = $this->csvUseCase->execute();
            if (!$csv_result['success']) {
                $error_message = $csv_result['error'] ?? 'Błąd generowania pliku CSV';
                Logger::error('GenerateFiles: CSV generation FAILED: ' . $error_message);
                
                Logger::info('GenerateFiles: Logging ERROR to history...');
                $this->historyUseCase->execute(PublicationStatus::Error, $error_message, $trigger_type);
                return $csv_result;
            }
            
            Logger::success('GenerateFiles: CSV generation SUCCESS: ' . ($csv_result['csv']['filename'] ?? 'unknown'));
            
            // Create submission files (XML + MD5) based on CSV
            Logger::info('GenerateFiles: Starting XML generation...');
            $submission_result = $this->submissionUseCase->createSubmissionFiles($csv_result['csv']['url'] ?? null);
            if (!$submission_result['success']) {
                $error_message = $submission_result['error'] ?? 'Błąd generowania plików XML/MD5';
                Logger::error('GenerateFiles: XML generation FAILED: ' . $error_message);
                
                Logger::info('GenerateFiles: Logging ERROR to history...');
                $this->historyUseCase->execute(PublicationStatus::Error, $error_message, $trigger_type);
                return $submission_result;
            }
            
            Logger::success('GenerateFiles: XML generation SUCCESS');
            
            // Log success to history
            Logger::info('GenerateFiles: Logging SUCCESS to history...');
            $history_result = $this->historyUseCase->execute(
                PublicationStatus::Success,
                'Pliki zostały wygenerowane pomyślnie',
                $trigger_type
            );
            
            if (!$history_result['success']) {
                Logger::error('GenerateFiles: History logging FAILED: ' . ($history_result['message'] ?? 'unknown error'));
            } else {
                Logger::success('GenerateFiles: History logging SUCCESS');
            }
            
            Logger::success('GenerateFiles: Returning SUCCESS response');
            
            return [
                'success' => true,
                'message' => 'Pliki zostały wygenerowane i przygotowane do zgłoszenia na dane.gov.pl',
                'csv' => $csv_result['csv'],
                'xml' => $submission_result['files']
            ];
            
        } catch (Exception $e) {
            $error_message = 'Wyjątek: ' . $e->getMessage();
            Logger::error('GenerateFiles: EXCEPTION caught: ' . $error_message);
            
            Logger::info('GenerateFiles: Logging EXCEPTION to history...');
            $this->historyUseCase->execute(PublicationStatus::Error, $error_message, $trigger_type);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}