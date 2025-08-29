<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Panel administracyjny wtyczki - wersja modularna
 */
class UJC_Admin {
    
    private $dashboard_page_instance;
    private $supplier_data_page_instance;
    private $resources_page_instance;
    private $publication_page_instance;
    private $resource_modal_instance;
    private $investment_modal_instance;
    private $history_modal_instance;
    private $dev_console_instance;
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // Wszystkie instancje tworzone w konstruktorze (klasy już załadowane w głównym loaderze)
        $this->dashboard_page_instance = new UJC_Dashboard_Page();
        $this->supplier_data_page_instance = new UJC_Supplier_Data_Page();
        $this->resources_page_instance = new UJC_Resources_Page();
        $this->publication_page_instance = new UJC_Publication_Page();
        $this->resource_modal_instance = new UJC_Resource_Modal();
        $this->investment_modal_instance = new UJC_Investment_Modal();
        $this->history_modal_instance = new UJC_History_Modal();
        $this->dev_console_instance = new UJC_Dev_Console();
        
        // AJAX handlers usunięte - są obsługiwane w głównym pliku pluginu
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'DeweloperJawneCeny',
            'DeweloperJawneCeny',
            'manage_options',
            'ujc-dashboard',
            [$this, 'dashboard_page'],
            'dashicons-building',
            30
        );
        
        add_submenu_page(
            'ujc-dashboard',
            'Dane Dostawcy',
            'Dane dostawcy',
            'manage_options',
            'ujc-developer',
            [$this, 'developer_page']
        );
        
        add_submenu_page(
            'ujc-dashboard',
            'Zasoby',
            'Zasoby',
            'manage_options',
            'ujc-resources',
            [$this, 'resources_page']
        );
        
        add_submenu_page(
            'ujc-dashboard',
            'Publikacja Danych',
            'Publikacja Danych',
            'manage_options',
            'ujc-publication',
            [$this, 'publication_page']
        );
    }
    
    public function dashboard_page() {
        $this->dashboard_page_instance->render();
    }
    
    public function developer_page() {
        $this->supplier_data_page_instance->render();
    }
    
    public function resources_page() {
        $this->resources_page_instance->render();
    }
    
    public function publication_page() {
        $this->publication_page_instance->render();
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'ujc-') === false) {
            return;
        }
        
        wp_enqueue_script('ujc-admin-js', PLUGIN_URL . 'assets/admin.js', ['jquery'], VERSION, true);
        wp_enqueue_style('ujc-admin-css', PLUGIN_URL . 'assets/admin.css', [], VERSION);
        
        wp_localize_script('ujc-admin-js', 'ujc_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ujc_admin_nonce'),
            'strings' => [
                'confirm_delete' => 'Czy na pewno chcesz usunąć ten zasób?',
                'saving' => 'Zapisywanie...',
                'saved' => 'Zapisano pomyślnie!',
                'error' => 'Wystąpił błąd!'
            ]
        ]);
    }
    
    public function register_settings() {
        // Rejestracja ustawień jeśli potrzebne
    }
    
    // AJAX handlers
    
    
    
}