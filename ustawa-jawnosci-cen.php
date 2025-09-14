<?php
/**
 * Plugin Name: Deweloper Jawne Ceny
 * Plugin URI: https://www.deweloperjawneceny.pl/
 * Description: Automatyzacja procesu dostarczania danych o cenach mieszkań zgodnie z polską Ustawą o jawności cen nieruchomości. Generowanie plików XML/CSV dla portalu dane.gov.pl.
 * Version: 4.2.0
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Author: DeweloperJawneCeny Team
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
define('DB_VERSION', '1.7');
define('VERSION', '4.2.0');

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
        add_action('admin_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
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
        require_once PLUGIN_DIR . 'includes/enums/ResourceStatus.php';
        require_once PLUGIN_DIR . 'includes/enums/PropertyType.php';
        
        // Models
        require_once PLUGIN_DIR . 'includes/models/DaneGovXmlDataset.php';
        require_once PLUGIN_DIR . 'includes/models/ResourceFormData.php';
        require_once PLUGIN_DIR . 'includes/models/PropertyPartFormData.php';
        require_once PLUGIN_DIR . 'includes/models/BelongingRoomFormData.php';
        require_once PLUGIN_DIR . 'includes/models/UsageRightFormData.php';
        require_once PLUGIN_DIR . 'includes/models/OtherServiceFormData.php';
        require_once PLUGIN_DIR . 'includes/models/PresentableResource.php';
        require_once PLUGIN_DIR . 'includes/models/ShortcodeResource.php';
        require_once PLUGIN_DIR . 'includes/models/FileData.php';
        require_once PLUGIN_DIR . 'includes/models/FileGenerationResult.php';
        
        // DTOs
        require_once PLUGIN_DIR . 'includes/core/ModelDto.php';
        require_once PLUGIN_DIR . 'includes/core/Result.php';
        require_once PLUGIN_DIR . 'includes/dto/ResourceDto.php';
        require_once PLUGIN_DIR . 'includes/dto/InvestmentDto.php';
        require_once PLUGIN_DIR . 'includes/dto/PriceHistoryDto.php';
        require_once PLUGIN_DIR . 'includes/dto/SupplierDto.php';
        require_once PLUGIN_DIR . 'includes/dto/XmlResourceDto.php';
        
        // Helpers
        require_once PLUGIN_DIR . 'includes/helpers/Logger.php';
        
        // Services
        require_once PLUGIN_DIR . 'includes/services/DateHelper.php';
        require_once PLUGIN_DIR . 'includes/services/CSVFormatter.php';
        require_once PLUGIN_DIR . 'includes/services/XMLFormatter.php';
        require_once PLUGIN_DIR . 'includes/services/FileManager.php';
        
        // DTOs that depend on services
        require_once PLUGIN_DIR . 'includes/dto/PublicationHistoryDto.php';
        require_once PLUGIN_DIR . 'includes/models/PresentablePriceHistory.php';
        
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
        require_once PLUGIN_DIR . 'includes/repositories/XmlResourceRepository.php';
        
        // UseCases - Resources
        require_once PLUGIN_DIR . 'includes/UseCases/Resources/CreateResourceUseCase.php';
        require_once PLUGIN_DIR . 'includes/UseCases/Resources/UpdateResourceUseCase.php';
        require_once PLUGIN_DIR . 'includes/UseCases/Resources/GetResourceByIdUseCase.php';
        require_once PLUGIN_DIR . 'includes/UseCases/Resources/GetAllResourcesUseCase.php';
        require_once PLUGIN_DIR . 'includes/UseCases/Resources/GetResourcesForGovernmentUseCase.php';
        require_once PLUGIN_DIR . 'includes/UseCases/Resources/DeleteResourceUseCase.php';
        
        // UseCases - Developer
        require_once PLUGIN_DIR . 'includes/UseCases/Developer/SaveDeveloperInfoUseCase.php';
        require_once PLUGIN_DIR . 'includes/UseCases/Developer/GetDeveloperInfoUseCase.php';
        
        // UseCases - Investment
        require_once PLUGIN_DIR . 'includes/UseCases/Investment/CreateInvestmentUseCase.php';
        require_once PLUGIN_DIR . 'includes/UseCases/Investment/UpdateInvestmentUseCase.php';
        require_once PLUGIN_DIR . 'includes/UseCases/Investment/GetInvestmentUseCase.php';
        
        // UseCases - File Generation
        require_once PLUGIN_DIR . 'includes/UseCases/FileGeneration/GenerateCSVFileUseCase.php';
        require_once PLUGIN_DIR . 'includes/UseCases/FileGeneration/GenerateFilesUseCase.php';
        require_once PLUGIN_DIR . 'includes/UseCases/FileGeneration/CreateDaneGovSubmissionFilesUseCase.php';
        
        // UseCases - Publication History
        require_once PLUGIN_DIR . 'includes/UseCases/PublicationHistory/AddPublicationHistoryUseCase.php';
        require_once PLUGIN_DIR . 'includes/UseCases/PublicationHistory/GetPublicationHistoryUseCase.php';
        
        // UseCases - Price History
        require_once PLUGIN_DIR . 'includes/UseCases/PriceHistory/GetPriceHistoryUseCase.php';
        
        // UseCases - XML Resource
        require_once PLUGIN_DIR . 'includes/UseCases/XmlResource/AddXmlResourceUseCase.php';
        
        // UseCases - System
        require_once PLUGIN_DIR . 'includes/UseCases/System/ImportResourcesUseCase.php';
        require_once PLUGIN_DIR . 'includes/UseCases/System/ResetDatabaseUseCase.php';
        
        // UseCases - Frontend
        require_once PLUGIN_DIR . 'includes/UseCases/Frontend/GetResourcesForFrontendUseCase.php';
        
        // Views - Admin Components
        require_once PLUGIN_DIR . 'includes/views/admin/components/ResourceModal.php';
        require_once PLUGIN_DIR . 'includes/views/admin/components/InvestmentModal.php';
        require_once PLUGIN_DIR . 'includes/views/admin/components/ResourceItem.php';
        require_once PLUGIN_DIR . 'includes/views/admin/components/HistoryModal.php';
        
        // Dashboard Components
        require_once PLUGIN_DIR . 'includes/views/admin/Dashboard/AutomationTile.php';
        require_once PLUGIN_DIR . 'includes/views/admin/Dashboard/HistoryTile.php';
        require_once PLUGIN_DIR . 'includes/views/admin/Dashboard/CsvFilesSection.php';
        
        // Views - Admin Pages
        require_once PLUGIN_DIR . 'includes/views/admin/pages/DashboardPage.php';
        require_once PLUGIN_DIR . 'includes/views/admin/pages/SupplierDataPage.php';
        require_once PLUGIN_DIR . 'includes/views/admin/pages/ResourcesPage.php';
        require_once PLUGIN_DIR . 'includes/views/admin/pages/PublicationPage.php';
        require_once PLUGIN_DIR . 'includes/views/admin/pages/DevConsoleTile.php';
        require_once PLUGIN_DIR . 'includes/views/admin/pages/FrontendManagementPage.php';
        
        // Blocks
        require_once PLUGIN_DIR . 'includes/blocks/ResourcesListBlock.php';
        require_once PLUGIN_DIR . 'includes/blocks/BlocksManager.php';
        
        // Shortcodes
        require_once PLUGIN_DIR . 'includes/shortcodes/ResourcesListShortcode.php';
        require_once PLUGIN_DIR . 'includes/shortcodes/ShortcodeManager.php';
        
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
        new ShortcodeManager();
        
        // Initialize WordPress Filesystem
        WP_Filesystem();
        
        // Initialize Logger
        Logger::init();
    }
    
    
    public function enqueue_frontend_scripts() {
        wp_enqueue_style('ujc-frontend', PLUGIN_URL . 'assets/admin.css', [], VERSION);
        wp_enqueue_style('ujc-resource-modal', PLUGIN_URL . 'assets/admin-resource-modal.css', [], VERSION);
        
        wp_enqueue_script('ujc-resources-list-widget', PLUGIN_URL . 'assets/blocks/resources-list-widget.js', ['jquery'], VERSION, true);
        
        wp_localize_script('ujc-resources-list-widget', 'resourcesListAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('resources_list_nonce'),
            'strings' => [
                'loading' => 'Ładowanie...',
                'error' => 'Wystąpił błąd podczas ładowania historii cen.',
                'no_history' => 'Brak historii cen dla tego zasobu.'
            ]
        ]);
    }
    
    public function handle_file_requests() {
        if (isset($_GET['file'])) {
            $file = sanitize_file_name($_GET['file']);
            $upload_dir = wp_upload_dir();
            $filepath = $upload_dir['basedir'] . '/ujc-data/' . $file;
            
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $allowed_extensions = ['csv', 'xml', 'pdf'];
            
            if (file_exists($filepath) && in_array($extension, $allowed_extensions)) {
                global $wp_filesystem;
                
                // Set appropriate MIME type
                $mime_types = [
                    'csv' => 'text/csv',
                    'xml' => 'application/xml',
                    'pdf' => 'application/pdf'
                ];
                
                $mime_type = $mime_types[$extension] ?? 'application/octet-stream';
                
                header('Content-Type: ' . $mime_type . '; charset=UTF-8');
                header('Content-Disposition: attachment; filename="' . esc_attr($file) . '"');
                
                // Use WordPress Filesystem instead of readfile()
                $file_contents = $wp_filesystem->get_contents($filepath);
                if ($file_contents !== false) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- File download content should not be escaped
                    echo $file_contents;
                }
                exit;
            }
        }
    }
    
    
    public function activate() {
        try {
            // Load all dependencies needed for schema creation
            $this->load_dependencies();
            
            // NOW we can safely use Logger
            Logger::info('UJC Plugin activate() - START');
            Logger::info('UJC Plugin: Dependencies loaded');
            
            Logger::info('UJC Plugin: Calling UJC_Schema_Manager::create_tables()...');
            UJC_Schema_Manager::create_tables();
            Logger::info('UJC Plugin: create_tables() completed');
            
            $upload_dir = wp_upload_dir();
            $data_dir = $upload_dir['basedir'] . '/ujc-data';
            if (!file_exists($data_dir)) {
                wp_mkdir_p($data_dir);
                Logger::info('UJC Plugin: Created data directory: ' . $data_dir);
            } else {
                Logger::info('UJC Plugin: Data directory already exists: ' . $data_dir);
            }
            
            $settings_repository = new SettingsRepository();
            $currentVersion = $settings_repository->getDbVersion();
            Logger::info('UJC Plugin: Current DB version before update: ' . $currentVersion);
            Logger::info('UJC Plugin: Setting DB version to: ' . DB_VERSION);
            
            $settings_repository->setDbVersion(DB_VERSION);
            
            $newVersion = $settings_repository->getDbVersion();
            Logger::info('UJC Plugin: DB version after update: ' . $newVersion);
            
            Logger::info('UJC Plugin activate() - COMPLETED');
            
        } catch (Exception $e) {
            Logger::error('UJC Activation Error: ' . $e->getMessage());
        }
    }
    
    public function deactivate() {
        // External cron cleanup handled by premium features if available
        // No cleanup needed for freemium version
    }

}

// Initialize
DeweloperJawneCeny::get_instance();