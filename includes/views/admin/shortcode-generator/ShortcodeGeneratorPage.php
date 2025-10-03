<?php

namespace JawneCeny;

use JawneCeny_AdminPage;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode Generator Admin Page
 * Simple shortcode generator without backend storage
 */
class ShortcodeGeneratorPage {

    public function __construct() {

        // Add AJAX handlers
        add_action('wp_ajax_jawneceny_reset_frontend_settings', [$this, 'ajax_reset_settings']);
        add_action('wp_ajax_jawneceny_preview_shortcode', [$this, 'ajax_preview_shortcode']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

    }

    public function enqueue_assets() {
        $viewPath = JAWNECENY_PLUGIN_URL . 'includes/views/admin/shortcode-generator/';

        wp_enqueue_style(
            'shortcode-generator-page',
            $viewPath . 'ShortcodeGeneratorPage.css',
            [],
            JAWNECENY_VERSION
        );

        wp_enqueue_script(
            'shortcode-generator-page',
            $viewPath . 'ShortcodeGeneratorPage.js',
            ['jquery'],
            JAWNECENY_VERSION,
            true
        );

        wp_localize_script('shortcode-generator-page', 'shortcodeGeneratorPageData', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('jawneceny_frontend_nonce')
        ]);
    }

    /**
     * Render the shortcode generator page
     */
    public function render() {
        // Use default settings for display
        $settings = $this->getDefaultSettings();

        ?>
        <div class="wrap shortcode-generator-page">
            <div class="page-header">
                <h1>Generator Shortcode</h1>
                <div class="page-actions">
                    <button type="button" class="button button-secondary" id="show-instructions">
                        <span class="dashicons dashicons-editor-help"></span>
                        Instrukcja
                    </button>
                    <button type="button" class="button button-secondary" id="reset-settings">
                        <span class="dashicons dashicons-undo"></span>
                        Przywróć domyślne
                    </button>
                </div>
            </div>
            
            <div class="notice notice-info">
                <p><strong>Konfiguracja widgetów frontendowych</strong></p>
                <p>Skonfiguruj sposób wyświetlania nieruchomości na stronie internetowej i wygeneruj shortcode.</p>
            </div>
            
            <form id="frontend-settings-form" method="post">
                <?php wp_nonce_field('jawneceny_frontend_settings_nonce', 'nonce'); ?>
                
                <!-- Live Preview Section -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2>Podgląd na żywo</h2>
                    </div>
                    <div class="inside">
                        <div id="live-preview-container">
                            <div id="preview-loading" style="display: none;">
                                <p>Ładowanie podglądu...</p>
                            </div>
                            <div id="preview-content">
                                <!-- Podgląd będzie wstawiany tutaj przez AJAX -->
                            </div>
                        </div>
                        <p class="description">Zobacz jak będzie wyglądać tabela na stronie. Podgląd aktualizuje się automatycznie po zmianie ustawień.</p>
                    </div>
                </div>
                
                <!-- Configuration Sections -->
                <div class="config-section-grid">
                    <!-- Basic Settings -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2>Podstawowe ustawienia</h2>
                        </div>
                        <div class="inside">
                            <div class="setting-group">
                                <label>Typy nieruchomości</label>
                                <?php $this->render_property_types_checkboxes($settings); ?>
                                <p class="description">Wybierz które typy nieruchomości mają być wyświetlane.</p>
                            </div>
                            
                            <div class="setting-group">
                                <label>Widoczne kolumny</label>
                                <?php $this->render_columns_with_names($settings); ?>
                                <p class="description">Zaznacz kolumny które mają być wyświetlane w tabeli.</p>
                            </div>

                            <div class="setting-group">
                                <label for="detail-page-url">URL strony szczegółów (opcjonalne)</label>
                                <input type="text" id="detail-page-url" name="detail_page_url" value="<?php echo esc_attr($settings['detail_page_url']); ?>" class="regular-text" placeholder="/mieszkania/">
                                <p class="description">Podaj URL gdzie mają przekierowywać kliknięte wiersze (np. /mieszkania/). Numer lokalu zostanie automatycznie dodany na końcu.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Personalization -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2>Personalizacja</h2>
                        </div>
                        <div class="inside">
                            <div class="setting-group">
                                <h4>Kolory</h4>
                                <?php $this->render_compact_colors($settings); ?>
                            </div>
                            
                            <div class="setting-group">
                                <h4>Czcionki</h4>
                                <div class="font-inputs-grid">
                                    <div class="font-input">
                                        <label for="header-font-family">Czcionka nagłówka</label>
                                        <select id="header-font-family" name="header_font_family">
                                            <option value="inherit" <?php selected($settings['header_font_family'], 'inherit'); ?>>Domyślna</option>
                                            <option value="Arial, sans-serif" <?php selected($settings['header_font_family'], 'Arial, sans-serif'); ?>>Arial</option>
                                            <option value="Helvetica, sans-serif" <?php selected($settings['header_font_family'], 'Helvetica, sans-serif'); ?>>Helvetica</option>
                                            <option value="Georgia, serif" <?php selected($settings['header_font_family'], 'Georgia, serif'); ?>>Georgia</option>
                                            <option value="Times New Roman, serif" <?php selected($settings['header_font_family'], 'Times New Roman, serif'); ?>>Times</option>
                                        </select>
                                    </div>

                                    <div class="font-input">
                                        <label for="content-font-family">Czcionka treści</label>
                                        <select id="content-font-family" name="content_font_family">
                                            <option value="inherit" <?php selected($settings['content_font_family'], 'inherit'); ?>>Domyślna</option>
                                            <option value="Arial, sans-serif" <?php selected($settings['content_font_family'], 'Arial, sans-serif'); ?>>Arial</option>
                                            <option value="Helvetica, sans-serif" <?php selected($settings['content_font_family'], 'Helvetica, sans-serif'); ?>>Helvetica</option>
                                            <option value="Georgia, serif" <?php selected($settings['content_font_family'], 'Georgia, serif'); ?>>Georgia</option>
                                            <option value="Times New Roman, serif" <?php selected($settings['content_font_family'], 'Times New Roman, serif'); ?>>Times</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="setting-group">
                                <h4>Stylizacja statusów</h4>
                                <?php $this->render_status_styling($settings); ?>
                            </div>

                            <div class="setting-group">
                                <h4>Stylizacja przycisków</h4>
                                <?php $this->render_button_styling($settings); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Generated Shortcode Section -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2>Wygenerowany shortcode</h2>
                    </div>
                    <div class="inside">
                        <div class="shortcode-display">
                            <code id="generated-shortcode"><?php echo esc_html($this->generateShortcode($settings)); ?></code>
                            <button type="button" class="button button-primary" id="copy-shortcode">
                                <span class="dashicons dashicons-admin-page"></span>
                                Kopiuj shortcode
                            </button>
                        </div>
                        <p class="description">Skopiuj powyższy kod i wklej go tam gdzie chcesz wyświetlić tabelę z nieruchomościami.</p>
                    </div>
                </div>
                
            </form>
        </div>
        
        <!-- Instructions Modal -->
        <div id="instructions-modal" class="instructions-modal" style="display: none;">
            <div class="modal-backdrop"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Instrukcje użycia</h2>
                    <button class="modal-close" id="close-instructions">&times;</button>
                </div>
                <div class="modal-body">
                    <h3>Jak dodać widget na stronę:</h3>
                    <ol>
                        <li><strong>W edytorze Gutenberg:</strong> Dodaj blok "Shortcode" i wklej wygenerowany kod</li>
                        <li><strong>W widżetach:</strong> Dodaj widget "Tekst" lub "HTML" i wklej shortcode</li>
                        <li><strong>W szablonie PHP:</strong> Użyj <code>&lt;?php echo do_shortcode('[shortcode_tutaj]'); ?&gt;</code></li>
                    </ol>
                    
                    <h3>Przykłady użycia:</h3>
                    <ul>
                        <li><code>[resources_list types="residential_unit"]</code> - tylko mieszkania</li>
                        <li><code>[resources_list types="parking_space"]</code> - tylko miejsca postojowe</li>
                        <li><code>[resources_list types="residential_unit,parking_space"]</code> - mieszkania i miejsca postojowe</li>
                    </ul>

                    <h3>Klikalne wiersze (opcjonalne):</h3>
                    <p>Dodaj parametr <code>detail_page_url</code> aby wiersze tabeli były klikalne:</p>
                    <ul>
                        <li><code>[resources_list detail_page_url="/mieszkania/"]</code> - kliknięcie na lokal A1 przekieruje do <code>/mieszkania/A1</code></li>
                        <li><code>[resources_list detail_page_url="/nieruchomosci/" types="parking_space"]</code> - miejsca postojowe z linkami</li>
                        <li><code>[resources_list detail_page_url="/lokale/" types="residential_unit,parking_space"]</code> - wszystkie typy z linkami</li>
                    </ul>
                    <p><strong>Uwagi:</strong></p>
                    <ul>
                        <li>Numer lokalu jest automatycznie dodawany do URL (np. A1, MP-15)</li>
                        <li>Kliknięcie otwiera nową kartę przeglądarki</li>
                        <li>Przyciski "Historia" i "Karta lokalu" nadal działają normalnie</li>
                        <li>Bez tego parametru wiersze nie są klikalne</li>
                    </ul>
                    
                    <div class="notice notice-warning inline">
                        <p><strong>Uwaga:</strong> Po skopiowaniu shortcode sprawdź efekt na stronie. Zmiany stylów będą widoczne natychmiast.</p>
                    </div>
                    
                    <h3>Wskazówki:</h3>
                    <ul>
                        <li>Użyj podglądu na żywo aby zobaczyć jak będzie wyglądać tabela</li>
                        <li>Dostosuj kolory do swojego motywu WordPress</li>
                        <li>Możesz pozostawić puste nazwy kolumn - wtedy nie będą miały nagłówków</li>
                        <li><strong>Kolejność kolumn w shortcode określa kolejność wyświetlania w tabeli</strong></li>
                        <li>Przycisk "Historia cen" działa tylko gdy są dostępne dane historyczne</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button button-primary" id="close-instructions-footer">Rozumiem</button>
                </div>
            </div>
        </div>

        <?php
    }
    
    /**
     * Render property types checkboxes
     */
    private function render_property_types_checkboxes(array $settings) {
        $selected_values = array_map(fn($type) => $type->value, $settings['selected_types']);
        
        echo '<div class="property-types-checkboxes">';
        foreach (PropertyType::cases() as $type) {
            echo '<label>';
            echo '<input type="checkbox" name="selected_types[]" value="' . esc_attr($type->value) . '" ' . checked(in_array($type->value, $selected_values), true, false) . '>';
            echo '<span>' . esc_html($type->getDisplayText()) . '</span>';
            echo '</label><br>';
        }
        echo '</div>';
    }
    
    /**
     * Render visible columns checkboxes
     */
    private function render_visible_columns_checkboxes(array $settings) {
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
            ResourcesListShortcode::COLUMN_HISTORIA_CEN
        ];
        
        echo '<div class="visible-columns-checkboxes">';
        foreach ($available_columns as $column) {
            $display_name = $settings['column_names'][$column] ?? ucfirst($column);

            echo '<label>';
            echo '<input type="checkbox" name="visible_columns[]" value="' . esc_attr($column) . '" ' . checked(in_array($column, $settings['visible_columns']), true, false) . '>';
            echo '<span>' . esc_html($display_name) . '</span>';
            echo '</label><br>';
        }
        echo '</div>';
    }
    
    /**
     * Render column names input fields (original - for old section)
     */
    private function render_column_names_inputs(array $settings) {
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
            ResourcesListShortcode::COLUMN_HISTORIA_CEN
        ];
        
        foreach ($available_columns as $column) {
            $current_name = $settings['column_names'][$column] ?? ucfirst($column);
            
            echo '<tr>';
            echo '<th scope="row">';
            echo '<label for="column_name_' . esc_attr($column) . '">' . esc_html(ucfirst($column)) . '</label>';
            echo '</th>';
            echo '<td>';
            echo '<input type="text" id="column_name_' . esc_attr($column) . '" name="column_names[' . esc_attr($column) . ']" value="' . esc_attr($current_name) . '" class="regular-text">';
            echo '</td>';
            echo '</tr>';
        }
    }
    
    /**
     * Render dynamic column names inputs - only for selected columns
     */
    private function render_dynamic_column_names_inputs(array $settings) {
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
            ResourcesListShortcode::COLUMN_HISTORIA_CEN
        ];
        $default_names = $this->getDefaultColumnNames();
        
        echo '<div class="dynamic-column-names">';
        
        foreach ($available_columns as $column) {
            $current_name = $settings['column_names'][$column] ?? ($default_names[$column] ?? ucfirst($column));
            $is_visible = in_array($column, $settings['visible_columns']);
            $display_name = $default_names[$column] ?? ucfirst($column);
            
            echo '<div class="column-name-input" data-column="' . esc_attr($column) . '" style="' . esc_attr($is_visible ? '' : 'display: none;') . '">';
            echo '<label for="dynamic_column_name_' . esc_attr($column) . '">Nazwa dla kolumny: <strong>' . esc_html($display_name) . '</strong></label>';
            echo '<input type="text" id="dynamic_column_name_' . esc_attr($column) . '" name="column_names[' . esc_attr($column) . ']" value="' . esc_attr($current_name) . '" class="regular-text" placeholder="Wprowadź własną nazwę">';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    
    /**
     * AJAX handler for resetting settings
     */
    public function ajax_reset_settings() {
        check_ajax_referer('jawneceny_frontend_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
        }
        
        // Reset to defaults by reloading page with default values
        wp_send_json_success([
            'message' => 'Ustawienia zostały zresetowane do domyślnych',
            'reload' => true
        ]);
    }
    
    /**
     * AJAX handler for live preview using generated shortcode
     */
    public function ajax_preview_shortcode() {
        check_ajax_referer('jawneceny_frontend_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
        }
        
        try {
            // Get shortcode from POST - no sanitization needed as shortcode will be parsed by WordPress
            $shortcode = $_POST['shortcode'] ?? '';
            
            Logger::info('Preview shortcode: ' . $shortcode);
            
            if (empty($shortcode)) {
                Logger::error('Preview shortcode is empty');
                wp_send_json_error('Brak shortcode do podglądu');
                return;
            }
            
            // Parse shortcode manually for preview to preserve colons
            Logger::info('Parsing shortcode manually for preview: ' . $shortcode);
            
            // Remove escape slashes added by WordPress
            $shortcode = stripslashes($shortcode);
            Logger::info('After stripslashes: ' . $shortcode);
            
            $attrs = [];
            
            // Extract attributes using regex that handles double quotes
            if (preg_match_all('/(\w+)="([^"]*)"/', $shortcode, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $attrs[$match[1]] = $match[2];
                }
            }
            
            Logger::info('Manually parsed attributes for preview: ' . print_r($attrs, true));
            
            // Call ResourcesListShortcode::handle() directly for preview
            $preview_html = ResourcesListShortcode::handle($attrs);
            Logger::info('Preview result length: ' . strlen($preview_html));
            Logger::info('Preview result content: ' . $preview_html);
            
            wp_send_json_success(['html' => $preview_html]);
            
        } catch (Exception $e) {
            Logger::error('Preview error: ' . $e->getMessage());
            wp_send_json_error('Błąd podczas generowania podglądu');
        }
    }
    
    /**
     * Get default settings for the form
     */
    private function getDefaultSettings() {
        return [
            'selected_types' => [PropertyType::RESIDENTIAL_UNIT],
            'visible_columns' => [
                ResourceDto::FIELD_NR_LOKALU,
                ResourceDto::FIELD_RODZAJ_NIERUCHOMOSCI,
                ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA,
                PriceHistoryDto::FIELD_CENA_CALKOWITA,
                ResourceDto::FIELD_STATUS,
                ResourcesListShortcode::COLUMN_HISTORIA_CEN
            ],
            'column_names' => $this->getDefaultColumnNames(),
            'detail_page_url' => '',
            // Styling options
            'header_bg_color' => '#f9f9f9',
            'header_text_color' => '#333333',
            'hover_bg_color' => '#f5f5f5',
            'text_color' => '#333333',
            'header_font_family' => 'inherit',
            'content_font_family' => 'inherit',
            // Status styling options
            'status_available_bg_color' => '#28a745',
            'status_available_text_color' => '#ffffff',
            'status_sold_bg_color' => '#dc3545',
            'status_sold_text_color' => '#ffffff',
            'status_reserved_bg_color' => '#ffc107',
            'status_reserved_text_color' => '#000000',
            'status_display_style' => 'badge',
            'status_font_size' => '0.875em',
            'status_padding' => '4px 8px',
            'status_border_radius' => '4px',
            'status_font_weight' => '500',

            // Button styling defaults
            'historia_btn_text' => 'Historia',
            'historia_btn_bg_color' => '#007cba',
            'historia_btn_text_color' => '#ffffff',

            'karta_btn_text' => 'Karta lokalu',
            'karta_btn_bg_color' => '#007cba',
            'karta_btn_text_color' => '#ffffff',
        ];
    }
    
    /**
     * Get default column names for display
     */
    private function getDefaultColumnNames() {
        return [
            ResourceDto::FIELD_NR_LOKALU => 'Numer',
            ResourceDto::FIELD_RODZAJ_NIERUCHOMOSCI => 'Typ',
            ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA => 'Powierzchnia',
            ResourceDto::FIELD_STATUS => 'Status',
            PriceHistoryDto::FIELD_CENA_M2 => 'Cena za m²',
            PriceHistoryDto::FIELD_CENA_CALKOWITA => 'Cena lokalu',
            PriceHistoryDto::FIELD_CENA_Z_DODATKAMI => 'Cena pełna',
            ResourceDto::FIELD_FLOOR_NUMBER => 'Piętro',
            ResourceDto::FIELD_ROOM_COUNT => 'Liczba pokoi',
            ResourceDto::FIELD_ADDITIONAL_DESCRIPTION => 'Opis',
            ResourceDto::FIELD_GARDEN_AREA => 'Ogród',
            ResourceDto::FIELD_FLOOR_PLAN_PDF => 'Plan',
            ResourcesListShortcode::COLUMN_HISTORIA_CEN => 'Historia cen'
        ];
    }
    
    /**
     * Generate shortcode string based on current settings
     */
    private function generateShortcode(array $settings): string {
        $types_string = implode(',', array_map(fn($type) => $type->value, $settings['selected_types']));

        // Generate columns with names in format "field:name,field2:name2"
        $columns_with_names = [];
        foreach ($settings['visible_columns'] as $column) {
            $custom_name = trim($settings['column_names'][$column] ?? '');
            // Always include column, even with empty name (field:)
            $columns_with_names[] = "{$column}:{$custom_name}";
        }
        $columns_string = implode(',', $columns_with_names);

        $shortcode = "[resources_list types=\"{$types_string}\"";

        if (!empty($columns_string)) {
            $shortcode .= " columns=\"{$columns_string}\"";
        }

        // Add detail_page_url if provided
        if (!empty($settings['detail_page_url'])) {
            $shortcode .= " detail_page_url=\"{$settings['detail_page_url']}\"";
        }

        // Add styling parameters
        $shortcode .= " header_bg_color=\"{$settings['header_bg_color']}\"";
        $shortcode .= " header_text_color=\"{$settings['header_text_color']}\"";
        $shortcode .= " hover_bg_color=\"{$settings['hover_bg_color']}\"";
        $shortcode .= " text_color=\"{$settings['text_color']}\"";
        $shortcode .= " header_font_family=\"{$settings['header_font_family']}\"";
        $shortcode .= " content_font_family=\"{$settings['content_font_family']}\"";

        $shortcode .= "]";

        return $shortcode;
    }
    
    /**
     * Render columns with inline name inputs - compact version
     */
    private function render_columns_with_names(array $settings) {
        $available_columns = [
            ResourceDto::FIELD_NR_LOKALU,
            ResourceDto::FIELD_RODZAJ_NIERUCHOMOSCI,
            ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA,
            ResourceDto::FIELD_STATUS,
            PriceHistoryDto::FIELD_CENA_M2,
            PriceHistoryDto::FIELD_CENA_CALKOWITA,
            PriceHistoryDto::FIELD_CENA_Z_DODATKAMI,
            ResourceDto::FIELD_FLOOR_NUMBER,
            ResourceDto::FIELD_ROOM_COUNT,
            ResourceDto::FIELD_ADDITIONAL_DESCRIPTION,
            ResourceDto::FIELD_GARDEN_AREA,
            ResourceDto::FIELD_FLOOR_PLAN_PDF,
            ResourcesListShortcode::COLUMN_HISTORIA_CEN
        ];
        
        $default_names = $this->getDefaultColumnNames();
        
        echo '<div class="compact-columns">';
        
        foreach ($available_columns as $column) {
            $is_checked = in_array($column, $settings['visible_columns']);
            $display_name = $default_names[$column] ?? ucfirst($column);
            $current_name = $settings['column_names'][$column] ?? ($default_names[$column] ?? ucfirst($column));
            
            echo '<div class="compact-column-item">';
            echo '<div class="column-checkbox-section">';
            echo '<input type="checkbox" name="visible_columns[]" value="' . esc_attr($column) . '" id="column_' . esc_attr($column) . '"' . checked($is_checked, true, false) . ' class="column-checkbox">';
            echo '<label for="column_' . esc_attr($column) . '">' . esc_html($display_name) . '</label>';
            echo '</div>';
            echo '<div class="column-name-section" style="' . esc_attr($is_checked ? '' : 'display: none;') . '">';
            echo '<input type="text" name="column_names[' . esc_attr($column) . ']" value="' . esc_attr($current_name) . '" placeholder="Nazwa kolumny" class="regular-text column-name-input" data-column="' . esc_attr($column) . '">';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render compact color selection with visual preview
     */
    private function render_compact_colors(array $settings) {
        $color_options = [
            'header_bg_color' => 'Tło nagłówka',
            'header_text_color' => 'Tekst nagłówka',
            'hover_bg_color' => 'Hover wierszy',
            'text_color' => 'Kolor tekstu'
        ];
        
        echo '<div class="compact-colors">';
        
        foreach ($color_options as $field => $label) {
            $color_value = $settings[$field];
            echo '<div class="compact-color-item">';
            echo '<label for="' . esc_attr($field) . '">' . esc_html($label) . '</label>';
            echo '<div class="color-input-wrapper">';
            echo '<input type="color" id="' . esc_attr($field) . '" name="' . esc_attr($field) . '" value="' . esc_attr($color_value) . '" class="compact-color-input">';
            echo '<span class="color-hex-display">' . esc_html(strtoupper($color_value)) . '</span>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
    }

    /**
     * Render status styling options
     */
    private function render_status_styling(array $settings) {
        $status_options = [
            'status_available_bg_color' => 'Tło "Dostępne"',
            'status_available_text_color' => 'Tekst "Dostępne"',
            'status_sold_bg_color' => 'Tło "Sprzedane"',
            'status_sold_text_color' => 'Tekst "Sprzedane"',
            'status_reserved_bg_color' => 'Tło "Zarezerwowane"',
            'status_reserved_text_color' => 'Tekst "Zarezerwowane"'
        ];

        echo '<div class="status-styling-options">';
        foreach ($status_options as $field => $label) {
            $color_value = $settings[$field] ?? '#007cba';
            echo '<div class="status-color-item">';
            echo '<label for="' . esc_attr($field) . '">' . esc_html($label) . '</label>';
            echo '<div class="color-input-wrapper">';
            echo '<input type="color" id="' . esc_attr($field) . '" name="' . esc_attr($field) . '" value="' . esc_attr($color_value) . '" class="compact-color-input">';
            echo '<span class="color-hex-display">' . esc_html(strtoupper($color_value)) . '</span>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * Render button styling options
     */
    private function render_button_styling(array $settings) {
        echo '<div class="button-styling-options">';

        // Historia button styling
        echo '<div class="button-group">';
        echo '<h5>Przycisk "Historia cen"</h5>';
        echo '<div class="button-styling-row">';

        echo '<div class="button-text-input">';
        echo '<label for="historia_btn_text">Tekst przycisku</label>';
        echo '<input type="text" id="historia_btn_text" name="historia_btn_text" value="' . esc_attr($settings['historia_btn_text'] ?? 'Historia') . '" class="regular-text">';
        echo '</div>';

        echo '<div class="button-color-inputs">';
        echo '<label for="historia_btn_bg_color">Kolor tła</label>';
        echo '<input type="color" id="historia_btn_bg_color" name="historia_btn_bg_color" value="' . esc_attr($settings['historia_btn_bg_color'] ?? '#007cba') . '">';
        echo '</div>';

        echo '<div class="button-color-inputs">';
        echo '<label for="historia_btn_text_color">Kolor tekstu</label>';
        echo '<input type="color" id="historia_btn_text_color" name="historia_btn_text_color" value="' . esc_attr($settings['historia_btn_text_color'] ?? '#ffffff') . '">';
        echo '</div>';

        echo '</div>';
        echo '</div>';

        // Karta button styling
        echo '<div class="button-group">';
        echo '<h5>Przycisk "Karta lokalu"</h5>';
        echo '<div class="button-styling-row">';

        echo '<div class="button-text-input">';
        echo '<label for="karta_btn_text">Tekst przycisku</label>';
        echo '<input type="text" id="karta_btn_text" name="karta_btn_text" value="' . esc_attr($settings['karta_btn_text'] ?? 'Karta lokalu') . '" class="regular-text">';
        echo '</div>';

        echo '<div class="button-color-inputs">';
        echo '<label for="karta_btn_bg_color">Kolor tła</label>';
        echo '<input type="color" id="karta_btn_bg_color" name="karta_btn_bg_color" value="' . esc_attr($settings['karta_btn_bg_color'] ?? '#007cba') . '">';
        echo '</div>';

        echo '<div class="button-color-inputs">';
        echo '<label for="karta_btn_text_color">Kolor tekstu</label>';
        echo '<input type="color" id="karta_btn_text_color" name="karta_btn_text_color" value="' . esc_attr($settings['karta_btn_text_color'] ?? '#ffffff') . '">';
        echo '</div>';

        echo '</div>';
        echo '</div>';

        echo '</div>';
    }
}
