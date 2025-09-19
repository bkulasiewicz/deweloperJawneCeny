<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Unified Logger for UJC Plugin
 * Single source of truth for all plugin logging
 */
class Logger {
    
    const LOG_RETENTION_DAYS = 3;
    
    private static $log_file = null;
    private static $initialized = false;
    
    /**
     * Generate current log file name with date
     * 
     * @return string Log filename with current date
     */
    private static function getCurrentLogFileName() {
        return 'ujc-debug-' . DateHelper::getCurrentDateYmd() . '.log';
    }
    
    /**
     * Initialize logger with dedicated log file
     */
    public static function init() {
        if (self::$initialized) {
            return;
        }
        
        // Set log file with current date
        $log_filename = self::getCurrentLogFileName();
        self::$log_file = WP_CONTENT_DIR . '/' . $log_filename;
        self::$initialized = true;
        
        // Create log file if it doesn't exist
        if (!file_exists(self::$log_file)) {
            wp_mkdir_p(dirname(self::$log_file));
            file_put_contents(self::$log_file, '');
        }
        
        // Clean old log files only once per day
        $last_cleanup = get_option('logger_last_cleanup', '');
        $today = DateHelper::getCurrentDateYmd();
        
        if ($last_cleanup !== $today) {
            try {
                self::cleanOldLogFiles();
                // Only update if cleanup succeeded
                update_option('logger_last_cleanup', $today);
            } catch (Exception $e) {
                // Log the error but don't update the cleanup date
                // This ensures cleanup will be retried on next init
                // Note: Using direct error_log here to avoid infinite recursion
                // since we're already in Logger initialization
                error_log('UJC Logger cleanup failed: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Remove log files older than retention period
     * Optimized version - no glob() or regex, just direct file checks
     */
    private static function cleanOldLogFiles() {
        // Check files for the last 10 days (safety margin)
        for ($i = self::LOG_RETENTION_DAYS + 1; $i <= 10; $i++) {
            $old_timestamp = time() - ($i * 86400);
            $old_date = wp_date('Y-m-d', $old_timestamp);
            $old_file = WP_CONTENT_DIR . '/ujc-debug-' . $old_date . '.log';
            
            if (file_exists($old_file)) {
                wp_delete_file($old_file);
            }
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
        
        // Check if we need to rotate to today's log file (lazy rotation)
        $current_log_filename = self::getCurrentLogFileName();
        $expected_path = WP_CONTENT_DIR . '/' . $current_log_filename;
        
        if (self::$log_file !== $expected_path) {
            self::$log_file = $expected_path;
            
            // Create new log file if it doesn't exist
            if (!file_exists(self::$log_file)) {
                wp_mkdir_p(dirname(self::$log_file));
                file_put_contents(self::$log_file, '');
            }
        }
        
        $formatted = sprintf(
            "[%s] [%s] %s",
            DateHelper::currentDatetime(),
            strtoupper($type),
            $message
        );
        
        // Write to current log file
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
        
        // Ensure we're reading from current day's log
        $current_log_filename = self::getCurrentLogFileName();
        $current_log_path = WP_CONTENT_DIR . '/' . $current_log_filename;
        
        if (file_exists($current_log_path) && is_readable($current_log_path)) {
            return file_get_contents($current_log_path);
        }
        
        return "Brak logów UJC za dzisiaj - plik nie istnieje lub nie jest dostępny";
    }
    
    /**
     * Clear all logs
     */
    public static function clearLogs() {
        if (!self::$initialized) {
            self::init();
        }

        // Initialize WordPress filesystem
        if (!function_exists('WP_Filesystem')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        WP_Filesystem();
        global $wp_filesystem;

        // Clear only current day's log
        $current_log_filename = self::getCurrentLogFileName();
        $current_log_path = WP_CONTENT_DIR . '/' . $current_log_filename;

        // Use WordPress filesystem for security
        if ($wp_filesystem) {
            $wp_filesystem->put_contents($current_log_path, '');
        } else {
            // Fallback if filesystem not available
            file_put_contents($current_log_path, '');
        }

        // Update our internal reference
        self::$log_file = $current_log_path;

        self::log('Logi za dzisiaj zostały wyczyszczone', 'INFO');
    }
}