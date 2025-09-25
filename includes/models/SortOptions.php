<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Model for storing sorting options for resources
 */
class SortOptions {

    public readonly ?string $sortBy;
    public readonly string $sortOrder;

    // Available sort fields
    public const SORT_BY_ID = 'id';
    public const SORT_BY_UNIT_NUMBER = 'unit_number';
    public const SORT_BY_PROPERTY_TYPE = 'property_type';
    public const SORT_BY_AREA = 'usable_area';
    public const SORT_BY_PRICE = 'total_price';
    public const SORT_BY_STATUS = 'status';
    public const SORT_BY_FLOOR = 'floor_number';
    public const SORT_BY_ROOMS = 'room_count';

    // Sort directions
    public const ORDER_ASC = 'ASC';
    public const ORDER_DESC = 'DESC';

    public function __construct(
        ?string $sortBy = null,
        string $sortOrder = self::ORDER_ASC
    ) {
        $this->sortBy = $sortBy;
        $this->sortOrder = in_array(strtoupper($sortOrder), [self::ORDER_ASC, self::ORDER_DESC])
            ? strtoupper($sortOrder)
            : self::ORDER_ASC;
    }

    /**
     * Get available sort fields with their display names
     */
    public static function getAvailableSortFields(): array {
        return [
            self::SORT_BY_ID => 'ID',
            self::SORT_BY_UNIT_NUMBER => 'Numer lokalu',
            self::SORT_BY_PROPERTY_TYPE => 'Typ nieruchomości',
            self::SORT_BY_AREA => 'Powierzchnia',
            self::SORT_BY_PRICE => 'Cena',
            self::SORT_BY_STATUS => 'Status',
            self::SORT_BY_FLOOR => 'Piętro',
            self::SORT_BY_ROOMS => 'Pokoje'
        ];
    }

    /**
     * Get SQL field name for sorting
     */
    public function getSqlFieldName(): ?string {
        if (!$this->sortBy) {
            return null;
        }

        $fieldMapping = [
            self::SORT_BY_ID => ResourceDto::FIELD_ID,
            self::SORT_BY_UNIT_NUMBER => ResourceDto::FIELD_NR_LOKALU,
            self::SORT_BY_PROPERTY_TYPE => ResourceDto::FIELD_RODZAJ_NIERUCHOMOSCI,
            self::SORT_BY_AREA => ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA,
            self::SORT_BY_STATUS => ResourceDto::FIELD_STATUS,
            self::SORT_BY_FLOOR => ResourceDto::FIELD_FLOOR_NUMBER,
            self::SORT_BY_ROOMS => ResourceDto::FIELD_ROOM_COUNT,
            // Price sorting will need special handling as it comes from price_history table
            self::SORT_BY_PRICE => 'total_price'
        ];

        return $fieldMapping[$this->sortBy] ?? null;
    }

    /**
     * Check if sorting is needed for price (requires JOIN)
     */
    public function requiresPriceJoin(): bool {
        return $this->sortBy === self::SORT_BY_PRICE;
    }

    /**
     * Create SortOptions from array
     */
    public static function fromArray(array $data): self {
        return new self(
            $data['sortBy'] ?? null,
            $data['sortOrder'] ?? self::ORDER_ASC
        );
    }
}