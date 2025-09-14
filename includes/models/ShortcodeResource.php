<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dedicated model for shortcode resource display
 * Contains all fields needed for shortcode functionality including marketing fields
 */
class ShortcodeResource {
    
    // Basic properties
    public readonly int $id;
    public readonly string $rodzaj_nieruchomosci;
    public readonly string $nr_lokalu;
    public readonly float $powierzchnia_uzytkowa;
    public readonly string $status;
    
    // Price properties
    public readonly ?float $cena_m2;
    public readonly float $cena_calkowita;
    public readonly float $cena_z_dodatkami;
    
    // Date properties
    public readonly string $data_zmiany;
    public readonly string $data_cena_z_dodatkami;
    
    // Marketing properties
    public readonly ?int $floor_number;
    public readonly ?int $room_count;
    public readonly ?string $additional_description;
    public readonly ?float $garden_area;
    public readonly ?string $floor_plan_pdf;
    
    public function __construct(
        int $id,
        string $rodzaj_nieruchomosci,
        string $nr_lokalu,
        float $powierzchnia_uzytkowa,
        string $status,
        ?float $cena_m2,
        float $cena_calkowita,
        float $cena_z_dodatkami,
        string $data_zmiany,
        string $data_cena_z_dodatkami,
        ?int $floor_number = null,
        ?int $room_count = null,
        ?string $additional_description = null,
        ?float $garden_area = null,
        ?string $floor_plan_pdf = null
    ) {
        $this->id = $id;
        $this->rodzaj_nieruchomosci = $rodzaj_nieruchomosci;
        $this->nr_lokalu = $nr_lokalu;
        $this->powierzchnia_uzytkowa = $powierzchnia_uzytkowa;
        $this->status = $status;
        $this->cena_m2 = $cena_m2;
        $this->cena_calkowita = $cena_calkowita;
        $this->cena_z_dodatkami = $cena_z_dodatkami;
        $this->data_zmiany = $data_zmiany;
        $this->data_cena_z_dodatkami = $data_cena_z_dodatkami;
        $this->floor_number = $floor_number;
        $this->room_count = $room_count;
        $this->additional_description = $additional_description;
        $this->garden_area = $garden_area;
        $this->floor_plan_pdf = $floor_plan_pdf;
    }
}