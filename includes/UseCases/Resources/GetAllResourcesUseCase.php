<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

class GetAllResourcesUseCase {

    private ResourceRepository $resourceRepository;
    private PriceHistoryRepository $priceHistoryRepository;

    public function __construct(ResourceRepository $resourceRepository, PriceHistoryRepository $priceHistoryRepository) {
        $this->resourceRepository = $resourceRepository;
        $this->priceHistoryRepository = $priceHistoryRepository;
    }
    
    /**
     * @return PresentableResource[]
     * @throws Exception
     */
    public function execute(): array {
        
        $resources = $this->resourceRepository->readAll();
        
        $result = [];
        
        foreach ($resources as $resource) {
            try {
                
                $currentPrices = $this->priceHistoryRepository->getCurrentPricesForResource($resource->id);
                
                // Safe enum conversion with fallback
                $propertyTypeDisplay = $resource->rodzaj_nieruchomosci->getDisplayText();
                
                $statusDisplay = $resource->status->getDisplayText();
                
                $presentableResource = new PresentableResource(
                    $resource->id,
                    $propertyTypeDisplay,
                    $resource->nr_lokalu,
                    $resource->powierzchnia_uzytkowa,
                    $statusDisplay,
                    $currentPrices->cena_m2,
                    $currentPrices->cena_calkowita,
                    $currentPrices->cena_z_dodatkami,
                    DateHelper::formatForUser($currentPrices->data_zmiany),
                    DateHelper::formatForUser($currentPrices->data_cena_z_dodatkami)
                );
                
                $result[] = $presentableResource;
            } catch (Exception $e) {
                // Skip resources that can't be processed, log error
                Logger::error('GetAllResourcesUseCase: Failed to process resource ID ' . $resource->id . ': ' . $e->getMessage());
                continue;
            }
        }
        
        
        return $result;
    }
}