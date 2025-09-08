<?php

if (!defined('ABSPATH')) {
    exit;
}

class PropertyPartDto extends ModelDto {
    
    public const FIELD_ID = 'id';
    public const FIELD_RESOURCE_ID = 'resource_id';
    public const FIELD_TYPE = 'type';
    public const FIELD_DESIGNATION = 'designation';
    public const FIELD_PRICE = 'price';
    public const FIELD_PRICE_DATE = 'price_date';
    
    public readonly int $id;
    public readonly int $resource_id;
    public readonly string $type;
    public readonly string $designation;
    public readonly float $price;
    public readonly DateTime $price_date;
    
    public function __construct(
        int $id,
        int $resource_id,
        string $type,
        string $designation,
        float $price,
        DateTime $price_date
    ) {
        $this->id = $id;
        $this->resource_id = $resource_id;
        $this->type = $type;
        $this->designation = $designation;
        $this->price = $price;
        $this->price_date = $price_date;
    }
    
    public static function databaseToModel(array $data): static {
        return new static(
            (int)$data[self::FIELD_ID],
            (int)$data[self::FIELD_RESOURCE_ID],
            $data[self::FIELD_TYPE],
            $data[self::FIELD_DESIGNATION],
            (float)$data[self::FIELD_PRICE],
            new DateTime($data[self::FIELD_PRICE_DATE])
        );
    }
    
    public function modelToDatabase(): array {
        return [
            self::FIELD_RESOURCE_ID => $this->resource_id,
            self::FIELD_TYPE => $this->type,
            self::FIELD_DESIGNATION => $this->designation,
            self::FIELD_PRICE => $this->price,
            self::FIELD_PRICE_DATE => $this->price_date
        ];
    }
}