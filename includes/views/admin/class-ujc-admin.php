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
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // Załaduj moduły stron admin
        $this->load_admin_pages();
        
        // AJAX handlers usunięte - są obsługiwane w głównym pliku pluginu
    }
    
    private function load_admin_pages() {
        // Załaduj abstrakcyjną klasę bazową
        require_once PLUGIN_DIR . 'includes/core/abstract-ujc-admin-page.php';
        
        // Załaduj strony admin
        require_once PLUGIN_DIR . 'includes/views/admin/pages/class-ujc-dashboard-page.php';
        require_once PLUGIN_DIR . 'includes/views/admin/pages/class-ujc-supplier-data-page.php';
        require_once PLUGIN_DIR . 'includes/views/admin/pages/class-ujc-resources-page.php';
        require_once PLUGIN_DIR . 'includes/views/admin/pages/class-ujc-publication-page.php';
        require_once PLUGIN_DIR . 'includes/views/admin/pages/class-ujc-dev-console.php';
        
        // Załaduj komponenty UI
        require_once PLUGIN_DIR . 'includes/views/admin/components/class-ujc-resource-modal.php';
        require_once PLUGIN_DIR . 'includes/views/admin/components/class-ujc-investment-modal.php';
        require_once PLUGIN_DIR . 'includes/views/admin/components/class-ujc-history-modal.php';
        require_once PLUGIN_DIR . 'includes/views/admin/components/class-ujc-resource-item.php';
        
        
        // Inicjalizuj strony zgodnie z Dependency Injection - przechowaj instancje
        $this->dashboard_page_instance = new UJC_Dashboard_Page();
        $this->supplier_data_page_instance = new UJC_Supplier_Data_Page();
        $this->resources_page_instance = new UJC_Resources_Page();
        new UJC_Resource_Modal();
        new UJC_Investment_Modal();
        new UJC_History_Modal();
        new UJC_Dev_Console();
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
            'ujc-properties',
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
        $page = new UJC_Publication_Page();
        $page->render();
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