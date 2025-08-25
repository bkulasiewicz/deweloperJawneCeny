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
            error_log('UJC: User lacks capabilities: ' . $this->capabilities);
            return false;
        }
        return true;
    }
    
    /**
     * Weryfikacja nonce
     */
    protected function verify_nonce($nonce_field = 'ujc_nonce', $nonce_action = 'ujc_admin_nonce') {
        $nonce = $_POST[$nonce_field] ?? '';
        
        if (empty($nonce)) {
            error_log('UJC: Empty nonce field: ' . $nonce_field);
            return false;
        }
        
        if (!wp_verify_nonce($nonce, $nonce_action)) {
            error_log('UJC: Nonce verification failed. Nonce: ' . $nonce . ', Action: ' . $nonce_action);
            return false;
        }
        
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