<?php

if (!defined('ABSPATH')) {
    exit;
}

class UsageRightsRepository {
    
    /**
     * Get all usage rights for a resource
     */
    public function getByResourceId($resourceId): array {
        $table = TableNames::getResourceUsageRights();        
        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM `{$table}` WHERE resource_id = %d ORDER BY id ASC", 
            $resourceId
        ), ARRAY_A);
        
        $dtos = [];
        foreach($results as $row) {
            $dtos[] = UsageRightDto::databaseToModel($row);
        }
        
        return $dtos;
    }
    
    /**
     * Save usage rights for a resource
     */
    public function save(UsageRightDto $dto) {
        $table = TableNames::getResourceUsageRights();        
        global $wpdb;
        
        $data = $dto->modelToDatabase();
        
        return $wpdb->insert($table, $data);
    }
    
    /**
     * Delete all usage rights for a resource
     */
    public function deleteByResourceId($resourceId) {
        $table = TableNames::getResourceUsageRights();        
        global $wpdb;
        return $wpdb->delete($table, ['resource_id' => $resourceId], ['%d']);
    }
    
    /**
     * Update usage rights
     */
    public function update(UsageRightDto $dto) {
        $table = TableNames::getResourceUsageRights();        
        global $wpdb;
        
        $data = $dto->modelToDatabase();
        
        return $wpdb->update($table, $data, ['id' => $dto->id], null, ['%d']);
    }
    
    /**
     * Delete usage rights by ID
     */
    public function delete($id) {
        $table = TableNames::getResourceUsageRights();        
        global $wpdb;
        return $wpdb->delete($table, ['id' => $id], ['%d']);
    }
    
    /**
     * Create usage rights table using UsageRightDto field constants
     */
    public function createTable(): bool {
        global $wpdb;
        $table = TableNames::getResourceUsageRights();
        $charset_collate = $wpdb->get_charset_collate();
        
        if (UJC_Schema_Manager::tableExists($table)) {
            return true;
        }
        
        $sql = "CREATE TABLE `{$table}` (
            " . UsageRightDto::FIELD_ID . " int(11) NOT NULL AUTO_INCREMENT,
            " . UsageRightDto::FIELD_RESOURCE_ID . " int(11) NOT NULL,
            " . UsageRightDto::FIELD_DESCRIPTION . " text NOT NULL,
            " . UsageRightDto::FIELD_PRICE . " decimal(10,2),
            " . UsageRightDto::FIELD_PRICE_DATE . " datetime,
            
            PRIMARY KEY (" . UsageRightDto::FIELD_ID . "),
            KEY " . UsageRightDto::FIELD_RESOURCE_ID . " (" . UsageRightDto::FIELD_RESOURCE_ID . ")
        ) " . $charset_collate;
        
        return $wpdb->query($wpdb->prepare($sql)) !== false;
    }
}