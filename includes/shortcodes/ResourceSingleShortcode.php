<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Resource Single Shortcode Handler
 * Handles the [resource_single] shortcode for displaying single resource detail page
 */
class ResourceSingleShortcode {

    /**
     * Handle resource_single shortcode
     * Format: [resource_single columns="field:Label,field2:Label2" ...]
     */
    public static function handle($atts) {

        // Default columns (basic info + prices)
        $default_columns = implode(',', [
            ResourceDto::FIELD_NR_LOKALU . ':Numer',
            ResourceDto::FIELD_RODZAJ_NIERUCHOMOSCI . ':Typ_nieruchomości',
            ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA . ':Powierzchnia',
            ResourceDto::FIELD_ROOM_COUNT . ':Pokoje',
            ResourceDto::FIELD_FLOOR_NUMBER . ':Piętro',
            PriceHistoryDto::FIELD_CENA_CALKOWITA . ':Cena_całkowita',
            PriceHistoryDto::FIELD_CENA_M2 . ':Cena_za_m²',
            'historia_cen:Historia_cen'
        ]);

        $atts = shortcode_atts([
            'nr_lokalu' => '',
            'columns' => $default_columns,

            // Styling options
            'card_bg_color' => '#ffffff',
            'card_border_color' => '#e1e5e9',
            'card_border_radius' => '8px',
            'card_padding' => '24px',
            'card_text_color' => '#333333',
            'card_label_color' => '#666666',
            'label_font_weight' => 'bold',
            'field_spacing' => '12px',

            // Status styling (reuse from list)
            'status_display_style' => '',
            'status_available_bg_color' => '',
            'status_available_text_color' => '',
            'status_sold_bg_color' => '',
            'status_sold_text_color' => '',
            'status_reserved_bg_color' => '',
            'status_reserved_text_color' => '',
            'status_font_size' => '',
            'status_padding' => '',
            'status_border_radius' => '',
            'status_font_weight' => '',

            // Button styling
            'historia_btn_text' => '',
            'historia_btn_bg_color' => '',
            'historia_btn_text_color' => '',
            'karta_btn_text' => '',
            'karta_btn_bg_color' => '',
            'karta_btn_text_color' => ''
        ], $atts);

        // Auto-detect nr_lokalu from URL if not provided
        if (empty($atts['nr_lokalu'])) {
            $atts['nr_lokalu'] = self::detectNrLokaluFromUrl();
        }

        if (empty($atts['nr_lokalu'])) {
            return '<div class="ujc-error">Nie podano numeru lokalu. Upewnij się że shortcode zawiera parametr nr_lokalu lub znajduje się na stronie z numerem lokalu w URL.</div>';
        }

        // Parse columns (IDENTICAL LOGIC as ResourcesListShortcode)
        $column_strings = array_map('trim', explode(',', $atts['columns']));
        $visible_fields = [];
        $field_labels = [];

        $available_fields = [
            ResourceDto::FIELD_NR_LOKALU,
            ResourceDto::FIELD_RODZAJ_NIERUCHOMOSCI,
            ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA,
            ResourceDto::FIELD_STATUS,
            ResourceDto::FIELD_FLOOR_NUMBER,
            ResourceDto::FIELD_ROOM_COUNT,
            ResourceDto::FIELD_ADDITIONAL_DESCRIPTION,
            ResourceDto::FIELD_GARDEN_AREA,
            ResourceDto::FIELD_FLOOR_PLAN_PDF,
            PriceHistoryDto::FIELD_CENA_M2,
            PriceHistoryDto::FIELD_CENA_CALKOWITA,
            PriceHistoryDto::FIELD_CENA_Z_DODATKAMI,
            'historia_cen',
            // Component fields
            ResourceDto::FIELD_PROPERTY_PART_TITLE,
            ResourceDto::FIELD_PROPERTY_PART_DESIGNATION,
            ResourceDto::FIELD_PROPERTY_PART_PRICE,
            ResourceDto::FIELD_PROPERTY_PART_PRICE_DATE,
            ResourceDto::FIELD_BELONGING_ROOM_TITLE,
            ResourceDto::FIELD_BELONGING_ROOM_DESIGNATION,
            ResourceDto::FIELD_BELONGING_ROOM_PRICE,
            ResourceDto::FIELD_BELONGING_ROOM_PRICE_DATE,
            ResourceDto::FIELD_USAGE_RIGHT_TITLE,
            ResourceDto::FIELD_USAGE_RIGHT_PRICE,
            ResourceDto::FIELD_USAGE_RIGHT_PRICE_DATE,
            ResourceDto::FIELD_OTHER_SERVICE_TITLE,
            ResourceDto::FIELD_OTHER_SERVICE_PRICE,
            ResourceDto::FIELD_OTHER_SERVICE_PRICE_DATE
        ];

        foreach ($column_strings as $column_string) {
            if (strpos($column_string, ':') !== false) {
                [$field, $name] = explode(':', $column_string, 2);
                $field = trim($field);
                $name = trim($name);

                // Replace underscores back to spaces for display
                $name = str_replace('_', ' ', $name);

                // Only add if field is valid
                if (in_array($field, $available_fields)) {
                    $visible_fields[] = $field;
                    $field_labels[$field] = $name;
                } else {
                    Logger::error("ResourceSingleShortcode: Invalid column field: {$field}");
                }
            } else {
                Logger::error("ResourceSingleShortcode: Column must be in format 'field:name', got: {$column_string}");
            }
        }

        if (empty($visible_fields)) {
            return '<div class="ujc-error">Brak prawidłowych pól w formacie field:nazwa.</div>';
        }

        // Get resource from UseCase
        try {
            $use_case = DIContainer::get(GetResourceForSinglePageUseCase::class);
            $resource = $use_case->execute($atts['nr_lokalu']);

            if (!$resource) {
                return '<div class="ujc-error">Nie znaleziono zasobu: ' . esc_html($atts['nr_lokalu']) . '</div>';
            }
        } catch (\Exception $e) {
            Logger::error("ResourceSingleShortcode: Failed to get resource: " . $e->getMessage());
            return '<div class="ujc-error">Wystąpił błąd podczas ładowania danych zasobu.</div>';
        }

        // Build styling options
        $styling_options = [
            'card_bg_color' => $atts['card_bg_color'],
            'card_border_color' => $atts['card_border_color'],
            'card_border_radius' => $atts['card_border_radius'],
            'card_padding' => $atts['card_padding'],
            'text_color' => $atts['card_text_color'],
            'label_color' => $atts['card_label_color'],
            'label_font_weight' => $atts['label_font_weight'],
            'field_spacing' => $atts['field_spacing'],

            // Status styling
            'status_display_style' => $atts['status_display_style'],
            'status_available_bg_color' => $atts['status_available_bg_color'],
            'status_available_text_color' => $atts['status_available_text_color'],
            'status_sold_bg_color' => $atts['status_sold_bg_color'],
            'status_sold_text_color' => $atts['status_sold_text_color'],
            'status_reserved_bg_color' => $atts['status_reserved_bg_color'],
            'status_reserved_text_color' => $atts['status_reserved_text_color'],
            'status_font_size' => $atts['status_font_size'],
            'status_padding' => $atts['status_padding'],
            'status_border_radius' => $atts['status_border_radius'],
            'status_font_weight' => $atts['status_font_weight'],

            // Button styling
            'historia_btn_text' => $atts['historia_btn_text'],
            'historia_btn_bg_color' => $atts['historia_btn_bg_color'],
            'historia_btn_text_color' => $atts['historia_btn_text_color'],
            'karta_btn_text' => $atts['karta_btn_text'],
            'karta_btn_bg_color' => $atts['karta_btn_bg_color'],
            'karta_btn_text_color' => $atts['karta_btn_text_color']
        ];

        // Render using shared renderer
        return ResourceSingleRenderer::renderSingleCard(
            $resource,
            $visible_fields,
            $field_labels,
            $styling_options
        );
    }

    /**
     * Detect nr_lokalu from URL path
     * Pattern: /mieszkania/A1 -> "A1"
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
}
