<?php

if (!defined('ABSPATH')) {
    exit;
}

class ResourceRepository {
    
    /**
     * Get all resources
     */
    public function readAll() {
        global $wpdb;
        $table = $wpdb->prefix . 'ujc_resources'; // Will be updated to 'resources' later
        
        $sql = "SELECT r.* FROM $table r ORDER BY r.created_at DESC";
                
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Get resource by ID
     */
    public function readById($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'ujc_resources'; // Will be updated to 'resources' later
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d", 
            $id
        ), ARRAY_A);
    }
    
    /**
     * Save resource (create or update)
     */
    public function save($data, $id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'ujc_resources'; // Will be updated to 'resources' later
        
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
        global $wpdb;
        $table = $wpdb->prefix . 'ujc_resources'; // Will be updated to 'resources' later
        
        return $wpdb->delete($table, ['id' => $id]);
    }
}