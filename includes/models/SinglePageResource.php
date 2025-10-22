<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dedicated model for single resource detail page
 * Contains all resource fields including prices, components, and investment info
 */
class SinglePageResource {

    // Basic properties
    public readonly int $id;
    public readonly int $investment_id;
    public readonly string $rodzaj_nieruchomosci;
    public readonly string $nr_lokalu;
    public readonly ?float $powierzchnia_uzytkowa;
    public readonly string $status;

    // Price properties
    public readonly ?float $cena_m2;
    public readonly ?float $cena_calkowita;
    public readonly ?float $cena_z_dodatkami;

    // Marketing properties
    public readonly ?int $floor_number;
    public readonly ?int $room_count;
    public readonly ?string $additional_description;
    public readonly ?float $garden_area;
    public readonly ?string $floor_plan_pdf;

    // Property part component
    public readonly ?string $property_part_title;
    public readonly ?string $property_part_designation;
    public readonly ?float $property_part_price;
    public readonly ?string $property_part_price_date;

    // Belonging room component
    public readonly ?string $belonging_room_title;
    public readonly ?string $belonging_room_designation;
    public readonly ?float $belonging_room_price;
    public readonly ?string $belonging_room_price_date;

    // Usage right component
    public readonly ?string $usage_right_title;
    public readonly ?float $usage_right_price;
    public readonly ?string $usage_right_price_date;

    // Other service component
    public readonly ?string $other_service_title;
    public readonly ?float $other_service_price;
    public readonly ?string $other_service_price_date;

    public function __construct(
        int $id,
        int $investment_id,
        string $rodzaj_nieruchomosci,
        string $nr_lokalu,
        ?float $powierzchnia_uzytkowa,
        string $status,
        ?float $cena_m2,
        ?float $cena_calkowita,
        ?float $cena_z_dodatkami,
        ?int $floor_number = null,
        ?int $room_count = null,
        ?string $additional_description = null,
        ?float $garden_area = null,
        ?string $floor_plan_pdf = null,
        ?string $property_part_title = null,
        ?string $property_part_designation = null,
        ?float $property_part_price = null,
        ?string $property_part_price_date = null,
        ?string $belonging_room_title = null,
        ?string $belonging_room_designation = null,
        ?float $belonging_room_price = null,
        ?string $belonging_room_price_date = null,
        ?string $usage_right_title = null,
        ?float $usage_right_price = null,
        ?string $usage_right_price_date = null,
        ?string $other_service_title = null,
        ?float $other_service_price = null,
        ?string $other_service_price_date = null
    ) {
        $this->id = $id;
        $this->investment_id = $investment_id;
        $this->rodzaj_nieruchomosci = $rodzaj_nieruchomosci;
        $this->nr_lokalu = $nr_lokalu;
        $this->powierzchnia_uzytkowa = $powierzchnia_uzytkowa;
        $this->status = $status;
        $this->cena_m2 = $cena_m2;
        $this->cena_calkowita = $cena_calkowita;
        $this->cena_z_dodatkami = $cena_z_dodatkami;
        $this->floor_number = $floor_number;
        $this->room_count = $room_count;
        $this->additional_description = $additional_description;
        $this->garden_area = $garden_area;
        $this->floor_plan_pdf = $floor_plan_pdf;
        $this->property_part_title = $property_part_title;
        $this->property_part_designation = $property_part_designation;
        $this->property_part_price = $property_part_price;
        $this->property_part_price_date = $property_part_price_date;
        $this->belonging_room_title = $belonging_room_title;
        $this->belonging_room_designation = $belonging_room_designation;
        $this->belonging_room_price = $belonging_room_price;
        $this->belonging_room_price_date = $belonging_room_price_date;
        $this->usage_right_title = $usage_right_title;
        $this->usage_right_price = $usage_right_price;
        $this->usage_right_price_date = $usage_right_price_date;
        $this->other_service_title = $other_service_title;
        $this->other_service_price = $other_service_price;
        $this->other_service_price_date = $other_service_price_date;
    }
}
