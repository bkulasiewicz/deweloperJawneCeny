<?php

if (!defined('ABSPATH')) {
    exit;
}

class GetPriceHistoryUseCase {
    
    private $repository;
    
    public function __construct() {
        $this->repository = new PriceHistoryRepository();
    }
    
    public function execute(int $resource_id): array {
        try {
            return $this->repository->readByResourceId($resource_id);
        } catch (Exception $e) {
            throw new Exception('Błąd podczas pobierania historii cen: ' . $e->getMessage());
        }
    }
}