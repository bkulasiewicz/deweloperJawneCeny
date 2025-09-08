<?php

if (!defined('ABSPATH')) {
    exit;
}

class BelongingRoomsRepository {
    
    /**
     * Get all belonging rooms for a resource
     */
    public function getByResourceId($resourceId): array {
        $table = TableNames::getResourceBelongingRooms();        
        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM `{$table}` WHERE resource_id = %d ORDER BY created_at ASC", 
            $resourceId
        ), ARRAY_A);
        
        $dtos = [];
        foreach($results as $row) {
            $dtos[] = BelongingRoomDto::databaseToModel($row);
        }
        
        return $dtos;
    }
    
    /**
     * Save belonging room for a resource
     */
    public function save(BelongingRoomDto $dto) {
        $table = TableNames::getResourceBelongingRooms();        
        global $wpdb;
        
        $data = $dto->modelToDatabase();
        
        return $wpdb->insert($table, $data);
    }
    
    /**
     * Delete all belonging rooms for a resource
     */
    public function deleteByResourceId($resourceId) {
        $table = TableNames::getResourceBelongingRooms();        
        global $wpdb;
        return $wpdb->delete($table, ['resource_id' => $resourceId], ['%d']);
    }
    
    /**
     * Update belonging room
     */
    public function update(BelongingRoomDto $dto) {
        $table = TableNames::getResourceBelongingRooms();        
        global $wpdb;
        
        $data = $dto->modelToDatabase();
        
        return $wpdb->update($table, $data, ['id' => $dto->id], null, ['%d']);
    }
    
    /**
     * Delete belonging room by ID
     */
    public function delete($id) {
        $table = TableNames::getResourceBelongingRooms();        
        global $wpdb;
        return $wpdb->delete($table, ['id' => $id], ['%d']);
    }
}