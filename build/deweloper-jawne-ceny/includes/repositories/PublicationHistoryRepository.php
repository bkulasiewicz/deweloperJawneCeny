<?php

namespace JawneCeny;

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
        
        $result = $wpdb->insert($this->table_name, $data);
        
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
            "SELECT * FROM %i
             ORDER BY %i DESC
             LIMIT %d",
            $this->table_name,
            PublicationHistoryDto::FIELD_TIMESTAMP,
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

        $result = $wpdb->query($wpdb->prepare("TRUNCATE TABLE %i", $this->table_name));

        return $result !== false;
    }
    
    /**
     * Get last entry from history
     *
     * @return PublicationHistoryDto|null Last history entry or null if empty
     */
    public function getLastEntry() {
        global $wpdb;

        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM %i
             ORDER BY %i DESC
             LIMIT %d",
            $this->table_name,
            PublicationHistoryDto::FIELD_TIMESTAMP,
            1
        ), ARRAY_A);

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
                "SELECT COUNT(*) FROM %i WHERE %i = %s",
                $this->table_name,
                PublicationHistoryDto::FIELD_STATUS,
                $status
            ));
        } else {
            return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM %i", $this->table_name));
        }
    }
    
    /**
     * Create publication history table using PublicationHistoryDto field constants
     */
    public function createTable(?string $currentDbVersion = null): bool {
        global $wpdb;
        $table = TableNames::getPublicationHistory();
        $charset_collate = $wpdb->get_charset_collate();

        if (JawneCeny_SchemaManager::tableExists($table)) {
            return true;
        }

        $sql = $wpdb->prepare(
            "CREATE TABLE %i (
                %i int(11) NOT NULL AUTO_INCREMENT,
                %i datetime NOT NULL,
                %i varchar(20) NOT NULL,
                %i text,
                %i varchar(20) NOT NULL,

                PRIMARY KEY (%i),
                KEY %i (%i),
                KEY %i (%i)
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $charset_collate is safe, from $wpdb->get_charset_collate()
            ) " . $charset_collate,
            $table,
            PublicationHistoryDto::FIELD_ID,
            PublicationHistoryDto::FIELD_TIMESTAMP,
            PublicationHistoryDto::FIELD_STATUS,
            PublicationHistoryDto::FIELD_MESSAGE,
            PublicationHistoryDto::FIELD_TRIGGER_TYPE,
            PublicationHistoryDto::FIELD_ID,
            PublicationHistoryDto::FIELD_TIMESTAMP,
            PublicationHistoryDto::FIELD_TIMESTAMP,
            PublicationHistoryDto::FIELD_STATUS,
            PublicationHistoryDto::FIELD_STATUS
        );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is already prepared via $wpdb->prepare() above
        return $wpdb->query($sql) !== false;
    }
}