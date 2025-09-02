<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Use case for adding entries to publication history
 */
class AddPublicationHistoryUseCase {
    
    private $repository;
    
    public function __construct() {
        $this->repository = new PublicationHistoryRepository();
    }
    
    /**
     * Execute the use case - add entry to publication history
     * 
     * @param PublicationStatus $status Publication status
     * @param string $message Detailed message or error description
     * @param TriggerType $trigger_type Type of trigger
     * @return array Result with success status and message
     */
    public function execute(PublicationStatus $status, string $message, TriggerType $trigger_type) {
        error_log('AddHistory: Attempting to add history entry - Status: ' . $status->value . ', Trigger: ' . $trigger_type->value . ', Message: ' . substr($message, 0, 100) . '...');
        
        try {
            // Add entry to history
            error_log('AddHistory: Calling repository->addEntry...');
            $result = $this->repository->addEntry(
                $status->value,
                $message,
                $trigger_type->value
            );
            
            if ($result) {
                error_log('AddHistory: Database insert SUCCESS');
                return [
                    'success' => true,
                    'message' => 'Wpis dodany do historii publikacji'
                ];
            } else {
                error_log('AddHistory: Database insert FAILED - repository returned false');
                return [
                    'success' => false,
                    'message' => 'Błąd podczas zapisywania do historii'
                ];
            }
            
        } catch (Exception $e) {
            error_log('AddHistory: EXCEPTION caught: ' . $e->getMessage());
            error_log('AddPublicationHistoryUseCase Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Błąd: ' . $e->getMessage()
            ];
        }
    }
}