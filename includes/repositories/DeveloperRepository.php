<?php

if (!defined('ABSPATH')) {
    exit;
}

class DeveloperRepository {
    
    /**
     * Get developer data
     */
    public function read() {
        global $wpdb;
        $table = $wpdb->prefix . 'ujc_developer_info'; // Will be updated to 'developers' later
        
        return $wpdb->get_row("SELECT * FROM $table LIMIT 1", ARRAY_A);
    }
    
    /**
     * Save developer data (create or update)
     */
    public function save($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'ujc_developer_info'; // Will be updated to 'developers' later
        
        // Check if record already exists
        $existing = $this->read();
        
        if ($existing) {
            // Update existing
            return $wpdb->update($table, $data, ['id' => $existing['id']]);
        } else {
            // Create new
            return $wpdb->insert($table, $data);
        }
    }
}