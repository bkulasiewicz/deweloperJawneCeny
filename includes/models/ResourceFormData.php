<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Model representing resource data as entered by user in presentation layer
 * Contains only fields that user directly inputs, without business logic defaults
 */
class ResourceFormData {
    
    public readonly string $rodzaj_nieruchomosci;
    public readonly string $nr_lokalu;
    public readonly float $powierzchnia_uzytkowa;
    public readonly ?float $cena_m2;
    public readonly float $cena_calkowita;
    public readonly float $cena_z_dodatkami;
    public readonly string $status;
    
    public function __construct(
        string $rodzaj_nieruchomosci,
        string $nr_lokalu,
        float $powierzchnia_uzytkowa,
        ?float $cena_m2,
        float $cena_calkowita,
        float $cena_z_dodatkami,
        string $status
    ) {
        $this->rodzaj_nieruchomosci = $rodzaj_nieruchomosci;
        $this->nr_lokalu = $nr_lokalu;
        $this->powierzchnia_uzytkowa = $powierzchnia_uzytkowa;
        $this->cena_m2 = $cena_m2;
        $this->cena_calkowita = $cena_calkowita;
        $this->cena_z_dodatkami = $cena_z_dodatkami;
        $this->status = $status;
    }
}