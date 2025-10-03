<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Resource status enum with database keys
 * Values are used as database storage keys
 */
enum ResourceStatus: string {
    case AVAILABLE = 'available';
    case RESERVED = 'reserved';
    case SOLD = 'sold';
    
    /**
     * Get display text for presentation
     */
    public function getDisplayText(): string {
        return match($this) {
            self::AVAILABLE => 'Dostępny',
            self::RESERVED => 'Zarezerwowany',
            self::SOLD => 'Sprzedany',
        };
    }
    
}