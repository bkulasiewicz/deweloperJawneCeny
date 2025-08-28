<?php

if (!defined('ABSPATH')) {
    exit;
}

class PriceHistoryRepository {
    
    /**
     * Get price history for resource
     */
    public function readByResourceId($resource_id) {
        $table = TableNames::getPriceHistory();
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE resource_id = %d ORDER BY data_zmiany DESC", 
            $resource_id
        ), ARRAY_A);
    }
    
    /**
     * Save price change history (extended logic)
     */
    public function saveHistory($resource_id, $old_data, $new_data) {
        $table = TableNames::getPriceHistory();
        global $wpdb;
        
        // Check if price really changed
        $price_changed = false;
        $history_data = [
            'resource_id' => $resource_id,
            'data_zmiany' => DateHelper::currentDatetime(),
            'user_id' => get_current_user_id()
        ];
        
        // Check price per sqm change
        if (isset($old_data['cena_m2']) && isset($new_data['cena_m2']) && 
            $old_data['cena_m2'] != $new_data['cena_m2']) {
            $history_data['cena_m2_old'] = $old_data['cena_m2'];
            $history_data['cena_m2_new'] = $new_data['cena_m2'];
            $price_changed = true;
        } else {
            $history_data['cena_m2_new'] = $new_data['cena_m2'];
        }
        
        // Check total price change
        if (isset($old_data['cena_calkowita']) && isset($new_data['cena_calkowita']) && 
            $old_data['cena_calkowita'] != $new_data['cena_calkowita']) {
            $history_data['cena_calkowita_old'] = $old_data['cena_calkowita'];
            $history_data['cena_calkowita_new'] = $new_data['cena_calkowita'];
            $price_changed = true;
        } elseif (isset($new_data['cena_calkowita'])) {
            $history_data['cena_calkowita_new'] = $new_data['cena_calkowita'];
        }
        
        // Check price with extras change
        if (isset($old_data['cena_z_dodatkami']) && isset($new_data['cena_z_dodatkami']) && 
            $old_data['cena_z_dodatkami'] != $new_data['cena_z_dodatkami']) {
            $history_data['cena_z_dodatkami_old'] = $old_data['cena_z_dodatkami'];
            $history_data['cena_z_dodatkami_new'] = $new_data['cena_z_dodatkami'];
            $price_changed = true;
        } elseif (isset($new_data['cena_z_dodatkami'])) {
            $history_data['cena_z_dodatkami_new'] = $new_data['cena_z_dodatkami'];
        }
        
        // Save history only if price changed or it's first entry
        if ($price_changed || empty($old_data)) {
            return $wpdb->insert($table, $history_data);
        }
        
        return false;
    }
    
    /**
     * Save new price history entry (simple version)
     */
    public function save($data) {
        $table = TableNames::getPriceHistory();
        global $wpdb;
        
        return $wpdb->insert($table, $data);
    }
    
    /**
     * Get full price history for CSV export
     */
    public function readForExport() {
        $history_table = TableNames::getPriceHistory();
        $resources_table = TableNames::getResources();
        global $wpdb;
        
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