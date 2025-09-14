<?php

if (!defined('ABSPATH')) {
    exit;
}

class InvestmentDto extends ModelDto {
    
    // Field names constants
    public const FIELD_ID = 'id';
    public const FIELD_DEVELOPER_ID = 'developer_id';
    public const FIELD_NAME = 'name';
    public const FIELD_PROJ_WOJEWODZTWO = 'project_province';
    public const FIELD_PROJ_POWIAT = 'project_county';
    public const FIELD_PROJ_GMINA = 'project_municipality';
    public const FIELD_PROJ_MIEJSCOWOSC = 'project_city';
    public const FIELD_PROJ_ULICA = 'project_street';
    public const FIELD_PROJ_NR = 'project_number';
    public const FIELD_PROJ_KOD = 'project_postal_code';
    public const FIELD_HAS_PROPERTY_PARTS = 'has_property_parts';
    public const FIELD_HAS_BELONGING_ROOMS = 'has_belonging_rooms';
    public const FIELD_HAS_USAGE_RIGHTS = 'has_usage_rights';
    public const FIELD_HAS_OTHER_SERVICES = 'has_other_services';
    public const FIELD_SHOW_FLOOR_FIELD = 'show_floor_field';
    public const FIELD_SHOW_ROOMS_FIELD = 'show_rooms_field';
    public const FIELD_SHOW_DESCRIPTION_FIELD = 'show_description_field';
    public const FIELD_SHOW_GARDEN_FIELD = 'show_garden_field';
    public const FIELD_SHOW_FLOOR_PLAN_FIELD = 'show_floor_plan_field';
    
    public int $id;
    public int $developer_id;
    public string $name;
    public string $proj_wojewodztwo;
    public string $proj_powiat;
    public string $proj_gmina;
    public string $proj_miejscowosc;
    public string $proj_ulica;
    public string $proj_nr;
    public string $proj_kod;
    public bool $has_property_parts;
    public bool $has_belonging_rooms;
    public bool $has_usage_rights;
    public bool $has_other_services;
    public bool $show_floor_field;
    public bool $show_rooms_field;
    public bool $show_description_field;
    public bool $show_garden_field;
    public bool $show_floor_plan_field;
    
    public function __construct(
        int $id, 
        int $developer_id, 
        string $name, 
        string $proj_wojewodztwo, 
        string $proj_powiat, 
        string $proj_gmina, 
        string $proj_miejscowosc, 
        string $proj_ulica, 
        string $proj_nr, 
        string $proj_kod, 
        bool $has_property_parts, 
        bool $has_belonging_rooms, 
        bool $has_usage_rights, 
        bool $has_other_services,
        bool $show_floor_field = false,
        bool $show_rooms_field = false,
        bool $show_description_field = false,
        bool $show_garden_field = false,
        bool $show_floor_plan_field = false
    ) {
        $this->id = $id;
        $this->developer_id = $developer_id;
        $this->name = $name;
        $this->proj_wojewodztwo = $proj_wojewodztwo;
        $this->proj_powiat = $proj_powiat;
        $this->proj_gmina = $proj_gmina;
        $this->proj_miejscowosc = $proj_miejscowosc;
        $this->proj_ulica = $proj_ulica;
        $this->proj_nr = $proj_nr;
        $this->proj_kod = $proj_kod;
        $this->has_property_parts = $has_property_parts;
        $this->has_belonging_rooms = $has_belonging_rooms;
        $this->has_usage_rights = $has_usage_rights;
        $this->has_other_services = $has_other_services;
        $this->show_floor_field = $show_floor_field;
        $this->show_rooms_field = $show_rooms_field;
        $this->show_description_field = $show_description_field;
        $this->show_garden_field = $show_garden_field;
        $this->show_floor_plan_field = $show_floor_plan_field;
    }
    
    public function hasAnyComponents(): bool {
        return $this->has_property_parts || 
               $this->has_belonging_rooms || 
               $this->has_usage_rights || 
               $this->has_other_services;
    }
    
    public static function databaseToModel(array $data): static {
        return new static(
            (int)$data[self::FIELD_ID],
            (int)($data[self::FIELD_DEVELOPER_ID] ?? 1),
            $data[self::FIELD_NAME],
            $data[self::FIELD_PROJ_WOJEWODZTWO],
            $data[self::FIELD_PROJ_POWIAT],
            $data[self::FIELD_PROJ_GMINA],
            $data[self::FIELD_PROJ_MIEJSCOWOSC],
            $data[self::FIELD_PROJ_ULICA],
            $data[self::FIELD_PROJ_NR],
            $data[self::FIELD_PROJ_KOD],
            (bool)$data[self::FIELD_HAS_PROPERTY_PARTS],
            (bool)$data[self::FIELD_HAS_BELONGING_ROOMS],
            (bool)$data[self::FIELD_HAS_USAGE_RIGHTS],
            (bool)$data[self::FIELD_HAS_OTHER_SERVICES],
            (bool)($data[self::FIELD_SHOW_FLOOR_FIELD] ?? false),
            (bool)($data[self::FIELD_SHOW_ROOMS_FIELD] ?? false),
            (bool)($data[self::FIELD_SHOW_DESCRIPTION_FIELD] ?? false),
            (bool)($data[self::FIELD_SHOW_GARDEN_FIELD] ?? false),
            (bool)($data[self::FIELD_SHOW_FLOOR_PLAN_FIELD] ?? false)
        );
    }
    
    public function modelToDatabase(): array {
        return [
            self::FIELD_DEVELOPER_ID => $this->developer_id,
            self::FIELD_NAME => $this->name,
            self::FIELD_PROJ_WOJEWODZTWO => $this->proj_wojewodztwo,
            self::FIELD_PROJ_POWIAT => $this->proj_powiat,
            self::FIELD_PROJ_GMINA => $this->proj_gmina,
            self::FIELD_PROJ_MIEJSCOWOSC => $this->proj_miejscowosc,
            self::FIELD_PROJ_ULICA => $this->proj_ulica,
            self::FIELD_PROJ_NR => $this->proj_nr,
            self::FIELD_PROJ_KOD => $this->proj_kod,
            self::FIELD_HAS_PROPERTY_PARTS => $this->has_property_parts,
            self::FIELD_HAS_BELONGING_ROOMS => $this->has_belonging_rooms,
            self::FIELD_HAS_USAGE_RIGHTS => $this->has_usage_rights,
            self::FIELD_HAS_OTHER_SERVICES => $this->has_other_services,
            self::FIELD_SHOW_FLOOR_FIELD => $this->show_floor_field,
            self::FIELD_SHOW_ROOMS_FIELD => $this->show_rooms_field,
            self::FIELD_SHOW_DESCRIPTION_FIELD => $this->show_description_field,
            self::FIELD_SHOW_GARDEN_FIELD => $this->show_garden_field,
            self::FIELD_SHOW_FLOOR_PLAN_FIELD => $this->show_floor_plan_field
        ];
    }
}