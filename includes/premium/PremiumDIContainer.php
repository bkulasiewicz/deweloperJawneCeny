<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Premium Features DI Container Registration
 * Registers all premium-only classes in the main DI Container
 *
 * This class is only present in premium version and contains all DI bindings
 * for premium services, use cases, and controllers.
 */
class PremiumDIContainer {

    /**
     * Register all premium features in the container
     *
     * @param \lucatume\DI52\Container $container
     */
    public static function register($container) {
        self::registerPremiumServices($container);
        self::registerPremiumUseCases($container);
        self::registerPremiumControllers($container);
    }

    /**
     * Register premium services (formatters)
     */
    private static function registerPremiumServices($c) {
        // Premium formatters - singletons for performance
        $c->singleton(CSVFormatter::class);
        $c->singleton(XMLFormatter::class);
    }

    /**
     * Register premium use cases (file generation, automation)
     */
    private static function registerPremiumUseCases($c) {
        // Supporting Use Cases for file generation
        $c->bind(GetResourcesForGovernmentUseCase::class, function() use ($c) {
            return new GetResourcesForGovernmentUseCase(
                $c->get(ResourceRepository::class),
                $c->get(PriceHistoryRepository::class)
            );
        });

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

        // WP Cron Fallback Use Case
        $c->bind(WpCronFallbackUseCase::class, function() use ($c) {
            return new WpCronFallbackUseCase(
                $c->get(SettingsRepository::class),
                $c->get(GetPublicationHistoryUseCase::class),
                $c->get(GenerateFilesUseCase::class)
            );
        });

        // External Cron Use Cases
        $c->bind(RegisterExternalCronUseCase::class, function() use ($c) {
            return new RegisterExternalCronUseCase(
                $c->get(ExternalCronRepository::class),
                $c->get(DeveloperRepository::class),
                $c->get(InvestmentRepository::class),
                $c->get(ResourceRepository::class)
            );
        });

        $c->bind(UnregisterExternalCronUseCase::class, function() use ($c) {
            return new UnregisterExternalCronUseCase(
                $c->get(ExternalCronRepository::class)
            );
        });

        $c->bind(UpdateExternalCronScheduleUseCase::class, function() use ($c) {
            return new UpdateExternalCronScheduleUseCase(
                $c->get(ExternalCronRepository::class)
            );
        });

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

    /**
     * Register premium controllers (automation, external cron)
     */
    private static function registerPremiumControllers($c) {
        $c->bind(ExternalCronController::class, function() use ($c) {
            return new ExternalCronController(
                $c->get(ExternalCronRepository::class),
                $c->get(GenerateFilesUseCase::class),
                $c->get(UpdateExternalCronScheduleUseCase::class),
                $c->get(RegisterExternalCronUseCase::class),
                $c->get(UnregisterExternalCronUseCase::class)
            );
        });

        $c->bind(WpCronFallbackController::class, function() use ($c) {
            return new WpCronFallbackController(
                $c->get(WpCronFallbackUseCase::class)
            );
        });
    }
}
