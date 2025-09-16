<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * UseCase for getting resources filtered for government reporting
 * Excludes service premises from the results as they are not reported to the ministry
 */
class GetResourcesForGovernmentUseCase {
    
    private ResourceRepository $resourceRepository;
    private PriceHistoryRepository $priceHistoryRepository;
    
    public function __construct(
        ResourceRepository $resourceRepository,
        PriceHistoryRepository $priceHistoryRepository
    ) {
        $this->resourceRepository = $resourceRepository;
        $this->priceHistoryRepository = $priceHistoryRepository;
    }
    
    /**
     * Execute the use case
     * @return array Filtered array of ResourceDto objects (excluding service premises)
     */
    public function execute(): array {
        Logger::info("GetResourcesForGovernmentUseCase::execute - Starting");
        
        // Get all ResourceDto objects from repository
        $resources = $this->resourceRepository->readAll();
        Logger::info("GetResourcesForGovernmentUseCase::execute - Got " . count($resources) . " resources from repository");
        
        // Filter out service premises at ResourceDto level
        $filteredResources = array_filter($resources, function($resource) {
            return $resource->rodzaj_nieruchomosci !== PropertyType::SERVICE_PREMISES;
        });
        Logger::info("GetResourcesForGovernmentUseCase::execute - Filtered to " . count($filteredResources) . " resources (excluding service premises)");
        
        // Return filtered ResourceDto array directly - no conversion to PresentableResource needed for government use
        Logger::info("GetResourcesForGovernmentUseCase::execute - Returning " . count($filteredResources) . " ResourceDto objects for government");
        
        return array_values($filteredResources);
    }
}