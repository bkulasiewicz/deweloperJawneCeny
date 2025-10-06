<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Resource Table Renderer Helper
 * Shared logic for rendering resource tables between shortcode and Gutenberg block
 */
class ResourceTableRenderer {

    // Functional column constants
    public const COLUMN_HISTORIA_CEN = 'historia_cen';

    /**
     * Default column configuration
     */
    public static function getDefaultColumns(): array {
        return [
            ResourceDto::FIELD_NR_LOKALU,
            ResourceDto::FIELD_RODZAJ_NIERUCHOMOSCI,
            ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA,
            PriceHistoryDto::FIELD_CENA_CALKOWITA,
            ResourceDto::FIELD_STATUS,
            self::COLUMN_HISTORIA_CEN
        ];
    }

    /**
     * Get all available columns for configuration
     */
    public static function getAvailableColumns(): array {
        return [
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
    }

    /**
     * Get default column names for display
     */
    public static function getDefaultColumnNames(): array {
        return [
            ResourceDto::FIELD_NR_LOKALU => 'Numer',
            ResourceDto::FIELD_RODZAJ_NIERUCHOMOSCI => 'Typ',
            ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA => 'Powierzchnia',
            ResourceDto::FIELD_STATUS => 'Status',
            PriceHistoryDto::FIELD_CENA_M2 => 'Cena za m²',
            PriceHistoryDto::FIELD_CENA_CALKOWITA => 'Cena całkowita',
            PriceHistoryDto::FIELD_CENA_Z_DODATKAMI => 'Cena z dodatkami',
            ResourceDto::FIELD_FLOOR_NUMBER => 'Piętro',
            ResourceDto::FIELD_ROOM_COUNT => 'Pokoje',
            ResourceDto::FIELD_ADDITIONAL_DESCRIPTION => 'Opis',
            ResourceDto::FIELD_GARDEN_AREA => 'Ogród',
            ResourceDto::FIELD_FLOOR_PLAN_PDF => 'Plan',
            self::COLUMN_HISTORIA_CEN => 'Historia cen'
        ];
    }

    /**
     * Render resources table with full customization
     */
    public static function renderTable(
        array $resources,
        array $visible_columns,
        array $column_names,
        array $styling_options = [],
        string $detail_page_url = '',
        string $container_class = 'ujc-shortcode-resources-list',
        bool $enable_frontend_sorting = false
    ): string {

        if (empty($resources)) {
            return '<div class="ujc-no-resources">Brak zasobów do wyświetlenia.</div>';
        }

        // Start output buffering
        ob_start();

        // Ensure CSS is loaded
        wp_enqueue_style(
            'jawneceny-resources-list-css',
            JAWNECENY_PLUGIN_URL . 'assets/blocks/resources-list-block.css',
            [],
            JAWNECENY_VERSION
        );

        // Generate dynamic CSS
        self::addDynamicCSS($styling_options, !empty($detail_page_url));

        ?>
        <div class="<?php echo esc_attr($container_class); ?>">
            <table class="resources-table"<?php echo $enable_frontend_sorting ? ' data-sortable="true"' : ''; ?>>
                <thead>
                    <tr>
                        <?php foreach ($visible_columns as $column): ?>
                            <th<?php echo $enable_frontend_sorting ? ' data-sort-key="' . esc_attr($column) . '"' : ''; ?>>
                                <?php echo esc_html($column_names[$column] ?? ucfirst($column)); ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resources as $resource): ?>
                        <?php
                        $row_classes = [];

                        // Make row clickable if detail_page_url is provided
                        if (!empty($detail_page_url)) {
                            $row_classes[] = 'clickable-row';
                            $detail_url = rtrim($detail_page_url, '/') . '/' . urlencode($resource->nr_lokalu);
                        }
                        ?>
                        <tr<?php
                            if (!empty($row_classes)) {
                                echo ' class="' . esc_attr(implode(' ', $row_classes)) . '"';
                            }
                            if (!empty($detail_page_url)) {
                                echo ' data-detail-url="' . esc_attr($detail_url) . '"';
                            }
                        ?>>
                            <?php foreach ($visible_columns as $column): ?>
                                <td<?php echo wp_kses_post($enable_frontend_sorting ? self::getSortAttributes($resource, $column) : ''); ?>>
                                    <?php echo wp_kses_post(self::renderColumnValue($resource, $column, $styling_options)); ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Price History Modal -->
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

        return ob_get_clean();
    }

    /**
     * Get sort attributes for table cells
     */
    public static function getSortAttributes($resource, string $column): string {
        $sort_value = '';
        $sort_type = 'string';

        switch ($column) {
            case ResourceDto::FIELD_NR_LOKALU:
                $sort_value = $resource->nr_lokalu;
                $sort_type = 'alphanumeric';
                break;

            case ResourceDto::FIELD_RODZAJ_NIERUCHOMOSCI:
                try {
                    $propertyType = PropertyType::from($resource->rodzaj_nieruchomosci);
                    $sort_value = $propertyType->getDisplayText();
                } catch (ValueError $e) {
                    $sort_value = $resource->rodzaj_nieruchomosci;
                }
                $sort_type = 'string';
                break;

            case ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA:
                $sort_value = $resource->powierzchnia_uzytkowa ?? 0;
                $sort_type = 'number';
                break;

            case ResourceDto::FIELD_STATUS:
                try {
                    $status = ResourceStatus::from($resource->status);
                    $sort_value = $status->getDisplayText();
                } catch (ValueError $e) {
                    $sort_value = $resource->status;
                }
                $sort_type = 'string';
                break;

            case PriceHistoryDto::FIELD_CENA_M2:
                $sort_value = $resource->cena_m2 ?? 0;
                $sort_type = 'number';
                break;

            case PriceHistoryDto::FIELD_CENA_CALKOWITA:
                $sort_value = $resource->cena_calkowita ?? 0;
                $sort_type = 'number';
                break;

            case PriceHistoryDto::FIELD_CENA_Z_DODATKAMI:
                $sort_value = $resource->cena_z_dodatkami ?? 0;
                $sort_type = 'number';
                break;

            case ResourceDto::FIELD_FLOOR_NUMBER:
                $sort_value = $resource->floor_number ?? '';
                $sort_type = 'string';
                break;

            case ResourceDto::FIELD_ROOM_COUNT:
                $sort_value = $resource->room_count ?? 0;
                $sort_type = 'number';
                break;

            case ResourceDto::FIELD_ADDITIONAL_DESCRIPTION:
                // Description column is not sortable
                return '';

            case ResourceDto::FIELD_GARDEN_AREA:
                $sort_value = $resource->garden_area ?? 0;
                $sort_type = 'number';
                break;

            case ResourceDto::FIELD_FLOOR_PLAN_PDF:
                // Floor plan column is not sortable
                return '';

            case self::COLUMN_HISTORIA_CEN:
                // History column is not sortable
                return '';

            default:
                $sort_value = '';
                $sort_type = 'string';
                break;
        }

        return ' data-sort-key="' . esc_attr($column) . '" data-sort-value="' . esc_attr($sort_value) . '" data-sort-type="' . esc_attr($sort_type) . '"';
    }

    /**
     * Render individual column value
     */
    public static function renderColumnValue($resource, string $column, array $styling_options = []): string {
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

            case self::COLUMN_HISTORIA_CEN:
                $btn_text = !empty($styling_options['historia_btn_text']) ? $styling_options['historia_btn_text'] : 'Historia';
                $css_classes = ['ujc-historia-btn'];

                return '<button class="' . esc_attr(implode(' ', $css_classes)) . '" data-resource-id="' . esc_attr($resource->id) . '" data-resource-name="' . esc_attr($resource->nr_lokalu) . '">' . esc_html($btn_text) . '</button>';

            // Marketing fields
            case ResourceDto::FIELD_FLOOR_NUMBER:
                return ($resource->floor_number !== null && $resource->floor_number !== '') ? esc_html($resource->floor_number) : '—';

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
     * Add dynamic CSS using WordPress wp_add_inline_style
     */
    public static function addDynamicCSS(array $styling_options, bool $has_clickable_rows = false): void {
        $css = "
        /* CSS Custom Properties for resource table styling */
        :root {
            --header-bg-color: " . esc_attr($styling_options['header_bg_color'] ?? '#f9f9f9') . ";
            --header-text-color: " . esc_attr($styling_options['header_text_color'] ?? '#333333') . ";
            --text-color: " . esc_attr($styling_options['text_color'] ?? '#333333') . ";
            --hover-bg-color: " . esc_attr($styling_options['hover_bg_color'] ?? '#f5f5f5') . ";
            --header-font-family: " . esc_attr($styling_options['header_font_family'] ?? 'inherit') . ";
            --content-font-family: " . esc_attr($styling_options['content_font_family'] ?? 'inherit') . ";
            --historia-btn-bg-color: " . esc_attr($styling_options['historia_btn_bg_color'] ?? '#007cba') . ";
            --historia-btn-text-color: " . esc_attr($styling_options['historia_btn_text_color'] ?? '#ffffff') . ";
            --karta-btn-bg-color: " . esc_attr($styling_options['karta_btn_bg_color'] ?? '#007cba') . ";
            --karta-btn-text-color: " . esc_attr($styling_options['karta_btn_text_color'] ?? '#ffffff') . ";
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

            /* Layout & Spacing (Priority 1) */
            --container-margin: " . esc_attr($styling_options['container_margin'] ?? '20px 0') . ";
            --table-padding: " . esc_attr($styling_options['table_padding'] ?? '12px 15px') . ";
            --button-padding: " . esc_attr($styling_options['button_padding'] ?? '6px 12px') . ";
            --button-border-radius: " . esc_attr($styling_options['button_border_radius'] ?? '4px') . ";
            --button-font-size: " . esc_attr($styling_options['button_font_size'] ?? '0.875em') . ";

            /* Modal Options (Priority 1) */
            --modal-overlay-color: " . esc_attr($styling_options['modal_overlay_color'] ?? 'rgba(0, 0, 0, 0.5)') . ";
            --modal-background-color: " . esc_attr($styling_options['modal_background_color'] ?? '#ffffff') . ";
            --modal-border-radius: " . esc_attr($styling_options['modal_border_radius'] ?? '8px') . ";
            --modal-max-width: " . esc_attr($styling_options['modal_max_width'] ?? '600px') . ";
            --modal-padding: " . esc_attr($styling_options['modal_padding'] ?? '20px') . ";

            /* Advanced Responsive Options (Priority 2) */
            --mobile-breakpoint: " . esc_attr($styling_options['mobile_breakpoint'] ?? '768px') . ";
            --tablet-breakpoint: " . esc_attr($styling_options['tablet_breakpoint'] ?? '1024px') . ";
            --mobile-table-font-size: " . esc_attr($styling_options['mobile_table_font_size'] ?? '0.875em') . ";
            --mobile-padding: " . esc_attr($styling_options['mobile_padding'] ?? '8px 10px') . ";
            --small-mobile-padding: " . esc_attr($styling_options['small_mobile_padding'] ?? '6px 8px') . ";

            /* Advanced Table Behavior (Priority 2) */
            --table-min-width: " . esc_attr($styling_options['table_min_width'] ?? '600px') . ";
            --striped-rows-bg-color: " . esc_attr($styling_options['striped_rows_bg_color'] ?? '#f8f9fa') . ";
            --table-border-color: " . esc_attr($styling_options['table_border_color'] ?? '#dee2e6') . ";
            --table-border-width: " . esc_attr($styling_options['table_border_width'] ?? '1px') . ";

            /* Advanced Button Options (Priority 2) */
            --button-font-weight: " . esc_attr($styling_options['button_font_weight'] ?? 'normal') . ";
            --button-transition: " . esc_attr($styling_options['button_transition'] ?? 'all 0.2s ease') . ";
            --button-hover-transform: " . esc_attr($styling_options['button_hover_transform'] ?? 'none') . ";
            --button-hover-box-shadow: " . esc_attr($styling_options['button_hover_box_shadow'] ?? 'none') . ";
            --button-border-width: " . esc_attr($styling_options['button_border_width'] ?? '0') . ";
            --button-border-color: " . esc_attr($styling_options['button_border_color'] ?? 'transparent') . ";

            /* Accessibility & Focus States (Priority 2) */
            --focus-outline-color: " . esc_attr($styling_options['focus_outline_color'] ?? '#007cba') . ";
            --focus-outline-width: " . esc_attr($styling_options['focus_outline_width'] ?? '2px') . ";
            --focus-outline-offset: " . esc_attr($styling_options['focus_outline_offset'] ?? '2px') . ";
            --high-contrast-bg-color: " . esc_attr($styling_options['high_contrast_bg_color'] ?? '#000000') . ";
            --high-contrast-text-color: " . esc_attr($styling_options['high_contrast_text_color'] ?? '#ffffff') . ";

            /* Advanced Modal Options (Priority 2) */
            --modal-animation-duration: " . esc_attr($styling_options['modal_animation_duration'] ?? '0.3s') . ";
            --modal-box-shadow: " . esc_attr($styling_options['modal_box_shadow'] ?? '0 10px 30px rgba(0,0,0,0.3)') . ";
            --modal-header-border-color: " . esc_attr($styling_options['modal_header_border_color'] ?? '#dee2e6') . ";
            --modal-close-btn-color: " . esc_attr($styling_options['modal_close_btn_color'] ?? '#666666') . ";
            --modal-close-btn-hover-color: " . esc_attr($styling_options['modal_close_btn_hover_color'] ?? '#000000') . ";
            --modal-z-index: " . esc_attr($styling_options['modal_z_index'] ?? '10000') . ";
        }";

        wp_add_inline_style('jawneceny-resources-list-css', $css);
    }

}