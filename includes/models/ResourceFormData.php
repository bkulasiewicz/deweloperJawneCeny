<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Model representing resource data as entered by user in presentation layer
 * Contains only fields that user directly inputs, without business logic defaults
 */
class ResourceFormData {
    
    public readonly PropertyType $rodzaj_nieruchomosci;
    public readonly string $nr_lokalu;
    public readonly float $powierzchnia_uzytkowa;
    public readonly ?float $cena_m2;
    public readonly float $cena_calkowita;
    public readonly float $cena_z_dodatkami;
    public readonly ResourceStatus $status;
    
    public readonly ?PropertyPartFormData $property_part;
    public readonly ?BelongingRoomFormData $belonging_room;
    public readonly ?UsageRightFormData $usage_right;
    public readonly ?OtherServiceFormData $other_service;
    
    public function __construct(
        PropertyType $rodzaj_nieruchomosci,
        string $nr_lokalu,
        float $powierzchnia_uzytkowa,
        ?float $cena_m2,
        float $cena_calkowita,
        float $cena_z_dodatkami,
        ResourceStatus $status,
        ?PropertyPartFormData $property_part = null,
        ?BelongingRoomFormData $belonging_room = null,
        ?UsageRightFormData $usage_right = null,
        ?OtherServiceFormData $other_service = null
    ) {
        $this->rodzaj_nieruchomosci = $rodzaj_nieruchomosci;
        $this->nr_lokalu = $nr_lokalu;
        $this->powierzchnia_uzytkowa = $powierzchnia_uzytkowa;
        $this->cena_m2 = $cena_m2;
        $this->cena_calkowita = $cena_calkowita;
        $this->cena_z_dodatkami = $cena_z_dodatkami;
        $this->status = $status;
        $this->property_part = $property_part;
        $this->belonging_room = $belonging_room;
        $this->usage_right = $usage_right;
        $this->other_service = $other_service;
    }
}