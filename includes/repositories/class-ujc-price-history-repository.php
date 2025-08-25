<?php

if (!defined('ABSPATH')) {
    exit;
}

class UJC_Price_History_Repository {
    
    /**
     * Pobiera historię cen dla zasobu
     */
    public function readByResourceId($resource_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'ujc_price_history';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE resource_id = %d ORDER BY data_zmiany DESC", 
            $resource_id
        ), ARRAY_A);
    }
    
    /**
     * Zapisuje historię zmian cen (rozszerzona logika)
     */
    public function saveHistory($resource_id, $old_data, $new_data) {
        global $wpdb;
        $table = $wpdb->prefix . 'ujc_price_history';
        
        // Sprawdź czy rzeczywiście nastąpiła zmiana cen
        $price_changed = false;
        $history_data = [
            'resource_id' => $resource_id,
            'data_zmiany' => UJC_Date_Helper::current_datetime(),
            'user_id' => get_current_user_id()
        ];
        
        // Sprawdź zmianę ceny m2
        if (isset($old_data['cena_m2']) && isset($new_data['cena_m2']) && 
            $old_data['cena_m2'] != $new_data['cena_m2']) {
            $history_data['cena_m2_old'] = $old_data['cena_m2'];
            $history_data['cena_m2_new'] = $new_data['cena_m2'];
            $price_changed = true;
        } else {
            $history_data['cena_m2_new'] = $new_data['cena_m2'];
        }
        
        // Sprawdź zmianę ceny całkowitej
        if (isset($old_data['cena_calkowita']) && isset($new_data['cena_calkowita']) && 
            $old_data['cena_calkowita'] != $new_data['cena_calkowita']) {
            $history_data['cena_calkowita_old'] = $old_data['cena_calkowita'];
            $history_data['cena_calkowita_new'] = $new_data['cena_calkowita'];
            $price_changed = true;
        } elseif (isset($new_data['cena_calkowita'])) {
            $history_data['cena_calkowita_new'] = $new_data['cena_calkowita'];
        }
        
        // Sprawdź zmianę ceny z dodatkami
        if (isset($old_data['cena_z_dodatkami']) && isset($new_data['cena_z_dodatkami']) && 
            $old_data['cena_z_dodatkami'] != $new_data['cena_z_dodatkami']) {
            $history_data['cena_z_dodatkami_old'] = $old_data['cena_z_dodatkami'];
            $history_data['cena_z_dodatkami_new'] = $new_data['cena_z_dodatkami'];
            $price_changed = true;
        } elseif (isset($new_data['cena_z_dodatkami'])) {
            $history_data['cena_z_dodatkami_new'] = $new_data['cena_z_dodatkami'];
        }
        
        // Zapisz historię tylko jeśli nastąpiła zmiana lub to pierwszy wpis
        if ($price_changed || empty($old_data)) {
            return $wpdb->insert($table, $history_data);
        }
        
        return false;
    }
    
    /**
     * Zapisuje nowy wpis w historii cen (prosta wersja)
     */
    public function save($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'ujc_price_history';
        
        return $wpdb->insert($table, $data);
    }
    
    /**
     * Pobiera pełną historię cen do eksportu CSV
     */
    public function readForExport() {
        global $wpdb;
        $history_table = $wpdb->prefix . 'ujc_price_history';
        $resources_table = $wpdb->prefix . 'ujc_resources';
        
        $sql = "
            SELECT DISTINCT
                r.nr_lokalu,
                r.rodzaj_nieruchomosci,
                r.powierzchnia_uzytkowa,
                COALESCE(latest_m2.cena_m2, r.cena_m2) as cena_m2,
                COALESCE(latest_m2.data_zmiany, r.data_cena_m2) as data_cena_m2,
                COALESCE(latest_calkowita.cena_calkowita, r.cena_calkowita) as cena_calkowita,
                COALESCE(latest_calkowita.data_zmiany, r.data_cena_calkowita) as data_cena_calkowita
            FROM $resources_table r
            LEFT JOIN (
                SELECT resource_id, cena_m2, data_zmiany,
                       ROW_NUMBER() OVER (PARTITION BY resource_id ORDER BY data_zmiany DESC) as rn
                FROM $history_table 
                WHERE cena_m2 IS NOT NULL
            ) latest_m2 ON r.id = latest_m2.resource_id AND latest_m2.rn = 1
            LEFT JOIN (
                SELECT resource_id, cena_calkowita, data_zmiany,
                       ROW_NUMBER() OVER (PARTITION BY resource_id ORDER BY data_zmiany DESC) as rn
                FROM $history_table 
                WHERE cena_calkowita IS NOT NULL
            ) latest_calkowita ON r.id = latest_calkowita.resource_id AND latest_calkowita.rn = 1
            ORDER BY r.nr_lokalu
        ";
        
        return $wpdb->get_results($sql, ARRAY_A);
    }
}