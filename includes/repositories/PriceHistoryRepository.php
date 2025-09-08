<?php

if (!defined('ABSPATH')) {
    exit;
}

class PriceHistoryRepository {
    
    /**
     * Get price history for resource
     */
    public function readByResourceId($resource_id): array {
        $table = TableNames::getPriceHistory();
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM `{$table}` WHERE resource_id = %d ORDER BY data_zmiany DESC", 
            $resource_id
        ), ARRAY_A);
        
        $dtos = [];
        foreach($results as $row) {
            $dtos[] = PriceHistoryDto::databaseToModel($row);
        }
        
        return $dtos;
    }
    
    /**
     * Save price change history (extended logic)
     */
    public function save(PriceHistoryDto $dto) {
        $table = TableNames::getPriceHistory();
        global $wpdb;
        
        return $wpdb->insert($table, $dto->modelToDatabase());
    }
    
    /**
     * Get current prices for resource
     */
    public function getCurrentPricesForResource(int $resource_id): PriceHistoryDto {
        $table = TableNames::getPriceHistory();
        global $wpdb;
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM `{$table}` WHERE resource_id = %d ORDER BY data_zmiany DESC LIMIT 1",
            $resource_id
        ), ARRAY_A);
        
        if (!$result) {
            throw new Exception("No price history found for resource {$resource_id}");
        }
        
        return PriceHistoryDto::databaseToModel($result);
    }
}