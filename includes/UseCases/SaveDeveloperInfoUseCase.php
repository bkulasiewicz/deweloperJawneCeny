<?php

if (!defined('ABSPATH')) {
    exit;
}

class SaveDeveloperInfoUseCase {
    
    public static function execute($data) {
        try {
            // Walidacja danych
            $sanitized_data = self::sanitize_data($data);
            
            // Zapisz do bazy przez repozytorium
            $repository = new DeveloperRepository();
            $result = $repository->save($sanitized_data);
            
            if ($result !== false) {
                return [
                    'success' => true,
                    'message' => 'Dane dostawcy zostały zapisane!'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Błąd podczas zapisywania.'
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
        $sanitization_rules = [
            'nazwa' => 'sanitize_text_field',
            'forma_prawna' => 'sanitize_text_field',
            'nr_krs' => 'sanitize_text_field',
            'nr_ceidg' => 'sanitize_text_field',
            'nr_nip' => 'sanitize_text_field',
            'nr_regon' => 'sanitize_text_field',
            'telefon' => 'sanitize_text_field',
            'email' => 'sanitize_email',
            'fax' => 'sanitize_text_field',
            'strona_www' => 'esc_url_raw',
            
            'siedz_wojewodztwo' => 'sanitize_text_field',
            'siedz_powiat' => 'sanitize_text_field',
            'siedz_gmina' => 'sanitize_text_field',
            'siedz_miejscowosc' => 'sanitize_text_field',
            'siedz_ulica' => 'sanitize_text_field',
            'siedz_nr' => 'sanitize_text_field',
            'siedz_lokal' => 'sanitize_text_field',
            'siedz_kod' => 'sanitize_text_field',
            
            'sprzed_wojewodztwo' => 'sanitize_text_field',
            'sprzed_powiat' => 'sanitize_text_field',
            'sprzed_gmina' => 'sanitize_text_field',
            'sprzed_miejscowosc' => 'sanitize_text_field',
            'sprzed_ulica' => 'sanitize_text_field',
            'sprzed_nr' => 'sanitize_text_field',
            'sprzed_lokal' => 'sanitize_text_field',
            'sprzed_kod' => 'sanitize_text_field',
            
            'dodatkowe_lokalizacje' => 'sanitize_textarea_field',
            'sposob_kontaktu' => 'sanitize_textarea_field',
            'prospekt_url' => 'esc_url_raw'
        ];
        
        $sanitized = [];
        foreach ($sanitization_rules as $field => $sanitize_callback) {
            $value = $data[$field] ?? '';
            if (is_callable($sanitize_callback)) {
                $sanitized[$field] = call_user_func($sanitize_callback, $value);
            } else {
                $sanitized[$field] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
}