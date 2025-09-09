<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Model representing other services data as entered by user in presentation layer
 * Used for "Inne świadczenia pieniężne na rzecz dewelopera"
 */
class OtherServiceFormData {
    
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