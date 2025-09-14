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
    public function create(string $currentDbVersion) {
        global $wpdb;
        
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
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
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
        
        $results = $wpdb->get_results(
            "SELECT * FROM `{$this->table_name}` 
             ORDER BY `" . XmlResourceDto::FIELD_DATA_DATE . "` ASC",
            ARRAY_A
        );
        
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
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM `{$this->table_name}` WHERE `" . XmlResourceDto::FIELD_ID . "` = %d",
            $id
        ), ARRAY_A);
        
        if ($result === null) {
            return null;
        }
        
        return XmlResourceDto::databaseToModel($result);
    }
}