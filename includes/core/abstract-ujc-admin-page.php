<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstrakcyjna klasa bazowa dla stron administracyjnych
 * Implementuje Single Responsibility Principle
 */
abstract class JawneCeny_AdminPage {
    
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
            return false;
        }
        return true;
    }
    
    /**
     * Weryfikacja nonce
     */
    protected function verify_nonce($nonce_field = 'nonce', $nonce_action = 'jawneceny_admin_nonce') {
        $nonce = $_POST[$nonce_field] ?? '';

        if (empty($nonce)) {
            return false;
        }

        $verify_result = wp_verify_nonce($nonce, $nonce_action);

        if (!$verify_result) {
            return false;
        }

        return true;
    }
    
    
    /**
     * Abstrakcyjna metoda renderowania - musi być zaimplementowana
     */
    abstract public function render();
}