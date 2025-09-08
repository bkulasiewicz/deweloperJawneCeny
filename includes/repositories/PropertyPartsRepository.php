<?php

if (!defined('ABSPATH')) {
    exit;
}

class PropertyPartsRepository {
    
    /**
     * Get all property parts for a resource
     */
    public function getByResourceId($resourceId): array {
        $table = TableNames::getResourcePropertyParts();        
        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM `{$table}` WHERE resource_id = %d ORDER BY created_at ASC", 
            $resourceId
        ), ARRAY_A);
        
        $dtos = [];
        foreach($results as $row) {
            $dtos[] = PropertyPartDto::databaseToModel($row);
        }
        
        return $dtos;
    }
    
    /**
     * Save property part for a resource
     */
    public function save(PropertyPartDto $dto) {
        $table = TableNames::getResourcePropertyParts();        
        global $wpdb;
        
        $data = $dto->modelToDatabase();
        
        return $wpdb->insert($table, $data);
    }
    
    /**
     * Delete all property parts for a resource
     */
    public function deleteByResourceId($resourceId) {
        $table = TableNames::getResourcePropertyParts();        
        global $wpdb;
        return $wpdb->delete($table, ['resource_id' => $resourceId], ['%d']);
    }
    
    /**
     * Update property part
     */
    public function update(PropertyPartDto $dto) {
        $table = TableNames::getResourcePropertyParts();        
        global $wpdb;
        
        $data = $dto->modelToDatabase();
        
        return $wpdb->update($table, $data, ['id' => $dto->id], null, ['%d']);
    }
    
    /**
     * Delete property part by ID
     */
    public function delete($id) {
        $table = TableNames::getResourcePropertyParts();        
        global $wpdb;
        return $wpdb->delete($table, ['id' => $id], ['%d']);
    }
}