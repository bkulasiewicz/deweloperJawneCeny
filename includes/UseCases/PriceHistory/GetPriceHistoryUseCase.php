<?php

if (!defined('ABSPATH')) {
    exit;
}

class GetPriceHistoryUseCase {
    
    private $repository;
    
    public function __construct() {
        $this->repository = new PriceHistoryRepository();
    }
    
    /**
     * @return PresentablePriceHistory[]
     */
    public function execute(int $resource_id): array {
        try {
            $priceHistoryDtos = $this->repository->readByResourceId($resource_id);
            
            $result = [];
            foreach ($priceHistoryDtos as $dto) {
                $result[] = PresentablePriceHistory::fromDto($dto);
            }
            
            return $result;
        } catch (Exception $e) {
            throw new Exception('Błąd podczas pobierania historii cen: ' . $e->getMessage());
        }
    }
}