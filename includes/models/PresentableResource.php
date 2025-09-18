<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Model representing resource data for presentation layer (lists, views)
 * Contains all fields needed for displaying resources with pricing information
 */
class PresentableResource {
    
    public readonly int $id;
    public readonly string $rodzaj_nieruchomosci;
    public readonly string $nr_lokalu;
    public readonly ?float $powierzchnia_uzytkowa;
    public readonly string $status;
    public readonly ?float $cena_m2;
    public readonly float $cena_calkowita;
    public readonly float $cena_z_dodatkami;
    public readonly string $data_zmiany;
    public readonly string $data_cena_z_dodatkami;
    
    public function __construct(
        int $id,
        string $rodzaj_nieruchomosci,
        string $nr_lokalu,
        ?float $powierzchnia_uzytkowa,
        string $status,
        ?float $cena_m2,
        float $cena_calkowita,
        float $cena_z_dodatkami,
        string $data_zmiany,
        string $data_cena_z_dodatkami
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
    }
}