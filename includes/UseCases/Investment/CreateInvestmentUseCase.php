<?php

if (!defined('ABSPATH')) {
    exit;
}

class CreateInvestmentUseCase {

    private $repository;

    public function __construct(InvestmentRepository $repository) {
        $this->repository = $repository;
    }
    
    public function execute(InvestmentDto $dto): Result {
        Logger::info('UJC: CreateInvestmentUseCase::execute() started');
        Logger::info('UJC: InvestmentDto data: ' . print_r($dto, true));
        
        try {
            $investment_id = $this->repository->create($dto);
            Logger::info('UJC: CreateInvestmentUseCase completed successfully, ID: ' . $investment_id);
            
            return Result::success('Inwestycja została utworzona pomyślnie');
            
        } catch (Exception $e) {
            Logger::error('UJC: CreateInvestmentUseCase failed: ' . $e->getMessage());
            Logger::error('UJC: Exception trace: ' . $e->getTraceAsString());
            return Result::failure('Błąd podczas tworzenia inwestycji: ' . $e->getMessage());
        }
    }
}