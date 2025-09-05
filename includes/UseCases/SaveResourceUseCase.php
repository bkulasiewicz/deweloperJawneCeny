<?php

if (!defined('ABSPATH')) {
    exit;
}

class SaveResourceUseCase {
    
    private $repository;
    private $price_history_repo;
    
    public function __construct() {
        $this->repository = new ResourceRepository();
        $this->price_history_repo = new PriceHistoryRepository();
    }
    
    public function execute($data, $resource_id = null) {
        $validation_errors = $this->validate($data, $resource_id);
        if (!empty($validation_errors)) {
            return ['error' => implode(' ', $validation_errors)];
        }
        
        // Transform extra data to database column names (always set, even if empty to clear old data)
        if (!empty($data['extra_type'])) {
            $data['extra_rodzaj_czesci'] = sanitize_text_field($data['extra_type']);
            $data['extra_oznaczenie_czesci'] = sanitize_text_field($data['extra_oznaczenie'] ?? '');
            $data['extra_cena_czesci'] = !empty($data['extra_cena']) ? floatval($data['extra_cena']) : null;
            $data['extra_data_cena_czesci'] = !empty($data['extra_cena']) ? DateHelper::currentDatetime() : null;
        } else {
            // Clear extra data when checkbox is unchecked
            $data['extra_rodzaj_czesci'] = null;
            $data['extra_oznaczenie_czesci'] = null;
            $data['extra_cena_czesci'] = null;
            $data['extra_data_cena_czesci'] = null;
        }
        
        // Remove form field names
        unset($data['extra_type'], $data['extra_oznaczenie'], $data['extra_cena']);
        
        if ($resource_id) {
            $old_data = $this->repository->readById($resource_id);
            if (!$old_data) {
                return false;
            }
            
            $result = $this->repository->save($data, $resource_id);
            
            if ($result !== false) {
                $this->price_history_repo->saveHistory($resource_id, $old_data, $data);
            }
        } else {
            $result = $this->repository->save($data);
            
            if ($result !== false) {
                try {
                    $this->price_history_repo->saveHistory($result, [], $data);
                } catch (Exception $e) {
                    error_log('UJC Resource: Price history error: ' . $e->getMessage());
                }
            }
        }
        
        return $result;
    }
    
    private function validate($data, $exclude_id = null) {
        $errors = [];
        $table = TableNames::getResources();
        global $wpdb;
        
        if (!empty($data['nr_lokalu'])) {
            if ($exclude_id) {
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM `{$table}` WHERE nr_lokalu = %s AND id != %d",
                    $data['nr_lokalu'],
                    $exclude_id
                ));
            } else {
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM `{$table}` WHERE nr_lokalu = %s",
                    $data['nr_lokalu']
                ));
            }
            
            if ($existing > 0) {
                $errors[] = 'Numer lokalu "' . $data['nr_lokalu'] . '" już istnieje. Każdy zasób musi mieć unikalną nazwę.';
            }
        }
        
        return $errors;
    }
}