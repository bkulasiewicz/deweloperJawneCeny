<?php

if (!defined('ABSPATH')) {
    exit;
}

class UJC_Resource_Repository {
    
    /**
     * Pobiera wszystkie zasoby
     */
    public function readAll() {
        global $wpdb;
        $table = $wpdb->prefix . 'ujc_resources';
        
        $sql = "SELECT r.*, i.name as project_name 
                FROM $table r 
                LEFT JOIN {$wpdb->prefix}ujc_investments i ON r.investment_id = i.id 
                ORDER BY r.created_at DESC";
                
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Pobiera zasób po ID
     */
    public function readById($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'ujc_resources';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d", 
            $id
        ), ARRAY_A);
    }
    
    /**
     * Zapisuje zasób (create lub update)
     */
    public function save($data, $id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'ujc_resources';
        
        if ($id) {
            // Aktualizuj istniejący
            $result = $wpdb->update($table, $data, ['id' => $id]);
            return $result !== false ? $id : false;
        } else {
            // Utwórz nowy
            $result = $wpdb->insert($table, $data);
            return $result !== false ? $wpdb->insert_id : false;
        }
    }
    
    /**
     * Usuwa zasób
     */
    public function delete($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'ujc_resources';
        
        return $wpdb->delete($table, ['id' => $id]);
    }
    
}