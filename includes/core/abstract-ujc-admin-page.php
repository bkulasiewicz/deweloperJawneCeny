<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstrakcyjna klasa bazowa dla stron administracyjnych
 * Implementuje Single Responsibility Principle
 */
abstract class UJC_Admin_Page {
    
    protected $page_slug;
    protected $capabilities = 'manage_options';
    
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Inicjalizacja hooks - każda klasa może override
     */
    protected function init_hooks() {
        // Domyślnie puste - dzieci klas mogą override
    }
    
    /**
     * Sprawdzenie uprawnień
     */
    protected function check_permissions() {
        if (!current_user_can($this->capabilities)) {
            Logger::error('UJC: User lacks capabilities: ' . $this->capabilities);
            return false;
        }
        return true;
    }
    
    /**
     * Weryfikacja nonce
     */
    protected function verify_nonce($nonce_field = 'nonce', $nonce_action = 'ujc_admin_nonce') {
        Logger::info('UJC: verify_nonce called with field: ' . $nonce_field . ', action: ' . $nonce_action);
        Logger::info('UJC: POST data keys: ' . implode(', ', array_keys($_POST)));
        
        $nonce = $_POST[$nonce_field] ?? '';
        Logger::info('UJC: Nonce from POST: ' . ($nonce ? 'EXISTS (' . substr($nonce, 0, 10) . '...)' : 'EMPTY'));
        
        if (empty($nonce)) {
            Logger::error('UJC: Empty nonce field: ' . $nonce_field);
            return false;
        }
        
        $verify_result = wp_verify_nonce($nonce, $nonce_action);
        Logger::info('UJC: wp_verify_nonce result: ' . ($verify_result ? 'SUCCESS' : 'FAILED'));
        
        if (!$verify_result) {
            Logger::error('UJC: Nonce verification failed. Nonce: ' . $nonce . ', Action: ' . $nonce_action);
            return false;
        }
        
        Logger::info('UJC: Nonce verification successful');
        return true;
    }
    
    /**
     * Sanitizacja danych POST
     */
    protected function sanitize_post_data($fields = []) {
        $data = [];
        foreach ($fields as $field => $sanitize_callback) {
            $value = $_POST[$field] ?? '';
            // Wywołaj funkcję sanitizacji
            if (is_callable($sanitize_callback)) {
                $data[$field] = call_user_func($sanitize_callback, $value);
            } else {
                $data[$field] = sanitize_text_field($value);
            }
        }
        return $data;
    }
    
    /**
     * Abstrakcyjna metoda renderowania - musi być zaimplementowana
     */
    abstract public function render();
}