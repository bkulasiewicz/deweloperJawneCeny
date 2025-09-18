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
            "SELECT * FROM `{$table}` WHERE " . PriceHistoryDto::FIELD_RESOURCE_ID . " = %d ORDER BY " . PriceHistoryDto::FIELD_ID . " DESC", 
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
            "SELECT * FROM `{$table}` WHERE " . PriceHistoryDto::FIELD_RESOURCE_ID . " = %d ORDER BY " . PriceHistoryDto::FIELD_ID . " DESC LIMIT 1",
            $resource_id
        ), ARRAY_A);
        
        if (!$result) {
            throw new Exception("No price history found for resource {$resource_id}");
        }
        
        return PriceHistoryDto::databaseToModel($result);
    }
    
    /**
     * Create price history table using PriceHistoryDto field constants
     */
    public function createTable(?string $currentDbVersion = null): bool {
        global $wpdb;
        $table = TableNames::getPriceHistory();
        $charset_collate = $wpdb->get_charset_collate();
        
        if (UJC_Schema_Manager::tableExists($table)) {
            $needsMigration = ($currentDbVersion === null) ||
                              ($currentDbVersion !== null && version_compare($currentDbVersion, '1.8', '<'));

            if ($needsMigration) {
                Logger::info('PriceHistoryRepository: Migrating price_per_m2 to DEFAULT NULL');

                $result = $wpdb->query("ALTER TABLE `{$table}` MODIFY COLUMN `price_per_m2` decimal(10,2) DEFAULT NULL");

                if ($result === false) {
                    Logger::error('PriceHistoryRepository: Migration failed - ' . $wpdb->last_error);
                    return false;
                }
            }

            return true;
        }
        
        $sql = "CREATE TABLE `{$table}` (
            " . PriceHistoryDto::FIELD_ID . " int(11) NOT NULL AUTO_INCREMENT,
            " . PriceHistoryDto::FIELD_RESOURCE_ID . " int(11) NOT NULL,
            " . PriceHistoryDto::FIELD_CENA_M2 . " decimal(10,2) DEFAULT NULL,
            " . PriceHistoryDto::FIELD_CENA_CALKOWITA . " decimal(12,2) NOT NULL,
            " . PriceHistoryDto::FIELD_DATA_ZMIANY . " datetime NOT NULL,
            " . PriceHistoryDto::FIELD_CENA_Z_DODATKAMI . " decimal(12,2) NOT NULL,
            " . PriceHistoryDto::FIELD_DATA_CENA_Z_DODATKAMI . " datetime NOT NULL,
            
            PRIMARY KEY (" . PriceHistoryDto::FIELD_ID . "),
            KEY " . PriceHistoryDto::FIELD_RESOURCE_ID . " (" . PriceHistoryDto::FIELD_RESOURCE_ID . "),
            KEY " . PriceHistoryDto::FIELD_DATA_ZMIANY . " (" . PriceHistoryDto::FIELD_DATA_ZMIANY . "),
            FOREIGN KEY (" . PriceHistoryDto::FIELD_RESOURCE_ID . ") REFERENCES " . TableNames::getResources() . "(id) ON DELETE CASCADE
        ) " . $charset_collate;
        
        return $wpdb->query($sql) !== false;
    }
}