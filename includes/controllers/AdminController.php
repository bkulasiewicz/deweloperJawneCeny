<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Controller - Centralized controller for WordPress admin interface
 * Manages admin pages, modals, and AJAX handlers through dependency injection
 */
class AdminController {

    // Admin Pages
    private $dashboardPage;
    private $supplierDataPage;
    private $resourcesPage;
    private $publicationPage;
    private $shortcodeGeneratorPage;
    private $devConsoleTile;

    public function __construct() {

        // Initialize WordPress admin hooks
        $this->initializeAdminHooks();

        // Initialize all admin pages immediately to ensure AJAX handlers are registered
        $this->initializeAdminPages();

    }

    /**
     * Initialize WordPress admin hooks
     */
    private function initializeAdminHooks() {
        add_action('admin_menu', [$this, 'registerAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    /**
     * Initialize all admin pages immediately (no lazy loading)
     * This ensures AJAX handlers are registered on every admin request
     */
    private function initializeAdminPages() {
        $this->dashboardPage = DIContainer::get(DashboardPage::class);
        $this->supplierDataPage = DIContainer::get(SupplierDataPage::class);
        $this->resourcesPage = DIContainer::get(ResourcesPage::class);
        $this->publicationPage = DIContainer::get(PublicationPage::class);
        $this->shortcodeGeneratorPage = DIContainer::get(ShortcodeGeneratorPage::class);

    }

    /**
     * Register WordPress admin menu structure
     */
    public function registerAdminMenu() {
        // Main menu page
        add_menu_page(
            'Jawne Ceny',
            'Jawne Ceny',
            'manage_options',
            'ujc-dashboard',
            [$this, 'renderDashboardPage'],
            'dashicons-building',
            30
        );

        // Submenu pages
        add_submenu_page(
            'ujc-dashboard',
            'Pulpit',
            'Pulpit',
            'manage_options',
            'ujc-dashboard',
            [$this, 'renderDashboardPage']
        );

        add_submenu_page(
            'ujc-dashboard',
            'Dane Dostawcy',
            'Dane dostawcy',
            'manage_options',
            'ujc-developer',
            [$this, 'renderSupplierDataPage']
        );

        add_submenu_page(
            'ujc-dashboard',
            'Zasoby',
            'Zasoby',
            'manage_options',
            'ujc-resources',
            [$this, 'renderResourcesPage']
        );

        add_submenu_page(
            'ujc-dashboard',
            'Publikacja Danych',
            'Publikacja Danych',
            'manage_options',
            'ujc-publication',
            [$this, 'renderPublicationPage']
        );

        add_submenu_page(
            'ujc-dashboard',
            'Generator Shortcode',
            'Generator Shortcode',
            'manage_options',
            'ujc-shortcode-generator',
            [$this, 'renderShortcodeGeneratorPage']
        );

    }

    /**
     * Enqueue admin CSS and JavaScript assets
     */
    public function enqueueAdminAssets($hook) {
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

    /**
     * Page render methods
     */
    public function renderDashboardPage() {
        $this->dashboardPage->render();
    }

    public function renderSupplierDataPage() {
        $this->supplierDataPage->render();
    }

    public function renderResourcesPage() {
        $this->resourcesPage->render();
    }

    public function renderPublicationPage() {
        $this->publicationPage->render();
    }

    public function renderShortcodeGeneratorPage() {
        $this->shortcodeGeneratorPage->render();
    }

}