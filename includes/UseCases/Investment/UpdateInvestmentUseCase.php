<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

class UpdateInvestmentUseCase {
    
    private $repository;
    
    public function __construct(InvestmentRepository $repository) {
        $this->repository = $repository;
    }
    
    public function execute(InvestmentDto $dto, int $investment_id): Result {
        Logger::info('UJC: UpdateInvestmentUseCase::execute() started for ID: ' . $investment_id);
        Logger::info('UJC: InvestmentDto data: ' . print_r($dto, true));
        
        try {
            $this->repository->update($dto, $investment_id);
            Logger::info('UJC: UpdateInvestmentUseCase completed successfully for ID: ' . $investment_id);
            
            return Result::success('Inwestycja została zaktualizowana pomyślnie');
            
        } catch (Exception $e) {
            Logger::error('UJC: UpdateInvestmentUseCase failed: ' . $e->getMessage());
            Logger::error('UJC: Exception trace: ' . $e->getTraceAsString());
            return Result::failure('Błąd podczas aktualizacji inwestycji: ' . $e->getMessage());
        }
    }
}