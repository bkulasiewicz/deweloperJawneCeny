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
     * Check if publication history table exists
     * 
     * @return bool True if table exists
     */
    private function tableExists() {
        global $wpdb;
        $result = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $this->table_name));
        return $result == $this->table_name;
    }
    
    /**
     * Add new entry to publication history
     * 
     * @param string $status 'success' or 'error'
     * @param string $message Detailed message or error description
     * @param string $trigger_type 'manual' or 'external_cron'
     * @return bool Success status
     */
    public function addEntry($status, $message, $trigger_type) {
        global $wpdb;
        
        // Check if table exists first
        if (!$this->tableExists()) {
            Logger::error("PublicationHistory: Table '{$this->table_name}' does not exist!");
            return false;
        }
        
        Logger::info("PublicationHistory: Inserting entry - Status: {$status}, Trigger: {$trigger_type}, Table: {$this->table_name}");
        
        $result = $wpdb->insert(
            $this->table_name,
            [
                'timestamp' => time(),
                'status' => $status,
                'message' => $message,
                'trigger_type' => $trigger_type
            ],
            ['%d', '%s', '%s', '%s']
        );
        
        if ($result === false) {
            Logger::error("PublicationHistory: INSERT FAILED - Error: " . $wpdb->last_error);
            Logger::error("PublicationHistory: Last query: " . $wpdb->last_query);
            return false;
        }
        
        Logger::success("PublicationHistory: INSERT SUCCESS - Inserted ID: " . $wpdb->insert_id);
        return true;
    }
    
    /**
     * Get publication history with limit
     * 
     * @param int $limit Number of records to retrieve
     * @return array Array of history entries
     */
    public function getHistory($limit = 50) {
        global $wpdb;
        
        // Check if table exists first
        if (!$this->tableExists()) {
            Logger::error("PublicationHistory: Table '{$this->table_name}' does not exist for getHistory()!");
            return [];
        }
        
        Logger::info("PublicationHistory: Getting history from table: {$this->table_name}, limit: {$limit}");
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM `{$this->table_name}` 
             ORDER BY timestamp DESC 
             LIMIT %d",
            $limit
        ), ARRAY_A);
        
        if ($results === null) {
            Logger::error("PublicationHistory: SELECT FAILED - Error: " . $wpdb->last_error);
            Logger::error("PublicationHistory: Last query: " . $wpdb->last_query);
            return [];
        }
        
        $count = is_array($results) ? count($results) : 0;
        Logger::success("PublicationHistory: SELECT SUCCESS - Found {$count} entries");
        
        return $results ?: [];
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
     * @return array|null Last history entry or null if empty
     */
    public function getLastEntry() {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$this->table_name}` 
                  ORDER BY timestamp DESC 
                  LIMIT %d", 1), ARRAY_A);
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