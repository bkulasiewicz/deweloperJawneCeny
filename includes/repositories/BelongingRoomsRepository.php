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
            "SELECT * FROM `{$table}` WHERE resource_id = %d ORDER BY id ASC", 
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
    
    /**
     * Create belonging rooms table
     */
    public function createTable(): bool {
        global $wpdb;
        $table = TableNames::getResourceBelongingRooms();
        $charset_collate = $wpdb->get_charset_collate();
        
        if (UJC_Schema_Manager::tableExists($table)) {
            return true;
        }
        
        $sql = "CREATE TABLE `{$table}` (
            " . BelongingRoomDto::FIELD_ID . " int(11) NOT NULL AUTO_INCREMENT,
            " . BelongingRoomDto::FIELD_RESOURCE_ID . " int(11) NOT NULL,
            " . BelongingRoomDto::FIELD_TYPE . " varchar(100) NOT NULL,
            " . BelongingRoomDto::FIELD_DESIGNATION . " varchar(50),
            " . BelongingRoomDto::FIELD_PRICE . " decimal(10,2),
            " . BelongingRoomDto::FIELD_PRICE_DATE . " datetime,
            
            PRIMARY KEY (" . BelongingRoomDto::FIELD_ID . "),
            KEY " . BelongingRoomDto::FIELD_RESOURCE_ID . " (" . BelongingRoomDto::FIELD_RESOURCE_ID . ")
        ) " . $charset_collate;
        
        return $wpdb->query($wpdb->prepare($sql)) !== false;
    }
}