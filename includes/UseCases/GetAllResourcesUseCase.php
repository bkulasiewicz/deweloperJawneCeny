<?php

if (!defined('ABSPATH')) {
    exit;
}

class GetAllResourcesUseCase {
    
    private $repository;
    
    public function __construct() {
        $this->repository = new UJC_Resource_Repository();
    }
    
    public function execute() {
        return $this->repository->readAll();
    }
}