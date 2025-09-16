<?php

if (!defined('ABSPATH')) {
    exit;
}

class UpdateResourceUseCase {

    private $repository;
    private $price_history_repo;

    public function __construct(ResourceRepository $repository, PriceHistoryRepository $priceHistoryRepo) {
        $this->repository = $repository;
        $this->price_history_repo = $priceHistoryRepo;
    }
    
    public function execute(ResourceFormData $formData, int $resource_id): Result {
        Logger::info("UpdateResourceUseCase::execute - Starting for resource ID: {$resource_id}, nr_lokalu: {$formData->nr_lokalu}");
        
        if (!$this->validate($formData, $resource_id)) {
            Logger::info("UpdateResourceUseCase::execute - Validation failed - nr_lokalu '{$formData->nr_lokalu}' already exists");
            return Result::failure('Numer lokalu "' . $formData->nr_lokalu . '" już istnieje. Każdy zasób musi mieć unikalną nazwę.');
        }
        Logger::info("UpdateResourceUseCase::execute - Validation passed");
        
        try {
            // Create resource DTO with proper date logic
            Logger::info("UpdateResourceUseCase::execute - Creating ResourceDto from FormData");
            $resourceDto = $this->createResourceDto($formData, $resource_id);
            
            Logger::info("UpdateResourceUseCase::execute - Updating resource in repository");
            $this->repository->update($resourceDto, $resource_id);
            
            // Zapisz historię cen jeśli się zmieniły
            Logger::info("UpdateResourceUseCase::execute - Checking and saving price history changes");
            $this->savePriceHistoryIfChanged($formData, $resource_id);
            
            Logger::info("UpdateResourceUseCase::execute - Successfully completed for resource ID: {$resource_id}");
            return Result::success('Zasób został zaktualizowany pomyślnie');
            
        } catch (Exception $e) {
            Logger::error("UpdateResourceUseCase::execute - Exception: " . $e->getMessage());
            Logger::error("UpdateResourceUseCase::execute - Exception trace: " . $e->getTraceAsString());
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
    
    
    private function createResourceDto(ResourceFormData $formData, int $resource_id): ResourceDto {
        // Get existing resource for date comparison
        $existingResource = $this->repository->readById($resource_id);
        $currentDate = DateHelper::currentDatetime();
        
        return new ResourceDto(
            id: 0, // ID will be set by repository->update()
            investment_id: 1, // Default investment_id for compatibility
            rodzaj_nieruchomosci: $formData->rodzaj_nieruchomosci,
            nr_lokalu: $formData->nr_lokalu,
            powierzchnia_uzytkowa: $formData->powierzchnia_uzytkowa,
            status: $formData->status,
            property_part_title: $formData->property_part?->title,
            property_part_designation: $formData->property_part?->designation,
            property_part_price: $formData->property_part?->price,
            belonging_room_title: $formData->belonging_room?->title,
            belonging_room_designation: $formData->belonging_room?->designation,
            belonging_room_price: $formData->belonging_room?->price,
            usage_right_title: $formData->usage_right?->title,
            usage_right_price: $formData->usage_right?->price,
            other_service_title: $formData->other_service?->title,
            other_service_price: $formData->other_service?->price,
            property_part_price_date: $this->getDateForComponent(
                $formData->property_part?->price,
                $existingResource?->property_part_price,
                $existingResource?->property_part_price_date,
                $currentDate
            ),
            belonging_room_price_date: $this->getDateForComponent(
                $formData->belonging_room?->price,
                $existingResource?->belonging_room_price,
                $existingResource?->belonging_room_price_date,
                $currentDate
            ),
            usage_right_price_date: $this->getDateForComponent(
                $formData->usage_right?->price,
                $existingResource?->usage_right_price,
                $existingResource?->usage_right_price_date,
                $currentDate
            ),
            other_service_price_date: $this->getDateForComponent(
                $formData->other_service?->price,
                $existingResource?->other_service_price,
                $existingResource?->other_service_price_date,
                $currentDate
            ),
            // Marketing fields
            floor_number: $formData->floor_number,
            room_count: $formData->room_count,
            additional_description: $formData->additional_description,
            garden_area: $formData->garden_area,
            floor_plan_pdf: $formData->floor_plan_pdf
        );
    }
    
    /**
     * Calculate date for component based on price changes
     */
    private function getDateForComponent(?float $newPrice, ?float $oldPrice, ?string $oldDate, string $currentDate): ?string {
        // If no price, no date needed
        if ($newPrice === null) {
            return null;
        }
        
        // If price changed, use current date
        if ($newPrice != $oldPrice) {
            return $currentDate;
        }
        
        // If price same, keep old date (or current if no old date)
        return $oldDate ?? $currentDate;
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
                ? $currentDatetime
                : $currentPrices->data_zmiany;
                
            $data_cena_z_dodatkami = $cena_z_dodatkami_changed 
                ? $currentDatetime
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