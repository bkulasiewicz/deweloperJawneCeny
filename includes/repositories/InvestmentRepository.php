<?php

if (!defined('ABSPATH')) {
    exit;
}

class InvestmentRepository {
    
    /**
     * Get investment data as model
     */
    public function read(): ?InvestmentDto {
        $table = TableNames::getInvestmentInfo();        
        global $wpdb;
        $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table}` LIMIT 1"), ARRAY_A);
        
        return $data ? InvestmentDto::databaseToModel($data) : null;
    }
    
    /**
     * Create new investment
     */
    public function create(InvestmentDto $dto): int {
        $table = TableNames::getInvestmentInfo();        
        global $wpdb;
        
        $data = $dto->modelToDatabase();
        $result = $wpdb->insert($table, $data);
        
        if ($result === false) {
            throw new Exception('Failed to create investment: ' . $wpdb->last_error);
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update existing investment
     */
    public function update(InvestmentDto $dto, int $id): void {
        $table = TableNames::getInvestmentInfo();        
        global $wpdb;
        
        $data = $dto->modelToDatabase();
        $result = $wpdb->update($table, $data, ['id' => $id]);
        
        if ($result === false) {
            throw new Exception('Failed to update investment: ' . $wpdb->last_error);
        }
    }
}