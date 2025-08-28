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
        
        $sql = "SELECT r.* FROM $table r ORDER BY r.created_at DESC";
                
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Get resource by ID
     */
    public function readById($id) {
        $table = TableNames::getResources();
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d", 
            $id
        ), ARRAY_A);
    }
    
    /**
     * Save resource (create or update)
     */
    public function save($data, $id = null) {
        $table = TableNames::getResources();
        global $wpdb;
        
        if ($id) {
            // Update existing
            $result = $wpdb->update($table, $data, ['id' => $id]);
            return $result !== false ? $id : false;
        } else {
            // Create new
            $result = $wpdb->insert($table, $data);
            return $result !== false ? $wpdb->insert_id : false;
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