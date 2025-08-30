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
        try {
            // Add entry to history
            $result = $this->repository->addEntry(
                $status->value,
                $message,
                $trigger_type->value
            );
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Wpis dodany do historii publikacji'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Błąd podczas zapisywania do historii'
                ];
            }
            
        } catch (Exception $e) {
            error_log('AddPublicationHistoryUseCase Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Błąd: ' . $e->getMessage()
            ];
        }
    }
}