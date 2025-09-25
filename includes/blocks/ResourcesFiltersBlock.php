<?php

if (!defined('ABSPATH')) {
    exit;
}

class ResourcesFiltersBlock {

    public static function render($attributes, $content) {
        Logger::info("ResourcesFiltersBlock: render started with attributes: " . print_r($attributes, true));

        // Parse and validate attributes
        $parsed_attributes = self::parseAttributes($attributes);

        // Get enabled filter groups and all sections (sections are always shown)
        $display_items = array_filter($parsed_attributes['filterGroups'], function($group) {
            return ($group['type'] === 'section') || !empty($group['enabled']);
        });

        // Get only enabled filters for configuration (excluding sections)
        $enabled_filters = array_filter($parsed_attributes['filterGroups'], function($group) {
            return ($group['type'] !== 'section') && !empty($group['enabled']);
        });

        // Save filter configuration for ResourcesListBlock to use
        self::saveFilterConfiguration($enabled_filters);

        if (empty($display_items)) {
            return '<div class="resources-filters-widget">Brak skonfigurowanych filtrów.</div>';
        }

        // Render the filters widget
        return self::renderFiltersWidget($display_items, $parsed_attributes);
    }

    /**
     * Parse and validate block attributes
     */
    private static function parseAttributes(array $attributes): array {
        $defaults = [
            'filterGroups' => [
                [
                    'id' => 'price_300_500',
                    'enabled' => false,
                    'label' => 'Cena 300.000 - 500.000 PLN',
                    'field' => 'total_price',
                    'filterType' => 'range',
                    'conditions' => [
                        'min' => 300000,
                        'max' => 500000
                    ]
                ],
                [
                    'id' => 'area_above_60',
                    'enabled' => false,
                    'label' => 'Powierzchnia powyżej 60m²',
                    'field' => 'usable_area',
                    'filterType' => 'min',
                    'conditions' => [
                        'min' => 60
                    ]
                ],
                [
                    'id' => 'rooms_2_3',
                    'enabled' => false,
                    'label' => '2-3 pokoje',
                    'field' => 'room_count',
                    'filterType' => 'list',
                    'conditions' => [
                        'values' => ['2', '3']
                    ]
                ],
                [
                    'id' => 'ground_floor',
                    'enabled' => false,
                    'label' => 'Parter',
                    'field' => 'floor_number',
                    'filterType' => 'exact',
                    'conditions' => [
                        'value' => '0'
                    ]
                ]
            ],
            // Styling defaults
            'headerBgColor' => '#ffffff',
            'headerTextColor' => '#333',
            'borderColor' => '#e1e5e9',
            'groupBgColor' => '#fafafa',
            'groupHoverBgColor' => '#f8f9fa',
            'textColor' => '#333',
            'applyBtnBgColor' => '#007cba',
            'applyBtnTextColor' => '#ffffff',
            'clearBtnBgColor' => '#f6f7f7',
            'clearBtnTextColor' => '#50575e',
            'headerFontFamily' => 'inherit',
            'contentFontFamily' => 'inherit',
            'applyBtnText' => 'Zastosuj filtry',
            'clearBtnText' => 'Wyczyść',
            'widgetTitle' => 'Filtry'
        ];

        // Merge with defaults and validate
        $parsed = array_merge($defaults, $attributes);

        // Ensure filterGroups is array
        if (!is_array($parsed['filterGroups'])) {
            $parsed['filterGroups'] = $defaults['filterGroups'];
        }

        Logger::info("ResourcesFiltersBlock: Parsed attributes: " . print_r($parsed, true));
        return $parsed;
    }

    /**
     * Render filters widget HTML
     */
    private static function renderFiltersWidget(array $filterGroups, array $attributes): string {
        // Generate inline styles
        $inline_styles = self::generateInlineStyles($attributes);

        $html = '<div class="resources-filters-widget" style="' . $inline_styles . '">';

        // Add title if provided
        if (!empty($attributes['widgetTitle']) && $attributes['widgetTitle'] !== 'Filtry') {
            $html .= '<h3>' . esc_html($attributes['widgetTitle']) . '</h3>';
        }
        $html .= '<form id="resources-filters-form" method="get">';

        // Preserve existing URL parameters (except filter ones)
        foreach ($_GET as $key => $value) {
            if (strpos($key, 'filter_') !== 0) {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $html .= '<input type="hidden" name="' . esc_attr($key) . '[]" value="' . esc_attr($v) . '">';
                    }
                } else {
                    $html .= '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                }
            }
        }

        $html .= '<div class="filters-container">';

        foreach ($filterGroups as $group) {
            if (isset($group['type']) && $group['type'] === 'section') {
                $html .= self::renderSection($group, $attributes);
            } else {
                $html .= self::renderFilterGroup($group, $attributes);
            }
        }

        $html .= '</div>';

        $html .= '<div class="filters-actions">';
        $html .= '<button type="submit" class="apply-filters-btn">' . esc_html($attributes['applyBtnText']) . '</button>';
        $html .= '<button type="button" id="clear-filters" class="clear-filters-btn">' . esc_html($attributes['clearBtnText']) . '</button>';
        $html .= '</div>';

        $html .= '</form>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Render single filter group
     */
    private static function renderFilterGroup(array $group, array $attributes): string {
        $filterId = 'filter_' . sanitize_key($group['id']);
        $isChecked = !empty($_GET[$filterId]);

        $html = '<div class="filter-group">';
        $html .= '<label class="filter-label">';
        $html .= '<input type="checkbox" ';
        $html .= 'name="' . esc_attr($filterId) . '" ';
        $html .= 'value="1" ';
        $html .= checked($isChecked, true, false);
        $html .= ' class="filter-checkbox">';
        $html .= '<span class="filter-text">' . esc_html($group['label']) . '</span>';
        $html .= '</label>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Render section header
     */
    private static function renderSection(array $section, array $attributes): string {
        $html = '<div class="filter-section">';
        $html .= '<h4 class="section-title">' . esc_html($section['title']) . '</h4>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Get human-readable description of filter conditions
     */
    private static function getFilterDescription(array $group): string {
        $fieldNames = [
            'total_price' => 'cena',
            'usable_area' => 'powierzchnia',
            'room_count' => 'pokoje',
            'floor_number' => 'piętro',
            'status' => 'status'
        ];

        $fieldName = $fieldNames[$group['field']] ?? $group['field'];

        switch ($group['filterType']) {
            case 'range':
                return "Filtruje {$fieldName} od {$group['conditions']['min']} do {$group['conditions']['max']}";
            case 'min':
                return "Filtruje {$fieldName} powyżej {$group['conditions']['min']}";
            case 'max':
                return "Filtruje {$fieldName} poniżej {$group['conditions']['max']}";
            case 'exact':
                return "Filtruje {$fieldName} = {$group['conditions']['value']}";
            case 'list':
                $values = is_array($group['conditions']['values']) ? implode(', ', $group['conditions']['values']) : $group['conditions']['values'];
                return "Filtruje {$fieldName}: {$values}";
            default:
                return "Filtr: {$fieldName}";
        }
    }

    /**
     * Get filter configuration for use by other blocks
     * Stores in transient for ResourcesListBlock to read
     */
    public static function saveFilterConfiguration(array $filterGroups): void {
        set_transient('resources_filters_config', $filterGroups, HOUR_IN_SECONDS);
        Logger::info("ResourcesFiltersBlock: Saved filter configuration");
    }

    /**
     * Get saved filter configuration
     */
    public static function getFilterConfiguration(): array {
        return get_transient('resources_filters_config') ?: [];
    }

    /**
     * Generate inline styles for the widget container
     */
    private static function generateInlineStyles(array $attributes): string {
        $styles = [];

        // Container styles
        $styles[] = 'background-color: ' . esc_attr($attributes['headerBgColor']);
        $styles[] = 'border-color: ' . esc_attr($attributes['borderColor']);
        $styles[] = 'font-family: ' . esc_attr($attributes['contentFontFamily']);
        $styles[] = 'color: ' . esc_attr($attributes['textColor']);

        // CSS custom properties for hover effects and dynamic styling
        $styles[] = '--group-bg-color: ' . esc_attr($attributes['groupBgColor']);
        $styles[] = '--group-hover-bg-color: ' . esc_attr($attributes['groupHoverBgColor']);
        $styles[] = '--text-color: ' . esc_attr($attributes['textColor']);
        $styles[] = '--header-text-color: ' . esc_attr($attributes['headerTextColor']);
        $styles[] = '--header-font-family: ' . esc_attr($attributes['headerFontFamily']);
        $styles[] = '--content-font-family: ' . esc_attr($attributes['contentFontFamily']);

        // Button styling custom properties
        $styles[] = '--apply-btn-bg-color: ' . esc_attr($attributes['applyBtnBgColor']);
        $styles[] = '--apply-btn-text-color: ' . esc_attr($attributes['applyBtnTextColor']);
        $styles[] = '--clear-btn-bg-color: ' . esc_attr($attributes['clearBtnBgColor']);
        $styles[] = '--clear-btn-text-color: ' . esc_attr($attributes['clearBtnTextColor']);

        // Layout & Spacing (Priority 1) - only working ones
        $styles[] = '--widget-padding: ' . esc_attr($attributes['widgetPadding'] ?? '20px');
        $styles[] = '--widget-border-radius: ' . esc_attr($attributes['widgetBorderRadius'] ?? '8px');
        $styles[] = '--widget-border-width: ' . esc_attr($attributes['widgetBorderWidth'] ?? '1px');
        $styles[] = '--filter-group-padding: ' . esc_attr($attributes['filterGroupPadding'] ?? '12px');
        $styles[] = '--filter-group-border-radius: ' . esc_attr($attributes['filterGroupBorderRadius'] ?? '6px');
        $styles[] = '--button-padding: ' . esc_attr($attributes['buttonPadding'] ?? '10px 20px');
        $styles[] = '--button-border-radius: ' . esc_attr($attributes['buttonBorderRadius'] ?? '5px');

        // Typography (Priority 1) - only working ones
        $styles[] = '--section-title-font-size: ' . esc_attr($attributes['sectionTitleFontSize'] ?? '1.1em');
        $styles[] = '--button-font-size: ' . esc_attr($attributes['buttonFontSize'] ?? '0.95em');

        // Border Colors (Priority 1) - only working ones
        $styles[] = '--section-title-border-color: ' . esc_attr($attributes['sectionTitleBorderColor'] ?? '#f0f0f1');
        $styles[] = '--filter-group-border-color: ' . esc_attr($attributes['filterGroupBorderColor'] ?? '#f0f0f1');
        $styles[] = '--actions-border-color: ' . esc_attr($attributes['actionsBorderColor'] ?? '#f0f0f1');

        return implode('; ', $styles);
    }
}