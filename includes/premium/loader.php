<?php
/**
 * Premium Features Loader
 *
 * Loads all premium functionality including external cron automation.
 * This file is only present in premium version of the plugin.
 */

namespace JawneCeny;

use Exception;

if (!defined('ABSPATH')) {
    exit;
}

// Premium DI Container Registration
require_once __DIR__ . '/PremiumDIContainer.php';

// Premium Models
require_once __DIR__ . '/models/DaneGovXmlDataset.php';

// Premium Services - Formatters
require_once __DIR__ . '/services/CSVFormatter.php';
require_once __DIR__ . '/services/XMLFormatter.php';

// Premium Use Cases - File Generation
require_once __DIR__ . '/UseCases/FileGeneration/GenerateFilesUseCase.php';
require_once __DIR__ . '/UseCases/FileGeneration/GenerateCSVFileUseCase.php';
require_once __DIR__ . '/UseCases/FileGeneration/CreateDaneGovSubmissionFilesUseCase.php';

// Premium Use Cases - Supporting
require_once __DIR__ . '/UseCases/XmlResource/AddXmlResourceUseCase.php';
require_once __DIR__ . '/UseCases/Resources/GetResourcesForGovernmentUseCase.php';

// Premium Use Cases - WP Cron Fallback
require_once __DIR__ . '/UseCases/WpCronFallbackUseCase.php';

// Premium Use Cases - External Cron
require_once __DIR__ . '/UseCases/RegisterExternalCronUseCase.php';
require_once __DIR__ . '/UseCases/UnregisterExternalCronUseCase.php';
require_once __DIR__ . '/UseCases/UpdateExternalCronScheduleUseCase.php';
require_once __DIR__ . '/UseCases/ToggleExternalCronUseCase.php';

// Premium Controllers
require_once __DIR__ . '/controllers/ExternalCronController.php';
require_once __DIR__ . '/controllers/WpCronFallbackController.php';

// Premium Repositories
require_once __DIR__ . '/repositories/ExternalCronRepository.php';

// Initialize Premium Components through DI Container
DIContainer::get(ExternalCronController::class);
DIContainer::get(WpCronFallbackController::class);

// Register deactivation hook for premium cleanup
register_deactivation_hook(JAWNECENY_PLUGIN_DIR . 'ustawa-jawnosci-cen.php', function() {
    try {
        // Clean up External Cron
        $unregisterUseCase = DIContainer::get(UnregisterExternalCronUseCase::class);
        $result = $unregisterUseCase->execute();
        Logger::info("UJC: External cron cleanup on deactivation - " . $result['message']);
        
        // Clean up WP Cron Fallback
        WpCronFallbackController::disable_fallback_cron();
        Logger::info("UJC: WP Cron fallback cleanup on deactivation completed");
        
    } catch (Exception $e) {
        Logger::error("UJC: Error during premium cleanup: " . $e->getMessage());
    }
});