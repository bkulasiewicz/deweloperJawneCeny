<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Use case for retrieving publication history
 */
class GetPublicationHistoryUseCase {
    
    private $repository;
    
    public function __construct() {
        $this->repository = new PublicationHistoryRepository();
    }
    
    /**
     * Execute the use case - get publication history
     * 
     * @param int $limit Number of records to retrieve (default 50)
     * @return PublicationHistoryEntry[] Array of history entries
     */
    public function execute(int $limit = 50): array {
        try {
            $history = $this->repository->getHistory($limit);
            
            // Convert database rows to model objects
            return array_map(
                fn($entry) => PublicationHistoryEntry::fromArray($entry),
                $history
            );
            
        } catch (Exception $e) {
            error_log('GetPublicationHistoryUseCase Error: ' . $e->getMessage());
            return [];
        }
    }
}