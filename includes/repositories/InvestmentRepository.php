<?php

if (!defined('ABSPATH')) {
    exit;
}

class InvestmentRepository {
    
    /**
     * Get investment data
     */
    public function read() {
        $table = TableNames::getInvestmentInfo();        
        global $wpdb;
        return $wpdb->get_row("SELECT * FROM $table LIMIT 1", ARRAY_A);
    }
    
    /**
     * Save investment data (create or update)
     */
    public function save($data) {
        $table = TableNames::getInvestmentInfo();        
        global $wpdb;
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