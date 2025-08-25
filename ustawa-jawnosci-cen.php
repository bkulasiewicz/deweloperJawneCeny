<?php
/**
 * Plugin Name: DeweloperJawneCeny
 * Description: Plugin do automatyzacji procesu dostarczania danych zgodnie z wymogami Ustawy z dnia 21 maja 2025 r. o zmianie ustawy o ochronie praw nabywcy lokalu mieszkalnego
 * Version: 1.12.0
 * Author: Deweloper
 */

if (!defined('ABSPATH')) {
    exit;
}

define('UJC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('UJC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UJC_DB_VERSION', '1.1');
define('UJC_VERSION', '1.11.0');

class DeweloperJawneCeny {
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        add_action('plugins_loaded', [$this, 'init']);
    }
    
    public function init() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        // Services
        require_once UJC_PLUGIN_DIR . 'includes/services/class-ujc-date-helper.php';
        
        // Core
        require_once UJC_PLUGIN_DIR . 'includes/core/class-ujc-schema-manager.php';
        require_once UJC_PLUGIN_DIR . 'includes/core/class-ujc-database-versioning.php';
        
        // Models
        require_once UJC_PLUGIN_DIR . 'includes/models/class-ujc-resource.php';
        
        // Repositories
        require_once UJC_PLUGIN_DIR . 'includes/repositories/class-ujc-developer-repository.php';
        require_once UJC_PLUGIN_DIR . 'includes/repositories/class-ujc-investment-repository.php';
        require_once UJC_PLUGIN_DIR . 'includes/repositories/class-ujc-resource-repository.php';
        require_once UJC_PLUGIN_DIR . 'includes/repositories/class-ujc-price-history-repository.php';
        
        // Controllers
        require_once UJC_PLUGIN_DIR . 'includes/controllers/class-ujc-csv-generator.php';
        require_once UJC_PLUGIN_DIR . 'includes/controllers/class-ujc-xml-generator.php';
        require_once UJC_PLUGIN_DIR . 'includes/controllers/class-ujc-automated-generator.php';
        
        // Views - Frontend
        require_once UJC_PLUGIN_DIR . 'includes/views/frontend/class-ujc-shortcode.php';
        
        
        if (is_admin()) {
            require_once UJC_PLUGIN_DIR . 'includes/views/admin/class-ujc-admin.php';
            new UJC_Admin();
        }
        
        new UJC_Shortcode();
        new UJC_Automated_Generator();
        
        // Inicjalizuj wersjonowanie bazy danych
        UJC_Database_Versioning::init();
    }
    
    private function init_hooks() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('init', [$this, 'handle_file_requests']);
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'ujc') !== false) {
            wp_enqueue_script('ujc-admin', UJC_PLUGIN_URL . 'assets/admin.js', ['jquery'], '1.0', true);
            wp_enqueue_style('ujc-admin', UJC_PLUGIN_URL . 'assets/admin.css', [], '1.0');
            
            wp_localize_script('ujc-admin', 'ujc_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ujc_admin_nonce'),
                'strings' => [
                    'saving' => 'Zapisywanie...',
                    'saved' => 'Zapisano!',
                    'error' => 'Błąd podczas zapisywania'
                ]
            ]);
        }
    }
    
    public function enqueue_frontend_scripts() {
        wp_enqueue_style('ujc-frontend', UJC_PLUGIN_URL . 'assets/admin.css', [], '1.0');
    }
    
    public function handle_file_requests() {
        if (isset($_GET['ujc_file'])) {
            $file = sanitize_file_name($_GET['ujc_file']);
            $upload_dir = wp_upload_dir();
            $filepath = $upload_dir['basedir'] . '/ujc-data/' . $file;
            
            if (file_exists($filepath) && in_array(pathinfo($file, PATHINFO_EXTENSION), ['csv', 'xml'])) {
                $mime_type = pathinfo($file, PATHINFO_EXTENSION) === 'csv' ? 'text/csv' : 'application/xml';
                header('Content-Type: ' . $mime_type . '; charset=UTF-8');
                readfile($filepath);
                exit;
            }
        }
    }
    
    
    public function activate() {
        try {
            require_once UJC_PLUGIN_DIR . 'includes/core/class-ujc-schema-manager.php';
            UJC_Schema_Manager::create_tables();
            
            $upload_dir = wp_upload_dir();
            $ujc_dir = $upload_dir['basedir'] . '/ujc-data';
            if (!file_exists($ujc_dir)) {
                wp_mkdir_p($ujc_dir);
            }
            
            $settings_repository = new SettingsRepository();
            $settings_repository->set('ujc_db_version', UJC_DB_VERSION);
            
        } catch (Exception $e) {
            error_log('UJC Activation Error: ' . $e->getMessage());
        }
    }
    
    public function deactivate() {
        // Cleanup if needed
    }
}

// Initialize
DeweloperJawneCeny::get_instance();