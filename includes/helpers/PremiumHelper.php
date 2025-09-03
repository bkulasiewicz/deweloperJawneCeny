<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Premium Helper
 * 
 * Provides utility methods for premium feature detection and messaging.
 */
class PremiumHelper {
    
    public static function is_premium() {
        return file_exists(PLUGIN_DIR . 'includes/premium/');
    }
    
    public static function get_upgrade_message() {
        return 'Funkcje automatycznego publikowania danych dostępne na 
                <a href="https://www.deweloperjawneceny.pl" target="_blank">www.deweloperjawneceny.pl</a>';
    }
}