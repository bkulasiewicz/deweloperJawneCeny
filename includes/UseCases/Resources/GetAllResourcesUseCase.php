<?php

if (!defined('ABSPATH')) {
    exit;
}

class GetAllResourcesUseCase {
    
    private ResourceRepository $resourceRepository;
    private PriceHistoryRepository $priceHistoryRepository;
    
    public function __construct() {
        $this->resourceRepository = new ResourceRepository();
        $this->priceHistoryRepository = new PriceHistoryRepository();
    }
    
    /**
     * @return PresentableResource[]
     * @throws Exception
     */
    public function execute(): array {
        Logger::info("GetAllResourcesUseCase::execute - Starting");
        
        $resources = $this->resourceRepository->readAll();
        Logger::info("GetAllResourcesUseCase::execute - Got " . count($resources) . " resources from repository");
        
        $result = [];
        
        foreach ($resources as $resource) {
            try {
                Logger::info("GetAllResourcesUseCase::execute - Processing resource ID: " . $resource->id);
                
                Logger::info("GetAllResourcesUseCase::execute - Getting current prices for resource ID: " . $resource->id);
                $currentPrices = $this->priceHistoryRepository->getCurrentPricesForResource($resource->id);
                Logger::info("GetAllResourcesUseCase::execute - Got prices for resource ID: " . $resource->id);
                
                // Safe enum conversion with fallback
                Logger::info("GetAllResourcesUseCase::execute - Converting PropertyType: " . $resource->rodzaj_nieruchomosci);
                try {
                    $propertyTypeDisplay = PropertyType::from($resource->rodzaj_nieruchomosci)->getDisplayText();
                } catch (ValueError $e) {
                    Logger::info("GetAllResourcesUseCase::execute - PropertyType conversion failed, using fallback");
                    // Fallback for old/invalid values
                    $propertyTypeDisplay = $resource->rodzaj_nieruchomosci;
                }
                
                Logger::info("GetAllResourcesUseCase::execute - Converting ResourceStatus: " . $resource->status);
                try {
                    $statusDisplay = ResourceStatus::from($resource->status)->getDisplayText();
                } catch (ValueError $e) {
                    Logger::info("GetAllResourcesUseCase::execute - ResourceStatus conversion failed, using fallback");
                    // Fallback for old/invalid values
                    $statusDisplay = $resource->status;
                }
                
                Logger::info("GetAllResourcesUseCase::execute - Creating PresentableResource for ID: " . $resource->id);
                $presentableResource = new PresentableResource(
                    $resource->id,
                    $propertyTypeDisplay,
                    $resource->nr_lokalu,
                    $resource->powierzchnia_uzytkowa,
                    $statusDisplay,
                    $currentPrices->cena_m2,
                    $currentPrices->cena_calkowita,
                    $currentPrices->cena_z_dodatkami,
                    DateHelper::formatDateTimeForUser($currentPrices->data_zmiany),
                    DateHelper::formatDateTimeForUser($currentPrices->data_cena_z_dodatkami)
                );
                
                Logger::info("GetAllResourcesUseCase::execute - Successfully created PresentableResource for ID: " . $resource->id);
                $result[] = $presentableResource;
            } catch (Exception $e) {
                // Skip resources that can't be processed, log error
                Logger::error('GetAllResourcesUseCase: Failed to process resource ID ' . $resource->id . ': ' . $e->getMessage());
                continue;
            }
        }
        
        Logger::info("GetAllResourcesUseCase::execute - Processed " . count($result) . " presentable resources");
        
        return $result;
    }
}