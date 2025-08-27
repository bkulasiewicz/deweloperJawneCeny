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
        return $this->repository->readById($resource_id);
    }
}