<?php

if (!defined('ABSPATH')) {
    exit;
}

class GetResourceByIdUseCase {
    
    private $repository;
    private $price_history_repo;
    
    public function __construct() {
        $this->repository = new ResourceRepository();
        $this->price_history_repo = new PriceHistoryRepository();
    }
    
    public function execute($resource_id) {
        Logger::info("GetResourceByIdUseCase::execute - Starting for resource ID: " . $resource_id);
        
        $resource = $this->repository->readById($resource_id);
        
        if (!$resource) {
            Logger::info("GetResourceByIdUseCase::execute - Resource not found for ID: " . $resource_id);
            return null;
        }
        
        Logger::info("GetResourceByIdUseCase::execute - Found resource: " . $resource->nr_lokalu . " (ID: " . $resource->id . ")");
        
        // Get current prices
        try {
            Logger::info("GetResourceByIdUseCase::execute - Loading current prices for resource ID: " . $resource_id);
            $current_prices = $this->price_history_repo->getCurrentPricesForResource($resource_id);
            Logger::info("GetResourceByIdUseCase::execute - Current prices loaded successfully");
        } catch (Exception $e) {
            Logger::error("GetResourceByIdUseCase::execute - Failed to load current prices: " . $e->getMessage());
            $current_prices = null;
        }
        
        // Return resource data with prices, component fields and marketing fields
        Logger::info("GetResourceByIdUseCase::execute - Successfully completed for resource ID: " . $resource_id);
        return [
            'id' => $resource->id,
            'rodzaj_nieruchomosci' => $resource->rodzaj_nieruchomosci,
            'nr_lokalu' => $resource->nr_lokalu,
            'powierzchnia_uzytkowa' => $resource->powierzchnia_uzytkowa,
            'status' => $resource->status,
            'cena_m2' => $current_prices ? $current_prices->cena_m2 : null,
            'cena_calkowita' => $current_prices ? $current_prices->cena_calkowita : null,
            'cena_z_dodatkami' => $current_prices ? $current_prices->cena_z_dodatkami : null,
            'property_part_title' => $resource->property_part_title,
            'property_part_designation' => $resource->property_part_designation,
            'property_part_price' => $resource->property_part_price,
            'belonging_room_title' => $resource->belonging_room_title,
            'belonging_room_designation' => $resource->belonging_room_designation,
            'belonging_room_price' => $resource->belonging_room_price,
            'usage_right_title' => $resource->usage_right_title,
            'usage_right_price' => $resource->usage_right_price,
            'usage_right_price_date' => $resource->usage_right_price_date,
            'other_service_title' => $resource->other_service_title,
            'other_service_price' => $resource->other_service_price,
            'other_service_price_date' => $resource->other_service_price_date,
            'property_part_price_date' => $resource->property_part_price_date,
            'belonging_room_price_date' => $resource->belonging_room_price_date,
            // Marketing fields
            'floor_number' => $resource->floor_number,
            'room_count' => $resource->room_count,
            'additional_description' => $resource->additional_description,
            'garden_area' => $resource->garden_area,
            'floor_plan_pdf' => $resource->floor_plan_pdf
        ];
    }
}