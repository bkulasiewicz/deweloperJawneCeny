<?php

if (!defined('ABSPATH')) {
    exit;
}

class ResourceDto extends ModelDto {
    
    public const FIELD_ID = 'id';
    public const FIELD_RODZAJ_NIERUCHOMOSCI = 'rodzaj_nieruchomosci';
    public const FIELD_NR_LOKALU = 'nr_lokalu';
    public const FIELD_POWIERZCHNIA_UZYTKOWA = 'powierzchnia_uzytkowa';
    public const FIELD_STATUS = 'status';
    
    public int $id;
    public string $rodzaj_nieruchomosci;
    public string $nr_lokalu;
    public float $powierzchnia_uzytkowa;
    public string $status;
    
    public function __construct(
        int $id,
        string $rodzaj_nieruchomosci,
        string $nr_lokalu,
        float $powierzchnia_uzytkowa,
        string $status
    ) {
        $this->id = $id;
        $this->rodzaj_nieruchomosci = $rodzaj_nieruchomosci;
        $this->nr_lokalu = $nr_lokalu;
        $this->powierzchnia_uzytkowa = $powierzchnia_uzytkowa;
        $this->status = $status;
    }
    
    public static function databaseToModel(array $data): static {
        return new static(
            (int)$data[self::FIELD_ID],
            $data[self::FIELD_RODZAJ_NIERUCHOMOSCI] ?? '',
            $data[self::FIELD_NR_LOKALU] ?? '',
            (float)($data[self::FIELD_POWIERZCHNIA_UZYTKOWA] ?? 0),
            $data[self::FIELD_STATUS]
        );
    }
    
    public function modelToDatabase(): array {
        return [
            self::FIELD_RODZAJ_NIERUCHOMOSCI => $this->rodzaj_nieruchomosci,
            self::FIELD_NR_LOKALU => $this->nr_lokalu,
            self::FIELD_POWIERZCHNIA_UZYTKOWA => $this->powierzchnia_uzytkowa,
            self::FIELD_STATUS => $this->status
        ];
    }
}