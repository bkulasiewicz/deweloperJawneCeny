<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Model for storing filtering criteria for resources
 */
class FilterCriteria {

    public readonly ?float $priceMin;
    public readonly ?float $priceMax;
    public readonly ?float $areaMin;
    public readonly ?float $areaMax;
    public readonly ?array $statusFilter;
    public readonly ?array $floorFilter;
    public readonly ?array $roomsFilter;
    public readonly ?array $propertyTypes;
    public readonly ?array $unitNumberFilter;

    public function __construct(
        ?float $priceMin = null,
        ?float $priceMax = null,
        ?float $areaMin = null,
        ?float $areaMax = null,
        ?array $statusFilter = null,
        ?array $floorFilter = null,
        ?array $roomsFilter = null,
        ?array $propertyTypes = null,
        ?array $unitNumberFilter = null
    ) {
        $this->priceMin = $priceMin;
        $this->priceMax = $priceMax;
        $this->areaMin = $areaMin;
        $this->areaMax = $areaMax;
        $this->statusFilter = $statusFilter;
        $this->floorFilter = $floorFilter;
        $this->roomsFilter = $roomsFilter;
        $this->propertyTypes = $propertyTypes;
        $this->unitNumberFilter = $unitNumberFilter;
    }

    /**
     * Check if any filters are active
     */
    public function hasFilters(): bool {
        return $this->priceMin !== null ||
               $this->priceMax !== null ||
               $this->areaMin !== null ||
               $this->areaMax !== null ||
               !empty($this->statusFilter) ||
               !empty($this->floorFilter) ||
               !empty($this->roomsFilter) ||
               !empty($this->propertyTypes) ||
               !empty($this->unitNumberFilter);
    }

    /**
     * Create FilterCriteria from array
     */
    public static function fromArray(array $data): self {
        return new self(
            $data['priceMin'] ?? null,
            $data['priceMax'] ?? null,
            $data['areaMin'] ?? null,
            $data['areaMax'] ?? null,
            $data['statusFilter'] ?? null,
            $data['floorFilter'] ?? null,
            $data['roomsFilter'] ?? null,
            $data['propertyTypes'] ?? null,
            $data['unitNumberFilter'] ?? null
        );
    }
}