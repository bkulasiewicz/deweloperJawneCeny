<?php

if (!defined('ABSPATH')) {
    exit;
}

class UJC_Database_Versioning {
    
    public static function init() {
        add_action('plugins_loaded', [__CLASS__, 'check_version']);
    }
    
    public static function check_version() {
        $installed_version = get_option('ujc_db_version', '0');
        
        if ($installed_version !== DB_VERSION) {
            UJC_Schema_Manager::create_tables();
            update_option('ujc_db_version', DB_VERSION);
        }
    }
    
    public static function force_recreate_tables() {
        UJC_Schema_Manager::create_tables();
        update_option('ujc_db_version', DB_VERSION);
    }
}