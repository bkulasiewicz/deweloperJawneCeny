<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * UseCase for getting resources specifically for frontend display (shortcode, widgets)
 * Returns ShortcodeResource objects with all fields for shortcode functionality
 */
class GetResourcesForFrontendUseCase {
    
    private $resourceRepository;
    private $priceHistoryRepository;
    
    public function __construct() {
        $this->resourceRepository = new ResourceRepository();
        $this->priceHistoryRepository = new PriceHistoryRepository();
    }
    
    /**
     * Execute - get resources filtered by property types
     */
    public function execute(array $propertyTypes = []): array {
        Logger::info("GetResourcesForFrontendUseCase::execute - Starting with " . count($propertyTypes) . " property types");
        
        // Get all resources from repository
        $resources = $this->resourceRepository->readAll();
        Logger::info("GetResourcesForFrontendUseCase::execute - Got " . count($resources) . " total resources");
        
        $result = [];
        
        foreach ($resources as $resource) {
            try {
                Logger::info("GetResourcesForFrontendUseCase::execute - Processing resource ID: " . $resource->id);
                
                // Filter by property types if specified
                if (!empty($propertyTypes)) {
                    if (!in_array($resource->rodzaj_nieruchomosci, $propertyTypes)) {
                        Logger::info("GetResourcesForFrontendUseCase::execute - Resource ID " . $resource->id . " filtered out by type");
                        continue;
                    }
                }
                
                // Get current prices
                $currentPrices = $this->priceHistoryRepository->getCurrentPricesForResource($resource->id);
                
                // Create ShortcodeResource with all fields including marketing data
                $shortcodeResource = new ShortcodeResource(
                    $resource->id,
                    $resource->rodzaj_nieruchomosci->value, // ← enum value, not display text
                    $resource->nr_lokalu,
                    $resource->powierzchnia_uzytkowa,
                    $resource->status->value, // ← enum value, not display text  
                    $currentPrices->cena_m2,
                    $currentPrices->cena_calkowita,
                    $currentPrices->cena_z_dodatkami,
                    DateHelper::formatForUser($currentPrices->data_zmiany),
                    DateHelper::formatForUser($currentPrices->data_cena_z_dodatkami),
                    $resource->floor_number,
                    $resource->room_count,
                    $resource->additional_description,
                    $resource->garden_area,
                    $resource->floor_plan_pdf
                );
                
                Logger::info("GetResourcesForFrontendUseCase::execute - Successfully created ShortcodeResource for ID: " . $resource->id);
                $result[] = $shortcodeResource;
                
            } catch (Exception $e) {
                Logger::error('GetResourcesForFrontendUseCase::execute - Failed to process resource ID ' . $resource->id . ': ' . $e->getMessage());
                continue;
            }
        }
        
        Logger::info("GetResourcesForFrontendUseCase::execute - Processed " . count($result) . " frontend resources");
        return $result;
    }
}