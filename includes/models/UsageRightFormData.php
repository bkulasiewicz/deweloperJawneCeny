<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Model representing usage rights data as entered by user in presentation layer
 * Used for "Prawa niezbędne do korzystania z lokalu lub domu"
 */
class UsageRightFormData {
    
    public readonly string $title;
    public readonly float $price;
    
    public function __construct(
        string $title,
        float $price
    ) {
        $this->title = $title;
        $this->price = $price;
    }
}