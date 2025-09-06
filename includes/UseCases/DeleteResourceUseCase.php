<?php

if (!defined('ABSPATH')) {
    exit;
}

class DeleteResourceUseCase {
    
    private $repository;
    
    public function __construct() {
        $this->repository = new ResourceRepository();
    }
    
    public function execute($resource_id) {
        if (!$resource_id || !is_numeric($resource_id)) {
            return ['error' => 'Nieprawidłowy ID zasobu'];
        }
        
        $resource_id = intval($resource_id);
        
        // Check if resource exists
        $existing_resource = $this->repository->readById($resource_id);
        if (!$existing_resource) {
            return ['error' => 'Zasób nie został znaleziony'];
        }
        
        // Delete the resource
        $result = $this->repository->delete($resource_id);
        
        if ($result === false) {
            return ['error' => 'Błąd podczas usuwania zasobu'];
        }
        
        return ['success' => true, 'message' => 'Zasób został pomyślnie usunięty'];
    }
}