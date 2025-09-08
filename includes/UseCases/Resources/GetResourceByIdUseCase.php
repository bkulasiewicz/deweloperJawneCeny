<?php

if (!defined('ABSPATH')) {
    exit;
}

class GetResourceByIdUseCase {
    
    private $repository;
    private $price_history_repo;
    private $property_parts_repo;
    private $belonging_rooms_repo;
    private $usage_rights_repo;
    private $other_services_repo;
    
    public function __construct() {
        $this->repository = new ResourceRepository();
        $this->price_history_repo = new PriceHistoryRepository();
        $this->property_parts_repo = new PropertyPartsRepository();
        $this->belonging_rooms_repo = new BelongingRoomsRepository();
        $this->usage_rights_repo = new UsageRightsRepository();
        $this->other_services_repo = new OtherServicesRepository();
    }
    
    public function execute($resource_id) {
        $resource = $this->repository->readById($resource_id);
        
        if (!$resource) {
            return null;
        }
        
        // Get current prices
        try {
            $current_prices = $this->price_history_repo->getCurrentPricesForResource($resource_id);
        } catch (Exception $e) {
            $current_prices = null;
        }
        
        // Load all component data
        $property_parts = $this->property_parts_repo->findByResourceId($resource_id);
        $belonging_rooms = $this->belonging_rooms_repo->findByResourceId($resource_id);
        $usage_rights = $this->usage_rights_repo->findByResourceId($resource_id);
        $other_services = $this->other_services_repo->findByResourceId($resource_id);
        
        // Return resource data with prices and components
        return [
            'id' => $resource->id,
            'rodzaj_nieruchomosci' => $resource->rodzaj_nieruchomosci,
            'nr_lokalu' => $resource->nr_lokalu,
            'powierzchnia_uzytkowa' => $resource->powierzchnia_uzytkowa,
            'status' => $resource->status,
            'cena_m2' => $current_prices ? $current_prices->cena_m2 : null,
            'cena_calkowita' => $current_prices ? $current_prices->cena_calkowita : null,
            'cena_z_dodatkami' => $current_prices ? $current_prices->cena_z_dodatkami : null,
            'property_parts' => $property_parts,
            'belonging_rooms' => $belonging_rooms,
            'usage_rights' => $usage_rights,
            'other_services' => $other_services
        ];
    }
}