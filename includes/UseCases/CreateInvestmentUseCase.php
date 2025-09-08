<?php

if (!defined('ABSPATH')) {
    exit;
}

class CreateInvestmentUseCase {
    
    private $repository;
    
    public function __construct() {
        $this->repository = new InvestmentRepository();
    }
    
    public function execute(InvestmentDto $dto): Result {
        try {
            $this->repository->create($dto);
            
            return Result::success('Inwestycja została utworzona pomyślnie');
            
        } catch (Exception $e) {
            return Result::failure('Błąd podczas tworzenia inwestycji: ' . $e->getMessage());
        }
    }
}