<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Property type enum with database keys
 * Values are used as database storage keys
 */
enum PropertyType: string {
    case RESIDENTIAL_UNIT = 'residential_unit';
    case SINGLE_FAMILY_HOUSE = 'single_family_house';  
    case PARKING_SPACE = 'parking_space';
    case STORAGE_ROOM = 'storage_room';
    case GARAGE = 'garage';
    
    /**
     * Get display text for presentation
     */
    public function getDisplayText(): string {
        return match($this) {
            self::RESIDENTIAL_UNIT => 'Lokal mieszkalny',
            self::SINGLE_FAMILY_HOUSE => 'Dom jednorodzinny',
            self::PARKING_SPACE => 'Miejsce postojowe',
            self::STORAGE_ROOM => 'Komórka lokatorska',
            self::GARAGE => 'Garaż',
        };
    }
    
}