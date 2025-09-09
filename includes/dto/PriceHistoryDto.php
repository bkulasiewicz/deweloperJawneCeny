<?php

if (!defined('ABSPATH')) {
    exit;
}

class PriceHistoryDto extends ModelDto {
    
    public const FIELD_ID = 'id';
    public const FIELD_RESOURCE_ID = 'resource_id';
    public const FIELD_CENA_M2 = 'cena_m2';
    public const FIELD_CENA_CALKOWITA = 'cena_calkowita';
    public const FIELD_CENA_Z_DODATKAMI = 'cena_z_dodatkami';
    public const FIELD_DATA_ZMIANY = 'data_zmiany';
    public const FIELD_DATA_CENA_Z_DODATKAMI = 'data_cena_z_dodatkami';
    
    public readonly int $id;
    public readonly int $resource_id;
    public readonly ?float $cena_m2;
    public readonly float $cena_calkowita;
    public readonly string $data_zmiany;
    public readonly float $cena_z_dodatkami;
    public readonly string $data_cena_z_dodatkami;
    
    public function __construct(
        int $id,
        int $resource_id,
        ?float $cena_m2,
        float $cena_calkowita,
        string $data_zmiany,
        float $cena_z_dodatkami,
        string $data_cena_z_dodatkami
    ) {
        $this->id = $id;
        $this->resource_id = $resource_id;
        $this->cena_m2 = $cena_m2;
        $this->cena_calkowita = $cena_calkowita;
        $this->data_zmiany = $data_zmiany;
        $this->cena_z_dodatkami = $cena_z_dodatkami;
        $this->data_cena_z_dodatkami = $data_cena_z_dodatkami;
    }
    
    public static function databaseToModel(array $data): static {
        return new static(
            (int)$data[self::FIELD_ID],
            (int)$data[self::FIELD_RESOURCE_ID],
            $data[self::FIELD_CENA_M2] !== null ? (float)$data[self::FIELD_CENA_M2] : null,
            (float)$data[self::FIELD_CENA_CALKOWITA],
            $data[self::FIELD_DATA_ZMIANY],
            (float)$data[self::FIELD_CENA_Z_DODATKAMI],
            $data[self::FIELD_DATA_CENA_Z_DODATKAMI]
        );
    }
    
    public function modelToDatabase(): array {
        return [
            self::FIELD_RESOURCE_ID => $this->resource_id,
            self::FIELD_CENA_M2 => $this->cena_m2,
            self::FIELD_CENA_CALKOWITA => $this->cena_calkowita,
            self::FIELD_DATA_ZMIANY => $this->data_zmiany,
            self::FIELD_CENA_Z_DODATKAMI => $this->cena_z_dodatkami,
            self::FIELD_DATA_CENA_Z_DODATKAMI => $this->data_cena_z_dodatkami
        ];
    }
}