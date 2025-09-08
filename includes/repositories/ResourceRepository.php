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
        
        $results = $wpdb->get_results($wpdb->prepare("SELECT r.* FROM `{$table}` r ORDER BY r.created_at ASC"), ARRAY_A);
        
        $dtos = [];
        foreach($results as $row) {
            $dtos[] = ResourceDto::databaseToModel($row);
        }
        
        return $dtos;
    }
    
    /**
     * Get resource by ID
     */
    public function readById($id) {
        $table = TableNames::getResources();
        global $wpdb;
        
        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM `{$table}` WHERE id = %d", 
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
        
        $data = $dto->modelToDatabase();
        $result = $wpdb->insert($table, $data);
        
        if ($result === false) {
            throw new Exception('Failed to create resource: ' . $wpdb->last_error);
        }
        
        return $wpdb->insert_id;
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
}