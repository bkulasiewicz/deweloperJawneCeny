<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @template T
 */
abstract class ModelDto {
    
    /**
     * Convert database array to model instance
     * @param array $data
     * @return static<T>
     */
    abstract public static function databaseToModel(array $data): static;
    
    /**
     * Convert model instance to database array
     * @return array
     */
    abstract public function modelToDatabase(): array;
}