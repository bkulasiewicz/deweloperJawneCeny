<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Resource Single Gutenberg Block
 * Renders single resource detail page using ResourceSingleRenderer
 * Identical functionality to ResourceSingleShortcode but for Gutenberg
 */
class ResourceSingleBlock {

    /**
     * Render block callback
     *
     * @param array $attributes Block attributes from Gutenberg
     * @return string Rendered HTML
     */
    public static function render($attributes) {
        Logger::info("ResourceSingleBlock: render started with attributes: " . esc_html(print_r($attributes, true)));

        // Parse and validate attributes with defaults
        $parsed = self::parseAttributes($attributes);

        // Auto-detect nr_lokalu from URL (REQUIRED - same logic as shortcode)
        $nr_lokalu = self::detectNrLokaluFromUrl();

        if (empty($nr_lokalu)) {
            return '<div class="ujc-error">Nie wykryto numeru lokalu w URL. Upewnij się że ten blok znajduje się na stronie z numerem lokalu w adresie URL (np. /mieszkania/A1).</div>';
        }

        Logger::info("ResourceSingleBlock: Detected nr_lokalu from URL: {$nr_lokalu}");

        // Get visible fields and labels from parsed attributes
        // Use fieldOrder to maintain user-defined order (drag & drop)
        $field_order = $parsed['fieldOrder'];
        $visible_fields = $parsed['visibleFields'];

        // Filter fieldOrder to only include visible fields (respects user's drag & drop order)
        $ordered_visible_fields = array_values(array_filter($field_order, function($field) use ($visible_fields) {
            return in_array($field, $visible_fields);
        }));

        $field_labels = $parsed['fieldLabels'];

        Logger::info("ResourceSingleBlock: Visible fields count: " . count($ordered_visible_fields));

        // Get resource from UseCase
        try {
            $use_case = DIContainer::get(GetResourceForSinglePageUseCase::class);
            $resource = $use_case->execute($nr_lokalu);

            if (!$resource) {
                return '<div class="ujc-error">Nie znaleziono zasobu: ' . esc_html($nr_lokalu) . '</div>';
            }

            Logger::info("ResourceSingleBlock: Resource loaded successfully, ID: {$resource->id}");
        } catch (\Exception $e) {
            Logger::error("ResourceSingleBlock: Failed to get resource: " . $e->getMessage());
            return '<div class="ujc-error">Wystąpił błąd podczas ładowania danych zasobu.</div>';
        }

        // Build styling options from parsed attributes
        $styling_options = self::buildStylingOptions($parsed);

        // Render using shared renderer (identical to shortcode)
        return ResourceSingleRenderer::renderSingleCard(
            $resource,
            $ordered_visible_fields,
            $field_labels,
            $styling_options
        );
    }

    /**
     * Detect nr_lokalu from URL path (copied from ResourceSingleShortcode)
     * Pattern: /mieszkania/A1 -> "A1"
     *
     * @return string|null Last URL segment or null if not found
     */
    private static function detectNrLokaluFromUrl(): ?string {
        if (!isset($_SERVER['REQUEST_URI'])) {
            return null;
        }

        $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $segments = explode('/', $path);

        // Last segment is nr_lokalu
        $last_segment = end($segments);

        return !empty($last_segment) ? $last_segment : null;
    }

    /**
     * Parse and validate block attributes with sensible defaults
     *
     * @param array $attributes Raw attributes from Gutenberg
     * @return array Parsed attributes with defaults
     */
    private static function parseAttributes(array $attributes): array {
        // Default visible fields (same as shortcode)
        $default_visible_fields = [
            ResourceDto::FIELD_NR_LOKALU,
            ResourceDto::FIELD_RODZAJ_NIERUCHOMOSCI,
            ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA,
            ResourceDto::FIELD_ROOM_COUNT,
            ResourceDto::FIELD_FLOOR_NUMBER,
            PriceHistoryDto::FIELD_CENA_CALKOWITA,
            PriceHistoryDto::FIELD_CENA_M2,
            'historia_cen'
        ];

        $defaults = [
            // Visible fields configuration
            'visibleFields' => $default_visible_fields,
            'fieldOrder' => $default_visible_fields,
            'fieldLabels' => [],

            // Card styling
            'cardBgColor' => '#ffffff',
            'cardBorderColor' => '#e1e5e9',
            'cardBorderRadius' => '8px',
            'cardPadding' => '24px',
            'cardTextColor' => '#333333',
            'cardLabelColor' => '#666666',
            'labelFontWeight' => 'bold',
            'fieldSpacing' => '12px',

            // Status styling (same as resources-list)
            'statusDisplayStyle' => 'badge',
            'statusAvailableBgColor' => '#28a745',
            'statusAvailableTextColor' => '#ffffff',
            'statusSoldBgColor' => '#dc3545',
            'statusSoldTextColor' => '#ffffff',
            'statusReservedBgColor' => '#ffc107',
            'statusReservedTextColor' => '#000000',
            'statusFontSize' => '0.875em',
            'statusPadding' => '4px 8px',
            'statusBorderRadius' => '4px',
            'statusFontWeight' => 'normal',

            // Button styling
            'historiaBtnText' => 'Historia cen',
            'historiaBtnBgColor' => '#007cba',
            'historiaBtnTextColor' => '#ffffff',
            'kartaBtnText' => 'Karta lokalu',
            'kartaBtnBgColor' => '#007cba',
            'kartaBtnTextColor' => '#ffffff'
        ];

        // Merge with defaults
        $parsed = array_merge($defaults, $attributes);

        // Ensure arrays are arrays
        if (!is_array($parsed['visibleFields'])) {
            $parsed['visibleFields'] = $default_visible_fields;
        }
        if (!is_array($parsed['fieldOrder'])) {
            $parsed['fieldOrder'] = $default_visible_fields;
        }
        if (!is_array($parsed['fieldLabels'])) {
            $parsed['fieldLabels'] = [];
        }

        // Validate visible fields against available fields
        $available_fields = self::getAvailableFields();
        $parsed['visibleFields'] = array_intersect($parsed['visibleFields'], $available_fields);
        $parsed['fieldOrder'] = array_intersect($parsed['fieldOrder'], $available_fields);

        // Ensure at least one field is visible
        if (empty($parsed['visibleFields'])) {
            $parsed['visibleFields'] = [ResourceDto::FIELD_NR_LOKALU, PriceHistoryDto::FIELD_CENA_CALKOWITA];
        }
        if (empty($parsed['fieldOrder'])) {
            $parsed['fieldOrder'] = [ResourceDto::FIELD_NR_LOKALU, PriceHistoryDto::FIELD_CENA_CALKOWITA];
        }

        Logger::info("ResourceSingleBlock: Parsed attributes: visible fields count = " . count($parsed['visibleFields']));
        return $parsed;
    }

    /**
     * Get all available fields for single resource display
     * Same as ResourceSingleShortcode lines 83-112
     *
     * @return array List of available field names
     */
    private static function getAvailableFields(): array {
        return [
            // Basic information from ResourceDto
            ResourceDto::FIELD_NR_LOKALU,
            ResourceDto::FIELD_RODZAJ_NIERUCHOMOSCI,
            ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA,
            ResourceDto::FIELD_STATUS,
            ResourceDto::FIELD_FLOOR_NUMBER,
            ResourceDto::FIELD_ROOM_COUNT,
            ResourceDto::FIELD_ADDITIONAL_DESCRIPTION,
            ResourceDto::FIELD_GARDEN_AREA,
            ResourceDto::FIELD_FLOOR_PLAN_PDF,

            // Prices from PriceHistory
            PriceHistoryDto::FIELD_CENA_M2,
            PriceHistoryDto::FIELD_CENA_CALKOWITA,
            PriceHistoryDto::FIELD_CENA_Z_DODATKAMI,

            // Functional
            'historia_cen',

            // Component fields - Property part
            ResourceDto::FIELD_PROPERTY_PART_TITLE,
            ResourceDto::FIELD_PROPERTY_PART_DESIGNATION,
            ResourceDto::FIELD_PROPERTY_PART_PRICE,
            ResourceDto::FIELD_PROPERTY_PART_PRICE_DATE,

            // Component fields - Belonging room
            ResourceDto::FIELD_BELONGING_ROOM_TITLE,
            ResourceDto::FIELD_BELONGING_ROOM_DESIGNATION,
            ResourceDto::FIELD_BELONGING_ROOM_PRICE,
            ResourceDto::FIELD_BELONGING_ROOM_PRICE_DATE,

            // Component fields - Usage right
            ResourceDto::FIELD_USAGE_RIGHT_TITLE,
            ResourceDto::FIELD_USAGE_RIGHT_PRICE,
            ResourceDto::FIELD_USAGE_RIGHT_PRICE_DATE,

            // Component fields - Other service
            ResourceDto::FIELD_OTHER_SERVICE_TITLE,
            ResourceDto::FIELD_OTHER_SERVICE_PRICE,
            ResourceDto::FIELD_OTHER_SERVICE_PRICE_DATE
        ];
    }

    /**
     * Build styling options array for ResourceSingleRenderer
     * Maps camelCase block attributes to snake_case renderer options
     *
     * @param array $attributes Parsed attributes
     * @return array Styling options for renderer
     */
    private static function buildStylingOptions(array $attributes): array {
        return [
            // Card styling (camelCase -> snake_case)
            'card_bg_color' => $attributes['cardBgColor'],
            'card_border_color' => $attributes['cardBorderColor'],
            'card_border_radius' => $attributes['cardBorderRadius'],
            'card_padding' => $attributes['cardPadding'],
            'text_color' => $attributes['cardTextColor'],
            'label_color' => $attributes['cardLabelColor'],
            'label_font_weight' => $attributes['labelFontWeight'],
            'field_spacing' => $attributes['fieldSpacing'],

            // Status styling
            'status_display_style' => $attributes['statusDisplayStyle'],
            'status_available_bg_color' => $attributes['statusAvailableBgColor'],
            'status_available_text_color' => $attributes['statusAvailableTextColor'],
            'status_sold_bg_color' => $attributes['statusSoldBgColor'],
            'status_sold_text_color' => $attributes['statusSoldTextColor'],
            'status_reserved_bg_color' => $attributes['statusReservedBgColor'],
            'status_reserved_text_color' => $attributes['statusReservedTextColor'],
            'status_font_size' => $attributes['statusFontSize'],
            'status_padding' => $attributes['statusPadding'],
            'status_border_radius' => $attributes['statusBorderRadius'],
            'status_font_weight' => $attributes['statusFontWeight'],

            // Button styling
            'historia_btn_text' => $attributes['historiaBtnText'],
            'historia_btn_bg_color' => $attributes['historiaBtnBgColor'],
            'historia_btn_text_color' => $attributes['historiaBtnTextColor'],
            'karta_btn_text' => $attributes['kartaBtnText'],
            'karta_btn_bg_color' => $attributes['kartaBtnBgColor'],
            'karta_btn_text_color' => $attributes['kartaBtnTextColor']
        ];
    }
}
