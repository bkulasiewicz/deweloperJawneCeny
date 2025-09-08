<?php

if (!defined('ABSPATH')) {
    exit;
}

class GetDeveloperInfoUseCase {
    
    private $repository;
    
    public function __construct() {
        $this->repository = new DeveloperRepository();
    }
    
    public function execute(): ?SupplierDto {
        return $this->repository->read();
    }
}