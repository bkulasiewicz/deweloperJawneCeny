<?php

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

        Logger::info('Raw atts before shortcode_atts: ' . print_r($atts, true));

        // Fix WordPress parsing issue - convert indexed array to associative
        if (isset($atts[0]) && is_string($atts[0])) {
            $fixed_atts = [];
            foreach ($atts as $attr_string) {
                if (preg_match('/(\w+)="([^"]*)"/', $attr_string, $matches)) {
                    $fixed_atts[$matches[1]] = $matches[2];
                }
            }
            $atts = $fixed_atts;
            Logger::info('Fixed atts from indexed array: ' . print_r($atts, true));
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
        ], $atts);

        Logger::info('Processed atts after shortcode_atts: ' . print_r($atts, true));

        // Debug: Check if styling parameters are present
        if (!empty($atts['status_display_style'])) {
            Logger::info('Status styling enabled: ' . $atts['status_display_style']);
        }
        if (!empty($atts['historia_btn_text'])) {
            Logger::info('Historia button text: ' . $atts['historia_btn_text']);
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
            
            // Render the resources table
            return self::render_resources_table($selected_types, $visible_columns, $column_names, $styling_options, $atts['detail_page_url']);
            
        } catch (Exception $e) {
            Logger::error("Shortcode error: " . $e->getMessage());
            return '<div class="ujc-shortcode-error">Błąd podczas ładowania zasobów.</div>';
        }
    }
    
    /**
     * Render resources table for shortcode
     */
    private static function render_resources_table(array $selected_types, array $visible_columns, array $column_names, array $styling_options, string $detail_page_url = ''): string {
        Logger::info("Shortcode: render_resources_table started");
        Logger::info("Shortcode: Selected types count: " . count($selected_types));
        Logger::info("Shortcode: Selected type values: " . implode(', ', array_map(fn($type) => $type->value, $selected_types)));
        
        // Get resources using dedicated frontend UseCase with built-in filtering
        $frontend_use_case = new GetResourcesForFrontendUseCase();
        $filtered_resources = $frontend_use_case->execute($selected_types);
        Logger::info("Shortcode: Got " . count($filtered_resources) . " filtered resources from GetResourcesForFrontendUseCase");
        
        if (empty($filtered_resources)) {
            Logger::error("Shortcode: No resources to display");
            return '<div class="ujc-no-resources">Brak zasobów do wyświetlenia.</div>';
        }
        
        // Start output buffering
        ob_start();

        // Ensure CSS is loaded for shortcode
        wp_enqueue_style(
            'ujc-resources-list-shortcode-css',
            PLUGIN_URL . 'assets/blocks/resources-list-block.css',
            [],
            VERSION
        );

        // Generate dynamic CSS
        Logger::info('DEBUG: About to call render_dynamic_css');
        self::render_dynamic_css($styling_options, !empty($detail_page_url));
        Logger::info('DEBUG: After calling render_dynamic_css');
        
        ?>
        <div class="ujc-shortcode-resources-list">
            <table class="resources-table">
                <thead>
                    <tr>
                        <?php foreach ($visible_columns as $column): ?>
                            <th><?php echo esc_html($column_names[$column] ?? ucfirst($column)); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filtered_resources as $resource): ?>
                        <?php
                        $row_classes = [];
                        $row_attributes = [];

                        // Make row clickable if detail_page_url is provided
                        if (!empty($detail_page_url)) {
                            $row_classes[] = 'clickable-row';
                            $detail_url = rtrim($detail_page_url, '/') . '/' . urlencode($resource->nr_lokalu);
                            $row_attributes[] = 'data-detail-url="' . esc_attr($detail_url) . '"';
                        }

                        $class_attr = !empty($row_classes) ? ' class="' . esc_attr(implode(' ', $row_classes)) . '"' : '';
                        $data_attrs = !empty($row_attributes) ? ' ' . implode(' ', $row_attributes) : '';
                        ?>
                        <tr<?php echo $class_attr . $data_attrs; ?>>
                            <?php foreach ($visible_columns as $column): ?>
                                <td><?php echo self::render_column_value($resource, $column, $styling_options); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Price History Modal (same as in existing block) -->
            <div id="price-history-modal" class="price-history-modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Historia cen - <span id="modal-resource-name"></span></h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div id="history-loading">Ładowanie...</div>
                        <div id="history-content"></div>
                    </div>
                </div>
            </div>
        </div>

        <?php
        // Always enqueue the resources widget JavaScript for modal functionality
        wp_enqueue_script(
            'ujc-shortcode-resources-widget',
            plugins_url('assets/blocks/resources-list-widget.js', dirname(dirname(__DIR__)) . '/ustawa-jawnosci-cen.php'),
            ['jquery'],
            VERSION,
            true
        );

        wp_localize_script('ujc-shortcode-resources-widget', 'resourcesListAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('resources_list_nonce'),
            'strings' => [
                'loading' => 'Ładowanie...',
                'error' => 'Wystąpił błąd podczas ładowania historii cen.',
                'no_history' => 'Brak historii cen dla tego zasobu.'
            ]
        ]);

        // Enqueue clickable rows JavaScript if needed
        if (!empty($detail_page_url)):
            wp_enqueue_script(
                'ujc-clickable-rows',
                plugins_url('assets/frontend-clickable-rows.js', dirname(dirname(__DIR__)) . '/ustawa-jawnosci-cen.php'),
                ['jquery'],
                '1.0.1',
                true
            );
        endif;
        ?>
        <?php

        return ob_get_clean();
    }
    
    /**
     * Render individual column value
     */
    private static function render_column_value($resource, string $column, array $styling_options = []): string {
        switch ($column) {
            case ResourceDto::FIELD_NR_LOKALU:
                return esc_html($resource->nr_lokalu);
                
            case ResourceDto::FIELD_RODZAJ_NIERUCHOMOSCI:
                try {
                    $propertyType = PropertyType::from($resource->rodzaj_nieruchomosci);
                    return esc_html($propertyType->getDisplayText());
                } catch (ValueError $e) {
                    return esc_html($resource->rodzaj_nieruchomosci);
                }
                
            case ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA:
                // Hide surface area for parking spaces (existing logic)
                if ($resource->rodzaj_nieruchomosci === PropertyType::PARKING_SPACE->value) {
                    return '—';
                }
                return esc_html(number_format($resource->powierzchnia_uzytkowa, 2, ',', ' ')) . ' m²';
                
            case ResourceDto::FIELD_STATUS:
                try {
                    $status = ResourceStatus::from($resource->status);
                    $status_value = $resource->status;
                    $status_text = $status->getDisplayText();

                    // Get display style
                    $display_style = $styling_options['status_display_style'] ?? 'badge';

                    // Build CSS classes
                    $css_classes = ['ujc-status-' . $status_value];
                    if (!empty($display_style)) {
                        $css_classes[] = 'ujc-status-style-' . $display_style;
                    }

                    return '<span class="' . esc_attr(implode(' ', $css_classes)) . '">' . esc_html($status_text) . '</span>';
                } catch (ValueError $e) {
                    return esc_html($resource->status);
                }
                
            case PriceHistoryDto::FIELD_CENA_M2:
                // Hide price per m2 for parking spaces (existing logic)
                if ($resource->rodzaj_nieruchomosci === PropertyType::PARKING_SPACE->value) {
                    return '—';
                }
                if ($resource->cena_m2 !== null && $resource->cena_m2 > 0) {
                    return esc_html(number_format($resource->cena_m2, 2, ',', ' ')) . ' zł';
                }
                return '—';
                
            case PriceHistoryDto::FIELD_CENA_CALKOWITA:
                if ($resource->cena_calkowita) {
                    return esc_html(number_format($resource->cena_calkowita, 2, ',', ' ')) . ' zł';
                }
                return '—';
                
            case PriceHistoryDto::FIELD_CENA_Z_DODATKAMI:
                if ($resource->cena_z_dodatkami) {
                    return esc_html(number_format($resource->cena_z_dodatkami, 2, ',', ' ')) . ' zł';
                }
                return '—';
                
            case self::COLUMN_HISTORIA_CEN:
                $btn_text = !empty($styling_options['historia_btn_text']) ? $styling_options['historia_btn_text'] : 'Historia';

                $css_classes = ['ujc-historia-btn'];

                return '<button class="' . esc_attr(implode(' ', $css_classes)) . '" data-resource-id="' . esc_attr($resource->id) . '" data-resource-name="' . esc_attr($resource->nr_lokalu) . '">' . esc_html($btn_text) . '</button>';
                
            // Marketing fields
            case ResourceDto::FIELD_FLOOR_NUMBER:
                return $resource->floor_number ? esc_html($resource->floor_number) : '—';
                
            case ResourceDto::FIELD_ROOM_COUNT:
                return $resource->room_count ? esc_html($resource->room_count) : '—';
                
            case ResourceDto::FIELD_ADDITIONAL_DESCRIPTION:
                return $resource->additional_description ? esc_html(wp_trim_words($resource->additional_description, 10)) : '—';
                
            case ResourceDto::FIELD_GARDEN_AREA:
                return $resource->garden_area ? esc_html(number_format($resource->garden_area, 2, ',', ' ')) . ' m²' : '—';
                
            case ResourceDto::FIELD_FLOOR_PLAN_PDF:
                if ($resource->floor_plan_pdf) {
                    $filename = basename($resource->floor_plan_pdf);
                    $btn_text = !empty($styling_options['karta_btn_text']) ? $styling_options['karta_btn_text'] : 'Karta lokalu';

                    $css_classes = ['download-floorplan-btn', 'ujc-karta-btn'];

                    return '<button class="' . esc_attr(implode(' ', $css_classes)) . '" data-filename="' . esc_attr($filename) . '">' . esc_html($btn_text) . '</button>';
                }
                return '—';
                
            default:
                return '—';
        }
    }

    /**
     * Render CSS variables globally for compatibility with optimized CSS
     */
    private static function render_dynamic_css(array $styling_options, bool $has_clickable_rows = false): void {
        ?>
        <style>
        /* CSS Custom Properties from shortcode parameters - global scope for optimized CSS */
        :root {
            --header-bg-color: <?php echo esc_attr($styling_options['header_bg_color']); ?>;
            --header-text-color: <?php echo esc_attr($styling_options['header_text_color']); ?>;
            --text-color: <?php echo esc_attr($styling_options['text_color']); ?>;
            --hover-bg-color: <?php echo esc_attr($styling_options['hover_bg_color']); ?>;
            --header-font-family: <?php echo esc_attr($styling_options['header_font_family']); ?>;
            --content-font-family: <?php echo esc_attr($styling_options['content_font_family']); ?>;
            --historia-btn-bg-color: <?php echo esc_attr($styling_options['historia_btn_bg_color']); ?>;
            --historia-btn-text-color: <?php echo esc_attr($styling_options['historia_btn_text_color']); ?>;
            --karta-btn-bg-color: <?php echo esc_attr($styling_options['karta_btn_bg_color']); ?>;
            --karta-btn-text-color: <?php echo esc_attr($styling_options['karta_btn_text_color']); ?>;
            --status-available-bg: <?php echo esc_attr($styling_options['status_available_bg_color']); ?>;
            --status-available-color: <?php echo esc_attr($styling_options['status_available_text_color']); ?>;
            --status-sold-bg: <?php echo esc_attr($styling_options['status_sold_bg_color']); ?>;
            --status-sold-color: <?php echo esc_attr($styling_options['status_sold_text_color']); ?>;
            --status-reserved-bg: <?php echo esc_attr($styling_options['status_reserved_bg_color']); ?>;
            --status-reserved-color: <?php echo esc_attr($styling_options['status_reserved_text_color']); ?>;
            --status-font-size: <?php echo esc_attr($styling_options['status_font_size']); ?>;
            --status-padding: <?php echo esc_attr($styling_options['status_padding']); ?>;
            --status-border-radius: <?php echo esc_attr($styling_options['status_border_radius']); ?>;
            --status-font-weight: <?php echo esc_attr($styling_options['status_font_weight']); ?>;
            --status-display-style: <?php echo esc_attr($styling_options['status_display_style']); ?>;
        }
        </style>
        <?php
    }
}
