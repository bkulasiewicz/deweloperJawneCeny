<?php
/**
 * Premium Features Loader
 * 
 * Loads all premium functionality including external cron automation.
 * This file is only present in premium version of the plugin.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Premium Controllers
require_once __DIR__ . '/controllers/ExternalCronController.php';
require_once __DIR__ . '/controllers/WpCronFallbackController.php';

// Premium Repositories
require_once __DIR__ . '/repositories/ExternalCronRepository.php';

// Premium Use Cases
require_once __DIR__ . '/UseCases/WpCronFallbackUseCase.php';
require_once __DIR__ . '/UseCases/RegisterExternalCronUseCase.php';
require_once __DIR__ . '/UseCases/UnregisterExternalCronUseCase.php';
require_once __DIR__ . '/UseCases/UpdateExternalCronScheduleUseCase.php';
require_once __DIR__ . '/UseCases/ToggleExternalCronUseCase.php';

// Initialize Premium Components
new ExternalCronController();
new WpCronFallbackController();

// Register deactivation hook for premium cleanup
register_deactivation_hook(PLUGIN_DIR . 'ustawa-jawnosci-cen.php', function() {
    try {
        // Clean up External Cron
        $unregisterUseCase = new UnregisterExternalCronUseCase();
        $result = $unregisterUseCase->execute();
        error_log("UJC: External cron cleanup on deactivation - " . $result['message']);
        
        // Clean up WP Cron Fallback
        WpCronFallbackController::disable_fallback_cron();
        error_log("UJC: WP Cron fallback cleanup on deactivation completed");
        
    } catch (Exception $e) {
        error_log("UJC: Error during premium cleanup: " . $e->getMessage());
    }
});