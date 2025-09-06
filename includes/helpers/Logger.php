<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Unified Logger for UJC Plugin
 * Single source of truth for all plugin logging
 */
class Logger {
    
    private static $log_file = null;
    private static $initialized = false;
    
    /**
     * Initialize logger with dedicated log file
     */
    public static function init() {
        if (self::$initialized) {
            return;
        }
        
        // Set dedicated log file for the plugin
        self::$log_file = WP_CONTENT_DIR . '/ujc-debug.log';
        self::$initialized = true;
        
        // Create log file if it doesn't exist
        if (!file_exists(self::$log_file)) {
            touch(self::$log_file);
        }
    }
    
    /**
     * Log message with consistent format
     * 
     * @param string $message Log message
     * @param string $type Log type (INFO, ERROR, SUCCESS, etc.)
     */
    public static function log($message, $type = 'INFO') {
        if (!self::$initialized) {
            self::init();
        }
        
        $formatted = sprintf(
            "[%s] [%s] %s",
            gmdate('Y-m-d H:i:s'),
            strtoupper($type),
            $message
        );
        
        // Write to our dedicated log file
        error_log($formatted . PHP_EOL, 3, self::$log_file);
    }
    
    /**
     * Log info message
     */
    public static function info($message) {
        self::log($message, 'INFO');
    }
    
    /**
     * Log error message
     */
    public static function error($message) {
        self::log($message, 'ERROR');
    }
    
    /**
     * Log success message
     */
    public static function success($message) {
        self::log($message, 'SUCCESS');
    }
    
    /**
     * Get log file path
     * 
     * @return string Path to log file
     */
    public static function getLogFile() {
        if (!self::$initialized) {
            self::init();
        }
        return self::$log_file;
    }
    
    /**
     * Get all log contents
     * 
     * @return string Log file contents
     */
    public static function getLogs() {
        if (!self::$initialized) {
            self::init();
        }
        
        if (file_exists(self::$log_file) && is_readable(self::$log_file)) {
            return file_get_contents(self::$log_file);
        }
        
        return "Brak logów UJC - plik nie istnieje lub nie jest dostępny";
    }
    
    /**
     * Clear all logs
     */
    public static function clearLogs() {
        if (!self::$initialized) {
            self::init();
        }
        
        file_put_contents(self::$log_file, '');
        self::log('Logi zostały wyczyszczone', 'INFO');
    }
}