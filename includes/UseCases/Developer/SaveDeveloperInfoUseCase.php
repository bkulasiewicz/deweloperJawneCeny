<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

class SaveDeveloperInfoUseCase {

    private $repository;

    public function __construct(DeveloperRepository $repository) {
        $this->repository = $repository;
    }
    
    public function execute(SupplierDto $supplier): Result {
        // Business validation
        if (empty($supplier->nazwa)) {
            return Result::failure('Nazwa firmy jest wymagana');
        }
        
        if (empty($supplier->nr_krs)) {
            return Result::failure('Numer KRS jest wymagany');
        }
        
        if (empty($supplier->nr_nip)) {
            return Result::failure('NIP jest wymagany');
        }
        
        if (strlen($supplier->nr_nip) !== 10 || !is_numeric($supplier->nr_nip)) {
            return Result::failure('NIP musi mieć dokładnie 10 cyfr');
        }
        
        if (empty($supplier->email) || !filter_var($supplier->email, FILTER_VALIDATE_EMAIL)) {
            return Result::failure('Prawidłowy email jest wymagany');
        }
        
        if (empty($supplier->siedz_wojewodztwo)) {
            return Result::failure('Województwo siedziby jest wymagane');
        }
        
        if (empty($supplier->siedz_miejscowosc)) {
            return Result::failure('Miejscowość siedziby jest wymagana');
        }
        
        if (empty($supplier->sprzed_wojewodztwo)) {
            return Result::failure('Województwo sprzedaży jest wymagane');
        }
        
        if (empty($supplier->sprzed_miejscowosc)) {
            return Result::failure('Miejscowość sprzedaży jest wymagana');
        }
        
        try {
            $this->repository->save($supplier);
            return Result::success('Dane dostawcy zostały zapisane!');
        } catch (Exception $e) {
            Logger::log('Repository error in SaveDeveloperInfoUseCase: ' . $e->getMessage(), Logger::LEVEL_ERROR);
            return Result::failure('Błąd podczas zapisywania danych');
        }
    }
}