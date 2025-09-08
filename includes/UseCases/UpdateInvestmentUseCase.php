<?php

if (!defined('ABSPATH')) {
    exit;
}

class UpdateInvestmentUseCase {
    
    private $repository;
    
    public function __construct() {
        $this->repository = new InvestmentRepository();
    }
    
    public function execute(InvestmentDto $dto, int $investment_id): Result {
        try {
            $this->repository->update($dto, $investment_id);
            
            return Result::success('Inwestycja została zaktualizowana pomyślnie');
            
        } catch (Exception $e) {
            return Result::failure('Błąd podczas aktualizacji inwestycji: ' . $e->getMessage());
        }
    }
}