<?php

if (!defined('ABSPATH')) {
    exit;
}

class SaveInvestmentInfoUseCase {
    
    public static function execute($data) {
        try {
            $sanitized_data = self::sanitize_data($data);
            $repository = new InvestmentRepository();
            $result = $repository->save($sanitized_data);
            
            if ($result !== false) {
                return [
                    'success' => true,
                    'message' => 'Inwestycja została zapisana'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Błąd podczas zapisywania inwestycji'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Błąd serwera: ' . $e->getMessage()
            ];
        }
    }
    
    private static function sanitize_data($data) {
        return [
            'name' => sanitize_text_field($data['investment_name'] ?? ''),
            'proj_wojewodztwo' => sanitize_text_field($data['proj_wojewodztwo'] ?? ''),
            'proj_powiat' => sanitize_text_field($data['proj_powiat'] ?? ''),
            'proj_gmina' => sanitize_text_field($data['proj_gmina'] ?? ''),
            'proj_miejscowosc' => sanitize_text_field($data['proj_miejscowosc'] ?? ''),
            'proj_ulica' => sanitize_text_field($data['proj_ulica'] ?? ''),
            'proj_nr' => sanitize_text_field($data['proj_nr'] ?? ''),
            'proj_kod' => sanitize_text_field($data['proj_kod'] ?? '')
        ];
    }
}