<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Resource Card Renderer Helper
 * Renders resources in card/tile layout as an alternative to table view
 */
class ResourceCardRenderer {

    /**
     * Render resources as a grid of cards
     */
    public static function renderCardGrid(
        array $resources,
        array $visible_columns,
        array $column_names,
        array $styling_options = [],
        array $card_config = []
    ): string {

        if (empty($resources)) {
            return '<div class="ujc-no-resources">Brak zasobów do wyświetlenia.</div>';
        }

        // Start output buffering
        ob_start();

        // Generate dynamic CSS for cards
        self::addDynamicCSS($styling_options, $card_config);

        // Determine cards per row
        $cards_per_row = $card_config['cardsPerRow'] ?? 3;
        ?>
        <div class="resource-cards-grid cards-per-row-<?php echo esc_attr($cards_per_row); ?>">
            <?php foreach ($resources as $resource): ?>
                <?php echo self::renderSingleCard($resource, $visible_columns, $column_names, $styling_options, $card_config); ?>
            <?php endforeach; ?>
        </div>

        <!-- Price History Modal (shared with table view) -->
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
        <?php

        return ob_get_clean();
    }

    /**
     * Render a single resource card
     */
    private static function renderSingleCard(
        $resource,
        array $visible_columns,
        array $column_names,
        array $styling_options,
        array $card_config
    ): string {

        // Handle clickable cards (same as clickable rows)
        $detail_url = $card_config['detailPageUrl'] ?? '';
        $clickable_attrs = '';
        $clickable_class = '';

        if (!empty($detail_url)) {
            $clickable_class = ' clickable-card';
            $full_detail_url = rtrim($detail_url, '/') . '/' . urlencode($resource->nr_lokalu);
            $clickable_attrs = ' data-detail-url="' . esc_attr($full_detail_url) . '"';
        }

        // Build HTML using string concatenation (like renderColumnValue pattern)
        $html = '<div class="resource-card' . $clickable_class . '"' . $clickable_attrs . '>';
        $html .= '<div class="card-header">';
        $html .= '<div class="card-title-area">';
        $html .= self::renderCardTitle($resource, $visible_columns, $column_names, $styling_options);
        $html .= '</div>';
        $html .= '<div class="status-badge-fixed">';
        $html .= self::renderStatusBadge($resource, $styling_options);
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="card-body">';
        $html .= self::renderCardBody($resource, $visible_columns, $column_names, $styling_options);
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Render card title (first field from visible_columns, excluding status)
     */
    private static function renderCardTitle(
        $resource,
        array $visible_columns,
        array $column_names,
        array $styling_options
    ): string {

        // Find first non-status field for title
        $title_field = '';
        foreach ($visible_columns as $field) {
            if ($field !== ResourceDto::FIELD_STATUS) {
                $title_field = $field;
                break;
            }
        }

        if (empty($title_field)) {
            $title_field = ResourceDto::FIELD_NR_LOKALU; // Fallback
        }

        $title_value = self::getFieldValue($resource, $title_field);
        $title_label = $column_names[$title_field] ?? ucfirst(str_replace('_', ' ', $title_field));

        return '<h3 class="card-title">' . esc_html($title_value) . '</h3>';
    }

    /**
     * Render card body with remaining fields
     */
    private static function renderCardBody(
        $resource,
        array $visible_columns,
        array $column_names,
        array $styling_options
    ): string {

        $body_html = '';

        // Get body fields (exclude status and title field)
        $title_field = '';
        foreach ($visible_columns as $field) {
            if ($field !== ResourceDto::FIELD_STATUS) {
                $title_field = $field;
                break;
            }
        }

        foreach ($visible_columns as $field) {
            // Skip status (in header) and title field (already rendered)
            if ($field === ResourceDto::FIELD_STATUS || $field === $title_field) {
                continue;
            }

            $body_html .= self::renderCardField($resource, $field, $column_names, $styling_options);
        }

        return $body_html;
    }

    /**
     * Render a single field in the card
     */
    private static function renderCardField(
        $resource,
        string $field,
        array $column_names,
        array $styling_options
    ): string {

        $field_value = self::getFieldValue($resource, $field);
        $field_label = $column_names[$field] ?? ucfirst(str_replace('_', ' ', $field));

        // Handle special fields like buttons
        if ($field === ResourceTableRenderer::COLUMN_HISTORIA_CEN) {
            return self::renderHistoriaButton($resource, $styling_options);
        }

        if ($field === ResourceDto::FIELD_FLOOR_PLAN_PDF) {
            return self::renderFloorPlanButton($resource, $styling_options);
        }

        // Add semantic classes based on field type
        $field_classes = ['card-field'];

        // Add price field class for price-related fields
        if (self::isPriceField($field)) {
            $field_classes[] = 'price-field';
        }

        // Add area field class for area-related fields
        if (self::isAreaField($field)) {
            $field_classes[] = 'area-field';
        }

        $class_attr = implode(' ', $field_classes);

        // Regular field
        return '<div class="' . esc_attr($class_attr) . '">
            <span class="field-label">' . esc_html($field_label) . ':</span>
            <span class="field-value">' . esc_html($field_value) . '</span>
        </div>';
    }

    /**
     * Render status badge (same logic as table)
     */
    private static function renderStatusBadge($resource, array $styling_options): string {

        // Use same logic as ResourceTableRenderer for status rendering
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
    }

    /**
     * Render Historia Cen button
     */
    private static function renderHistoriaButton($resource, array $styling_options): string {
        $button_text = $styling_options['historia_btn_text'] ?? 'Historia';

        return '<div class="card-field card-action">
            <button class="ujc-historia-btn" data-resource-id="' . esc_attr($resource->id) . '" data-resource-name="' . esc_attr($resource->nr_lokalu) . '">
                ' . esc_html($button_text) . '
            </button>
        </div>';
    }

    /**
     * Render floor plan button
     */
    private static function renderFloorPlanButton($resource, array $styling_options): string {
        if (empty($resource->floor_plan_pdf)) {
            return '';
        }

        $filename = basename($resource->floor_plan_pdf);
        $button_text = $styling_options['karta_btn_text'] ?? 'Karta lokalu';

        return '<div class="card-field card-action">
            <button class="download-floorplan-btn ujc-karta-btn" data-filename="' . esc_attr($filename) . '">
                ' . esc_html($button_text) . '
            </button>
        </div>';
    }

    /**
     * Get field value from resource (same logic as ResourceTableRenderer)
     */
    private static function getFieldValue($resource, string $field): string {

        switch ($field) {
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
                // Hide surface area for parking spaces
                if ($resource->rodzaj_nieruchomosci === PropertyType::PARKING_SPACE->value) {
                    return '—';
                }
                if ($resource->powierzchnia_uzytkowa !== null && $resource->powierzchnia_uzytkowa > 0) {
                    return esc_html(number_format($resource->powierzchnia_uzytkowa, 2, ',', ' ')) . ' m²';
                }
                return '—';

            case ResourceDto::FIELD_STATUS:
                try {
                    $status = ResourceStatus::from($resource->status);
                    $status_text = $status->getDisplayText();
                    return esc_html($status_text);
                } catch (ValueError $e) {
                    return esc_html($resource->status);
                }

            case PriceHistoryDto::FIELD_CENA_M2:
                // Hide price per m2 for parking spaces
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

            case ResourceDto::FIELD_FLOOR_NUMBER:
                return ($resource->floor_number !== null && $resource->floor_number !== '') ? esc_html($resource->floor_number) : '—';

            case ResourceDto::FIELD_ROOM_COUNT:
                return $resource->room_count ? esc_html($resource->room_count) : '—';

            case ResourceDto::FIELD_ADDITIONAL_DESCRIPTION:
                return $resource->additional_description ? esc_html(wp_trim_words($resource->additional_description, 10)) : '—';

            case ResourceDto::FIELD_GARDEN_AREA:
                return $resource->garden_area ? esc_html(number_format($resource->garden_area, 2, ',', ' ')) . ' m²' : '—';

            case ResourceTableRenderer::COLUMN_HISTORIA_CEN:
                return ''; // Handled by renderCardField() -> renderHistoriaButton()

            case ResourceDto::FIELD_FLOOR_PLAN_PDF:
                return ''; // Handled by renderCardField() -> renderFloorPlanButton()

            default:
                return '—';
        }
    }


    /**
     * Check if field is a price-related field
     */
    private static function isPriceField(string $field): bool {
        return in_array($field, [
            PriceHistoryDto::FIELD_CENA_M2,
            PriceHistoryDto::FIELD_CENA_CALKOWITA,
            PriceHistoryDto::FIELD_CENA_Z_DODATKAMI
        ]);
    }

    /**
     * Check if field is an area-related field
     */
    private static function isAreaField(string $field): bool {
        return in_array($field, [
            ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA,
            ResourceDto::FIELD_GARDEN_AREA
        ]);
    }

    /**
     * Enqueue necessary JavaScript assets for card functionality
     */
    private static function enqueueCardAssets(bool $has_clickable_cards = false): void {
        // Always enqueue the resources widget JavaScript for modal functionality
        wp_enqueue_script(
            'jawneceny-resources-widget',
            plugins_url('assets/blocks/resources-list-widget.js', dirname(dirname(__DIR__)) . '/ustawa-jawnosci-cen.php'),
            ['jquery'],
            JAWNECENY_VERSION,
            true
        );

        // Localize script with AJAX data
        wp_localize_script('jawneceny-resources-widget', 'jawnecenyResourcesListAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('resources_list_nonce'),
            'strings' => [
                'loading' => 'Ładowanie...',
                'error' => 'Wystąpił błąd podczas ładowania historii cen.',
                'no_history' => 'Brak historii cen dla tego zasobu.'
            ]
        ]);

        // Enqueue clickable cards JavaScript if needed
        if ($has_clickable_cards) {
            wp_enqueue_script(
                'jawneceny-clickable-rows',
                plugins_url('assets/frontend-clickable-rows.js', dirname(dirname(__DIR__)) . '/ustawa-jawnosci-cen.php'),
                ['jquery'],
                '1.0.1',
                true
            );
        }
    }

    /**
     * Add dynamic CSS using WordPress wp_add_inline_style for card styling
     */
    private static function addDynamicCSS(array $styling_options, array $card_config): void {
        $css = "
        /* CSS Custom Properties for resource cards styling */
        :root {
            /* Shared variables (same as table) */
            --header-bg-color: " . esc_attr($styling_options['header_bg_color'] ?? '#f9f9f9') . ";
            --header-text-color: " . esc_attr($styling_options['header_text_color'] ?? '#333333') . ";
            --text-color: " . esc_attr($styling_options['text_color'] ?? '#333') . ";
            --hover-bg-color: " . esc_attr($styling_options['hover_bg_color'] ?? '#f5f5f5') . ";
            --header-font-family: " . esc_attr($styling_options['header_font_family'] ?? 'inherit') . ";
            --content-font-family: " . esc_attr($styling_options['content_font_family'] ?? 'inherit') . ";

            /* Status variables (shared) */
            --status-available-bg: " . esc_attr($styling_options['status_available_bg_color'] ?? '#28a745') . ";
            --status-available-color: " . esc_attr($styling_options['status_available_text_color'] ?? '#ffffff') . ";
            --status-sold-bg: " . esc_attr($styling_options['status_sold_bg_color'] ?? '#dc3545') . ";
            --status-sold-color: " . esc_attr($styling_options['status_sold_text_color'] ?? '#ffffff') . ";
            --status-reserved-bg: " . esc_attr($styling_options['status_reserved_bg_color'] ?? '#ffc107') . ";
            --status-reserved-color: " . esc_attr($styling_options['status_reserved_text_color'] ?? '#000000') . ";
            --status-font-size: " . esc_attr($styling_options['status_font_size'] ?? '0.875em') . ";
            --status-padding: " . esc_attr($styling_options['status_padding'] ?? '4px 8px') . ";
            --status-border-radius: " . esc_attr($styling_options['status_border_radius'] ?? '4px') . ";
            --status-font-weight: " . esc_attr($styling_options['status_font_weight'] ?? 'normal') . ";

            /* Button variables (shared) */
            --historia-btn-bg-color: " . esc_attr($styling_options['historia_btn_bg_color'] ?? '#007cba') . ";
            --historia-btn-text-color: " . esc_attr($styling_options['historia_btn_text_color'] ?? '#ffffff') . ";
            --karta-btn-bg-color: " . esc_attr($styling_options['karta_btn_bg_color'] ?? '#007cba') . ";
            --karta-btn-text-color: " . esc_attr($styling_options['karta_btn_text_color'] ?? '#ffffff') . ";

            /* Card-specific variables */
            --card-bg-color: " . esc_attr($card_config['cardBgColor'] ?? '#ffffff') . ";
            --card-border-color: " . esc_attr($card_config['cardBorderColor'] ?? '#e1e5e9') . ";
            --card-border-radius: " . esc_attr($card_config['cardBorderRadius'] ?? '8px') . ";
            --card-padding: " . esc_attr($card_config['cardPadding'] ?? '16px') . ";
            --card-shadow: " . esc_attr($card_config['cardShadow'] ?? '0 2px 4px rgba(0,0,0,0.1)') . ";
            --card-hover-shadow: " . esc_attr($card_config['cardHoverShadow'] ?? '0 4px 12px rgba(0,0,0,0.15)') . ";
            --card-gap: " . esc_attr($card_config['cardGap'] ?? '20px') . ";

            /* Layout & Button variables */
            --container-margin: " . esc_attr($styling_options['container_margin'] ?? '20px 0') . ";
            --button-padding: " . esc_attr($styling_options['button_padding'] ?? '6px 12px') . ";
            --button-border-radius: " . esc_attr($styling_options['button_border_radius'] ?? '4px') . ";
            --button-font-size: " . esc_attr($styling_options['button_font_size'] ?? '0.875em') . ";
            --button-font-weight: " . esc_attr($styling_options['button_font_weight'] ?? 'normal') . ";
            --button-transition: " . esc_attr($styling_options['button_transition'] ?? 'all 0.2s ease') . ";
            --button-hover-transform: " . esc_attr($styling_options['button_hover_transform'] ?? 'none') . ";
            --button-hover-box-shadow: " . esc_attr($styling_options['button_hover_box_shadow'] ?? 'none') . ";
            --button-border-width: " . esc_attr($styling_options['button_border_width'] ?? '0') . ";
            --button-border-color: " . esc_attr($styling_options['button_border_color'] ?? 'transparent') . ";

            /* Modal variables (shared) */
            --modal-overlay-color: " . esc_attr($styling_options['modal_overlay_color'] ?? 'rgba(0, 0, 0, 0.5)') . ";
            --modal-background-color: " . esc_attr($styling_options['modal_background_color'] ?? '#ffffff') . ";
            --modal-border-radius: " . esc_attr($styling_options['modal_border_radius'] ?? '8px') . ";
            --modal-max-width: " . esc_attr($styling_options['modal_max_width'] ?? '600px') . ";
            --modal-padding: " . esc_attr($styling_options['modal_padding'] ?? '20px') . ";
            --modal-box-shadow: " . esc_attr($styling_options['modal_box_shadow'] ?? '0 10px 30px rgba(0,0,0,0.3)') . ";
            --modal-header-border-color: " . esc_attr($styling_options['modal_header_border_color'] ?? '#dee2e6') . ";
            --modal-close-btn-color: " . esc_attr($styling_options['modal_close_btn_color'] ?? '#666666') . ";
            --modal-close-btn-hover-color: " . esc_attr($styling_options['modal_close_btn_hover_color'] ?? '#000000') . ";
            --modal-z-index: " . esc_attr($styling_options['modal_z_index'] ?? '10000') . ";
            --modal-animation-duration: " . esc_attr($styling_options['modal_animation_duration'] ?? '0.3s') . ";

            /* Responsive variables */
            --mobile-breakpoint: " . esc_attr($styling_options['mobile_breakpoint'] ?? '768px') . ";
            --tablet-breakpoint: " . esc_attr($styling_options['tablet_breakpoint'] ?? '1024px') . ";
            --mobile-table-font-size: " . esc_attr($styling_options['mobile_table_font_size'] ?? '0.875em') . ";
            --mobile-padding: " . esc_attr($styling_options['mobile_padding'] ?? '8px 10px') . ";
            --small-mobile-padding: " . esc_attr($styling_options['small_mobile_padding'] ?? '6px 8px') . ";

            /* Accessibility variables */
            --focus-outline-color: " . esc_attr($styling_options['focus_outline_color'] ?? '#007cba') . ";
            --focus-outline-width: " . esc_attr($styling_options['focus_outline_width'] ?? '2px') . ";
            --focus-outline-offset: " . esc_attr($styling_options['focus_outline_offset'] ?? '2px') . ";
            --high-contrast-bg-color: " . esc_attr($styling_options['high_contrast_bg_color'] ?? '#000000') . ";
            --high-contrast-text-color: " . esc_attr($styling_options['high_contrast_text_color'] ?? '#ffffff') . ";

            /* Additional table/color variables */
            --striped-rows-bg-color: " . esc_attr($styling_options['striped_rows_bg_color'] ?? '#f8f9fa') . ";
            --table-border-color: " . esc_attr($styling_options['table_border_color'] ?? '#dee2e6') . ";
        }";

        wp_add_inline_style('resource-cards-css', $css);
    }
}