<?php

if (!defined('ABSPATH')) {
    exit;
}

class UJC_Investment_Repository {
    
    /**
     * Pobiera dane inwestycji
     */
    public function read() {
        global $wpdb;
        $table = $wpdb->prefix . 'ujc_investment_info';
        
        return $wpdb->get_row("SELECT * FROM $table LIMIT 1", ARRAY_A);
    }
    
    /**
     * Zapisuje dane inwestycji (create lub update)
     */
    public function save($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'ujc_investment_info';
        
        // Sprawdź czy już istnieje rekord
        $existing = $this->read();
        
        if ($existing) {
            // Aktualizuj istniejący
            return $wpdb->update($table, $data, ['id' => $existing['id']]);
        } else {
            // Utwórz nowy
            return $wpdb->insert($table, $data);
        }
    }
}