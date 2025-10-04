<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

class ResourcesListBlock {

    public static function render($attributes, $content) {
        Logger::info("ResourcesListBlock: render started with attributes: " . esc_html(print_r($attributes, true)));

        // Parse and validate attributes with defaults
        $parsed_attributes = self::parseAttributes($attributes);

        // Get property types for filtering
        $selected_types = [];
        foreach ($parsed_attributes['selectedTypes'] as $type_string) {
            try {
                $selected_types[] = PropertyType::from($type_string);
            } catch (ValueError $e) {
                Logger::error("Invalid PropertyType in block: {$type_string}");
            }
        }

        // If no valid types, use default
        if (empty($selected_types)) {
            $selected_types = [PropertyType::RESIDENTIAL_UNIT];
        }

        // Build filters and sorting from attributes
        $filters = self::buildFilterCriteria($parsed_attributes);
        $sort = self::buildSortOptions($parsed_attributes);

        // Override with URL filters if enabled
        if (!empty($parsed_attributes['readFiltersFromUrl'])) {
            $url_filters = self::buildFilterCriteriaFromUrl();
            if ($url_filters !== null) {
                $filters = $url_filters;
                Logger::info("ResourcesListBlock: Using URL filters instead of block filters");
            }
        }

        // Get resources using frontend UseCase with filtering and sorting
        $frontend_use_case = DIContainer::get(GetResourcesForFrontendUseCase::class);
        $resources = $frontend_use_case->execute($selected_types, $filters, $sort);
        Logger::info("ResourcesListBlock: Got " . count($resources) . " resources with filters and sorting");

        if (empty($resources)) {
            return '<div class="ujc-no-resources">Brak dostępnych zasobów.</div>';
        }

        // Prepare column configuration
        $visible_columns = $parsed_attributes['visibleColumns'];
        $column_names = self::buildColumnNames($visible_columns, $parsed_attributes['columnNames']);

        // Apply column ordering
        $ordered_columns = self::applyColumnOrdering($visible_columns, $parsed_attributes['columnOrder']);

        // Prepare styling options
        $styling_options = self::buildStylingOptions($parsed_attributes);

        // Choose renderer based on display mode
        if ($parsed_attributes['displayMode'] === 'cards') {
            // Use ResourceCardRenderer for card layout
            return ResourceCardRenderer::renderCardGrid(
                $resources,
                $ordered_columns,
                $column_names,
                $styling_options,
                [
                    'cardsPerRow' => $parsed_attributes['cardsPerRow'],
                    'detailPageUrl' => $parsed_attributes['detailPageUrl'],
                    'cardBgColor' => $parsed_attributes['cardBgColor'],
                    'cardBorderColor' => $parsed_attributes['cardBorderColor'],
                    'cardPadding' => $parsed_attributes['cardPadding'],
                    'cardBorderRadius' => $parsed_attributes['cardBorderRadius'],
                    'cardShadow' => $parsed_attributes['cardShadow'],
                    'cardHoverShadow' => $parsed_attributes['cardHoverShadow'],
                    'cardGap' => $parsed_attributes['cardGap']
                ]
            );
        } else {
            // Use ResourceTableRenderer for table layout (default)
            return ResourceTableRenderer::renderTable(
                $resources,
                $ordered_columns,
                $column_names,
                $styling_options,
                $parsed_attributes['detailPageUrl'],
                'resources-list-block',  // Different container class for block
                $parsed_attributes['enableFrontendSorting']
            );
        }
    }

    /**
     * Parse and validate block attributes with sensible defaults
     */
    private static function parseAttributes(array $attributes): array {
        $defaults = [
            'visibleColumns' => ResourceTableRenderer::getDefaultColumns(),
            'columnOrder' => ResourceTableRenderer::getDefaultColumns(),
            'columnNames' => [],
            'selectedTypes' => ['residential_unit'],
            'headerBgColor' => '#f9f9f9',
            'headerTextColor' => '#333333',
            'textColor' => '#333333',
            'hoverBgColor' => '#f5f5f5',
            'headerFontFamily' => 'inherit',
            'contentFontFamily' => 'inherit',
            'detailPageUrl' => '',
            'historiaBtnText' => 'Historia',
            'historiaBtnBgColor' => '#007cba',
            'historiaBtnTextColor' => '#ffffff',
            'kartaBtnText' => 'Karta lokalu',
            'kartaBtnBgColor' => '#007cba',
            'kartaBtnTextColor' => '#ffffff',
            // Status styling options
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
            // Filtering and sorting options
            'enableFilters' => false,
            'priceMin' => null,
            'priceMax' => null,
            'areaMin' => null,
            'areaMax' => null,
            'statusFilter' => [],
            'floorFilter' => [],
            'roomsFilter' => [],
            'sortBy' => '',
            'sortOrder' => 'ASC',
            'enableFrontendFilters' => false,
            'enableFrontendSorting' => false,
            'readFiltersFromUrl' => false,

            // Display Mode Options
            'displayMode' => 'table', // 'table' | 'cards'

            // Card-specific Options
            'cardsPerRow' => 3,
            'cardGap' => '20px',
            'cardBgColor' => '#ffffff',
            'cardBorderColor' => '#e1e5e9',
            'cardBorderRadius' => '8px',
            'cardPadding' => '16px',
            'cardShadow' => '0 2px 4px rgba(0,0,0,0.1)',
            'cardHoverShadow' => '0 4px 12px rgba(0,0,0,0.15)',

            // Layout & Spacing Options (Priority 1)
            'containerMargin' => '20px 0',
            'tablePadding' => '12px 15px',
            'buttonPadding' => '6px 12px',
            'buttonBorderRadius' => '4px',
            'buttonFontSize' => '0.875em',

            // Modal Options (Priority 1)
            'modalBackgroundColor' => '#ffffff',
            'modalBorderRadius' => '8px',
            'modalMaxWidth' => '600px',
            'modalPadding' => '20px',

            // Advanced Responsive Options (Priority 2)
            'mobileBreakpoint' => '768px',
            'tabletBreakpoint' => '1024px',
            'mobileHiddenColumns' => ['property_type'],
            'smallMobileHiddenColumns' => ['property_type', 'total_price'],
            'mobileTableFontSize' => '0.875em',
            'mobilePadding' => '8px 10px',
            'smallMobilePadding' => '6px 8px',

            // Advanced Table Behavior (Priority 2)
            'tableMinWidth' => '600px',

            // Advanced Button Options (Priority 2)
            'buttonFontWeight' => 'normal',
            'buttonTransition' => 'all 0.2s ease',
            'buttonHoverTransform' => 'none',
            'buttonHoverBoxShadow' => 'none',
            'buttonBorderWidth' => '0',
            'buttonBorderColor' => 'transparent',


            // Advanced Modal Options (Priority 2)
            'modalAnimationDuration' => '0.3s',
            'modalBoxShadow' => '0 10px 30px rgba(0,0,0,0.3)',
            'modalHeaderBorderColor' => '#dee2e6',
            'modalCloseBtnColor' => '#666666',
            'modalCloseBtnHoverColor' => '#000000',
            'modalZIndex' => '10000'
        ];

        // Merge with defaults and validate
        $parsed = array_merge($defaults, $attributes);

        // Ensure arrays are arrays
        if (!is_array($parsed['visibleColumns'])) {
            $parsed['visibleColumns'] = $defaults['visibleColumns'];
        }
        if (!is_array($parsed['columnOrder'])) {
            $parsed['columnOrder'] = $defaults['columnOrder'];
        }
        if (!is_array($parsed['selectedTypes'])) {
            $parsed['selectedTypes'] = $defaults['selectedTypes'];
        }
        if (!is_array($parsed['columnNames'])) {
            $parsed['columnNames'] = [];
        }

        // Validate columns against available columns
        $available_columns = ResourceTableRenderer::getAvailableColumns();
        $parsed['visibleColumns'] = array_intersect($parsed['visibleColumns'], $available_columns);
        $parsed['columnOrder'] = array_intersect($parsed['columnOrder'], $available_columns);

        // Ensure at least one column is visible
        if (empty($parsed['visibleColumns'])) {
            $parsed['visibleColumns'] = [ResourceDto::FIELD_NR_LOKALU, ResourceDto::FIELD_CENA_CALKOWITA];
        }

        Logger::info("ResourcesListBlock: Parsed attributes: " . esc_html(print_r($parsed, true)));
        return $parsed;
    }

    /**
     * Build column names array combining defaults with custom names
     */
    private static function buildColumnNames(array $visible_columns, array $custom_names): array {
        $default_names = ResourceTableRenderer::getDefaultColumnNames();
        $column_names = [];

        foreach ($visible_columns as $column) {
            // Use custom name if provided, otherwise default
            if (!empty($custom_names[$column])) {
                $column_names[$column] = $custom_names[$column];
            } elseif (isset($default_names[$column])) {
                $column_names[$column] = $default_names[$column];
            } else {
                $column_names[$column] = ucfirst(str_replace('_', ' ', $column));
            }
        }

        return $column_names;
    }

    /**
     * Apply column ordering based on user preferences
     */
    private static function applyColumnOrdering(array $visible_columns, array $column_order): array {
        // Start with the order specified by user
        $ordered = array_intersect($column_order, $visible_columns);

        // Add any visible columns not in the order (append to end)
        $missing = array_diff($visible_columns, $ordered);

        return array_merge($ordered, $missing);
    }

    /**
     * Build styling options array for ResourceTableRenderer
     */
    private static function buildStylingOptions(array $attributes): array {
        return [
            'header_bg_color' => $attributes['headerBgColor'],
            'header_text_color' => $attributes['headerTextColor'],
            'text_color' => $attributes['textColor'],
            'hover_bg_color' => $attributes['hoverBgColor'],
            'header_font_family' => $attributes['headerFontFamily'],
            'content_font_family' => $attributes['contentFontFamily'],
            'historia_btn_text' => $attributes['historiaBtnText'],
            'historia_btn_bg_color' => $attributes['historiaBtnBgColor'],
            'historia_btn_text_color' => $attributes['historiaBtnTextColor'],
            'karta_btn_text' => $attributes['kartaBtnText'],
            'karta_btn_bg_color' => $attributes['kartaBtnBgColor'],
            'karta_btn_text_color' => $attributes['kartaBtnTextColor'],
            // Status styling options - convert from camelCase to snake_case for ResourceTableRenderer
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

            // Layout & Spacing Options (Priority 1)
            'container_margin' => $attributes['containerMargin'],
            'table_padding' => $attributes['tablePadding'],
            'button_padding' => $attributes['buttonPadding'],
            'button_border_radius' => $attributes['buttonBorderRadius'],
            'button_font_size' => $attributes['buttonFontSize'],

            // Modal Options (Priority 1)
            'modal_background_color' => $attributes['modalBackgroundColor'],
            'modal_border_radius' => $attributes['modalBorderRadius'],
            'modal_max_width' => $attributes['modalMaxWidth'],
            'modal_padding' => $attributes['modalPadding'],

            // Advanced Responsive Options (Priority 2)
            'mobile_breakpoint' => $attributes['mobileBreakpoint'],
            'tablet_breakpoint' => $attributes['tabletBreakpoint'],
            'mobile_table_font_size' => $attributes['mobileTableFontSize'],
            'mobile_padding' => $attributes['mobilePadding'],
            'small_mobile_padding' => $attributes['smallMobilePadding'],

            // Advanced Table Behavior (Priority 2)
            'table_min_width' => $attributes['tableMinWidth'],

            // Advanced Button Options (Priority 2)
            'button_font_weight' => $attributes['buttonFontWeight'],
            'button_transition' => $attributes['buttonTransition'],
            'button_hover_transform' => $attributes['buttonHoverTransform'],
            'button_hover_box_shadow' => $attributes['buttonHoverBoxShadow'],
            'button_border_width' => $attributes['buttonBorderWidth'],
            'button_border_color' => $attributes['buttonBorderColor'],


            // Advanced Modal Options (Priority 2)
            'modal_animation_duration' => $attributes['modalAnimationDuration'],
            'modal_box_shadow' => $attributes['modalBoxShadow'],
            'modal_header_border_color' => $attributes['modalHeaderBorderColor'],
            'modal_close_btn_color' => $attributes['modalCloseBtnColor'],
            'modal_close_btn_hover_color' => $attributes['modalCloseBtnHoverColor'],
            'modal_z_index' => $attributes['modalZIndex']
        ];
    }

    /**
     * Build FilterCriteria from block attributes
     */
    private static function buildFilterCriteria(array $attributes): ?FilterCriteria {
        // Only create filters if any are actually set
        $hasFilters = !empty($attributes['priceMin']) ||
                     !empty($attributes['priceMax']) ||
                     !empty($attributes['areaMin']) ||
                     !empty($attributes['areaMax']) ||
                     !empty($attributes['statusFilter']) ||
                     !empty($attributes['floorFilter']) ||
                     !empty($attributes['roomsFilter']);

        if (!$hasFilters) {
            return null;
        }

        return new FilterCriteria(
            $attributes['priceMin'],
            $attributes['priceMax'],
            $attributes['areaMin'],
            $attributes['areaMax'],
            !empty($attributes['statusFilter']) ? $attributes['statusFilter'] : null,
            !empty($attributes['floorFilter']) ? $attributes['floorFilter'] : null,
            !empty($attributes['roomsFilter']) ? $attributes['roomsFilter'] : null
        );
    }

    /**
     * Build SortOptions from block attributes
     */
    private static function buildSortOptions(array $attributes): ?SortOptions {
        if (empty($attributes['sortBy'])) {
            return null;
        }

        return new SortOptions(
            $attributes['sortBy'],
            $attributes['sortOrder'] ?: 'ASC'
        );
    }

    /**
     * Build FilterCriteria from URL parameters (from ResourcesFiltersBlock widget)
     */
    private static function buildFilterCriteriaFromUrl(): ?FilterCriteria {
        // Get saved filter configuration from ResourcesFiltersBlock
        $config = ResourcesFiltersBlock::getFilterConfiguration();

        if (empty($config)) {
            Logger::info("ResourcesListBlock: No filter configuration found");
            return null;
        }

        $priceMin = null;
        $priceMax = null;
        $areaMin = null;
        $areaMax = null;
        $statusFilter = [];
        $floorFilter = [];
        $roomsFilter = [];
        $unitNumberFilter = [];

        $hasActiveFilters = false;

        foreach ($config as $filterGroup) {
            if (empty($filterGroup['enabled'])) {
                continue;
            }

            $filterId = 'filter_' . sanitize_key($filterGroup['id']);

            // Check if this filter is active in URL
            $filter_value = sanitize_text_field($_GET[$filterId] ?? '');
            if (empty($filter_value) || $filter_value !== '1') {
                continue;
            }

            $hasActiveFilters = true;
            $field = $filterGroup['field'];
            $filterType = $filterGroup['filterType'];
            $conditions = $filterGroup['conditions'];

            Logger::info("ResourcesListBlock: Processing active URL filter: {$filterId} for field {$field}");

            switch ($field) {
                case 'total_price':
                case 'price_per_m2':
                    if ($filterType === 'range') {
                        $priceMin = $conditions['min'] ?? null;
                        $priceMax = $conditions['max'] ?? null;
                    } elseif ($filterType === 'min') {
                        $priceMin = $conditions['min'] ?? null;
                    } elseif ($filterType === 'max') {
                        $priceMax = $conditions['max'] ?? null;
                    }
                    break;

                case 'usable_area':
                case 'garden_area':
                    if ($filterType === 'range') {
                        $areaMin = $conditions['min'] ?? null;
                        $areaMax = $conditions['max'] ?? null;
                    } elseif ($filterType === 'min') {
                        $areaMin = $conditions['min'] ?? null;
                    } elseif ($filterType === 'max') {
                        $areaMax = $conditions['max'] ?? null;
                    }
                    break;

                case 'status':
                case 'property_type':
                    if ($filterType === 'list' && !empty($conditions['values'])) {
                        $statusFilter = array_merge($statusFilter, $conditions['values']);
                    } elseif ($filterType === 'exact' && !empty($conditions['value'])) {
                        $statusFilter[] = $conditions['value'];
                    }
                    break;

                case 'floor_number':
                    if ($filterType === 'list' && !empty($conditions['values'])) {
                        $floorFilter = array_merge($floorFilter, $conditions['values']);
                    } elseif ($filterType === 'exact' && !empty($conditions['value'])) {
                        $floorFilter[] = $conditions['value'];
                    }
                    break;

                case 'room_count':
                    if ($filterType === 'list' && !empty($conditions['values'])) {
                        $roomsFilter = array_merge($roomsFilter, $conditions['values']);
                    } elseif ($filterType === 'exact' && !empty($conditions['value'])) {
                        $roomsFilter[] = $conditions['value'];
                    }
                    break;

                case 'unit_number':
                    if ($filterType === 'list' && !empty($conditions['values'])) {
                        $unitNumberFilter = array_merge($unitNumberFilter, $conditions['values']);
                    } elseif ($filterType === 'exact' && !empty($conditions['value'])) {
                        $unitNumberFilter[] = $conditions['value'];
                    }
                    break;

                default:
                    Logger::warning("ResourcesListBlock: Unknown filter field: {$field}");
                    break;
            }
        }

        if (!$hasActiveFilters) {
            Logger::info("ResourcesListBlock: No active URL filters found");
            return null;
        }

        Logger::info("ResourcesListBlock: Built URL filters - price: {$priceMin}-{$priceMax}, area: {$areaMin}-{$areaMax}, status: " . implode(',', $statusFilter));

        return new FilterCriteria(
            $priceMin,
            $priceMax,
            $areaMin,
            $areaMax,
            !empty($statusFilter) ? $statusFilter : null,
            !empty($floorFilter) ? $floorFilter : null,
            !empty($roomsFilter) ? $roomsFilter : null,
            null, // propertyTypes - will be handled by status for now
            !empty($unitNumberFilter) ? $unitNumberFilter : null
        );
    }
}