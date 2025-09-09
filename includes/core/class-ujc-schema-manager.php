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
        
        // 3. ZASOBY/NIERUCHOMOŚCI (with integrated component fields)
        (new ResourceRepository())->createTable();
        
        // 4. HISTORIA CEN
        (new PriceHistoryRepository())->createTable();
        
        // 6. HISTORIA PUBLIKACJI
        (new PublicationHistoryRepository())->createTable();
        
        // 7. XML RESOURCES
        (new XmlResourceRepository())->create();
        
        // 8. KLUCZE OBCE
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
            TableNames::getXmlResource(),
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
        self::dropTable(TableNames::getPublicationHistory());
        (new PublicationHistoryRepository())->createTable();
    }
    
    /**
     * Usuwa tabelę xml_resources i odtwarza ją
     */
    public static function reset_xml_resource_table() {
        self::dropTable(TableNames::getXmlResource());
        (new XmlResourceRepository())->create();
    }
}