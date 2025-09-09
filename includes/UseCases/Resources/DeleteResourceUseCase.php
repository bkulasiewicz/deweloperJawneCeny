<?php

if (!defined('ABSPATH')) {
    exit;
}

class DeleteResourceUseCase {
    
    private $repository;
    
    public function __construct() {
        $this->repository = new ResourceRepository();
    }
    
    public function execute($resource_id): Result {
        if (!$resource_id || !is_numeric($resource_id)) {
            return Result::failure('Nieprawidłowy ID zasobu');
        }
        
        $resource_id = intval($resource_id);
        
        // Check if resource exists
        $existing_resource = $this->repository->readById($resource_id);
        if (!$existing_resource) {
            return Result::failure('Zasób nie został znaleziony');
        }
        
        // Delete the resource
        $result = $this->repository->delete($resource_id);
        
        if ($result === false) {
            return Result::failure('Błąd podczas usuwania zasobu');
        }
        
        return Result::success('Zasób został pomyślnie usunięty');
    }
}