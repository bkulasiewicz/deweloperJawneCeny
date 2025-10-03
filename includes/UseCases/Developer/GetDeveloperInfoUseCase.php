<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

class GetDeveloperInfoUseCase {

    private $repository;

    public function __construct(DeveloperRepository $repository) {
        $this->repository = $repository;
    }
    
    public function execute(): ?SupplierDto {
        return $this->repository->read();
    }
}