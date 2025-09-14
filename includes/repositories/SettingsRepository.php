<?php

if (!defined('ABSPATH')) {
    exit;
}

class SettingsRepository {
    
    const LAST_GENERATION_STATUS = 'last_generation_status';
    const LAST_GENERATION_TIME = 'last_generation_time';
    const DB_VERSION = 'db_version';
    
    /**
     * Zapisuje status ostatniej generacji
     */
    public function setLastGenerationStatus($status) {
        return update_option(self::LAST_GENERATION_STATUS, $status);
    }
    
    /**
     * Zapisuje czas ostatniej generacji (domyślnie aktualny czas)
     */
    public function setLastGenerationTime($time = null) {
        $time = $time ?: time();
        return update_option(self::LAST_GENERATION_TIME, $time);
    }
    
    
    /**
     * Ustawia wersję bazy danych
     */
    public function setDbVersion($version) {
        return update_option(self::DB_VERSION, $version);
    }
    
    /**
     * Pobiera wersję bazy danych
     */
    public function getDbVersion() {
        return get_option(self::DB_VERSION, DB_VERSION);
    }
}