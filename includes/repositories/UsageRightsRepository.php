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
            "SELECT * FROM `{$table}` WHERE resource_id = %d ORDER BY created_at ASC", 
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
}