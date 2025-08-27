<?php

if (!defined('ABSPATH')) {
    exit;
}

class GetAllResourcesUseCase {
    
    private $repository;
    
    public function __construct() {
        $this->repository = new ResourceRepository();
    }
    
    public function execute() {
        return $this->repository->readAll();
    }
}