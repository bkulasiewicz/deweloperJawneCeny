<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Resources List Shortcode Handler
 * Handles the [resources_list] shortcode with filtering and customization options
 */
class ResourcesListShortcode {

    // Functional column constants
    public const COLUMN_HISTORIA_CEN = 'historia_cen';
    
    /**
     * Handle resources_list shortcode with type-safe parsing
     * All configuration comes from shortcode parameters with sensible defaults
     */
    public static function handle($atts) {
        // Default columns
        $default_columns = implode(',', [
            ResourceDto::FIELD_NR_LOKALU,
            ResourceDto::FIELD_RODZAJ_NIERUCHOMOSCI,
            ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA,
            PriceHistoryDto::FIELD_CENA_CALKOWITA,
            ResourceDto::FIELD_STATUS,
            self::COLUMN_HISTORIA_CEN
        ]);

        // Default types - residential units only
        $default_types = PropertyType::RESIDENTIAL_UNIT->value;

        Logger::info('Raw atts before shortcode_atts: ' . esc_html(print_r($atts, true)));

        // Fix WordPress parsing issue - convert indexed array to associative
        if (isset($atts[0]) && is_string($atts[0])) {
            $fixed_atts = [];
            foreach ($atts as $attr_string) {
                if (preg_match('/(\w+)="([^"]*)"/', $attr_string, $matches)) {
                    $fixed_atts[$matches[1]] = $matches[2];
                }
            }
            $atts = $fixed_atts;
            Logger::info('Fixed atts from indexed array: ' . esc_html(print_r($atts, true)));
        }

        $atts = shortcode_atts([
            'types' => $default_types,
            'columns' => $default_columns,
            'detail_page_url' => '',
            'header_bg_color' => '',
            'header_text_color' => '',
            'hover_bg_color' => '',
            'text_color' => '',
            'header_font_family' => '',
            'content_font_family' => '',

            // Status styling options (no defaults - set via frontend generator)
            'status_available_bg_color' => '',
            'status_available_text_color' => '',
            'status_sold_bg_color' => '',
            'status_sold_text_color' => '',
            'status_reserved_bg_color' => '',
            'status_reserved_text_color' => '',
            'status_display_style' => '',
            'status_font_size' => '',
            'status_padding' => '',
            'status_border_radius' => '',
            'status_font_weight' => '',

            // Historia Cen button styling
            'historia_btn_text' => '',
            'historia_btn_bg_color' => '',
            'historia_btn_text_color' => '',

            // Karta Lokalu button styling
            'karta_btn_text' => '',
            'karta_btn_bg_color' => '',
            'karta_btn_text_color' => '',

            // Frontend sorting
            'enable_frontend_sorting' => 'false',

            // Sortowanie i filtrowanie (połączone w jeden parametr)
            'sorting' => '', // format: "field:order" np. "floor:desc"
            'filtering' => '', // format: "field:value" np. "floor:2"
        ], $atts);

        Logger::info('Processed atts after shortcode_atts: ' . esc_html(print_r($atts, true)));

        // Debug: Check if styling parameters are present
        if (!empty($atts['status_display_style'])) {
            Logger::info('Status styling enabled: ' . esc_html($atts['status_display_style']));
        }
        if (!empty($atts['historia_btn_text'])) {
            Logger::info('Historia button text: ' . esc_html($atts['historia_btn_text']));
        }

        try {
            // Parse property types
            $type_strings = array_map('trim', explode(',', $atts['types']));
            $selected_types = [];
            
            foreach ($type_strings as $type_string) {
                try {
                    $selected_types[] = PropertyType::from($type_string);
                } catch (ValueError $e) {
                    Logger::error("Invalid PropertyType in shortcode: {$type_string}");
                    // Skip invalid types, don't fail completely
                }
            }
            
            // If no valid types, use default
            if (empty($selected_types)) {
                $selected_types = [PropertyType::RESIDENTIAL_UNIT];
            }
            
            // Parse visible columns with names (format: field:name,field2:name2)
            $column_strings = array_map('trim', explode(',', $atts['columns']));
            $visible_columns = [];
            $column_names = [];
            
            $available_columns = [
                // Basic information from ResourceDto
                ResourceDto::FIELD_NR_LOKALU,
                ResourceDto::FIELD_RODZAJ_NIERUCHOMOSCI,
                ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA,
                ResourceDto::FIELD_STATUS,
                
                // Prices from PriceHistory
                PriceHistoryDto::FIELD_CENA_M2,
                PriceHistoryDto::FIELD_CENA_CALKOWITA,
                PriceHistoryDto::FIELD_CENA_Z_DODATKAMI,
                
                // Marketing fields from ResourceDto
                ResourceDto::FIELD_FLOOR_NUMBER,
                ResourceDto::FIELD_ROOM_COUNT,
                ResourceDto::FIELD_ADDITIONAL_DESCRIPTION,
                ResourceDto::FIELD_GARDEN_AREA,
                ResourceDto::FIELD_FLOOR_PLAN_PDF,
                
                // Functional
                self::COLUMN_HISTORIA_CEN
            ];
            
            foreach ($column_strings as $column_string) {
                if (strpos($column_string, ':') !== false) {
                    [$field, $name] = explode(':', $column_string, 2);
                    $field = trim($field);
                    $name = trim($name);
                    
                    // Replace underscores back to spaces for display
                    $name = str_replace('_', ' ', $name);
                    
                    // Only add if field is valid
                    if (in_array($field, $available_columns)) {
                        $visible_columns[] = $field;
                        $column_names[$field] = $name;
                    } else {
                        Logger::error("Invalid column field in shortcode: {$field}");
                    }
                } else {
                    Logger::error("Column must be in format 'field:name', got: {$column_string}");
                }
            }
            
            // If no valid columns, return error
            if (empty($visible_columns)) {
                Logger::error("No valid columns provided in shortcode");
                return '<div class="ujc-shortcode-error">Błąd: Brak prawidłowych kolumn w formacie field:nazwa.</div>';
            }
            
            // Use provided styling options
            $styling_options = [
                'header_bg_color' => $atts['header_bg_color'],
                'header_text_color' => $atts['header_text_color'],
                'hover_bg_color' => $atts['hover_bg_color'],
                'text_color' => $atts['text_color'],
                'header_font_family' => $atts['header_font_family'],
                'content_font_family' => $atts['content_font_family'],

                // Status styling options
                'status_available_bg_color' => $atts['status_available_bg_color'],
                'status_available_text_color' => $atts['status_available_text_color'],
                'status_sold_bg_color' => $atts['status_sold_bg_color'],
                'status_sold_text_color' => $atts['status_sold_text_color'],
                'status_reserved_bg_color' => $atts['status_reserved_bg_color'],
                'status_reserved_text_color' => $atts['status_reserved_text_color'],
                'status_display_style' => $atts['status_display_style'],
                'status_font_size' => $atts['status_font_size'],
                'status_padding' => $atts['status_padding'],
                'status_border_radius' => $atts['status_border_radius'],
                'status_font_weight' => $atts['status_font_weight'],

                // Button styling options
                'historia_btn_text' => $atts['historia_btn_text'],
                'historia_btn_bg_color' => $atts['historia_btn_bg_color'],
                'historia_btn_text_color' => $atts['historia_btn_text_color'],

                'karta_btn_text' => $atts['karta_btn_text'],
                'karta_btn_bg_color' => $atts['karta_btn_bg_color'],
                'karta_btn_text_color' => $atts['karta_btn_text_color'],
            ];

            // Parse sortowanie z nowego formatu "field:order"
            $sortOptions = self::createSortOptionsFromCombined($atts['sorting']);
            Logger::info("Shortcode: Sort options created: " . ($sortOptions ? $sortOptions->sortBy . " " . $sortOptions->sortOrder : "none"));

            // Parse filtrowanie z nowego formatu "field:value"
            $filterCriteria = self::createFilterFromCombined($atts['filtering']);
            Logger::info("Shortcode: Filter criteria created for filtering={$atts['filtering']}");

            // Render the resources table
            return self::render_resources_table($selected_types, $visible_columns, $column_names, $styling_options, $atts['detail_page_url'], $sortOptions, $filterCriteria, ($atts['enable_frontend_sorting'] === 'true'));
            
        } catch (Exception $e) {
            Logger::error("Shortcode error: " . $e->getMessage());
            return '<div class="ujc-shortcode-error">Błąd podczas ładowania zasobów.</div>';
        }
    }
    
    /**
     * Create SortOptions from combined format "field:order"
     */
    private static function createSortOptionsFromCombined(string $sorting): ?SortOptions {
        if (empty($sorting)) {
            return null;
        }

        // Parse format "field:order"
        $parts = explode(':', $sorting);
        if (count($parts) !== 2) {
            Logger::error("Invalid sorting format in shortcode: {$sorting}. Expected 'field:order'");
            return null;
        }

        $sort = trim($parts[0]);
        $order = trim($parts[1]);

        // Mapowanie user-friendly nazw na stałe SortOptions
        $sortMapping = [
            'price' => SortOptions::SORT_BY_PRICE,
            'area' => SortOptions::SORT_BY_AREA,
            'floor' => SortOptions::SORT_BY_FLOOR,
            'unit' => SortOptions::SORT_BY_UNIT_NUMBER,
            'status' => SortOptions::SORT_BY_STATUS,
            'rooms' => SortOptions::SORT_BY_ROOMS
        ];

        $mappedSort = $sortMapping[$sort] ?? null;
        if (!$mappedSort) {
            Logger::error("Invalid sort field in shortcode: {$sort}");
            return null;
        }

        return new SortOptions($mappedSort, $order);
    }

    /**
     * Create FilterCriteria from combined format "field:value"
     */
    private static function createFilterFromCombined(string $filtering): FilterCriteria {
        if (empty($filtering)) {
            return new FilterCriteria();
        }

        // Parse format "field:value"
        $parts = explode(':', $filtering);
        if (count($parts) !== 2) {
            Logger::error("Invalid filtering format in shortcode: {$filtering}. Expected 'field:value'");
            return new FilterCriteria();
        }

        $filterBy = trim($parts[0]);
        $filterValue = trim($parts[1]);

        switch ($filterBy) {
            case 'floor':
                $floor = (int)$filterValue;
                Logger::info("Shortcode: Filtering by floor = {$floor}");
                return new FilterCriteria(
                    null, null,        // price min/max
                    null, null,        // area min/max
                    null,              // status filter
                    [$floor],          // floor filter (array with single floor)
                    null,              // rooms filter
                    null,              // property types (handled separately)
                    null               // unit number filter
                );

            // TODO: Dodać w przyszłości:
            // case 'price': ...
            // case 'status': ...
            // case 'area': ...

            default:
                Logger::error("Invalid filterBy field in shortcode: {$filterBy}");
                return new FilterCriteria(); // empty filter
        }
    }

    /**
     * Render resources table for shortcode using shared ResourceTableRenderer
     */
    private static function render_resources_table(array $selected_types, array $visible_columns, array $column_names, array $styling_options, string $detail_page_url = '', ?SortOptions $sortOptions = null, ?FilterCriteria $filterCriteria = null, bool $enable_frontend_sorting = false): string {
        Logger::info("Shortcode: render_resources_table started");
        Logger::info("Shortcode: Selected types count: " . count($selected_types));
        Logger::info("Shortcode: Selected type values: " . implode(', ', array_map(fn($type) => $type->value, $selected_types)));

        // Get resources using dedicated frontend UseCase with built-in filtering
        $frontend_use_case = DIContainer::get(GetResourcesForFrontendUseCase::class);
        $filtered_resources = $frontend_use_case->execute($selected_types, $filterCriteria, $sortOptions);
        Logger::info("Shortcode: Got " . count($filtered_resources) . " filtered resources from GetResourcesForFrontendUseCase");

        // Use ResourceTableRenderer for consistent rendering
        return ResourceTableRenderer::renderTable(
            $filtered_resources,
            $visible_columns,
            $column_names,
            $styling_options,
            $detail_page_url,
            'ujc-shortcode-resources-list',  // Shortcode container class
            $enable_frontend_sorting
        );
    }
    
}
