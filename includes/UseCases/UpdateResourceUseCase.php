<?php

if (!defined('ABSPATH')) {
    exit;
}

class UpdateResourceUseCase {
    
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
    
    public function execute(ResourceFormData $formData, int $resource_id, $propertyParts = null, $belongingRooms = null, $usageRights = null, $otherServices = null): Result {
        if (!$this->validate($formData, $resource_id)) {
            return Result::failure('Numer lokalu "' . $formData->nr_lokalu . '" już istnieje. Każdy zasób musi mieć unikalną nazwę.');
        }
        
        try {
            // Create resource DTO without prices
            $resourceDto = $this->createResourceDto($formData);
            $this->repository->update($resourceDto, $resource_id);
            
            // Zapisz komponenty jeśli zostały przekazane
            $this->updateComponents($resource_id, $propertyParts, $belongingRooms, $usageRights, $otherServices);
            
            // Zapisz historię cen jeśli się zmieniły
            $this->savePriceHistoryIfChanged($formData, $resource_id);
            
            return Result::success('Zasób został zaktualizowany pomyślnie');
            
        } catch (Exception $e) {
            return Result::failure('Błąd podczas aktualizacji zasobu: ' . $e->getMessage());
        }
    }
    
    private function validate(ResourceFormData $formData, int $exclude_id): bool {
        $all_resources = $this->repository->readAll();
        
        foreach ($all_resources as $resource) {
            if ($resource->id == $exclude_id) {
                continue;
            }
            
            if ($resource->nr_lokalu === $formData->nr_lokalu) {
                return false;
            }
        }
        
        return true;
    }
    
    private function updateComponents($resource_id, $propertyParts, $belongingRooms, $usageRights, $otherServices) {
        if ($propertyParts) {
            $this->property_parts_repo->deleteByResourceId($resource_id);
            foreach ($propertyParts as $part) {
                $this->property_parts_repo->save($part, $resource_id);
            }
        }
        
        if ($belongingRooms) {
            $this->belonging_rooms_repo->deleteByResourceId($resource_id);
            foreach ($belongingRooms as $room) {
                $this->belonging_rooms_repo->save($room, $resource_id);
            }
        }
        
        if ($usageRights) {
            $this->usage_rights_repo->deleteByResourceId($resource_id);
            foreach ($usageRights as $right) {
                $this->usage_rights_repo->save($right, $resource_id);
            }
        }
        
        if ($otherServices) {
            $this->other_services_repo->deleteByResourceId($resource_id);
            foreach ($otherServices as $service) {
                $this->other_services_repo->save($service, $resource_id);
            }
        }
    }
    
    private function createResourceDto(ResourceFormData $formData): ResourceDto {
        return new ResourceDto(
            id: 0,
            rodzaj_nieruchomosci: $formData->rodzaj_nieruchomosci,
            nr_lokalu: $formData->nr_lokalu,
            powierzchnia_uzytkowa: $formData->powierzchnia_uzytkowa,
            status: $formData->status
        );
    }
    
    private function savePriceHistoryIfChanged(ResourceFormData $formData, int $resource_id) {
        // Get current prices from PriceHistory
        $currentPrices = $this->price_history_repo->getCurrentPricesForResource($resource_id);
        
        // Check what changed
        $cena_m2_changed = $currentPrices->cena_m2 != $formData->cena_m2;
        $cena_calkowita_changed = $currentPrices->cena_calkowita != $formData->cena_calkowita;
        $cena_z_dodatkami_changed = $currentPrices->cena_z_dodatkami != $formData->cena_z_dodatkami;
        
        if ($cena_m2_changed || $cena_calkowita_changed || $cena_z_dodatkami_changed) {
            $currentDatetime = DateHelper::currentDatetime();
            
            // Determine dates based on what changed
            $data_zmiany = $cena_m2_changed || $cena_calkowita_changed 
                ? new DateTime($currentDatetime) 
                : $currentPrices->data_zmiany;
                
            $data_cena_z_dodatkami = $cena_z_dodatkami_changed 
                ? new DateTime($currentDatetime) 
                : $currentPrices->data_cena_z_dodatkami;
            
            $priceHistoryDto = new PriceHistoryDto(
                id: 0,
                resource_id: $resource_id,
                cena_m2: $formData->cena_m2,
                cena_calkowita: $formData->cena_calkowita,
                data_zmiany: $data_zmiany,
                cena_z_dodatkami: $formData->cena_z_dodatkami,
                data_cena_z_dodatkami: $data_cena_z_dodatkami
            );
            
            $this->price_history_repo->save($priceHistoryDto);
        }
    }
}