<?php

namespace JawneCeny;

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

        if (JawneCeny_SchemaManager::tableExists($this->table_name)) {
            return true;
        }

        $charset_collate = $wpdb->get_charset_collate();

        $sql = $wpdb->prepare(
            "CREATE TABLE %i (
                %i int(11) NOT NULL AUTO_INCREMENT,
                %i varchar(36) NOT NULL,
                %i varchar(500) NOT NULL,
                %i date NOT NULL,
                %i datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (%i),
                UNIQUE KEY unique_ext_ident (%i),
                UNIQUE KEY unique_daily_publication (%i)
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $charset_collate is safe, from $wpdb->get_charset_collate()
            ) $charset_collate",
            $this->table_name,
            XmlResourceDto::FIELD_ID,
            XmlResourceDto::FIELD_EXT_IDENT,
            XmlResourceDto::FIELD_CSV_URL,
            XmlResourceDto::FIELD_DATA_DATE,
            XmlResourceDto::FIELD_CREATED_AT,
            XmlResourceDto::FIELD_ID,
            XmlResourceDto::FIELD_EXT_IDENT,
            XmlResourceDto::FIELD_DATA_DATE
        );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql is already prepared via $wpdb->prepare() above
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

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM %i ORDER BY %i ASC",
            $this->table_name,
            XmlResourceDto::FIELD_DATA_DATE
        ), ARRAY_A);

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
            "SELECT * FROM %i WHERE %i = %d",
            $this->table_name,
            XmlResourceDto::FIELD_ID,
            $id
        ), ARRAY_A);

        if ($result === null) {
            return null;
        }

        return XmlResourceDto::databaseToModel($result);
    }
}