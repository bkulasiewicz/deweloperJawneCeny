<?php

if (!defined('ABSPATH')) {
    exit;
}

class XmlResourceRepository {
    
    private $table_name;
    
    public function __construct() {
        $this->table_name = TableNames::getXmlResource();
    }
    
    /**
     * Create table structure
     */
    public function createTable(?string $currentDbVersion = null): bool {
        global $wpdb;

        if (UJC_Schema_Manager::tableExists($this->table_name)) {
            return true;
        }

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE `{$this->table_name}` (
            `" . XmlResourceDto::FIELD_ID . "` int(11) NOT NULL AUTO_INCREMENT,
            `" . XmlResourceDto::FIELD_EXT_IDENT . "` varchar(36) NOT NULL,
            `" . XmlResourceDto::FIELD_CSV_URL . "` varchar(500) NOT NULL,
            `" . XmlResourceDto::FIELD_DATA_DATE . "` date NOT NULL,
            `" . XmlResourceDto::FIELD_CREATED_AT . "` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`" . XmlResourceDto::FIELD_ID . "`),
            UNIQUE KEY `unique_ext_ident` (`" . XmlResourceDto::FIELD_EXT_IDENT . "`),
            UNIQUE KEY `unique_daily_publication` (`" . XmlResourceDto::FIELD_DATA_DATE . "`)
        ) $charset_collate;";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- CREATE TABLE statement with field constants is safe
        return $wpdb->query($sql) !== false;
    }
    
    /**
     * Add new XML resource to database (replaces existing entry for same date)
     * Uses REPLACE INTO to handle daily publications - one file per day
     * 
     * @param XmlResourceDto $dto Resource data
     * @return bool Success status
     */
    public function addResource(XmlResourceDto $dto): bool {
        global $wpdb;
        
        $data = $dto->modelToDatabase();
        
        // Use REPLACE INTO to handle duplicate data_date
        // This will replace existing entry for the same date
        $result = $wpdb->replace($this->table_name, $data);
        
        return $result !== false;
    }
    
    /**
     * Get all XML resources ordered chronologically
     * 
     * @return XmlResourceDto[] Array of XML resources
     */
    public function readAll(): array {
        global $wpdb;
        
        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Field name constants are safe static strings
        $results = $wpdb->get_results(
            "SELECT * FROM `{$this->table_name}`
             ORDER BY `" . XmlResourceDto::FIELD_DATA_DATE . "` ASC",
            ARRAY_A
        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
        
        if ($results === null) {
            return [];
        }
        
        $dtos = [];
        foreach ($results ?: [] as $row) {
            $dtos[] = XmlResourceDto::databaseToModel($row);
        }
        
        return $dtos;
    }
    
    /**
     * Get single XML resource by ID
     * 
     * @param int $id Resource ID
     * @return XmlResourceDto|null Resource or null if not found
     */
    public function readById(int $id): ?XmlResourceDto {
        global $wpdb;
        
        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Field name constants are safe static strings
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM `{$this->table_name}` WHERE `" . XmlResourceDto::FIELD_ID . "` = %d",
            $id
        ), ARRAY_A);
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
        
        if ($result === null) {
            return null;
        }
        
        return XmlResourceDto::databaseToModel($result);
    }
}