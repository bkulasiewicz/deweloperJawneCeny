<?php
/**
 * Plugin Name: Deweloper Jawne Ceny
 * Plugin URI: https://www.deweloperjawneceny.pl/?utm_source=wordpress&utm_medium=general-link&utm_campaign=promotion
 * Description: Automatyzacja procesu dostarczania danych o cenach mieszkań zgodnie z polską Ustawą o jawności cen nieruchomości. Generowanie plików XML/CSV dla portalu dane.gov.pl.
 * Description (EN): Automates real estate price data reporting in compliance with Polish Real Estate Price Transparency Law. Generates XML/CSV files for dane.gov.pl portal.
 * Version: 4.7.0
 * Requires at least: 6.2
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

define('JAWNECENY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('JAWNECENY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('JAWNECENY_DB_VERSION', '1.8');
define('JAWNECENY_VERSION', '4.6.18');

class DeweloperJawneCeny {
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        require_once JAWNECENY_PLUGIN_DIR . 'includes/services/DateHelper.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/helpers/Logger.php';

        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    
        add_action('plugins_loaded', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        add_action('init', [$this, 'handle_file_requests']);


    }
    
    public function init() {
        JawneCeny\Logger::init();
        $this->load_dependencies();

    }
    
    private function load_dependencies() {
        // WordPress filesystem functions
        require_once(ABSPATH . 'wp-admin/includes/file.php');

        // Composer autoloader - handles DI52 and PSR Container interfaces
        $autoloader = JAWNECENY_PLUGIN_DIR . 'includes/vendor/autoload.php';
        if (file_exists($autoloader)) {
            require_once $autoloader;
        } else {
            // Fallback error if Composer dependencies are missing
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>DeweloperJawneCeny: Brak zależności Composer. Uruchom <code>composer install</code> w katalogu wtyczki.</p></div>';
            });
            return;
        }

        // Helpers
        require_once JAWNECENY_PLUGIN_DIR . 'includes/helpers/PremiumHelper.php';

        // DI Container - must be loaded before other classes
        require_once JAWNECENY_PLUGIN_DIR . 'includes/core/DIContainer.php';
        
        // Config
        require_once JAWNECENY_PLUGIN_DIR . 'includes/config/TableNames.php';
        
        // Enums
        require_once JAWNECENY_PLUGIN_DIR . 'includes/enums/PublicationStatus.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/enums/TriggerType.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/enums/ResourceStatus.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/enums/PropertyType.php';
        
        // Models
        require_once JAWNECENY_PLUGIN_DIR . 'includes/models/DaneGovXmlDataset.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/models/ResourceFormData.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/models/PropertyPartFormData.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/models/BelongingRoomFormData.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/models/UsageRightFormData.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/models/OtherServiceFormData.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/models/PresentableResource.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/models/ShortcodeResource.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/models/FilterCriteria.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/models/SortOptions.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/models/FileData.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/models/FileGenerationResult.php';
        
        // DTOs - ModelDto must be loaded first
        require_once JAWNECENY_PLUGIN_DIR . 'includes/core/ModelDto.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/core/Result.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/dto/ResourceDto.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/dto/InvestmentDto.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/dto/PriceHistoryDto.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/dto/SupplierDto.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/dto/XmlResourceDto.php';
        
        // Services
        require_once JAWNECENY_PLUGIN_DIR . 'includes/services/DateHelper.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/services/CSVFormatter.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/services/XMLFormatter.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/services/FileManager.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/helpers/ResourceTableRenderer.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/helpers/ResourceCardRenderer.php';

        // DTOs that depend on services
        require_once JAWNECENY_PLUGIN_DIR . 'includes/dto/PublicationHistoryDto.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/models/PresentablePriceHistory.php';
        
        // Core
        require_once JAWNECENY_PLUGIN_DIR . 'includes/core/abstract-ujc-admin-page.php';
        
        
        // Repositories
        require_once JAWNECENY_PLUGIN_DIR . 'includes/repositories/DeveloperRepository.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/repositories/InvestmentRepository.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/repositories/ResourceRepository.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/repositories/PriceHistoryRepository.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/repositories/SettingsRepository.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/repositories/PublicationHistoryRepository.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/repositories/XmlResourceRepository.php';
        
        // UseCases - Resources
        require_once JAWNECENY_PLUGIN_DIR . 'includes/UseCases/Resources/CreateResourceUseCase.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/UseCases/Resources/UpdateResourceUseCase.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/UseCases/Resources/GetResourceByIdUseCase.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/UseCases/Resources/GetAllResourcesUseCase.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/UseCases/Resources/GetResourcesForGovernmentUseCase.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/UseCases/Resources/DeleteResourceUseCase.php';
        
        // UseCases - Developer
        require_once JAWNECENY_PLUGIN_DIR . 'includes/UseCases/Developer/SaveDeveloperInfoUseCase.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/UseCases/Developer/GetDeveloperInfoUseCase.php';
        
        // UseCases - Investment
        require_once JAWNECENY_PLUGIN_DIR . 'includes/UseCases/Investment/CreateInvestmentUseCase.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/UseCases/Investment/UpdateInvestmentUseCase.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/UseCases/Investment/GetInvestmentUseCase.php';
        
        // UseCases - File Generation
        require_once JAWNECENY_PLUGIN_DIR . 'includes/UseCases/FileGeneration/GenerateCSVFileUseCase.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/UseCases/FileGeneration/GenerateFilesUseCase.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/UseCases/FileGeneration/CreateDaneGovSubmissionFilesUseCase.php';
        
        // UseCases - Publication History
        require_once JAWNECENY_PLUGIN_DIR . 'includes/UseCases/PublicationHistory/AddPublicationHistoryUseCase.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/UseCases/PublicationHistory/GetPublicationHistoryUseCase.php';
        
        // UseCases - Price History
        require_once JAWNECENY_PLUGIN_DIR . 'includes/UseCases/PriceHistory/GetPriceHistoryUseCase.php';
        
        // UseCases - XML Resource
        require_once JAWNECENY_PLUGIN_DIR . 'includes/UseCases/XmlResource/AddXmlResourceUseCase.php';
        
        // UseCases - System
        require_once JAWNECENY_PLUGIN_DIR . 'includes/UseCases/System/ImportResourcesUseCase.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/UseCases/System/ResetDatabaseUseCase.php';
        
        // UseCases - Frontend
        require_once JAWNECENY_PLUGIN_DIR . 'includes/UseCases/Frontend/GetResourcesForFrontendUseCase.php';
        
        // Views - Admin Components
        require_once JAWNECENY_PLUGIN_DIR . 'includes/views/admin/components/resource-modal/ResourceModal.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/views/admin/components/investment-modal/InvestmentModal.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/views/admin/components/resource-item/ResourceItem.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/views/admin/components/history-modal/HistoryModal.php';
        
        // Dashboard Components
        require_once JAWNECENY_PLUGIN_DIR . 'includes/views/admin/Dashboard/automation-tile/AutomationTile.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/views/admin/Dashboard/HistoryTile.php';

        // Views - Admin Pages
        require_once JAWNECENY_PLUGIN_DIR . 'includes/views/admin/pages/DashboardPage.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/views/admin/supplier-data/SupplierDataPage.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/views/admin/pages/resources-page/ResourcesPage.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/views/admin/pages/publication-page/csv-files-section/CsvFilesSection.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/views/admin/pages/publication-page/PublicationPage.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/views/admin/pages/DevConsoleTile.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/views/admin/shortcode-generator/ShortcodeGeneratorPage.php';

        // Controllers
        require_once JAWNECENY_PLUGIN_DIR . 'includes/controllers/AdminController.php';
        
        // Blocks
        require_once JAWNECENY_PLUGIN_DIR . 'includes/blocks/ResourcesListBlock.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/blocks/ResourcesFiltersBlock.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/blocks/BlocksManager.php';
        
        // Shortcodes
        require_once JAWNECENY_PLUGIN_DIR . 'includes/shortcodes/ResourcesListShortcode.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/shortcodes/ShortcodeManager.php';

      
        // Load premium features if available
        $premium_loader = JAWNECENY_PLUGIN_DIR . 'includes/premium/loader.php';
        if (file_exists($premium_loader)) {
            require_once $premium_loader;
        }
        
        // Initialize DI Container after all classes are loaded
        JawneCeny\DIContainer::init();

        // Initialize context-specific components
        if (is_admin()) {
            $this->init_admin();
        }

        $this->init_frontend();

        // Initialize WordPress Filesystem
        WP_Filesystem();
    }
    
    
    public function enqueue_frontend_scripts() {
        wp_enqueue_style('jawneceny-frontend', JAWNECENY_PLUGIN_URL . 'assets/admin.css', [], JAWNECENY_VERSION);
        wp_enqueue_style('jawneceny-resource-modal', JAWNECENY_PLUGIN_URL . 'assets/admin-resource-modal.css', [], JAWNECENY_VERSION);

        wp_enqueue_script('jawneceny-resources-list-widget', JAWNECENY_PLUGIN_URL . 'assets/blocks/resources-list-widget.js', ['jquery'], JAWNECENY_VERSION, true);

        wp_localize_script('jawneceny-resources-list-widget', 'jawnecenyResourcesListAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('resources_list_nonce'),
            'strings' => [
                'loading' => 'Ładowanie...',
                'error' => 'Wystąpił błąd podczas ładowania historii cen.',
                'no_history' => 'Brak historii cen dla tego zasobu.'
            ]
        ]);
    }

    /**
     * Handle public file download requests
     *
     * This function intentionally does not require nonce verification or user authentication.
     * According to Polish law (Ustawa o jawności cen nieruchomości), price transparency data
     * must be publicly accessible. The CSV and XML files contain only public property information
     * required by law, with no personal or sensitive data.
     *
     * Security measures:
     * - sanitize_file_name() prevents path traversal attacks
     * - Whitelist of allowed extensions (csv, xml, pdf)
     * - Files served only from controlled directory (wp-content/uploads/ujc-data/)
     *
     * @return void
     */
    public function handle_file_requests() {
        if (isset($_GET['file'])) {
            $file = sanitize_file_name($_GET['file']);
            $upload_dir = wp_upload_dir();
            $filepath = $upload_dir['basedir'] . '/ujc-data/' . $file;

            // Log file request details for debugging
            $file_exists = file_exists($filepath);
            $file_info = $file_exists ? sprintf('exists, size: %d bytes, modified: %s', filesize($filepath), gmdate('Y-m-d H:i:s', filemtime($filepath))) : 'does not exist';
            JawneCeny\Logger::info(sprintf('File Request: %s -> %s (%s)', $file, $filepath, $file_info));
            
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $allowed_extensions = ['csv', 'xml', 'pdf'];
            
            if (file_exists($filepath) && in_array($extension, $allowed_extensions)) {
                global $wp_filesystem;

                // Anti-cache headers - force fresh download every time
                header('Cache-Control: no-cache, no-store, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');

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

    /**
     * Load minimal dependencies needed for plugin activation
     */
    private function load_activationDependencies() {
        // WordPress filesystem functions
        require_once(ABSPATH . 'wp-admin/includes/file.php');

        // Services needed by Logger
        require_once JAWNECENY_PLUGIN_DIR . 'includes/services/DateHelper.php';

        // Basic helpers needed for activation
        require_once JAWNECENY_PLUGIN_DIR . 'includes/helpers/Logger.php';
    
        require_once JAWNECENY_PLUGIN_DIR . 'includes/config/TableNames.php';

        // Core classes needed for schema management
        require_once JAWNECENY_PLUGIN_DIR . 'includes/core/class-ujc-schema-manager.php';

        // Enums needed by repositories
        require_once JAWNECENY_PLUGIN_DIR . 'includes/enums/PropertyType.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/enums/ResourceStatus.php';

        // Repositories needed for table creation
        require_once JAWNECENY_PLUGIN_DIR . 'includes/repositories/SettingsRepository.php';

        // DTOs needed by repositories
        require_once JAWNECENY_PLUGIN_DIR . 'includes/core/ModelDto.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/dto/ResourceDto.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/dto/InvestmentDto.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/dto/PriceHistoryDto.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/dto/SupplierDto.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/dto/PublicationHistoryDto.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/dto/XmlResourceDto.php';

        // Repositories needed for table creation
        require_once JAWNECENY_PLUGIN_DIR . 'includes/repositories/DeveloperRepository.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/repositories/InvestmentRepository.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/repositories/ResourceRepository.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/repositories/PriceHistoryRepository.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/repositories/PublicationHistoryRepository.php';
        require_once JAWNECENY_PLUGIN_DIR . 'includes/repositories/XmlResourceRepository.php';

        // Initialize Logger - must be after DateHelper is loaded and all files loaded
        JawneCeny\Logger::init();
    }

    public function activate() {
        try {
            // Load minimal dependencies needed for activation
            $this->load_activationDependencies();

            JawneCeny\Logger::info('Plugin Activation: Starting database schema creation/migration...');
            $success = JawneCeny\JawneCeny_SchemaManager::create_tables();

            if (!$success) {
                JawneCeny\Logger::error('Plugin Activation: Database schema creation/migration failed');
                throw new Exception('Database migration failed - check logs for details');
            }

            // Ustaw nową wersję TYLKO po pomyślnych migracjach
            $settingsRepo = new JawneCeny\SettingsRepository();
            $settingsRepo->setDbVersion(JAWNECENY_DB_VERSION);

            JawneCeny\Logger::info('Plugin Activation: Successfully set DB version to ' . JAWNECENY_DB_VERSION);

            // Log plugin activation with version information
            JawneCeny\Logger::info(sprintf(
                'Plugin Activation: Deweloper Jawne Ceny v%s activated on WordPress %s with PHP %s',
                JAWNECENY_VERSION,
                get_bloginfo('version'),
                PHP_VERSION
            ));
        } catch (Exception $e) {
            JawneCeny\Logger::error('Plugin Activation Error: ' . $e->getMessage());
            JawneCeny\Logger::error('Plugin Activation: Activation failed - plugin may not work correctly');
            throw $e; // WordPress will show activation error
        }
    }
    
    /**
     * Initialize admin context - controllers for WordPress admin panel
     */
    private function init_admin() {
        // Initialize WordPress admin interface through AdminController
        JawneCeny\DIContainer::get(JawneCeny\AdminController::class);
    }

    /**
     * Initialize frontend context - public-facing features
     */
    private function init_frontend() {
        // Initialize through DI Container
        JawneCeny\DIContainer::get(JawneCeny\BlocksManager::class);
        JawneCeny\DIContainer::get(JawneCeny\ShortcodeManager::class);
    }

    public function deactivate() {
        // Log plugin deactivation
        JawneCeny\Logger::info(sprintf(
            'Plugin Deactivation: Deweloper Jawne Ceny v%s deactivated',
            JAWNECENY_VERSION
        ));

        // External cron cleanup handled by premium features if available
        // No cleanup needed for freemium version
    }

}

// Initialize
DeweloperJawneCeny::get_instance();