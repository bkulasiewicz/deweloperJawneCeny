<?php

if (!defined('ABSPATH')) {
    exit;
}

class OtherServicesRepository {
    
    /**
     * Get all other services for a resource
     */
    public function getByResourceId($resourceId): array {
        $table = TableNames::getResourceOtherServices();        
        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM `{$table}` WHERE resource_id = %d ORDER BY id ASC", 
            $resourceId
        ), ARRAY_A);
        
        $dtos = [];
        foreach($results as $row) {
            $dtos[] = OtherServiceDto::databaseToModel($row);
        }
        
        return $dtos;
    }
    
    /**
     * Save other services for a resource
     */
    public function save(OtherServiceDto $dto) {
        $table = TableNames::getResourceOtherServices();        
        global $wpdb;
        
        $data = $dto->modelToDatabase();
        
        return $wpdb->insert($table, $data);
    }
    
    /**
     * Delete all other services for a resource
     */
    public function deleteByResourceId($resourceId) {
        $table = TableNames::getResourceOtherServices();        
        global $wpdb;
        return $wpdb->delete($table, ['resource_id' => $resourceId], ['%d']);
    }
    
    /**
     * Update other services
     */
    public function update(OtherServiceDto $dto) {
        $table = TableNames::getResourceOtherServices();        
        global $wpdb;
        
        $data = $dto->modelToDatabase();
        
        return $wpdb->update($table, $data, ['id' => $dto->id], null, ['%d']);
    }
    
    /**
     * Delete other services by ID
     */
    public function delete($id) {
        $table = TableNames::getResourceOtherServices();        
        global $wpdb;
        return $wpdb->delete($table, ['id' => $id], ['%d']);
    }
    
    /**
     * Create other services table using OtherServiceDto field constants
     */
    public function createTable(): bool {
        global $wpdb;
        $table = TableNames::getResourceOtherServices();
        $charset_collate = $wpdb->get_charset_collate();
        
        if (UJC_Schema_Manager::tableExists($table)) {
            return true;
        }
        
        $sql = "CREATE TABLE `{$table}` (
            " . OtherServiceDto::FIELD_ID . " int(11) NOT NULL AUTO_INCREMENT,
            " . OtherServiceDto::FIELD_RESOURCE_ID . " int(11) NOT NULL,
            " . OtherServiceDto::FIELD_DESCRIPTION . " text NOT NULL,
            " . OtherServiceDto::FIELD_PRICE . " decimal(10,2),
            " . OtherServiceDto::FIELD_PRICE_DATE . " datetime,
            
            PRIMARY KEY (" . OtherServiceDto::FIELD_ID . "),
            KEY " . OtherServiceDto::FIELD_RESOURCE_ID . " (" . OtherServiceDto::FIELD_RESOURCE_ID . ")
        ) " . $charset_collate;
        
        return $wpdb->query($wpdb->prepare($sql)) !== false;
    }
}