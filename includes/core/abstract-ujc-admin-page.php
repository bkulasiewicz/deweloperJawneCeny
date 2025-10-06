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
     * Abstrakcyjna metoda renderowania - musi być zaimplementowana
     */
    abstract public function render();
}