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
            "SELECT * FROM `{$table}` WHERE resource_id = %d ORDER BY created_at ASC", 
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
}