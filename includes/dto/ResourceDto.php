<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

class ResourceDto extends ModelDto {
    
    public const FIELD_ID = 'id';
    public const FIELD_INVESTMENT_ID = 'investment_id';
    public const FIELD_RODZAJ_NIERUCHOMOSCI = 'property_type';
    public const FIELD_NR_LOKALU = 'unit_number';
    public const FIELD_POWIERZCHNIA_UZYTKOWA = 'usable_area';
    public const FIELD_STATUS = 'status';
    
    public const FIELD_PROPERTY_PART_TITLE = 'property_part_title';
    public const FIELD_PROPERTY_PART_DESIGNATION = 'property_part_designation';
    public const FIELD_PROPERTY_PART_PRICE = 'property_part_price';
    
    public const FIELD_BELONGING_ROOM_TITLE = 'belonging_room_title';
    public const FIELD_BELONGING_ROOM_DESIGNATION = 'belonging_room_designation';
    public const FIELD_BELONGING_ROOM_PRICE = 'belonging_room_price';
    
    public const FIELD_USAGE_RIGHT_TITLE = 'usage_right_title';
    public const FIELD_USAGE_RIGHT_PRICE = 'usage_right_price';
    
    public const FIELD_OTHER_SERVICE_TITLE = 'other_service_title';
    public const FIELD_OTHER_SERVICE_PRICE = 'other_service_price';
    
    public const FIELD_PROPERTY_PART_PRICE_DATE = 'property_part_price_date';
    public const FIELD_BELONGING_ROOM_PRICE_DATE = 'belonging_room_price_date';
    public const FIELD_USAGE_RIGHT_PRICE_DATE = 'usage_right_price_date';
    public const FIELD_OTHER_SERVICE_PRICE_DATE = 'other_service_price_date';
    
    public const FIELD_FLOOR_NUMBER = 'floor_number';
    public const FIELD_ROOM_COUNT = 'room_count';
    public const FIELD_ADDITIONAL_DESCRIPTION = 'additional_description';
    public const FIELD_GARDEN_AREA = 'garden_area';
    public const FIELD_FLOOR_PLAN_PDF = 'floor_plan_pdf';
    
    public int $id;
    public int $investment_id;
    public PropertyType $rodzaj_nieruchomosci;
    public string $nr_lokalu;
    public ?float $powierzchnia_uzytkowa;
    public ResourceStatus $status;
    
    public ?string $property_part_title;
    public ?string $property_part_designation;
    public ?float $property_part_price;
    
    public ?string $belonging_room_title;
    public ?string $belonging_room_designation;
    public ?float $belonging_room_price;
    
    public ?string $usage_right_title;
    public ?float $usage_right_price;
    
    public ?string $other_service_title;
    public ?float $other_service_price;
    
    public ?string $property_part_price_date;
    public ?string $belonging_room_price_date;
    public ?string $usage_right_price_date;
    public ?string $other_service_price_date;
    
    public ?int $floor_number;
    public ?int $room_count;
    public ?string $additional_description;
    public ?float $garden_area;
    public ?string $floor_plan_pdf;
    
    public function __construct(
        int $id,
        int $investment_id,
        PropertyType $rodzaj_nieruchomosci,
        string $nr_lokalu,
        ?float $powierzchnia_uzytkowa,
        ResourceStatus $status,
        ?string $property_part_title = null,
        ?string $property_part_designation = null,
        ?float $property_part_price = null,
        ?string $belonging_room_title = null,
        ?string $belonging_room_designation = null,
        ?float $belonging_room_price = null,
        ?string $usage_right_title = null,
        ?float $usage_right_price = null,
        ?string $other_service_title = null,
        ?float $other_service_price = null,
        ?string $property_part_price_date = null,
        ?string $belonging_room_price_date = null,
        ?string $usage_right_price_date = null,
        ?string $other_service_price_date = null,
        ?int $floor_number = null,
        ?int $room_count = null,
        ?string $additional_description = null,
        ?float $garden_area = null,
        ?string $floor_plan_pdf = null
    ) {
        $this->id = $id;
        $this->investment_id = $investment_id;
        $this->rodzaj_nieruchomosci = $rodzaj_nieruchomosci;
        $this->nr_lokalu = $nr_lokalu;
        $this->powierzchnia_uzytkowa = $powierzchnia_uzytkowa;
        $this->status = $status;
        $this->property_part_title = $property_part_title;
        $this->property_part_designation = $property_part_designation;
        $this->property_part_price = $property_part_price;
        $this->belonging_room_title = $belonging_room_title;
        $this->belonging_room_designation = $belonging_room_designation;
        $this->belonging_room_price = $belonging_room_price;
        $this->usage_right_title = $usage_right_title;
        $this->usage_right_price = $usage_right_price;
        $this->other_service_title = $other_service_title;
        $this->other_service_price = $other_service_price;
        $this->property_part_price_date = $property_part_price_date;
        $this->belonging_room_price_date = $belonging_room_price_date;
        $this->usage_right_price_date = $usage_right_price_date;
        $this->other_service_price_date = $other_service_price_date;
        $this->floor_number = $floor_number;
        $this->room_count = $room_count;
        $this->additional_description = $additional_description;
        $this->garden_area = $garden_area;
        $this->floor_plan_pdf = $floor_plan_pdf;
    }
    
    public static function databaseToModel(array $data): static {
        return new static(
            (int)$data[self::FIELD_ID],
            (int)($data[self::FIELD_INVESTMENT_ID] ?? 1),
            PropertyType::from($data[self::FIELD_RODZAJ_NIERUCHOMOSCI] ?? ''),
            $data[self::FIELD_NR_LOKALU] ?? '',
            isset($data[self::FIELD_POWIERZCHNIA_UZYTKOWA]) ? (float)$data[self::FIELD_POWIERZCHNIA_UZYTKOWA] : null,
            ResourceStatus::from($data[self::FIELD_STATUS] ?? ''),
            $data[self::FIELD_PROPERTY_PART_TITLE] ?? null,
            $data[self::FIELD_PROPERTY_PART_DESIGNATION] ?? null,
            isset($data[self::FIELD_PROPERTY_PART_PRICE]) ? (float)$data[self::FIELD_PROPERTY_PART_PRICE] : null,
            $data[self::FIELD_BELONGING_ROOM_TITLE] ?? null,
            $data[self::FIELD_BELONGING_ROOM_DESIGNATION] ?? null,
            isset($data[self::FIELD_BELONGING_ROOM_PRICE]) ? (float)$data[self::FIELD_BELONGING_ROOM_PRICE] : null,
            $data[self::FIELD_USAGE_RIGHT_TITLE] ?? null,
            isset($data[self::FIELD_USAGE_RIGHT_PRICE]) ? (float)$data[self::FIELD_USAGE_RIGHT_PRICE] : null,
            $data[self::FIELD_OTHER_SERVICE_TITLE] ?? null,
            isset($data[self::FIELD_OTHER_SERVICE_PRICE]) ? (float)$data[self::FIELD_OTHER_SERVICE_PRICE] : null,
            $data[self::FIELD_PROPERTY_PART_PRICE_DATE] ?? null,
            $data[self::FIELD_BELONGING_ROOM_PRICE_DATE] ?? null,
            $data[self::FIELD_USAGE_RIGHT_PRICE_DATE] ?? null,
            $data[self::FIELD_OTHER_SERVICE_PRICE_DATE] ?? null,
            isset($data[self::FIELD_FLOOR_NUMBER]) ? (int)$data[self::FIELD_FLOOR_NUMBER] : null,
            isset($data[self::FIELD_ROOM_COUNT]) ? (int)$data[self::FIELD_ROOM_COUNT] : null,
            $data[self::FIELD_ADDITIONAL_DESCRIPTION] ?? null,
            isset($data[self::FIELD_GARDEN_AREA]) ? (float)$data[self::FIELD_GARDEN_AREA] : null,
            $data[self::FIELD_FLOOR_PLAN_PDF] ?? null
        );
    }
    
    public function modelToDatabase(): array {
        return [
            self::FIELD_INVESTMENT_ID => $this->investment_id,
            self::FIELD_RODZAJ_NIERUCHOMOSCI => $this->rodzaj_nieruchomosci->value,
            self::FIELD_NR_LOKALU => $this->nr_lokalu,
            self::FIELD_POWIERZCHNIA_UZYTKOWA => $this->powierzchnia_uzytkowa,
            self::FIELD_STATUS => $this->status->value,
            self::FIELD_PROPERTY_PART_TITLE => $this->property_part_title,
            self::FIELD_PROPERTY_PART_DESIGNATION => $this->property_part_designation,
            self::FIELD_PROPERTY_PART_PRICE => $this->property_part_price,
            self::FIELD_BELONGING_ROOM_TITLE => $this->belonging_room_title,
            self::FIELD_BELONGING_ROOM_DESIGNATION => $this->belonging_room_designation,
            self::FIELD_BELONGING_ROOM_PRICE => $this->belonging_room_price,
            self::FIELD_USAGE_RIGHT_TITLE => $this->usage_right_title,
            self::FIELD_USAGE_RIGHT_PRICE => $this->usage_right_price,
            self::FIELD_OTHER_SERVICE_TITLE => $this->other_service_title,
            self::FIELD_OTHER_SERVICE_PRICE => $this->other_service_price,
            self::FIELD_PROPERTY_PART_PRICE_DATE => $this->property_part_price_date,
            self::FIELD_BELONGING_ROOM_PRICE_DATE => $this->belonging_room_price_date,
            self::FIELD_USAGE_RIGHT_PRICE_DATE => $this->usage_right_price_date,
            self::FIELD_OTHER_SERVICE_PRICE_DATE => $this->other_service_price_date,
            self::FIELD_FLOOR_NUMBER => $this->floor_number,
            self::FIELD_ROOM_COUNT => $this->room_count,
            self::FIELD_ADDITIONAL_DESCRIPTION => $this->additional_description,
            self::FIELD_GARDEN_AREA => $this->garden_area,
            self::FIELD_FLOOR_PLAN_PDF => $this->floor_plan_pdf
        ];
    }
    
    
}