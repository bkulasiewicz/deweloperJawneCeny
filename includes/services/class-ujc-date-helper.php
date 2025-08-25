<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper do zarządzania formatami dat w całej aplikacji
 */
class UJC_Date_Helper {
    
    /**
     * Aktualna data i czas w UTC do zapisu w bazie danych
     */
    public static function current_datetime() {
        return gmdate('Y-m-d H:i:s');
    }
    
    /**
     * Formatuje datę UTC z bazy dla użytkownika (DD.MM.YYYY HH:MM)
     * Konwertuje z UTC na lokalną strefę czasową WordPress
     */
    public static function format_for_user($date) {
        if (empty($date)) return '';
        // Konwertuj UTC z bazy na lokalny czas WordPress
        return wp_date('d.m.Y H:i', strtotime($date . ' UTC'));
    }
    
    /**
     * Formatuje datę UTC z bazy dla CSV export (YYYY-MM-DD HH:MM:SS)
     * Konwertuje z UTC na lokalną strefę czasową WordPress
     */
    public static function format_for_csv($date) {
        if (empty($date)) return '';
        // Konwertuj UTC z bazy na lokalny czas WordPress
        return wp_date('Y-m-d H:i:s', strtotime($date . ' UTC'));
    }
    
    /**
     * Formatuje datę dla XML (YYYY-MM-DD)
     * Konwertuje z UTC na lokalną strefę czasową WordPress. Jeśli $date nie podano, używa aktualnej daty
     */
    public static function format_for_xml($date = null) {
        if ($date === null) {
            // Dla aktualnej daty użyj lokalnego czasu
            return wp_date('Y-m-d');
        }
        if (empty($date)) return '';
        // Konwertuj UTC z bazy na lokalny czas WordPress
        return wp_date('Y-m-d', strtotime($date . ' UTC'));
    }
    
    /**
     * Formatuje datę dla nazw plików i identyfikatorów (YYYYMMDD)
     * Konwertuje z UTC na lokalną strefę czasową WordPress. Jeśli $date nie podano, używa aktualnej daty
     */
    public static function format_for_filename($date = null) {
        if ($date === null) {
            // Dla aktualnej daty użyj lokalnego czasu
            return wp_date('Ymd');
        }
        if (empty($date)) return '';
        // Konwertuj UTC z bazy na lokalny czas WordPress
        return wp_date('Ymd', strtotime($date . ' UTC'));
    }
}