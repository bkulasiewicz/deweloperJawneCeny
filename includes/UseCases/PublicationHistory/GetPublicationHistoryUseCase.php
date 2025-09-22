<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Use case for retrieving publication history
 */
class GetPublicationHistoryUseCase {

    private $repository;

    public function __construct(PublicationHistoryRepository $repository) {
        $this->repository = $repository;
    }
    
    /**
     * Execute the use case - get publication history
     * 
     * @param int $limit Number of records to retrieve (default 50)
     * @return PublicationHistoryDto[] Array of history entries
     */
    public function execute(int $limit = 50): array {
        try {
            $history = $this->repository->getHistory($limit);
            
            $count = count($history);
            
            if (empty($history)) {
                return [];
            }
            
            
            return $history;
            
        } catch (Exception $e) {
            Logger::error('GetPublicationHistoryUseCase Error: ' . $e->getMessage());
            return [];
        }
    }
}