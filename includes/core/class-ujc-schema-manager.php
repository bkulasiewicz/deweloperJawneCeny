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
        self::create_developer_table($wpdb, $charset_collate);
        
        // 2. INWESTYCJA
        self::create_investment_table($wpdb, $charset_collate);
        
        // 3. ZASOBY/NIERUCHOMOŚCI
        self::create_resources_table($wpdb, $charset_collate);
        
        
        // 5. HISTORIA CEN
        self::create_price_history_table($wpdb, $charset_collate);
        
        // 6. HISTORIA PUBLIKACJI
        self::create_publication_history_table($wpdb, $charset_collate);
        
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
            TableNames::getResources(), 
            TableNames::getInvestmentInfo(),
            TableNames::getDeveloperInfo()
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
    
    private static function create_developer_table($wpdb, $charset_collate) {
        $table = TableNames::getDeveloperInfo();
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
            return;
        }
        
        $wpdb->query($wpdb->prepare("CREATE TABLE `{$table}` (
            id int(11) NOT NULL AUTO_INCREMENT,
            nazwa varchar(255) NOT NULL,
            forma_prawna varchar(100),
            nr_krs varchar(20),
            nr_ceidg varchar(20),
            nr_nip varchar(15),
            nr_regon varchar(20),
            telefon varchar(20),
            email varchar(100),
            fax varchar(20),
            strona_www varchar(255),
            
            siedz_wojewodztwo varchar(50),
            siedz_powiat varchar(50),
            siedz_gmina varchar(50),
            siedz_miejscowosc varchar(50),
            siedz_ulica varchar(100),
            siedz_nr varchar(20),
            siedz_lokal varchar(20),
            siedz_kod varchar(10),
            
            sprzed_wojewodztwo varchar(50),
            sprzed_powiat varchar(50),
            sprzed_gmina varchar(50),
            sprzed_miejscowosc varchar(50),
            sprzed_ulica varchar(100),
            sprzed_nr varchar(20),
            sprzed_lokal varchar(20),
            sprzed_kod varchar(10),
            
            dodatkowe_lokalizacje text,
            sposob_kontaktu text,
            prospekt_url varchar(255),
            
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) %s", $charset_collate));
    }
    
    private static function create_investment_table($wpdb, $charset_collate) {
        $table = TableNames::getInvestmentInfo();
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
            return;
        }
        
        $wpdb->query($wpdb->prepare("CREATE TABLE `{$table}` (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            proj_wojewodztwo varchar(50) NOT NULL,
            proj_powiat varchar(50),
            proj_gmina varchar(50),
            proj_miejscowosc varchar(50) NOT NULL,
            proj_ulica varchar(100),
            proj_nr varchar(20),
            proj_kod varchar(10),
            
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) %s", $charset_collate));
    }
    
    private static function create_resources_table($wpdb, $charset_collate) {
        $table = TableNames::getResources();
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
            return;
        }
        
        $wpdb->query($wpdb->prepare("CREATE TABLE `{$table}` (
            id int(11) NOT NULL AUTO_INCREMENT,
            
            rodzaj_nieruchomosci enum('Lokal mieszkalny', 'Dom jednorodzinny', 'Miejsce postojowe', 'Komórka lokatorska', 'Część nieruchomości', 'Garaż') NOT NULL,
            nr_lokalu varchar(50) NOT NULL,
            powierzchnia_uzytkowa decimal(8,2) NOT NULL,
            
            cena_m2 decimal(10,2) NOT NULL,
            data_cena_m2 datetime NOT NULL,
            
            cena_calkowita decimal(12,2),
            data_cena_calkowita datetime,
            
            cena_z_dodatkami decimal(12,2),
            data_cena_z_dodatkami datetime,
            
            status enum('dostepny', 'sprzedany', 'rezerwacja') DEFAULT 'dostepny',
            
            extra_rodzaj_czesci varchar(100),
            extra_oznaczenie_czesci varchar(50),
            extra_cena_czesci decimal(10,2),
            extra_data_cena_czesci datetime,
            
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            PRIMARY KEY (id),
            KEY status (status)
        ) %s", $charset_collate));
    }
    
    
    private static function create_price_history_table($wpdb, $charset_collate) {
        $table = TableNames::getPriceHistory();
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
            return;
        }
        
        $wpdb->query($wpdb->prepare("CREATE TABLE `{$table}` (
            id int(11) NOT NULL AUTO_INCREMENT,
            resource_id int(11) NOT NULL,
            
            cena_m2_old decimal(10,2),
            cena_m2_new decimal(10,2) NOT NULL,
            data_zmiany datetime NOT NULL,
            
            cena_calkowita_old decimal(12,2),
            cena_calkowita_new decimal(12,2),
            
            cena_z_dodatkami_old decimal(12,2),
            cena_z_dodatkami_new decimal(12,2),
            
            user_id int(11),
            
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            
            PRIMARY KEY (id),
            KEY resource_id (resource_id),
            KEY data_zmiany (data_zmiany)
        ) %s", $charset_collate));
    }
    
    private static function create_publication_history_table($wpdb, $charset_collate) {
        $table = $wpdb->prefix . 'publication_history';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
            return;
        }
        
        $wpdb->query($wpdb->prepare("CREATE TABLE `{$table}` (
            id int(11) NOT NULL AUTO_INCREMENT,
            timestamp int(11) NOT NULL,
            status varchar(20) NOT NULL,
            message text,
            trigger_type varchar(20) NOT NULL,
            
            PRIMARY KEY (id),
            KEY timestamp (timestamp),
            KEY status (status)
        ) %s", $charset_collate));
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
     * Usuwa tabelę developer_info i odtwarza ją
     */
    public static function reset_developer_table() {
        global $wpdb;
        $table = TableNames::getDeveloperInfo();
        $wpdb->query("DROP TABLE IF EXISTS $table");
        self::create_developer_table($wpdb, $wpdb->get_charset_collate());
    }
    
    /**
     * Usuwa tabelę investment_info i odtwarza ją
     */
    public static function reset_investment_table() {
        global $wpdb;
        $table = TableNames::getInvestmentInfo();
        $wpdb->query("DROP TABLE IF EXISTS $table");
        self::create_investment_table($wpdb, $wpdb->get_charset_collate());
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
        
        self::create_resources_table($wpdb, $charset_collate);
        self::create_price_history_table($wpdb, $charset_collate);
        self::create_foreign_keys($wpdb);
        
        // Włącz z powrotem sprawdzanie kluczy obcych
        $wpdb->query("SET FOREIGN_KEY_CHECKS=1");
    }
}