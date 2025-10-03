<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Model representing price history data for presentation layer
 * Contains formatted dates and all fields needed for displaying price history
 */
class PresentablePriceHistory {
    
    public readonly int $id;
    public readonly int $resource_id;
    public readonly ?float $cena_m2;
    public readonly float $cena_calkowita;
    public readonly float $cena_z_dodatkami;
    public readonly string $data_zmiany;
    public readonly string $data_cena_z_dodatkami;
    
    public function __construct(
        int $id,
        int $resource_id,
        ?float $cena_m2,
        float $cena_calkowita,
        float $cena_z_dodatkami,
        string $data_zmiany,
        string $data_cena_z_dodatkami
    ) {
        $this->id = $id;
        $this->resource_id = $resource_id;
        $this->cena_m2 = $cena_m2;
        $this->cena_calkowita = $cena_calkowita;
        $this->cena_z_dodatkami = $cena_z_dodatkami;
        $this->data_zmiany = $data_zmiany;
        $this->data_cena_z_dodatkami = $data_cena_z_dodatkami;
    }
    
    /**
     * Create PresentablePriceHistory from PriceHistoryDto with formatted dates
     */
    public static function fromDto(PriceHistoryDto $dto): self {
        return new self(
            $dto->id,
            $dto->resource_id,
            $dto->cena_m2,
            $dto->cena_calkowita,
            $dto->cena_z_dodatkami,
            DateHelper::formatForUser($dto->data_zmiany),
            DateHelper::formatForUser($dto->data_cena_z_dodatkami)
        );
    }
}