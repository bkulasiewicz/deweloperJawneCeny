<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Repository for managing publication history records
 */
class PublicationHistoryRepository {
    
    private $table_name;
    
    public function __construct() {
        $this->table_name = TableNames::getPublicationHistory();
    }
    
    
    /**
     * Add new entry to publication history
     * 
     * @param PublicationHistoryDto $dto Publication history data
     * @return bool Success status
     */
    public function addEntry(PublicationHistoryDto $dto) {
        global $wpdb;
        
        $data = $dto->modelToDatabase();
        
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            ['%d', '%s', '%s', '%s']
        );
        
        if ($result === false) {
            return false;
        }
        return true;
    }
    
    /**
     * Get publication history with limit
     * 
     * @param int $limit Number of records to retrieve
     * @return PublicationHistoryDto[] Array of history entries
     */
    public function getHistory($limit = 50) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM `{$this->table_name}` 
             ORDER BY timestamp DESC 
             LIMIT %d",
            $limit
        ), ARRAY_A);
        
        if ($results === null) {
            return [];
        }
        
        // Convert database rows to DTO objects
        $dtos = [];
        foreach ($results ?: [] as $row) {
            $dtos[] = PublicationHistoryDto::databaseToModel($row);
        }
        
        return $dtos;
    }
    
    /**
     * Clear all history entries
     * 
     * @return bool Success status
     */
    public function clearHistory() {
        global $wpdb;
        
        $result = $wpdb->query("TRUNCATE TABLE {$this->table_name}");
        
        return $result !== false;
    }
    
    /**
     * Get last entry from history
     * 
     * @return PublicationHistoryDto|null Last history entry or null if empty
     */
    public function getLastEntry() {
        global $wpdb;
        
        $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$this->table_name}` 
                  ORDER BY timestamp DESC 
                  LIMIT %d", 1), ARRAY_A);
        
        return $result ? PublicationHistoryDto::databaseToModel($result) : null;
    }
    
    /**
     * Count history entries by status
     * 
     * @param string|null $status Filter by status (optional)
     * @return int Number of entries
     */
    public function countEntries($status = null) {
        global $wpdb;
        
        if ($status) {
            return (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM `{$this->table_name}` WHERE status = %s",
                $status
            ));
        } else {
            return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `{$this->table_name}`"));
        }
    }
}