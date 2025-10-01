<?php

if (!defined('ABSPATH')) {
    exit;
}

class ResetDatabaseUseCase {
    
    /**
     * Resetuje dane dewelopera
     */
    public function resetDeveloperData() {
        JawneCeny_SchemaManager::reset_developer_table();
        return 'Dane dewelopera zostały zresetowane';
    }
    
    /**
     * Resetuje dane inwestycji
     */
    public function resetInvestmentData() {
        JawneCeny_SchemaManager::reset_investment_table();
        return 'Dane inwestycji zostały zresetowane';
    }
    
    /**
     * Resetuje dane historii publikacji
     */
    public function resetPublicationHistoryData() {
        JawneCeny_SchemaManager::reset_publication_history_table();
        return 'Historia publikacji została zresetowana';
    }
    
    /**
     * Resetuje dane XML Resource
     */
    public function resetXmlResourceData() {
        JawneCeny_SchemaManager::reset_xml_resource_table();
        return 'Tabela XML Resources została zresetowana';
    }
    
    /**
     * Resetuje wszystkie tabele z danymi
     */
    public function resetAllData() {
        JawneCeny_SchemaManager::reset_resources_tables();
        return ['Wszystkie tabele zasobów zostały zresetowane'];
    }
}