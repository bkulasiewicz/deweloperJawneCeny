<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Use case for adding entries to publication history
 */
class AddPublicationHistoryUseCase {

    private $repository;

    public function __construct(PublicationHistoryRepository $repository) {
        $this->repository = $repository;
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
        Logger::info('AddHistory: Attempting to add history entry - Status: ' . $status->value . ', Trigger: ' . $trigger_type->value . ', Message: ' . substr($message, 0, 100) . '...');
        
        try {
            // Create DTO for new entry
            Logger::info('AddHistory: Creating PublicationHistoryDto...');
            $currentDatetime = DateHelper::currentDatetime();
            Logger::info('AddHistory: Current datetime from DateHelper: ' . $currentDatetime);
            
            $dto = new PublicationHistoryDto(
                $currentDatetime,
                $status->value,
                $message,
                $trigger_type->value
            );
            Logger::info('AddHistory: PublicationHistoryDto created successfully');
            
            // Add entry to history
            Logger::info('AddHistory: Calling repository->addEntry...');
            $result = $this->repository->addEntry($dto);
            
            if ($result) {
                Logger::success('AddHistory: Database insert SUCCESS');
                return [
                    'success' => true,
                    'message' => 'Wpis dodany do historii publikacji'
                ];
            } else {
                Logger::error('AddHistory: Database insert FAILED - repository returned false');
                return [
                    'success' => false,
                    'message' => 'Błąd podczas zapisywania do historii'
                ];
            }
            
        } catch (Exception $e) {
            Logger::error('AddHistory: EXCEPTION caught: ' . $e->getMessage());
            Logger::error('AddPublicationHistoryUseCase Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Błąd: ' . $e->getMessage()
            ];
        }
    }
}