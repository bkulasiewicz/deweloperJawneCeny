<?php

if (!defined('ABSPATH')) {
    exit;
}

class ResetDatabaseUseCase {
    
    /**
     * Resetuje dane dewelopera
     */
    public function resetDeveloperData() {
        UJC_Schema_Manager::reset_developer_table();
        return 'Dane dewelopera zostały zresetowane';
    }
    
    /**
     * Resetuje dane inwestycji
     */
    public function resetInvestmentData() {
        UJC_Schema_Manager::reset_investment_table();
        return 'Dane inwestycji zostały zresetowane';
    }
    
    /**
     * Resetuje wszystkie tabele z danymi
     */
    public function resetAllData() {
        UJC_Schema_Manager::reset_resources_tables();
        return ['Wszystkie tabele zasobów zostały zresetowane'];
    }
}