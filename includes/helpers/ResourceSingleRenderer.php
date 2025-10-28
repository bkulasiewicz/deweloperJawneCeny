<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Resource Single Renderer Helper
 * Renders single resource detail page with configurable fields
 * Used by both shortcode and Gutenberg block
 */
class ResourceSingleRenderer {

    /**
     * Render single resource card with configurable fields
     *
     * @param SinglePageResource $resource Complete resource data
     * @param array $visible_fields Array of field names to display in order
     * @param array $field_labels Custom labels for fields ['field' => 'Label']
     * @param array $styling_options Styling configuration
     * @return string Rendered HTML
     */
    public static function renderSingleCard(
        SinglePageResource $resource,
        array $visible_fields,
        array $field_labels,
        array $styling_options
    ): string {

        // Start output buffering
        ob_start();

        // Enqueue CSS
        wp_enqueue_style(
            'jawneceny-resource-single-css',
            JAWNECENY_PLUGIN_URL . 'assets/blocks/resource-single.css',
            [],
            JAWNECENY_VERSION
        );

        // Enqueue JavaScript for modal (reuse from list)
        wp_enqueue_script(
            'jawneceny-resources-widget',
            JAWNECENY_PLUGIN_URL . 'assets/blocks/resources-list-widget.js',
            ['jquery'],
            JAWNECENY_VERSION,
            true
        );

        // Localize script (for modal)
        wp_localize_script('jawneceny-resources-widget', 'jawnecenyResourcesListAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('resources_list_nonce'),
            'strings' => [
                'loading' => 'Ładowanie...',
                'error' => 'Wystąpił błąd podczas ładowania historii cen.',
                'no_history' => 'Brak historii cen dla tego zasobu.'
            ]
        ]);

        // Generate dynamic CSS
        self::addDynamicCSS($styling_options);

        ?>
        <div class="ujc-resource-single">

            <!-- Header (always shown) -->
            <?php echo wp_kses_post(self::renderHeader($resource, $styling_options)); ?>

            <!-- Fields (in order from visible_fields) -->
            <div class="resource-single-fields">
                <?php foreach ($visible_fields as $field): ?>
                    <?php echo wp_kses_post(self::renderField($resource, $field, $field_labels, $styling_options)); ?>
                <?php endforeach; ?>
            </div>

            <!-- Action Buttons (historia_cen, floor_plan_pdf) at the end -->
            <?php echo wp_kses_post(self::renderActions($resource, $visible_fields, $styling_options)); ?>

            <!-- Price History Modal rendered globally via PriceHistoryModal helper -->

        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Render header section with nr_lokalu and status
     */
    private static function renderHeader(SinglePageResource $resource, array $styling_options): string {
        // Render status badge
        $status_badge = self::renderStatusBadge($resource, $styling_options);

        return sprintf(
            '<div class="resource-single-header">
                <h1 class="resource-title">%s</h1>
                %s
            </div>',
            esc_html($resource->nr_lokalu),
            $status_badge
        );
    }

    /**
     * Render individual field row
     */
    private static function renderField(
        SinglePageResource $resource,
        string $field,
        array $field_labels,
        array $styling_options
    ): string {

        // Skip action buttons - they are rendered separately in renderActions()
        if ($field === 'historia_cen' || $field === ResourceDto::FIELD_FLOOR_PLAN_PDF) {
            return '';
        }

        // Get field value for regular fields
        $value = self::getFieldValue($resource, $field, $styling_options);

        // Skip empty values
        if ($value === '—' || $value === '' || $value === null) {
            return '';
        }

        // Get label
        $label = $field_labels[$field] ?? self::getDefaultLabel($field);

        // Render field row
        return sprintf(
            '<div class="field-row field-%s">
                <span class="field-label">%s:</span>
                <span class="field-value">%s</span>
            </div>',
            esc_attr($field),
            esc_html($label),
            wp_kses_post($value)
        );
    }

    /**
     * Render action buttons section (historia_cen, floor_plan_pdf)
     * Buttons are always rendered at the end, matching ResourceCardRenderer pattern
     */
    private static function renderActions(
        SinglePageResource $resource,
        array $visible_fields,
        array $styling_options
    ): string {

        $buttons = [];

        // Check if historia_cen is in visible_fields
        if (in_array('historia_cen', $visible_fields)) {
            $buttons[] = self::renderHistoriaButton($resource, $styling_options);
        }

        // Check if floor_plan_pdf is in visible_fields
        if (in_array(ResourceDto::FIELD_FLOOR_PLAN_PDF, $visible_fields)) {
            $buttons[] = self::renderFloorPlanButton($resource, $styling_options);
        }

        // If no buttons, return empty
        if (empty($buttons)) {
            return '';
        }

        // Wrap buttons in actions container
        return '<div class="resource-single-actions">' . implode('', $buttons) . '</div>';
    }

    /**
     * Get field value from resource
     */
    private static function getFieldValue(SinglePageResource $resource, string $field, array $styling_options): string {

        // Reuse logic from ResourceTableRenderer for consistency
        switch ($field) {
            case ResourceDto::FIELD_NR_LOKALU:
                return esc_html($resource->nr_lokalu);

            case ResourceDto::FIELD_RODZAJ_NIERUCHOMOSCI:
                try {
                    $propertyType = PropertyType::from($resource->rodzaj_nieruchomosci);
                    return esc_html($propertyType->getDisplayText());
                } catch (\ValueError $e) {
                    return esc_html($resource->rodzaj_nieruchomosci);
                }

            case ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA:
                if ($resource->powierzchnia_uzytkowa !== null && $resource->powierzchnia_uzytkowa > 0) {
                    return esc_html(number_format($resource->powierzchnia_uzytkowa, 2, ',', ' ')) . ' m²';
                }
                return '—';

            case ResourceDto::FIELD_STATUS:
                return self::renderStatusBadge($resource, $styling_options);

            case PriceHistoryDto::FIELD_CENA_M2:
                if ($resource->cena_m2 !== null && $resource->cena_m2 > 0) {
                    return esc_html(number_format($resource->cena_m2, 2, ',', ' ')) . ' zł';
                }
                return '—';

            case PriceHistoryDto::FIELD_CENA_CALKOWITA:
                if ($resource->cena_calkowita !== null) {
                    return esc_html(number_format($resource->cena_calkowita, 2, ',', ' ')) . ' zł';
                }
                return '—';

            case PriceHistoryDto::FIELD_CENA_Z_DODATKAMI:
                if ($resource->cena_z_dodatkami !== null) {
                    return esc_html(number_format($resource->cena_z_dodatkami, 2, ',', ' ')) . ' zł';
                }
                return '—';

            case ResourceDto::FIELD_FLOOR_NUMBER:
                return ($resource->floor_number !== null && $resource->floor_number !== '') ? esc_html($resource->floor_number) : '—';

            case ResourceDto::FIELD_ROOM_COUNT:
                return $resource->room_count ? esc_html($resource->room_count) : '—';

            case ResourceDto::FIELD_ADDITIONAL_DESCRIPTION:
                return $resource->additional_description ? esc_html($resource->additional_description) : '—';

            case ResourceDto::FIELD_GARDEN_AREA:
                return $resource->garden_area ? esc_html(number_format($resource->garden_area, 2, ',', ' ')) . ' m²' : '—';

            // Property part component
            case ResourceDto::FIELD_PROPERTY_PART_TITLE:
                return $resource->property_part_title ? esc_html($resource->property_part_title) : '—';

            case ResourceDto::FIELD_PROPERTY_PART_DESIGNATION:
                return $resource->property_part_designation ? esc_html($resource->property_part_designation) : '—';

            case ResourceDto::FIELD_PROPERTY_PART_PRICE:
                return $resource->property_part_price ? esc_html(number_format($resource->property_part_price, 2, ',', ' ')) . ' zł' : '—';

            case ResourceDto::FIELD_PROPERTY_PART_PRICE_DATE:
                return $resource->property_part_price_date ? esc_html($resource->property_part_price_date) : '—';

            // Belonging room component
            case ResourceDto::FIELD_BELONGING_ROOM_TITLE:
                return $resource->belonging_room_title ? esc_html($resource->belonging_room_title) : '—';

            case ResourceDto::FIELD_BELONGING_ROOM_DESIGNATION:
                return $resource->belonging_room_designation ? esc_html($resource->belonging_room_designation) : '—';

            case ResourceDto::FIELD_BELONGING_ROOM_PRICE:
                return $resource->belonging_room_price ? esc_html(number_format($resource->belonging_room_price, 2, ',', ' ')) . ' zł' : '—';

            case ResourceDto::FIELD_BELONGING_ROOM_PRICE_DATE:
                return $resource->belonging_room_price_date ? esc_html($resource->belonging_room_price_date) : '—';

            // Usage right component
            case ResourceDto::FIELD_USAGE_RIGHT_TITLE:
                return $resource->usage_right_title ? esc_html($resource->usage_right_title) : '—';

            case ResourceDto::FIELD_USAGE_RIGHT_PRICE:
                return $resource->usage_right_price ? esc_html(number_format($resource->usage_right_price, 2, ',', ' ')) . ' zł' : '—';

            case ResourceDto::FIELD_USAGE_RIGHT_PRICE_DATE:
                return $resource->usage_right_price_date ? esc_html($resource->usage_right_price_date) : '—';

            // Other service component
            case ResourceDto::FIELD_OTHER_SERVICE_TITLE:
                return $resource->other_service_title ? esc_html($resource->other_service_title) : '—';

            case ResourceDto::FIELD_OTHER_SERVICE_PRICE:
                return $resource->other_service_price ? esc_html(number_format($resource->other_service_price, 2, ',', ' ')) . ' zł' : '—';

            case ResourceDto::FIELD_OTHER_SERVICE_PRICE_DATE:
                return $resource->other_service_price_date ? esc_html($resource->other_service_price_date) : '—';

            case 'historia_cen':
            case ResourceDto::FIELD_FLOOR_PLAN_PDF:
                return ''; // Handled separately

            default:
                return '—';
        }
    }

    /**
     * Get default label for field
     */
    private static function getDefaultLabel(string $field): string {
        $default_labels = [
            ResourceDto::FIELD_NR_LOKALU => 'Numer lokalu',
            ResourceDto::FIELD_RODZAJ_NIERUCHOMOSCI => 'Typ nieruchomości',
            ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA => 'Powierzchnia użytkowa',
            ResourceDto::FIELD_STATUS => 'Status',
            PriceHistoryDto::FIELD_CENA_M2 => 'Cena za m²',
            PriceHistoryDto::FIELD_CENA_CALKOWITA => 'Cena lokalu',
            PriceHistoryDto::FIELD_CENA_Z_DODATKAMI => 'Cena pełna',
            ResourceDto::FIELD_FLOOR_NUMBER => 'Piętro',
            ResourceDto::FIELD_ROOM_COUNT => 'Pokoje',
            ResourceDto::FIELD_ADDITIONAL_DESCRIPTION => 'Opis',
            ResourceDto::FIELD_GARDEN_AREA => 'Ogród',
            'historia_cen' => 'Historia cen',
            ResourceDto::FIELD_FLOOR_PLAN_PDF => 'Plan',
            // Property part component
            ResourceDto::FIELD_PROPERTY_PART_TITLE => 'Część nieruchomości - tytuł',
            ResourceDto::FIELD_PROPERTY_PART_DESIGNATION => 'Część nieruchomości - oznaczenie',
            ResourceDto::FIELD_PROPERTY_PART_PRICE => 'Część nieruchomości - cena',
            ResourceDto::FIELD_PROPERTY_PART_PRICE_DATE => 'Część nieruchomości - data ceny',
            // Belonging room component
            ResourceDto::FIELD_BELONGING_ROOM_TITLE => 'Pomieszczenie przynależne - tytuł',
            ResourceDto::FIELD_BELONGING_ROOM_DESIGNATION => 'Pomieszczenie przynależne - oznaczenie',
            ResourceDto::FIELD_BELONGING_ROOM_PRICE => 'Pomieszczenie przynależne - cena',
            ResourceDto::FIELD_BELONGING_ROOM_PRICE_DATE => 'Pomieszczenie przynależne - data ceny',
            // Usage right component
            ResourceDto::FIELD_USAGE_RIGHT_TITLE => 'Prawo użytkowania - tytuł',
            ResourceDto::FIELD_USAGE_RIGHT_PRICE => 'Prawo użytkowania - cena',
            ResourceDto::FIELD_USAGE_RIGHT_PRICE_DATE => 'Prawo użytkowania - data ceny',
            // Other service component
            ResourceDto::FIELD_OTHER_SERVICE_TITLE => 'Inna usługa - tytuł',
            ResourceDto::FIELD_OTHER_SERVICE_PRICE => 'Inna usługa - cena',
            ResourceDto::FIELD_OTHER_SERVICE_PRICE_DATE => 'Inna usługa - data ceny'
        ];

        return $default_labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    /**
     * Render status badge
     */
    private static function renderStatusBadge(SinglePageResource $resource, array $styling_options): string {
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
        } catch (\ValueError $e) {
            return esc_html($resource->status);
        }
    }

    /**
     * Render Historia Cen button
     */
    private static function renderHistoriaButton(SinglePageResource $resource, array $styling_options): string {
        $btn_text = $styling_options['historia_btn_text'] ?? 'Historia cen';

        return sprintf(
            '<div class="field-row field-action">
                <button class="ujc-historia-btn" data-resource-id="%d" data-resource-name="%s">%s</button>
            </div>',
            esc_attr($resource->id),
            esc_attr($resource->nr_lokalu),
            esc_html($btn_text)
        );
    }

    /**
     * Render floor plan button
     */
    private static function renderFloorPlanButton(SinglePageResource $resource, array $styling_options): string {
        if (empty($resource->floor_plan_pdf)) {
            return '';
        }

        $filename = basename($resource->floor_plan_pdf);
        $btn_text = $styling_options['karta_btn_text'] ?? 'Karta lokalu';

        return sprintf(
            '<div class="field-row field-action">
                <button class="download-floorplan-btn ujc-karta-btn" data-filename="%s">%s</button>
            </div>',
            esc_attr($filename),
            esc_html($btn_text)
        );
    }

    /**
     * Add dynamic CSS using WordPress wp_add_inline_style
     */
    private static function addDynamicCSS(array $styling_options): void {
        $css = "
        /* CSS Custom Properties for resource single page */
        :root {
            --card-bg-color: " . esc_attr($styling_options['card_bg_color'] ?? '#ffffff') . ";
            --card-border-color: " . esc_attr($styling_options['card_border_color'] ?? '#e1e5e9') . ";
            --card-border-radius: " . esc_attr($styling_options['card_border_radius'] ?? '8px') . ";
            --card-padding: " . esc_attr($styling_options['card_padding'] ?? '24px') . ";
            --text-color: " . esc_attr($styling_options['text_color'] ?? '#333333') . ";
            --label-color: " . esc_attr($styling_options['label_color'] ?? '#666666') . ";
            --label-font-weight: " . esc_attr($styling_options['label_font_weight'] ?? 'bold') . ";
            --field-spacing: " . esc_attr($styling_options['field_spacing'] ?? '12px') . ";

            /* Status styling */
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

            /* Button styling */
            --historia-btn-bg-color: " . esc_attr($styling_options['historia_btn_bg_color'] ?? '#007cba') . ";
            --historia-btn-text-color: " . esc_attr($styling_options['historia_btn_text_color'] ?? '#ffffff') . ";
            --karta-btn-bg-color: " . esc_attr($styling_options['karta_btn_bg_color'] ?? '#007cba') . ";
            --karta-btn-text-color: " . esc_attr($styling_options['karta_btn_text_color'] ?? '#ffffff') . ";
        }";

        wp_add_inline_style('jawneceny-resource-single-css', $css);
    }
}
