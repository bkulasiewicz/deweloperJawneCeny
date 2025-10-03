<?php

namespace JawneCeny;

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
            "SELECT * FROM %i WHERE %i = %d ORDER BY %i DESC",
            $table,
            PriceHistoryDto::FIELD_RESOURCE_ID,
            $resource_id,
            PriceHistoryDto::FIELD_ID
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
            "SELECT * FROM %i WHERE %i = %d ORDER BY %i DESC LIMIT 1",
            $table,
            PriceHistoryDto::FIELD_RESOURCE_ID,
            $resource_id,
            PriceHistoryDto::FIELD_ID
        ), ARRAY_A);

        if (!$result) {
            throw new Exception("No price history found for resource " . esc_html($resource_id));
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
        
        if (JawneCeny_SchemaManager::tableExists($table)) {
            $needsMigration = ($currentDbVersion === null) ||
                              ($currentDbVersion !== null && version_compare($currentDbVersion, '1.8', '<'));

            if ($needsMigration) {
                Logger::info('PriceHistoryRepository: Migrating price_per_m2 to DEFAULT NULL');

                $result = $wpdb->query($wpdb->prepare(
                    "ALTER TABLE %i MODIFY COLUMN %i decimal(10,2) DEFAULT NULL",
                    $table,
                    PriceHistoryDto::FIELD_CENA_M2
                ));

                if ($result === false) {
                    Logger::error('PriceHistoryRepository: Migration failed - ' . $wpdb->last_error);
                    return false;
                }
            }

            return true;
        }
        
        $resourcesTable = TableNames::getResources();

        $sql = $wpdb->prepare(
            "CREATE TABLE %i (
                %i int(11) NOT NULL AUTO_INCREMENT,
                %i int(11) NOT NULL,
                %i decimal(10,2) DEFAULT NULL,
                %i decimal(12,2) NOT NULL,
                %i datetime NOT NULL,
                %i decimal(12,2) NOT NULL,
                %i datetime NOT NULL,

                PRIMARY KEY (%i),
                KEY %i (%i),
                KEY %i (%i),
                FOREIGN KEY (%i) REFERENCES %i(id) ON DELETE CASCADE
            ) " . $charset_collate,
            $table,
            PriceHistoryDto::FIELD_ID,
            PriceHistoryDto::FIELD_RESOURCE_ID,
            PriceHistoryDto::FIELD_CENA_M2,
            PriceHistoryDto::FIELD_CENA_CALKOWITA,
            PriceHistoryDto::FIELD_DATA_ZMIANY,
            PriceHistoryDto::FIELD_CENA_Z_DODATKAMI,
            PriceHistoryDto::FIELD_DATA_CENA_Z_DODATKAMI,
            PriceHistoryDto::FIELD_ID,
            PriceHistoryDto::FIELD_RESOURCE_ID,
            PriceHistoryDto::FIELD_RESOURCE_ID,
            PriceHistoryDto::FIELD_DATA_ZMIANY,
            PriceHistoryDto::FIELD_DATA_ZMIANY,
            PriceHistoryDto::FIELD_RESOURCE_ID,
            $resourcesTable
        );

        return $wpdb->query($sql) !== false;
    }
}