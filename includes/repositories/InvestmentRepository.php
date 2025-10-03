<?php

namespace JawneCeny;

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
        $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i LIMIT 1", $table), ARRAY_A);

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
        
        if (JawneCeny_SchemaManager::tableExists($table)) {
            return true;
        }

        $developerTable = TableNames::getDeveloperInfo();

        $sql = $wpdb->prepare(
            "CREATE TABLE %i (
                %i int(11) NOT NULL AUTO_INCREMENT,
                %i int(11) NOT NULL DEFAULT 1,
                %i varchar(255) NOT NULL,
                %i varchar(50) NOT NULL,
                %i varchar(50),
                %i varchar(50),
                %i varchar(50) NOT NULL,
                %i varchar(100),
                %i varchar(20),
                %i varchar(10),

                %i tinyint(1) DEFAULT 0,
                %i tinyint(1) DEFAULT 0,
                %i tinyint(1) DEFAULT 0,
                %i tinyint(1) DEFAULT 0,

                %i tinyint(1) DEFAULT 0,
                %i tinyint(1) DEFAULT 0,
                %i tinyint(1) DEFAULT 0,
                %i tinyint(1) DEFAULT 0,
                %i tinyint(1) DEFAULT 0,

                PRIMARY KEY (%i),
                FOREIGN KEY (%i) REFERENCES %i(id) ON DELETE CASCADE
            ) " . $charset_collate,
            $table,
            InvestmentDto::FIELD_ID,
            InvestmentDto::FIELD_DEVELOPER_ID,
            InvestmentDto::FIELD_NAME,
            InvestmentDto::FIELD_PROJ_WOJEWODZTWO,
            InvestmentDto::FIELD_PROJ_POWIAT,
            InvestmentDto::FIELD_PROJ_GMINA,
            InvestmentDto::FIELD_PROJ_MIEJSCOWOSC,
            InvestmentDto::FIELD_PROJ_ULICA,
            InvestmentDto::FIELD_PROJ_NR,
            InvestmentDto::FIELD_PROJ_KOD,
            InvestmentDto::FIELD_HAS_PROPERTY_PARTS,
            InvestmentDto::FIELD_HAS_BELONGING_ROOMS,
            InvestmentDto::FIELD_HAS_USAGE_RIGHTS,
            InvestmentDto::FIELD_HAS_OTHER_SERVICES,
            InvestmentDto::FIELD_SHOW_FLOOR_FIELD,
            InvestmentDto::FIELD_SHOW_ROOMS_FIELD,
            InvestmentDto::FIELD_SHOW_DESCRIPTION_FIELD,
            InvestmentDto::FIELD_SHOW_GARDEN_FIELD,
            InvestmentDto::FIELD_SHOW_FLOOR_PLAN_FIELD,
            InvestmentDto::FIELD_ID,
            InvestmentDto::FIELD_DEVELOPER_ID,
            $developerTable
        );

        return $wpdb->query($sql) !== false;
    }
    
}