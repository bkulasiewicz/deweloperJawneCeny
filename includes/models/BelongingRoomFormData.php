<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Model representing belonging room data as entered by user in presentation layer
 * Used for "Pomieszczenie przynależne"
 */
class BelongingRoomFormData {
    
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