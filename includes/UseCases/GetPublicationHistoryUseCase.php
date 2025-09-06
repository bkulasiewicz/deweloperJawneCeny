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
            Logger::info("GetPublicationHistoryUseCase: Requesting {$limit} entries");
            $history = $this->repository->getHistory($limit);
            
            $raw_count = count($history);
            Logger::info("GetPublicationHistoryUseCase: Repository returned {$raw_count} raw entries");
            
            if (empty($history)) {
                Logger::info("GetPublicationHistoryUseCase: No history entries found, returning empty array");
                return [];
            }
            
            // Convert database rows to model objects
            $models = array_map(
                fn($entry) => PublicationHistoryEntry::fromArray($entry),
                $history
            );
            
            $model_count = count($models);
            Logger::success("GetPublicationHistoryUseCase: Successfully converted {$model_count} entries to models");
            
            return $models;
            
        } catch (Exception $e) {
            Logger::error('GetPublicationHistoryUseCase Error: ' . $e->getMessage());
            return [];
        }
    }
}