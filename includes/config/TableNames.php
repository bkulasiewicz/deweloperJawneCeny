<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

class TableNames {
    
    public static function getDeveloperInfo(): string {
        global $wpdb;
        return $wpdb->prefix . 'developers';
    }
    
    public static function getInvestmentInfo(): string {
        global $wpdb;
        return $wpdb->prefix . 'investments';
    }
    
    public static function getResources(): string {
        global $wpdb;
        return $wpdb->prefix . 'resources';
    }
    
    public static function getPriceHistory(): string {
        global $wpdb;
        return $wpdb->prefix . 'price_history';
    }
    
    public static function getPublicationHistory(): string {
        global $wpdb;
        return $wpdb->prefix . 'publication_history';
    }
    
    public static function getXmlResource(): string {
        global $wpdb;
        return $wpdb->prefix . 'xml_resources';
    }
    
}