<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Zarządzanie schematem bazy danych
 */
class UJC_Schema_Manager {
    
    /**
     * Tworzy wszystkie tabele wtyczki
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // 1. DEWELOPER
        (new DeveloperRepository())->createTable();
        
        // 2. INWESTYCJA
        (new InvestmentRepository())->createTable();
        
        // 3. ZASOBY/NIERUCHOMOŚCI
        (new ResourceRepository())->createTable();
        
        // 4. KOMPONENTY ZASOBÓW
        (new PropertyPartsRepository())->createTable();
        (new BelongingRoomsRepository())->createTable();
        (new UsageRightsRepository())->createTable();
        (new OtherServicesRepository())->createTable();
        
        // 5. HISTORIA CEN
        (new PriceHistoryRepository())->createTable();
        
        // 6. HISTORIA PUBLIKACJI
        (new PublicationHistoryRepository())->createTable();
        
        // 7. KLUCZE OBCE
        self::create_foreign_keys($wpdb);
    }
    
    /**
     * Usuwa wszystkie tabele (dev only)
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = [
            TableNames::getPriceHistory(),
            TableNames::getPublicationHistory(),
            TableNames::getResourcePropertyParts(),
            TableNames::getResourceBelongingRooms(),
            TableNames::getResourceUsageRights(),
            TableNames::getResourceOtherServices(),
            TableNames::getResources(), 
            TableNames::getInvestmentInfo(),
            TableNames::getDeveloperInfo()
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
    
    private static function create_foreign_keys($wpdb) {
        $resources_table = TableNames::getResources();
        $price_history_table = TableNames::getPriceHistory();
        $property_parts_table = TableNames::getResourcePropertyParts();
        $belonging_rooms_table = TableNames::getResourceBelongingRooms();
        $usage_rights_table = TableNames::getResourceUsageRights();
        $other_services_table = TableNames::getResourceOtherServices();
        
        // FK dla price history
        if (!self::foreign_key_exists($wpdb, $price_history_table, 'resource_id')) {
            $wpdb->query("
                ALTER TABLE $price_history_table 
                ADD CONSTRAINT fk_history_ujc_resources 
                FOREIGN KEY (resource_id) 
                REFERENCES $resources_table(id) 
                ON DELETE CASCADE
            ");
        }
        
        // FK dla property parts
        if (!self::foreign_key_exists($wpdb, $property_parts_table, 'resource_id')) {
            $wpdb->query("
                ALTER TABLE $property_parts_table 
                ADD CONSTRAINT fk_property_parts_ujc_resources 
                FOREIGN KEY (resource_id) 
                REFERENCES $resources_table(id) 
                ON DELETE CASCADE
            ");
        }
        
        // FK dla belonging rooms
        if (!self::foreign_key_exists($wpdb, $belonging_rooms_table, 'resource_id')) {
            $wpdb->query("
                ALTER TABLE $belonging_rooms_table 
                ADD CONSTRAINT fk_belonging_rooms_ujc_resources 
                FOREIGN KEY (resource_id) 
                REFERENCES $resources_table(id) 
                ON DELETE CASCADE
            ");
        }
        
        // FK dla usage rights
        if (!self::foreign_key_exists($wpdb, $usage_rights_table, 'resource_id')) {
            $wpdb->query("
                ALTER TABLE $usage_rights_table 
                ADD CONSTRAINT fk_usage_rights_ujc_resources 
                FOREIGN KEY (resource_id) 
                REFERENCES $resources_table(id) 
                ON DELETE CASCADE
            ");
        }
        
        // FK dla other services
        if (!self::foreign_key_exists($wpdb, $other_services_table, 'resource_id')) {
            $wpdb->query("
                ALTER TABLE $other_services_table 
                ADD CONSTRAINT fk_other_services_ujc_resources 
                FOREIGN KEY (resource_id) 
                REFERENCES $resources_table(id) 
                ON DELETE CASCADE
            ");
        }
    }
    
    private static function foreign_key_exists($wpdb, $table, $column) {
        $constraints = $wpdb->get_results("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = '$table' 
            AND COLUMN_NAME = '$column'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        return !empty($constraints);
    }
    
    /**
     * Check if table exists
     */
    public static function tableExists(string $table): bool {
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
    }
    
    /**
     * Drop table if exists
     */
    public static function dropTable(string $table): bool {
        global $wpdb;
        return $wpdb->query("DROP TABLE IF EXISTS `$table`") !== false;
    }
    
    /**
     * Usuwa tabelę developer_info i odtwarza ją
     */
    public static function reset_developer_table() {
        self::dropTable(TableNames::getDeveloperInfo());
        (new DeveloperRepository())->createTable();
    }
    
    /**
     * Usuwa tabelę investment_info i odtwarza ją
     */
    public static function reset_investment_table() {
        self::dropTable(TableNames::getInvestmentInfo());
        (new InvestmentRepository())->createTable();
    }
    
    /**
     * Usuwa wszystkie tabele związane z zasobami i odtwarza je
     */
    public static function reset_resources_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Wyłącz sprawdzanie kluczy obcych
        $wpdb->query("SET FOREIGN_KEY_CHECKS=0");
        
        $tables = [
            TableNames::getPriceHistory(),
            TableNames::getResources()
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        (new ResourceRepository())->createTable();
        (new PriceHistoryRepository())->createTable();
        self::create_foreign_keys($wpdb);
        
        // Włącz z powrotem sprawdzanie kluczy obcych
        $wpdb->query("SET FOREIGN_KEY_CHECKS=1");
    }
    
    /**
     * Usuwa tabelę publication_history i odtwarza ją
     */
    public static function reset_publication_history_table() {
        global $wpdb;
        $table = TableNames::getPublicationHistory();
        
        // Sprawdź czy tabela istnieje przed usunięciem
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) == $table;
        
        if ($table_exists) {
            // Użyj TRUNCATE do wyczyszczenia danych zamiast DROP+CREATE
            $result = $wpdb->query("TRUNCATE TABLE `$table`");
            if ($result !== false) {
                Logger::success("Publication History Reset: TRUNCATE SUCCESS");
            } else {
                Logger::error("Publication History Reset: TRUNCATE FAILED - Error: " . $wpdb->last_error);
                // Fallback: try to recreate table
                Logger::info("Publication History Reset: Attempting table recreation as fallback");
                $wpdb->query("DROP TABLE IF EXISTS `$table`");
                (new PublicationHistoryRepository())->createTable();
            }
        } else {
            // Tabela nie istnieje, utwórz ją
            Logger::info("Publication History Reset: Table doesn't exist, creating new one");
            (new PublicationHistoryRepository())->createTable();
        }
    }
}