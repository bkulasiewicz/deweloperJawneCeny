<?php

namespace JawneCeny;

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

    public function __construct(
        ResourceRepository $resourceRepository,
        PriceHistoryRepository $priceHistoryRepository
    ) {
        $this->resourceRepository = $resourceRepository;
        $this->priceHistoryRepository = $priceHistoryRepository;
    }
    
    /**
     * Execute - get resources with filtering and sorting
     */
    public function execute(array $propertyTypes = [], FilterCriteria $filters = null, SortOptions $sort = null): array {
        Logger::info("GetResourcesForFrontendUseCase::execute - Starting with " . count($propertyTypes) . " property types");

        // Build filters including property types
        $combinedFilters = $filters ?: new FilterCriteria();
        if (!empty($propertyTypes)) {
            // Convert PropertyType enums to strings for filtering
            $typeStrings = array_map(function($type) {
                return $type instanceof PropertyType ? $type->value : $type;
            }, $propertyTypes);

            $combinedFilters = new FilterCriteria(
                $combinedFilters->priceMin,
                $combinedFilters->priceMax,
                $combinedFilters->areaMin,
                $combinedFilters->areaMax,
                $combinedFilters->statusFilter,
                $combinedFilters->floorFilter,
                $combinedFilters->roomsFilter,
                $typeStrings
            );
        }

        // Get filtered and sorted resources from repository
        $resources = $this->resourceRepository->readAllWithFiltersAndSort($combinedFilters, $sort);
        Logger::info("GetResourcesForFrontendUseCase::execute - Got " . count($resources) . " filtered resources");

        $result = [];

        foreach ($resources as $resource) {
            try {
                Logger::info("GetResourcesForFrontendUseCase::execute - Processing resource ID: " . $resource->id);
                
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