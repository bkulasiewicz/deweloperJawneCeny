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
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'publication_history';
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
        
        return $result !== false;
    }
    
    /**
     * Get publication history with limit
     * 
     * @param int $limit Number of records to retrieve
     * @return array Array of history entries
     */
    public function getHistory($limit = 50) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM `{$this->table_name}` 
             ORDER BY timestamp DESC 
             LIMIT %d",
            $limit
        ), ARRAY_A);
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