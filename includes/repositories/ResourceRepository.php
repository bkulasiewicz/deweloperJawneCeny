<?php

if (!defined('ABSPATH')) {
    exit;
}

class ResourceRepository {
    
    /**
     * Get all resources
     */
    public function readAll() {
        $table = TableNames::getResources();
        global $wpdb;
        
        Logger::info("ResourceRepository::readAll - Table: $table");
        
        $results = $wpdb->get_results($wpdb->prepare("SELECT r.* FROM `{$table}` r ORDER BY r." . ResourceDto::FIELD_ID . " ASC"), ARRAY_A);
        
        Logger::info("ResourceRepository::readAll - SQL Results count: " . count($results));
        if ($wpdb->last_error) {
            Logger::error("ResourceRepository::readAll - SQL Error: " . $wpdb->last_error);
        }
        
        $dtos = [];
        foreach($results as $row) {
            $dtos[] = ResourceDto::databaseToModel($row);
        }
        
        Logger::info("ResourceRepository::readAll - DTOs count: " . count($dtos));
        
        return $dtos;
    }
    
    /**
     * Get resource by ID
     */
    public function readById($id) {
        $table = TableNames::getResources();
        global $wpdb;
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM `{$table}` WHERE " . ResourceDto::FIELD_ID . " = %d", 
            $id
        ), ARRAY_A);
        
        if (!$data) {
            return null;
        }
        
        return ResourceDto::databaseToModel($data);
    }
    
    /**
     * Create new resource
     */
    public function create(ResourceDto $dto): int {
        $table = TableNames::getResources();
        global $wpdb;
        
        Logger::info("ResourceRepository::create - Table: $table");
        
        $data = $dto->modelToDatabase();
        Logger::info("ResourceRepository::create - Data: " . json_encode($data));
        
        $result = $wpdb->insert($table, $data);
        
        if ($result === false) {
            Logger::error("ResourceRepository::create - Failed: " . $wpdb->last_error);
            Logger::error("ResourceRepository::create - Last query: " . $wpdb->last_query);
            throw new Exception('Failed to create resource: ' . $wpdb->last_error);
        }
        
        $insert_id = $wpdb->insert_id;
        Logger::info("ResourceRepository::create - Success, inserted ID: " . $insert_id);
        
        return $insert_id;
    }
    
    /**
     * Update existing resource
     */
    public function update(ResourceDto $dto, int $id): void {
        $table = TableNames::getResources();
        global $wpdb;
        
        $data = $dto->modelToDatabase();
        $result = $wpdb->update($table, $data, ['id' => $id]);
        
        if ($result === false) {
            throw new Exception('Failed to update resource: ' . $wpdb->last_error);
        }
    }
    
    /**
     * Delete resource
     */
    public function delete($id) {
        $table = TableNames::getResources();
        global $wpdb;
        
        return $wpdb->delete($table, ['id' => $id]);
    }
    
    /**
     * Create resources table using ResourceDto field constants
     */
    public function createTable(): bool {
        global $wpdb;
        $table = TableNames::getResources();
        $charset_collate = $wpdb->get_charset_collate();
        
        if (UJC_Schema_Manager::tableExists($table)) {
            return true;
        }
        
        // Statyczne listy synchronizowane z enum keys
        $propertyTypes = [
            PropertyType::RESIDENTIAL_UNIT->value,
            PropertyType::SINGLE_FAMILY_HOUSE->value,
            PropertyType::PARKING_SPACE->value,
            PropertyType::STORAGE_ROOM->value,
            PropertyType::GARAGE->value
        ];
        
        $statusTypes = [
            ResourceStatus::AVAILABLE->value,
            ResourceStatus::RESERVED->value,
            ResourceStatus::SOLD->value
        ];
        
        $propertyEnum = "'" . implode("', '", $propertyTypes) . "'";
        $statusEnum = "'" . implode("', '", $statusTypes) . "'";
        
        $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
            " . ResourceDto::FIELD_ID . " int(11) NOT NULL AUTO_INCREMENT,
            " . ResourceDto::FIELD_INVESTMENT_ID . " int(11) NOT NULL DEFAULT 1,
            " . ResourceDto::FIELD_RODZAJ_NIERUCHOMOSCI . " enum({$propertyEnum}) NOT NULL,
            " . ResourceDto::FIELD_NR_LOKALU . " varchar(50) NOT NULL,
            " . ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA . " decimal(8,2) NOT NULL,
            " . ResourceDto::FIELD_STATUS . " enum({$statusEnum}),
            
            " . ResourceDto::FIELD_PROPERTY_PART_TITLE . " varchar(100) DEFAULT NULL,
            " . ResourceDto::FIELD_PROPERTY_PART_DESIGNATION . " varchar(50) DEFAULT NULL,
            " . ResourceDto::FIELD_PROPERTY_PART_PRICE . " decimal(10,2) DEFAULT NULL,
            " . ResourceDto::FIELD_PROPERTY_PART_PRICE_DATE . " datetime DEFAULT NULL,
            
            " . ResourceDto::FIELD_BELONGING_ROOM_TITLE . " varchar(100) DEFAULT NULL,
            " . ResourceDto::FIELD_BELONGING_ROOM_DESIGNATION . " varchar(50) DEFAULT NULL,
            " . ResourceDto::FIELD_BELONGING_ROOM_PRICE . " decimal(10,2) DEFAULT NULL,
            " . ResourceDto::FIELD_BELONGING_ROOM_PRICE_DATE . " datetime DEFAULT NULL,
            
            " . ResourceDto::FIELD_USAGE_RIGHT_TITLE . " text DEFAULT NULL,
            " . ResourceDto::FIELD_USAGE_RIGHT_PRICE . " decimal(10,2) DEFAULT NULL,
            " . ResourceDto::FIELD_USAGE_RIGHT_PRICE_DATE . " datetime DEFAULT NULL,
            
            " . ResourceDto::FIELD_OTHER_SERVICE_TITLE . " text DEFAULT NULL,
            " . ResourceDto::FIELD_OTHER_SERVICE_PRICE . " decimal(10,2) DEFAULT NULL,
            " . ResourceDto::FIELD_OTHER_SERVICE_PRICE_DATE . " datetime DEFAULT NULL,
            
            PRIMARY KEY (" . ResourceDto::FIELD_ID . "),
            KEY " . ResourceDto::FIELD_STATUS . " (" . ResourceDto::FIELD_STATUS . "),
            FOREIGN KEY (" . ResourceDto::FIELD_INVESTMENT_ID . ") REFERENCES " . TableNames::getInvestmentInfo() . "(id) ON DELETE CASCADE
        ) " . $charset_collate;
        
        return $wpdb->query($wpdb->prepare($sql)) !== false;
    }
}