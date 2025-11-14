<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode to extract single field values from resources
 * Usage: [jawneceny_resource_field number="A1" field="usable_area"]
 * Returns raw value or "N/A" on error
 */
class ResourceFieldShortcode {

    /**
     * List of allowed field names (English names for client convenience)
     */
    private static $allowed_fields = [
        // Basic resource fields
        'unit_number',
        'property_type',
        'usable_area',
        'status',
        'floor_number',
        'room_count',
        'additional_description',
        'garden_area',
        'floor_plan_pdf',
        // Price fields (from price history)
        'total_price',
        'price_per_m2',
        'price_with_extras'
    ];

    /**
     * Mapping of English field names to Polish property names
     * (SinglePageResource uses Polish property names matching database columns)
     */
    private static $field_mapping = [
        'unit_number' => 'nr_lokalu',
        'property_type' => 'rodzaj_nieruchomosci',
        'usable_area' => 'powierzchnia_uzytkowa',
        'status' => 'status',
        'floor_number' => 'floor_number',
        'room_count' => 'room_count',
        'additional_description' => 'additional_description',
        'garden_area' => 'garden_area',
        'floor_plan_pdf' => 'floor_plan_pdf',
        'total_price' => 'cena_calkowita',
        'price_per_m2' => 'cena_m2',
        'price_with_extras' => 'cena_z_dodatkami'
    ];

    /**
     * Handle shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Field value or "N/A"
     */
    public static function handle($atts) {
        // Parse attributes
        $atts = shortcode_atts([
            'number' => '',  // unit number (nr_lokalu)
            'field' => '',   // field name (DTO property)
        ], $atts, 'jawneceny_resource_field');

        // Validate required parameters
        if (empty($atts['number']) || empty($atts['field'])) {
            return 'N/A';
        }

        // Validate field name
        if (!in_array($atts['field'], self::$allowed_fields, true)) {
            Logger::info('ResourceFieldShortcode: Invalid field name: ' . $atts['field']);
            return 'N/A';
        }

        try {
            // Get resource using Use Case
            $useCase = DIContainer::get(\JawneCeny\GetResourceForSinglePageUseCase::class);
            $resource = $useCase->execute($atts['number']);

            // Resource not found
            if (!$resource) {
                Logger::info('ResourceFieldShortcode: Resource not found: ' . $atts['number']);
                return 'N/A';
            }

            // Translate English field name to Polish property name
            $english_field = $atts['field'];
            $polish_property = self::$field_mapping[$english_field] ?? $english_field;

            // Get field value
            $value = isset($resource->{$polish_property}) ? $resource->{$polish_property} : null;

            // Return value or N/A
            if ($value === null || $value === '') {
                return 'N/A';
            }

            // Escape and return
            return esc_html($value);

        } catch (\Exception $e) {
            Logger::error('ResourceFieldShortcode error: ' . $e->getMessage());
            return 'N/A';
        }
    }
}
