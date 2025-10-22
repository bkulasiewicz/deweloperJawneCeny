<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get Resource For Single Page UseCase
 * Retrieves complete resource data for single resource detail page
 */
class GetResourceForSinglePageUseCase {

    private ResourceRepository $repository;
    private PriceHistoryRepository $priceHistoryRepo;

    public function __construct(
        ResourceRepository $repository,
        PriceHistoryRepository $priceHistoryRepo
    ) {
        $this->repository = $repository;
        $this->priceHistoryRepo = $priceHistoryRepo;
    }

    /**
     * Execute - get resource by nr_lokalu with all related data
     *
     * @param string $nr_lokalu Resource unit number
     * @return SinglePageResource|null Complete resource data or null if not found
     */
    public function execute(string $nr_lokalu): ?SinglePageResource {
        Logger::info("GetResourceForSinglePageUseCase::execute - Starting for nr_lokalu: {$nr_lokalu}");

        // Find resource by nr_lokalu
        $resource = $this->repository->readByNrLokalu($nr_lokalu);

        if (!$resource) {
            Logger::info("GetResourceForSinglePageUseCase::execute - Resource not found for nr_lokalu: {$nr_lokalu}");
            return null;
        }

        Logger::info("GetResourceForSinglePageUseCase::execute - Found resource ID: {$resource->id}");

        // Get current prices
        $current_prices = null;
        try {
            $current_prices = $this->priceHistoryRepo->getCurrentPricesForResource($resource->id);
            Logger::info("GetResourceForSinglePageUseCase::execute - Current prices loaded");
        } catch (\Exception $e) {
            Logger::error("GetResourceForSinglePageUseCase::execute - Failed to load current prices: " . $e->getMessage());
        }

        // Build SinglePageResource model
        $result = new SinglePageResource(
            id: $resource->id,
            investment_id: $resource->investment_id,
            rodzaj_nieruchomosci: $resource->rodzaj_nieruchomosci->value,
            nr_lokalu: $resource->nr_lokalu,
            powierzchnia_uzytkowa: $resource->powierzchnia_uzytkowa,
            status: $resource->status->value,
            cena_m2: $current_prices ? $current_prices->cena_m2 : null,
            cena_calkowita: $current_prices ? $current_prices->cena_calkowita : null,
            cena_z_dodatkami: $current_prices ? $current_prices->cena_z_dodatkami : null,
            floor_number: $resource->floor_number,
            room_count: $resource->room_count,
            additional_description: $resource->additional_description,
            garden_area: $resource->garden_area,
            floor_plan_pdf: $resource->floor_plan_pdf,
            property_part_title: $resource->property_part_title,
            property_part_designation: $resource->property_part_designation,
            property_part_price: $resource->property_part_price,
            property_part_price_date: $resource->property_part_price_date,
            belonging_room_title: $resource->belonging_room_title,
            belonging_room_designation: $resource->belonging_room_designation,
            belonging_room_price: $resource->belonging_room_price,
            belonging_room_price_date: $resource->belonging_room_price_date,
            usage_right_title: $resource->usage_right_title,
            usage_right_price: $resource->usage_right_price,
            usage_right_price_date: $resource->usage_right_price_date,
            other_service_title: $resource->other_service_title,
            other_service_price: $resource->other_service_price,
            other_service_price_date: $resource->other_service_price_date
        );

        Logger::info("GetResourceForSinglePageUseCase::execute - Successfully completed for nr_lokalu: {$nr_lokalu}");
        return $result;
    }
}
