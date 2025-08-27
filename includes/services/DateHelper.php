<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Date formatting helper for the entire application
 */
class DateHelper {
    
    /**
     * Current date and time in UTC for database storage
     */
    public static function currentDatetime(): string {
        return gmdate('Y-m-d H:i:s');
    }
    
    /**
     * Format UTC date from database for user display (DD.MM.YYYY HH:MM)
     * Converts from UTC to WordPress local timezone
     */
    public static function formatForUser($date): string {
        if (empty($date)) return '';
        // Convert UTC from database to WordPress local time
        return wp_date('d.m.Y H:i', strtotime($date . ' UTC'));
    }
    
    /**
     * Format UTC date from database for CSV export (YYYY-MM-DD HH:MM:SS)
     * Converts from UTC to WordPress local timezone
     */
    public static function formatForCsv($date): string {
        if (empty($date)) return '';
        // Convert UTC from database to WordPress local time
        return wp_date('Y-m-d H:i:s', strtotime($date . ' UTC'));
    }
    
    /**
     * Format date for XML (YYYY-MM-DD)
     * Converts from UTC to WordPress local timezone. If $date not provided, uses current date
     */
    public static function formatForXml($date = null): string {
        if ($date === null) {
            // For current date use local time
            return wp_date('Y-m-d');
        }
        if (empty($date)) return '';
        // Convert UTC from database to WordPress local time
        return wp_date('Y-m-d', strtotime($date . ' UTC'));
    }
    
    /**
     * Format date for filenames and identifiers (YYYYMMDD)
     * Converts from UTC to WordPress local timezone. If $date not provided, uses current date
     */
    public static function formatForFilename($date = null): string {
        if ($date === null) {
            // For current date use local time
            return wp_date('Ymd');
        }
        if (empty($date)) return '';
        // Convert UTC from database to WordPress local time
        return wp_date('Ymd', strtotime($date . ' UTC'));
    }
    
    /**
     * Format timestamp for user display (DD.MM.YYYY HH:MM)
     * Converts UTC timestamp to WordPress local timezone
     */
    public static function formatTimestampForUser($timestamp): string {
        if (empty($timestamp)) return '';
        return wp_date('d.m.Y H:i', $timestamp);
    }
}