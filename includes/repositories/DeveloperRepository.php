<?php

if (!defined('ABSPATH')) {
    exit;
}

class DeveloperRepository {
    
    /**
     * Get developer data
     */
    public function read(): ?SupplierDto {
        $table = TableNames::getDeveloperInfo();        
        global $wpdb;
        $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table}` LIMIT 1"), ARRAY_A);
        
        return $data ? SupplierDto::databaseToModel($data) : null;
    }
    
    /**
     * Save developer data (create or update)
     */
    public function save(SupplierDto $dto) {
        $table = TableNames::getDeveloperInfo();        
        global $wpdb;
        
        $data = $dto->modelToDatabase();
        
        // Check if record already exists
        $existing = $this->read();
        
        if ($existing) {
            // Update existing
            return $wpdb->update($table, $data, ['id' => $existing->id]);
        } else {
            // Create new
            return $wpdb->insert($table, $data);
        }
    }
}