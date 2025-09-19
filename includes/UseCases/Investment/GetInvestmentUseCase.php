<?php

if (!defined('ABSPATH')) {
    exit;
}

class GetInvestmentUseCase {
    
    private $repository;
    
    public function __construct(InvestmentRepository $repository) {
        $this->repository = $repository;
    }
    
    public function execute(): ?InvestmentDto {
        try {
            return $this->repository->read();
        } catch (Exception $e) {
            throw new Exception('Błąd podczas pobierania danych inwestycji: ' . esc_html($e->getMessage()));
        }
    }
}