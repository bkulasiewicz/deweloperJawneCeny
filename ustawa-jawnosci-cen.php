<?php
/**
 * Plugin Name: Deweloper Jawne Ceny
 * Plugin URI: https://www.deweloperjawneceny.pl/
 * Description: Automatyzacja procesu dostarczania danych o cenach mieszkań zgodnie z polską Ustawą o jawności cen nieruchomości. Generowanie plików XML/CSV dla portalu dane.gov.pl.
 * Version: 1.25.3
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Author: DeweloperJawneCeny Team
 * Author URI: https://www.deweloperjawneceny.pl/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: deweloper-jawne-ceny
 * Domain Path: /languages
 * 
 * @package DeweloperJawneCeny
 * @author DeweloperJawneCeny Team
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}


define('PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PLUGIN_URL', plugin_dir_url(__FILE__));
define('DB_VERSION', '1.1');
define('VERSION', '1.25.1');

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
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        add_action('init', [$this, 'handle_file_requests']);
    }
    
    public function init() {
        $this->load_dependencies();
    }
    
    private function load_dependencies() {
        // WordPress filesystem functions
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        
        // Helpers
        require_once PLUGIN_DIR . 'includes/helpers/PremiumHelper.php';
        
        // Config
        require_once PLUGIN_DIR . 'includes/config/TableNames.php';
        
        // Enums
        require_once PLUGIN_DIR . 'includes/enums/PublicationStatus.php';
        require_once PLUGIN_DIR . 'includes/enums/TriggerType.php';
        
        // Models
        require_once PLUGIN_DIR . 'includes/models/DaneGovXmlDataset.php';
        require_once PLUGIN_DIR . 'includes/models/PublicationHistoryEntry.php';
        
        // Services
        require_once PLUGIN_DIR . 'includes/services/DateHelper.php';
        require_once PLUGIN_DIR . 'includes/services/CSVFormatter.php';
        require_once PLUGIN_DIR . 'includes/services/XMLFormatter.php';
        require_once PLUGIN_DIR . 'includes/services/FileManager.php';
        
        // Core
        require_once PLUGIN_DIR . 'includes/core/abstract-ujc-admin-page.php';
        require_once PLUGIN_DIR . 'includes/core/class-ujc-schema-manager.php';
        
        
        // Repositories
        require_once PLUGIN_DIR . 'includes/repositories/DeveloperRepository.php';
        require_once PLUGIN_DIR . 'includes/repositories/InvestmentRepository.php';
        require_once PLUGIN_DIR . 'includes/repositories/ResourceRepository.php';
        require_once PLUGIN_DIR . 'includes/repositories/PriceHistoryRepository.php';
        require_once PLUGIN_DIR . 'includes/repositories/SettingsRepository.php';
        require_once PLUGIN_DIR . 'includes/repositories/PublicationHistoryRepository.php';
        
        // UseCases
        require_once PLUGIN_DIR . 'includes/UseCases/SaveResourceUseCase.php';
        require_once PLUGIN_DIR . 'includes/UseCases/GetResourceByIdUseCase.php';
        require_once PLUGIN_DIR . 'includes/UseCases/GetAllResourcesUseCase.php';
        require_once PLUGIN_DIR . 'includes/UseCases/ImportResourcesUseCase.php';
        require_once PLUGIN_DIR . 'includes/UseCases/ResetDatabaseUseCase.php';
        require_once PLUGIN_DIR . 'includes/UseCases/SaveDeveloperInfoUseCase.php';
        require_once PLUGIN_DIR . 'includes/UseCases/SaveInvestmentInfoUseCase.php';
        require_once PLUGIN_DIR . 'includes/UseCases/GenerateCSVFileUseCase.php';
        require_once PLUGIN_DIR . 'includes/UseCases/CreateDaneGovSubmissionFilesUseCase.php';
        require_once PLUGIN_DIR . 'includes/UseCases/AddPublicationHistoryUseCase.php';
        require_once PLUGIN_DIR . 'includes/UseCases/GetPublicationHistoryUseCase.php';
        require_once PLUGIN_DIR . 'includes/UseCases/GenerateFilesUseCase.php';
        
        // Views - Admin Components
        require_once PLUGIN_DIR . 'includes/views/admin/components/class-ujc-resource-modal.php';
        require_once PLUGIN_DIR . 'includes/views/admin/components/class-ujc-investment-modal.php';
        require_once PLUGIN_DIR . 'includes/views/admin/components/class-ujc-resource-item.php';
        require_once PLUGIN_DIR . 'includes/views/admin/components/class-ujc-history-modal.php';
        
        // Dashboard Components
        require_once PLUGIN_DIR . 'includes/views/admin/Dashboard/AutomationTile.php';
        require_once PLUGIN_DIR . 'includes/views/admin/Dashboard/HistoryTile.php';
        
        // Views - Admin Pages
        require_once PLUGIN_DIR . 'includes/views/admin/pages/class-ujc-dashboard-page.php';
        require_once PLUGIN_DIR . 'includes/views/admin/pages/class-ujc-supplier-data-page.php';
        require_once PLUGIN_DIR . 'includes/views/admin/pages/class-ujc-resources-page.php';
        require_once PLUGIN_DIR . 'includes/views/admin/pages/class-ujc-publication-page.php';
        require_once PLUGIN_DIR . 'includes/views/admin/pages/class-ujc-dev-console.php';
        
        // Blocks
        require_once PLUGIN_DIR . 'includes/blocks/ResourcesListBlock.php';
        require_once PLUGIN_DIR . 'includes/blocks/BlocksManager.php';
        
        // Load premium features if available
        $premium_loader = PLUGIN_DIR . 'includes/premium/loader.php';
        if (file_exists($premium_loader)) {
            require_once $premium_loader;
        }
        
        // Inicjalizuj klasy po załadowaniu wszystkich zależności
        if (is_admin()) {
            require_once PLUGIN_DIR . 'includes/views/admin/class-ujc-admin.php';
            new UJC_Admin();
        }
        
        new BlocksManager();
    }
    
    
    public function enqueue_frontend_scripts() {
        wp_enqueue_style('ujc-frontend', PLUGIN_URL . 'assets/admin.css', [], '1.0');
    }
    
    public function handle_file_requests() {
        if (isset($_GET['file'])) {
            $file = sanitize_file_name($_GET['file']);
            $upload_dir = wp_upload_dir();
            $filepath = $upload_dir['basedir'] . '/ujc-data/' . $file;
            
            if (file_exists($filepath) && in_array(pathinfo($file, PATHINFO_EXTENSION), ['csv', 'xml'])) {
                $mime_type = pathinfo($file, PATHINFO_EXTENSION) === 'csv' ? 'text/csv' : 'application/xml';
                header('Content-Type: ' . $mime_type . '; charset=UTF-8');
                header('Content-Disposition: attachment; filename="' . $file . '"');
                readfile($filepath);
                exit;
            }
        }
    }
    
    
    public function activate() {
        try {
            require_once PLUGIN_DIR . 'includes/config/TableNames.php';
            require_once PLUGIN_DIR . 'includes/core/class-ujc-schema-manager.php';
            require_once PLUGIN_DIR . 'includes/repositories/SettingsRepository.php';
            
            UJC_Schema_Manager::create_tables();
            
            $upload_dir = wp_upload_dir();
            $data_dir = $upload_dir['basedir'] . '/ujc-data';
            if (!file_exists($data_dir)) {
                wp_mkdir_p($data_dir);
            }
            
            $settings_repository = new SettingsRepository();
            $settings_repository->setDbVersion(DB_VERSION);
            
        } catch (Exception $e) {
            error_log('UJC Activation Error: ' . $e->getMessage());
        }
    }
    
    public function deactivate() {
        // External cron cleanup handled by premium features if available
        // No cleanup needed for freemium version
    }
}

// Initialize
DeweloperJawneCeny::get_instance();