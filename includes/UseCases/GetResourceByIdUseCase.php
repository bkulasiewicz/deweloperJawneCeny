<?php

if (!defined('ABSPATH')) {
    exit;
}

class GetResourceByIdUseCase {
    
    private $repository;
    
    public function __construct() {
        $this->repository = new ResourceRepository();
    }
    
    public function execute($resource_id) {
        $resource = $this->repository->readById($resource_id);
        
        if ($resource) {
            // Transform extra columns to expected format for frontend
            if (!empty($resource['extra_rodzaj_czesci'])) {
                $resource['extra'] = [
                    'rodzaj_czesci' => $resource['extra_rodzaj_czesci'],
                    'oznaczenie_czesci' => $resource['extra_oznaczenie_czesci'],
                    'cena_czesci' => $resource['extra_cena_czesci'],
                    'data_cena_czesci' => $resource['extra_data_cena_czesci']
                ];
            }
        }
        
        return $resource;
    }
}