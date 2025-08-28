<?php

if (!defined('ABSPATH')) {
    exit;
}

class SettingsRepository {
    
    const GENERATION_INTERVAL = 'generation_interval';
    const AUTOMATION_ENABLED = 'automation_enabled';
    const LAST_GENERATION_STATUS = 'last_generation_status';
    const LAST_GENERATION_TIME = 'last_generation_time';
    const GENERATION_HISTORY = 'generation_history';
    const DB_VERSION = 'db_version';
    
    /**
     * Zwraca interwał generowania plików (1min, 15min, 1hour, 24hours)
     */
    public function getGenerationInterval($default = '24hours') {
        return get_option(self::GENERATION_INTERVAL, $default);
    }
    
    /**
     * Ustawia interwał generowania plików
     */
    public function setGenerationInterval($interval) {
        return update_option(self::GENERATION_INTERVAL, $interval);
    }
    
    /**
     * Sprawdza czy automatyzacja generowania jest włączona
     */
    public function isAutomationEnabled($default = true) {
        return get_option(self::AUTOMATION_ENABLED, $default);
    }
    
    /**
     * Włącza lub wyłącza automatyzację generowania
     */
    public function setAutomationEnabled($enabled) {
        return update_option(self::AUTOMATION_ENABLED, (bool)$enabled);
    }
    
    /**
     * Zwraca status ostatniej generacji (success, error, itp.)
     */
    public function getLastGenerationStatus() {
        return get_option(self::LAST_GENERATION_STATUS);
    }
    
    /**
     * Zapisuje status ostatniej generacji
     */
    public function setLastGenerationStatus($status) {
        return update_option(self::LAST_GENERATION_STATUS, $status);
    }
    
    /**
     * Zwraca timestamp ostatniej generacji
     */
    public function getLastGenerationTime() {
        return get_option(self::LAST_GENERATION_TIME);
    }
    
    /**
     * Zapisuje czas ostatniej generacji (domyślnie aktualny czas)
     */
    public function setLastGenerationTime($time = null) {
        $time = $time ?: time();
        return update_option(self::LAST_GENERATION_TIME, $time);
    }
    
    /**
     * Zwraca historię generacji plików jako array
     */
    public function getGenerationHistory($default = []) {
        return get_option(self::GENERATION_HISTORY, $default);
    }
    
    /**
     * Zapisuje historię generacji plików
     */
    public function setGenerationHistory($history) {
        return update_option(self::GENERATION_HISTORY, $history);
    }
    
    /**
     * Zwraca wersję bazy danych
     */
    public function getDbVersion($default = '0') {
        return get_option(self::DB_VERSION, $default);
    }
    
    /**
     * Ustawia wersję bazy danych
     */
    public function setDbVersion($version) {
        return update_option(self::DB_VERSION, $version);
    }
}