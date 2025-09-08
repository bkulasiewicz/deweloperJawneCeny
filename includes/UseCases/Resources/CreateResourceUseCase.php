<?php

if (!defined('ABSPATH')) {
    exit;
}

class CreateResourceUseCase {
    
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
    
    public function execute(ResourceFormData $formData, $propertyParts = null, $belongingRooms = null, $usageRights = null, $otherServices = null): Result {
        Logger::info("CreateResourceUseCase::execute - Starting for nr_lokalu: " . $formData->nr_lokalu);
        
        if (!$this->validate($formData)) {
            Logger::info("CreateResourceUseCase::execute - Validation failed for nr_lokalu: " . $formData->nr_lokalu);
            return Result::failure('Numer lokalu "' . $formData->nr_lokalu . '" już istnieje. Każdy zasób musi mieć unikalną nazwę.');
        }
        
        try {
            Logger::info("CreateResourceUseCase::execute - Creating resource DTO");
            // Create resource without prices
            $resourceDto = $this->createResourceDto($formData);
            
            Logger::info("CreateResourceUseCase::execute - Calling repository->create()");
            $resource_id = $this->repository->create($resourceDto);
            Logger::info("CreateResourceUseCase::execute - Resource created with ID: " . $resource_id);
            
            // Create initial price history entry
            Logger::info("CreateResourceUseCase::execute - Creating initial price history");
            $priceHistoryDto = $this->createInitialPriceHistory($formData, $resource_id);
            $this->price_history_repo->save($priceHistoryDto);
            Logger::info("CreateResourceUseCase::execute - Price history saved");
            
            // Zapisz komponenty jeśli zostały przekazane
            Logger::info("CreateResourceUseCase::execute - Saving components");
            $this->saveComponents($resource_id, $propertyParts, $belongingRooms, $usageRights, $otherServices);
            
            Logger::info("CreateResourceUseCase::execute - Resource creation completed successfully for ID: " . $resource_id);
            return Result::success('Zasób został utworzony pomyślnie');
            
        } catch (Exception $e) {
            Logger::error("CreateResourceUseCase::execute - Exception: " . $e->getMessage());
            return Result::failure('Błąd podczas tworzenia zasobu: ' . $e->getMessage());
        }
    }
    
    private function validate(ResourceFormData $formData): bool {
        $all_resources = $this->repository->readAll();
        
        foreach ($all_resources as $resource) {
            if ($resource->nr_lokalu === $formData->nr_lokalu) {
                return false;
            }
        }
        
        return true;
    }
    
    private function createResourceDto(ResourceFormData $formData): ResourceDto {
        return new ResourceDto(
            id: 0,
            rodzaj_nieruchomosci: $formData->rodzaj_nieruchomosci->value,
            nr_lokalu: $formData->nr_lokalu,
            powierzchnia_uzytkowa: $formData->powierzchnia_uzytkowa,
            status: $formData->status->value
        );
    }
    
    private function createInitialPriceHistory(ResourceFormData $formData, int $resource_id): PriceHistoryDto {
        $currentDatetime = DateHelper::currentDatetime();
        
        return new PriceHistoryDto(
            id: 0,
            resource_id: $resource_id,
            cena_m2: $formData->cena_m2,
            cena_calkowita: $formData->cena_calkowita,
            data_zmiany: new DateTime($currentDatetime),
            cena_z_dodatkami: $formData->cena_z_dodatkami,
            data_cena_z_dodatkami: new DateTime($currentDatetime)
        );
    }
    
    private function saveComponents($resource_id, $propertyParts, $belongingRooms, $usageRights, $otherServices) {
        if ($propertyParts) {
            foreach ($propertyParts as $part) {
                $this->property_parts_repo->save($part, $resource_id);
            }
        }
        
        if ($belongingRooms) {
            foreach ($belongingRooms as $room) {
                $this->belonging_rooms_repo->save($room, $resource_id);
            }
        }
        
        if ($usageRights) {
            foreach ($usageRights as $right) {
                $this->usage_rights_repo->save($right, $resource_id);
            }
        }
        
        if ($otherServices) {
            foreach ($otherServices as $service) {
                $this->other_services_repo->save($service, $resource_id);
            }
        }
    }
}