<?php

if (!defined('ABSPATH')) {
    exit;
}

class InvestmentRepository {
    
    /**
     * Get investment data as model
     */
    public function read(): ?InvestmentDto {
        $table = TableNames::getInvestmentInfo();        
        global $wpdb;
        $data = $wpdb->get_row("SELECT * FROM `{$table}` LIMIT 1", ARRAY_A);
        
        return $data ? InvestmentDto::databaseToModel($data) : null;
    }
    
    /**
     * Create new investment
     */
    public function create(InvestmentDto $dto): int {
        $table = TableNames::getInvestmentInfo();        
        global $wpdb;
        
        Logger::info('UJC: InvestmentRepository::create() started');
        Logger::info('UJC: Table name: ' . $table);
        
        $data = $dto->modelToDatabase();
        Logger::info('UJC: Data to insert: ' . print_r($data, true));
        
        $result = $wpdb->insert($table, $data);
        Logger::info('UJC: Insert result: ' . ($result !== false ? 'SUCCESS' : 'FAILED'));
        
        if ($result === false) {
            Logger::error('UJC: Insert failed. Last error: ' . $wpdb->last_error);
            Logger::error('UJC: Last query: ' . $wpdb->last_query);
            throw new Exception('Failed to create investment: ' . esc_html($wpdb->last_error));
        }
        
        $insert_id = $wpdb->insert_id;
        Logger::info('UJC: Created investment with ID: ' . $insert_id);
        
        return $insert_id;
    }
    
    /**
     * Update existing investment
     */
    public function update(InvestmentDto $dto, int $id): void {
        $table = TableNames::getInvestmentInfo();        
        global $wpdb;
        
        Logger::info('UJC: InvestmentRepository::update() started for ID: ' . $id);
        Logger::info('UJC: Table name: ' . $table);
        
        $data = $dto->modelToDatabase();
        Logger::info('UJC: Data to update: ' . print_r($data, true));
        
        $result = $wpdb->update($table, $data, ['id' => $id]);
        Logger::info('UJC: Update result: ' . ($result !== false ? 'SUCCESS (rows affected: ' . $result . ')' : 'FAILED'));
        
        if ($result === false) {
            Logger::error('UJC: Update failed. Last error: ' . $wpdb->last_error);
            Logger::error('UJC: Last query: ' . $wpdb->last_query);
            throw new Exception('Failed to update investment: ' . esc_html($wpdb->last_error));
        }
        
        Logger::info('UJC: Investment update completed successfully');
    }
    
    /**
     * Create investment table using InvestmentDto field constants
     */
    public function createTable(?string $currentDbVersion = null): bool {
        global $wpdb;
        $table = TableNames::getInvestmentInfo();
        $charset_collate = $wpdb->get_charset_collate();
        
        if (UJC_Schema_Manager::tableExists($table)) {
            return true;
        }
        
        $sql = "CREATE TABLE `{$table}` (
            " . InvestmentDto::FIELD_ID . " int(11) NOT NULL AUTO_INCREMENT,
            " . InvestmentDto::FIELD_DEVELOPER_ID . " int(11) NOT NULL DEFAULT 1,
            " . InvestmentDto::FIELD_NAME . " varchar(255) NOT NULL,
            " . InvestmentDto::FIELD_PROJ_WOJEWODZTWO . " varchar(50) NOT NULL,
            " . InvestmentDto::FIELD_PROJ_POWIAT . " varchar(50),
            " . InvestmentDto::FIELD_PROJ_GMINA . " varchar(50),
            " . InvestmentDto::FIELD_PROJ_MIEJSCOWOSC . " varchar(50) NOT NULL,
            " . InvestmentDto::FIELD_PROJ_ULICA . " varchar(100),
            " . InvestmentDto::FIELD_PROJ_NR . " varchar(20),
            " . InvestmentDto::FIELD_PROJ_KOD . " varchar(10),
            
            " . InvestmentDto::FIELD_HAS_PROPERTY_PARTS . " tinyint(1) DEFAULT 0,
            " . InvestmentDto::FIELD_HAS_BELONGING_ROOMS . " tinyint(1) DEFAULT 0,
            " . InvestmentDto::FIELD_HAS_USAGE_RIGHTS . " tinyint(1) DEFAULT 0,
            " . InvestmentDto::FIELD_HAS_OTHER_SERVICES . " tinyint(1) DEFAULT 0,
            
            " . InvestmentDto::FIELD_SHOW_FLOOR_FIELD . " tinyint(1) DEFAULT 0,
            " . InvestmentDto::FIELD_SHOW_ROOMS_FIELD . " tinyint(1) DEFAULT 0,
            " . InvestmentDto::FIELD_SHOW_DESCRIPTION_FIELD . " tinyint(1) DEFAULT 0,
            " . InvestmentDto::FIELD_SHOW_GARDEN_FIELD . " tinyint(1) DEFAULT 0,
            " . InvestmentDto::FIELD_SHOW_FLOOR_PLAN_FIELD . " tinyint(1) DEFAULT 0,
            
            PRIMARY KEY (" . InvestmentDto::FIELD_ID . "),
            FOREIGN KEY (" . InvestmentDto::FIELD_DEVELOPER_ID . ") REFERENCES " . TableNames::getDeveloperInfo() . "(id) ON DELETE CASCADE
        ) " . $charset_collate;
        
        // Safe: Table names come from validated constants, no user input
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return $wpdb->query($sql) !== false;
    }
    
}