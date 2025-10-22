<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

use lucatume\DI52\Container;

/**
 * Dependency Injection Container for UJC Plugin
 * Implements layered architecture: Infrastructure -> Domain -> Application -> Presentation
 * Optimized for performance with proper caching and lazy loading
 */
class DIContainer {
    private static $container = null;
    private static $isInitialized = false;

    /**
     * Initialize DI Container with all services (optimized)
     */
    public static function init() {
        if (self::$container === null) {
            $startTime = microtime(true);
            self::$container = new Container();

            self::registerInfrastructure();
            self::registerDomain();
            self::registerApplication();
            self::registerPresentation();

            self::$isInitialized = true;
            $endTime = microtime(true);
            $initTime = round(($endTime - $startTime) * 1000, 2);
            Logger::info("DIContainer: All services registered in {$initTime}ms");
        }
        return self::$container;
    }

    /**
     * Get service from container (optimized)
     */
    public static function get($abstract) {
        if (self::$container === null) {
            self::init();
        }


        return self::$container->get($abstract);
    }

    /**
     * Check if container is initialized
     */
    public static function isInitialized(): bool {
        return self::$isInitialized;
    }

    /**
     * Get container instance count for debugging
     */
    public static function getStats(): array {
        if (!self::$isInitialized) {
            return ['status' => 'not_initialized'];
        }

        return [
            'status' => 'initialized',
            'container_class' => get_class(self::$container),
            'services_count' => '20+ Use Cases, 7 repositories, 5 services, 3 modals, 2 controllers'
        ];
    }

    /**
     * INFRASTRUCTURE LAYER - Repositories jako singletony
     * Dostęp do bazy danych, zewnętrznych APIs, filesystem
     */
    private static function registerInfrastructure() {
        $c = self::$container;

        // Wszystkie repositories jako singletony - jedna instancja w całej aplikacji
        $c->singleton(ResourceRepository::class);
        $c->singleton(PriceHistoryRepository::class);
        $c->singleton(PublicationHistoryRepository::class);
        $c->singleton(SettingsRepository::class);
        $c->singleton(DeveloperRepository::class);
        $c->singleton(InvestmentRepository::class);
        $c->singleton(XmlResourceRepository::class);

        // Premium repositories (conditional loading)
        if (class_exists(ExternalCronRepository::class)) {
            $c->singleton(ExternalCronRepository::class);
        }
    }

    /**
     * DOMAIN LAYER - Use Cases z dependency injection
     * Business logic, główne przypadki użycia aplikacji
     */
    private static function registerDomain() {
        $c = self::$container;

        // Resource Use Cases
        $c->bind(GetAllResourcesUseCase::class, function() use ($c) {
            return new GetAllResourcesUseCase(
                $c->get(ResourceRepository::class),
                $c->get(PriceHistoryRepository::class)
            );
        });

        $c->bind(GetResourcesForFrontendUseCase::class, function() use ($c) {
            return new GetResourcesForFrontendUseCase(
                $c->get(ResourceRepository::class),
                $c->get(PriceHistoryRepository::class)
            );
        });

        $c->bind(GetResourceForSinglePageUseCase::class, function() use ($c) {
            return new GetResourceForSinglePageUseCase(
                $c->get(ResourceRepository::class),
                $c->get(PriceHistoryRepository::class)
            );
        });

        $c->bind(CreateResourceUseCase::class, function() use ($c) {
            return new CreateResourceUseCase(
                $c->get(ResourceRepository::class),
                $c->get(PriceHistoryRepository::class)
            );
        });

        $c->bind(UpdateResourceUseCase::class, function() use ($c) {
            return new UpdateResourceUseCase(
                $c->get(ResourceRepository::class),
                $c->get(PriceHistoryRepository::class)
            );
        });

        $c->bind(DeleteResourceUseCase::class, function() use ($c) {
            return new DeleteResourceUseCase(
                $c->get(ResourceRepository::class)
            );
        });

        $c->bind(GetResourceByIdUseCase::class, function() use ($c) {
            return new GetResourceByIdUseCase(
                $c->get(ResourceRepository::class),
                $c->get(PriceHistoryRepository::class)
            );
        });

        $c->bind(GetResourcesForGovernmentUseCase::class, function() use ($c) {
            return new GetResourcesForGovernmentUseCase(
                $c->get(ResourceRepository::class),
                $c->get(PriceHistoryRepository::class)
            );
        });

        // Publication History Use Cases
        $c->bind(GetPublicationHistoryUseCase::class, function() use ($c) {
            return new GetPublicationHistoryUseCase(
                $c->get(PublicationHistoryRepository::class)
            );
        });

        $c->bind(AddPublicationHistoryUseCase::class, function() use ($c) {
            return new AddPublicationHistoryUseCase(
                $c->get(PublicationHistoryRepository::class)
            );
        });

        // Price History Use Cases
        $c->bind(GetPriceHistoryUseCase::class, function() use ($c) {
            return new GetPriceHistoryUseCase(
                $c->get(PriceHistoryRepository::class)
            );
        });

        // Developer Use Cases
        $c->bind(SaveDeveloperInfoUseCase::class, function() use ($c) {
            return new SaveDeveloperInfoUseCase(
                $c->get(DeveloperRepository::class)
            );
        });

        $c->bind(GetDeveloperInfoUseCase::class, function() use ($c) {
            return new GetDeveloperInfoUseCase(
                $c->get(DeveloperRepository::class)
            );
        });

        // Investment Use Cases
        $c->bind(CreateInvestmentUseCase::class, function() use ($c) {
            return new CreateInvestmentUseCase(
                $c->get(InvestmentRepository::class)
            );
        });

        $c->bind(GetInvestmentUseCase::class, function() use ($c) {
            return new GetInvestmentUseCase(
                $c->get(InvestmentRepository::class)
            );
        });

        $c->bind(UpdateInvestmentUseCase::class, function() use ($c) {
            return new UpdateInvestmentUseCase(
                $c->get(InvestmentRepository::class)
            );
        });

        // XML Resource Use Cases
        $c->bind(AddXmlResourceUseCase::class, function() use ($c) {
            return new AddXmlResourceUseCase(
                $c->get(XmlResourceRepository::class),
                $c->get(DeveloperRepository::class)
            );
        });

        // File Generation Use Cases
        $c->bind(GenerateCSVFileUseCase::class, function() use ($c) {
            return new GenerateCSVFileUseCase(
                $c->get(DeveloperRepository::class),
                $c->get(InvestmentRepository::class),
                $c->get(ResourceRepository::class),
                $c->get(PriceHistoryRepository::class),
                $c->get(CSVFormatter::class),
                $c->get(FileManager::class),
                $c->get(GetResourcesForGovernmentUseCase::class)
            );
        });

        $c->bind(CreateDaneGovSubmissionFilesUseCase::class, function() use ($c) {
            return new CreateDaneGovSubmissionFilesUseCase(
                $c->get(XMLFormatter::class),
                $c->get(FileManager::class),
                $c->get(DeveloperRepository::class),
                $c->get(XmlResourceRepository::class)
            );
        });

        $c->bind(GenerateFilesUseCase::class, function() use ($c) {
            return new GenerateFilesUseCase(
                $c->get(GenerateCSVFileUseCase::class),
                $c->get(CreateDaneGovSubmissionFilesUseCase::class),
                $c->get(AddPublicationHistoryUseCase::class),
                $c->get(AddXmlResourceUseCase::class)
            );
        });

        // System Use Cases
        $c->bind(ImportResourcesUseCase::class, function() use ($c) {
            return new ImportResourcesUseCase(
                $c->get(CreateResourceUseCase::class),
                $c->get(UpdateResourceUseCase::class),
                $c->get(GetResourceByIdUseCase::class)
            );
        });

        $c->bind(ResetDatabaseUseCase::class, function() use ($c) {
            return new ResetDatabaseUseCase();
        });

        // Premium Use Cases (conditional loading)
        if (class_exists(WpCronFallbackUseCase::class)) {
            $c->bind(WpCronFallbackUseCase::class, function() use ($c) {
                return new WpCronFallbackUseCase(
                    $c->get(SettingsRepository::class),
                    $c->get(GetPublicationHistoryUseCase::class),
                    $c->get(GenerateFilesUseCase::class)
                );
            });
        }

        if (class_exists(ToggleExternalCronUseCase::class)) {
            $c->bind(ToggleExternalCronUseCase::class, function() use ($c) {
                return new ToggleExternalCronUseCase(
                    $c->get(RegisterExternalCronUseCase::class),
                    $c->get(UnregisterExternalCronUseCase::class),
                    $c->get(DeveloperRepository::class),
                    $c->get(InvestmentRepository::class),
                    $c->get(ResourceRepository::class)
                );
            });
        }

        if (class_exists(RegisterExternalCronUseCase::class)) {
            $c->bind(RegisterExternalCronUseCase::class, function() use ($c) {
                return new RegisterExternalCronUseCase(
                    $c->get(ExternalCronRepository::class),
                    $c->get(DeveloperRepository::class),
                    $c->get(InvestmentRepository::class),
                    $c->get(ResourceRepository::class)
                );
            });
        }

        if (class_exists(UnregisterExternalCronUseCase::class)) {
            $c->bind(UnregisterExternalCronUseCase::class, function() use ($c) {
                return new UnregisterExternalCronUseCase(
                    $c->get(ExternalCronRepository::class)
                );
            });
        }

        if (class_exists(UpdateExternalCronScheduleUseCase::class)) {
            $c->bind(UpdateExternalCronScheduleUseCase::class, function() use ($c) {
                return new UpdateExternalCronScheduleUseCase(
                    $c->get(ExternalCronRepository::class)
                );
            });
        }

    }

    /**
     * APPLICATION LAYER - Controllers i Services
     * Orchestracja, formatowanie, file operations
     */
    private static function registerApplication() {
        $c = self::$container;

        // Services jako singletony
        $c->singleton(FileManager::class);
        $c->singleton(CSVFormatter::class);
        $c->singleton(XMLFormatter::class);
        $c->singleton(DateHelper::class);

        // Modale - zwykłe bindings (nie singleton)
        $c->bind(ResourceModal::class, function() use ($c) {
            return new ResourceModal(
                $c->get(CreateResourceUseCase::class),
                $c->get(UpdateResourceUseCase::class),
                $c->get(GetResourceByIdUseCase::class),
                $c->get(DeleteResourceUseCase::class),
                $c->get(FileManager::class)
            );
        });

        $c->bind(InvestmentModal::class, function() use ($c) {
            return new InvestmentModal(
                $c->get(CreateInvestmentUseCase::class),
                $c->get(UpdateInvestmentUseCase::class),
                $c->get(GetInvestmentUseCase::class)
            );
        });

        $c->bind(HistoryModal::class, function() use ($c) {
            return new HistoryModal(
                $c->get(GetPriceHistoryUseCase::class)
            );
        });

        $c->singleton(ResourceItem::class, function() use ($c) {
            return new ResourceItem();
        });

        // Controllers - Faza 4
        $c->singleton(AdminController::class, function() use ($c) {
            return new AdminController();
        });

        $c->singleton(ShortcodeManager::class);
        $c->singleton(BlocksManager::class);

        // Premium controllers (conditional loading)
        if (class_exists(ExternalCronController::class)) {
            $c->bind(ExternalCronController::class, function() use ($c) {
                return new ExternalCronController(
                    $c->get(ExternalCronRepository::class),
                    $c->get(GenerateFilesUseCase::class),
                    $c->get(UpdateExternalCronScheduleUseCase::class),
                    $c->get(RegisterExternalCronUseCase::class),
                    $c->get(UnregisterExternalCronUseCase::class)
                );
            });
        }

        if (class_exists(WpCronFallbackController::class)) {
            $c->bind(WpCronFallbackController::class, function() use ($c) {
                return new WpCronFallbackController(
                    $c->get(WpCronFallbackUseCase::class)
                );
            });
        }

    }

    /**
     * PRESENTATION LAYER - Admin Pages, UI Components
     * Renderowanie, templates, user interface
     */
    private static function registerPresentation() {
        $c = self::$container;

        // Admin Pages - LAZY LOADING (tworzone tylko gdy potrzebne)
        $c->bind(ResourcesPage::class, function() use ($c) {
            return new ResourcesPage(
                $c->get(GetAllResourcesUseCase::class),
                $c->get(ResourceModal::class),
                $c->get(InvestmentModal::class),
                $c->get(DeveloperRepository::class),
                $c->get(InvestmentRepository::class),
                $c->get(HistoryModal::class),
                $c->get(ResourceItem::class)
            );
        });

        $c->bind(CsvFilesSection::class, function() use ($c) {
            return new CsvFilesSection(
                $c->get(XmlResourceRepository::class)
            );
        });

        $c->bind(PublicationPage::class, function() use ($c) {
            return new PublicationPage(
                $c->get(GetPublicationHistoryUseCase::class),
                $c->get(CsvFilesSection::class),
                $c->get(GenerateFilesUseCase::class)
            );
        });

        $c->bind(SupplierDataPage::class, function() use ($c) {
            return new SupplierDataPage(
                $c->get(SaveDeveloperInfoUseCase::class),
                $c->get(GetDeveloperInfoUseCase::class)
            );
        });

        $c->bind(DashboardPage::class, function() use ($c) {
            return new DashboardPage(
                $c->get(DeveloperRepository::class),
                $c->get(InvestmentRepository::class),
                $c->get(ResourceRepository::class),
                $c->get(GenerateFilesUseCase::class),
                $c->get(AutomationTile::class),
                $c->get(HistoryTile::class),
                $c->get(DevConsoleTile::class)
            );
        });

        $c->bind(DevConsoleTile::class, function() use ($c) {
            return new DevConsoleTile(
                $c->get(ResetDatabaseUseCase::class)
            );
        });

        $c->bind(AutomationTile::class, function() use ($c) {
            // Premium dependencies are optional (nullable)
            $externalCronRepo = class_exists(ExternalCronRepository::class) ? $c->get(ExternalCronRepository::class) : null;
            $externalCronController = class_exists(ExternalCronController::class) ? $c->get(ExternalCronController::class) : null;
            $toggleUseCase = class_exists(ToggleExternalCronUseCase::class) ? $c->get(ToggleExternalCronUseCase::class) : null;

            return new AutomationTile(
                $externalCronRepo,
                $externalCronController,
                $toggleUseCase
            );
        });

        $c->bind(HistoryTile::class, function() use ($c) {
            return new HistoryTile(
                $c->get(GetPublicationHistoryUseCase::class)
            );
        });

        $c->bind(ShortcodeGeneratorPage::class, function() use ($c) {
            return new ShortcodeGeneratorPage(
                $c->get(GetAllResourcesUseCase::class)
            );
        });

    }
}