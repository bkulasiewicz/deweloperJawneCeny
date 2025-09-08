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
     * @return PublicationHistoryDto[] Array of history entries
     */
    public function execute(int $limit = 50): array {
        try {
            Logger::info("GetPublicationHistoryUseCase: Requesting {$limit} entries");
            $history = $this->repository->getHistory($limit);
            
            $count = count($history);
            Logger::info("GetPublicationHistoryUseCase: Repository returned {$count} DTO entries");
            
            if (empty($history)) {
                Logger::info("GetPublicationHistoryUseCase: No history entries found, returning empty array");
                return [];
            }
            
            Logger::success("GetPublicationHistoryUseCase: Successfully retrieved {$count} DTOs from repository");
            
            return $history;
            
        } catch (Exception $e) {
            Logger::error('GetPublicationHistoryUseCase Error: ' . $e->getMessage());
            return [];
        }
    }
}