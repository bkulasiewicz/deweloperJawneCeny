<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Model representing property part data as entered by user in presentation layer
 * Used for "Część nieruchomości będąca przedmiotem umowy"
 */
class PropertyPartFormData {
    
    public readonly string $title;
    public readonly string $designation;
    public readonly float $price;
    
    public function __construct(
        string $title,
        string $designation,
        float $price
    ) {
        $this->title = $title;
        $this->designation = $designation;
        $this->price = $price;
    }
}