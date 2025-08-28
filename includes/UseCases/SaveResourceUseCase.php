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
            $sql = "SELECT COUNT(*) FROM $table WHERE nr_lokalu = %s";
            $params = [$data['nr_lokalu']];
            
            if ($exclude_id) {
                $sql .= " AND id != %d";
                $params[] = $exclude_id;
            }
            
            $existing = $wpdb->get_var($wpdb->prepare($sql, $params));
            
            if ($existing > 0) {
                $errors[] = 'Numer lokalu "' . $data['nr_lokalu'] . '" już istnieje. Każdy zasób musi mieć unikalną nazwę.';
            }
        }
        
        return $errors;
    }
}