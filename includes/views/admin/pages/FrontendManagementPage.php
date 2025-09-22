<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frontend Management Admin Page
 * Simple shortcode generator without backend storage
 */
class FrontendManagementPage {
    
    public function __construct() {
        
        // Add AJAX handlers  
        add_action('wp_ajax_ujc_reset_frontend_settings', [$this, 'ajax_reset_settings']);
        add_action('wp_ajax_ujc_preview_shortcode', [$this, 'ajax_preview_shortcode']);
        
    }
    
    /**
     * Render the frontend management page
     */
    public function render() {
        // Use default settings for display
        $settings = $this->getDefaultSettings();
        
        ?>
        <div class="wrap">
            <div class="page-header">
                <h1>Warstwa Frontendowa</h1>
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
                <?php wp_nonce_field('ujc_frontend_settings_nonce', 'nonce'); ?>
                
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
        
        <?php $this->render_scripts_and_styles(); ?>
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
            
            echo '<div class="column-name-input" data-column="' . esc_attr($column) . '" style="' . ($is_visible ? '' : 'display: none;') . '">';
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
        check_ajax_referer('ujc_frontend_settings_nonce', 'nonce');
        
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
        check_ajax_referer('ujc_frontend_settings_nonce', 'nonce');
        
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
            echo '<div class="column-name-section" style="' . ($is_checked ? '' : 'display: none;') . '">';
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
     * Render JavaScript and CSS
     */
    private function render_scripts_and_styles() {
        ?>
        <style>
        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding: 0 5px;
        }
        
        .page-header h1 {
            margin: 0;
            font-size: 23px;
            line-height: 1.3;
        }
        
        .page-actions {
            display: flex;
            gap: 12px;
        }
        
        /* Configuration Section Grid */
        .config-section-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin: 0 0 30px 0;
        }
        
        @media (max-width: 768px) {
            .config-section-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
                padding: 0;
            }
            
            .config-section-grid {
                gap: 20px;
            }
            
            .compact-colors {
                grid-template-columns: 1fr;
            }
        }
        
        /* Color inputs grid */
        .color-inputs-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 15px 0;
        }
        
        .color-input {
            display: flex;
            flex-direction: column;
        }
        
        .color-input label {
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 13px;
        }
        
        .color-input input[type="color"] {
            width: 100%;
            height: 35px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        
        /* Font inputs grid */
        .font-inputs-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 15px 0;
        }
        
        .font-input {
            display: flex;
            flex-direction: column;
        }
        
        .font-input label {
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 13px;
        }
        
        .setting-group {
            margin-bottom: 30px;
        }
        
        .setting-group:last-child {
            margin-bottom: 0;
        }
        
        .setting-group h4 {
            margin: 0 0 18px 0;
            padding-left: 5px;
            font-size: 14px;
            font-weight: 600;
            color: #1d2327;
        }
        
        .setting-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 10px;
            color: #1d2327;
        }
        
        /* Property Types & Visible Columns Checkboxes */
        .property-types-checkboxes label,
        .visible-columns-checkboxes label {
            display: block;
            margin-bottom: 10px;
            font-weight: normal;
            font-size: 14px;
        }
        
        .property-types-checkboxes input,
        .visible-columns-checkboxes input {
            margin-right: 10px;
        }
        
        /* Dynamic Column Names */
        .dynamic-column-names {
            min-height: 200px;
        }
        
        .column-name-input {
            margin-bottom: 15px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .column-name-input label {
            display: block;
            margin-bottom: 5px;
            font-size: 13px;
        }
        
        .column-name-input input {
            width: 100%;
        }
        
        /* Shortcode Preview */
        .shortcode-preview {
            margin-top: 30px;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .shortcode-display {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        
        .shortcode-display code {
            padding: 8px 12px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-family: Monaco, Consolas, monospace;
            flex-grow: 1;
            word-break: break-all;
        }
        
        /* Compact Columns Styles */
        .compact-columns {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .compact-column-item {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 12px 0;
            border-bottom: 1px solid #e8e8e8;
        }
        
        .compact-column-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .column-checkbox-section {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 180px;
        }
        
        .column-checkbox-section input[type="checkbox"] {
            margin: 0;
            transform: scale(1.1);
        }
        
        .column-checkbox-section label {
            margin: 0;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            color: #2c3338;
        }
        
        .column-name-section {
            flex: 1;
        }
        
        .column-name-section input {
            width: 100%;
            max-width: 240px;
            font-size: 13px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #ffffff;
        }
        
        .column-name-section input:focus {
            border-color: #007cba;
            box-shadow: 0 0 0 1px #007cba;
            outline: none;
        }
        
        /* Compact Colors Styles */
        .compact-colors {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }
        
        .compact-color-item {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        
        .compact-color-item label {
            min-width: 120px;
            font-size: 13px;
            margin: 0;
            font-weight: 500;
            color: #2c3338;
            text-align: left;
            flex-shrink: 0;
        }
        
        .color-input-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }
        
        .compact-color-input {
            width: 45px;
            height: 32px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            padding: 0;
        }
        
        .compact-color-input:focus {
            border-color: #007cba;
            box-shadow: 0 0 0 1px #007cba;
            outline: none;
        }
        
        .color-hex-display {
            font-family: Monaco, Consolas, monospace;
            font-size: 12px;
            background: #f8f9fa;
            padding: 6px 10px;
            border-radius: 3px;
            border: 1px solid #e1e5e9;
            color: #555;
            min-width: 70px;
            text-align: center;
        }
        
        /* General Styles */
        .submit-section {
            margin-top: 30px;
            padding: 25px 0;
            border-top: 1px solid #ddd;
        }
        
        .submit-section .button {
            margin-right: 12px;
        }
        
        .notice.inline {
            margin: 20px 0;
        }
        
        /* Live Preview Styles */
        #live-preview-container {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 25px;
            background: #fff;
            min-height: 300px;
            margin: 0 0 30px 0;
        }
        
        #preview-loading {
            text-align: center;
            padding: 50px 20px;
            color: #666;
            font-size: 14px;
        }
        
        #preview-content .ujc-shortcode-resources-list {
            margin: 0;
        }
        
        #preview-content .resources-table {
            font-size: 12px; /* Smaller for preview */
        }
        
        #preview-content .resources-table th,
        #preview-content .resources-table td {
            padding: 6px 4px; /* Smaller padding for preview */
        }
        
        /* Shortcode Display */
        .shortcode-display {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 15px 0;
        }
        
        .shortcode-display code {
            padding: 12px 15px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: Monaco, Consolas, monospace;
            flex-grow: 1;
            word-break: break-all;
            font-size: 13px;
        }
        
        /* Instructions Modal */
        .instructions-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 100000;
        }
        
        .modal-backdrop {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            position: relative;
            max-width: 800px;
            margin: 50px auto;
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            max-height: calc(100vh - 100px);
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px 15px;
            border-bottom: 1px solid #ddd;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 20px;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
        }
        
        .modal-close:hover {
            color: #000;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .modal-body h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #0073aa;
        }
        
        .modal-body ol,
        .modal-body ul {
            margin-bottom: 20px;
        }
        
        .modal-body li {
            margin-bottom: 8px;
        }
        
        .modal-body code {
            background: #f1f1f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .modal-footer {
            padding: 15px 25px;
            border-top: 1px solid #ddd;
            text-align: right;
        }
        
        /* Prevent body scroll when modal is open */
        body.modal-open {
            overflow: hidden;
        }
        
        /* Responsive modal */
        @media (max-width: 768px) {
            .modal-content {
                margin: 20px;
                max-width: none;
                max-height: calc(100vh - 40px);
            }
            
            .color-inputs-grid,
            .font-inputs-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            
            // Update shortcode preview when settings change
            function updateShortcodePreview() {
                const selectedTypes = $('input[name="selected_types[]"]:checked').map(function() {
                    return this.value;
                }).get();
                
                // Generate columns with names in format "field:name,field2:name2" - for compact layout
                const columnsWithNames = [];
                $('.column-checkbox:checked').each(function() {
                    const fieldName = this.value;
                    const columnItem = $(this).closest('.compact-column-item');
                    const nameInput = columnItem.find('.column-name-input');
                    const customName = nameInput.val();
                    // Replace spaces with underscores to prevent WordPress from splitting arguments
                    const finalName = customName ? customName.trim().replace(/\s+/g, '_') : '';
                    const columnString = fieldName + ':' + finalName;
                    // Always include column, even with empty name (field:)
                    columnsWithNames.push(columnString);
                });
                
                // Get detail page URL
                const detailPageUrl = $('#detail-page-url').val();

                // Get styling options - support both compact and legacy selectors
                const headerBgColor = $('#header_bg_color').val() || $('#header-bg-color').val();
                const headerTextColor = $('#header_text_color').val() || $('#header-text-color').val();
                const hoverBgColor = $('#hover_bg_color').val() || $('#hover-bg-color').val();
                const textColor = $('#text_color').val() || $('#text-color').val();
                const headerFontFamily = $('#header-font-family').val();
                const contentFontFamily = $('#content-font-family').val();

                // Status styling options
                const statusDisplayStyle = $('#status-display-style').val();
                const statusAvailableBgColor = $('#status-available-bg-color').val();
                const statusAvailableTextColor = $('#status-available-text-color').val();
                const statusSoldBgColor = $('#status-sold-bg-color').val();
                const statusSoldTextColor = $('#status-sold-text-color').val();
                const statusReservedBgColor = $('#status-reserved-bg-color').val();
                const statusReservedTextColor = $('#status-reserved-text-color').val();
                const statusFontSize = $('#status-font-size').val();
                const statusPadding = $('#status-padding').val();
                const statusBorderRadius = $('#status-border-radius').val();
                const statusFontWeight = $('#status-font-weight').val();

                // Button styling options - Historia
                const historiaBtnText = $('#historia-btn-text').val();
                const historiaBtnBgColor = $('#historia-btn-bg-color').val();
                const historiaBtnTextColor = $('#historia-btn-text-color').val();

                // Button styling options - Karta Lokalu
                const kartaBtnText = $('#karta-btn-text').val();
                const kartaBtnBgColor = $('#karta-btn-bg-color').val();
                const kartaBtnTextColor = $('#karta-btn-text-color').val();

                let shortcode = '[resources_list';
                
                if (selectedTypes.length > 0) {
                    shortcode += ' types="' + selectedTypes.join(',') + '"';
                }
                
                if (columnsWithNames.length > 0) {
                    shortcode += ' columns="' + columnsWithNames.join(',') + '"';
                }

                // Add detail_page_url if provided
                if (detailPageUrl && detailPageUrl.trim() !== '') {
                    shortcode += ' detail_page_url="' + detailPageUrl.trim() + '"';
                }

                // Add styling parameters
                shortcode += ' header_bg_color="' + headerBgColor + '"';
                shortcode += ' header_text_color="' + headerTextColor + '"';
                shortcode += ' hover_bg_color="' + hoverBgColor + '"';
                shortcode += ' text_color="' + textColor + '"';
                shortcode += ' header_font_family="' + headerFontFamily + '"';
                shortcode += ' content_font_family="' + contentFontFamily + '"';

                // Add status styling parameters (only if set)
                if (statusDisplayStyle) {
                    shortcode += ' status_display_style="' + statusDisplayStyle + '"';
                }
                if (statusAvailableBgColor) {
                    shortcode += ' status_available_bg_color="' + statusAvailableBgColor + '"';
                }
                if (statusAvailableTextColor) {
                    shortcode += ' status_available_text_color="' + statusAvailableTextColor + '"';
                }
                if (statusSoldBgColor) {
                    shortcode += ' status_sold_bg_color="' + statusSoldBgColor + '"';
                }
                if (statusSoldTextColor) {
                    shortcode += ' status_sold_text_color="' + statusSoldTextColor + '"';
                }
                if (statusReservedBgColor) {
                    shortcode += ' status_reserved_bg_color="' + statusReservedBgColor + '"';
                }
                if (statusReservedTextColor) {
                    shortcode += ' status_reserved_text_color="' + statusReservedTextColor + '"';
                }
                if (statusFontSize) {
                    shortcode += ' status_font_size="' + statusFontSize + '"';
                }
                if (statusPadding) {
                    shortcode += ' status_padding="' + statusPadding + '"';
                }
                if (statusBorderRadius) {
                    shortcode += ' status_border_radius="' + statusBorderRadius + '"';
                }
                if (statusFontWeight) {
                    shortcode += ' status_font_weight="' + statusFontWeight + '"';
                }

                // Add button styling parameters - Historia
                if (historiaBtnText) {
                    shortcode += ' historia_btn_text="' + historiaBtnText + '"';
                }
                if (historiaBtnBgColor) {
                    shortcode += ' historia_btn_bg_color="' + historiaBtnBgColor + '"';
                }
                if (historiaBtnTextColor) {
                    shortcode += ' historia_btn_text_color="' + historiaBtnTextColor + '"';
                }

                // Add button styling parameters - Karta Lokalu
                if (kartaBtnText) {
                    shortcode += ' karta_btn_text="' + kartaBtnText + '"';
                }
                if (kartaBtnBgColor) {
                    shortcode += ' karta_btn_bg_color="' + kartaBtnBgColor + '"';
                }
                if (kartaBtnTextColor) {
                    shortcode += ' karta_btn_text_color="' + kartaBtnTextColor + '"';
                }

                shortcode += ']';
                
                $('#generated-shortcode').text(shortcode);
            }
            
            // Update live preview
            let previewTimeout;
            function updatePreview() {
                // Clear previous timeout
                clearTimeout(previewTimeout);
                
                // Show loading
                $('#preview-loading').show();
                $('#preview-content').hide();
                
                // Debounce AJAX call by 500ms
                previewTimeout = setTimeout(function() {
                    const shortcodeText = $('#generated-shortcode').text();
                    
                    const formData = {
                        action: 'ujc_preview_shortcode',
                        nonce: $('[name="nonce"]').val(),
                        shortcode: shortcodeText
                    };
                    
                    $.post(ajaxurl, formData)
                        .done(function(response) {
                            $('#preview-loading').hide();
                            if (response.success) {
                                $('#preview-content').html(response.data.html).show();
                            } else {
                                $('#preview-content').html('<p style="color: red;">Błąd: ' + (response.data || 'Nieznany błąd') + '</p>').show();
                            }
                        })
                        .fail(function() {
                            $('#preview-loading').hide();
                            $('#preview-content').html('<p style="color: red;">Błąd połączenia z serwerem</p>').show();
                        });
                }, 500);
            }
            
            // Toggle column name inputs based on visible columns selection - for compact layout
            function toggleColumnNameInputs() {
                $('.column-checkbox').each(function() {
                    const isChecked = $(this).is(':checked');
                    const columnItem = $(this).closest('.compact-column-item');
                    const nameSection = columnItem.find('.column-name-section');
                    
                    if (isChecked) {
                        nameSection.show();
                    } else {
                        nameSection.hide();
                    }
                });
            }
            
            // Update hex displays for compact colors
            function updateColorHexDisplays() {
                $('.compact-color-input').each(function() {
                    const colorValue = $(this).val().toUpperCase();
                    const hexDisplay = $(this).siblings('.color-hex-display');
                    hexDisplay.text(colorValue);
                });
            }
            
            // Update preview and column inputs on changes - for compact layout
            $(document).on('change', '.column-checkbox, input[name="selected_types[]"]', function() {
                updateShortcodePreview();
                toggleColumnNameInputs();
                updatePreview();
            });
            
            // Update shortcode when compact color inputs change
            $(document).on('change input', '.compact-color-input', function() {
                updateColorHexDisplays();
                updateShortcodePreview();
                updatePreview();
            });
            
            // Update shortcode when font options change
            $('#header-font-family, #content-font-family').on('change', function() {
                updateShortcodePreview();
                updatePreview();
            });

            // Update shortcode when status styling options change
            $(document).on('change input', '.status-input', function() {
                updateShortcodePreview();
                updatePreview();
            });

            // Update shortcode when button styling options change
            $(document).on('change input', '.button-input', function() {
                updateShortcodePreview();
                updatePreview();
            });
            
            // Update shortcode when column names change - for compact layout
            $(document).on('input', '.column-name-input', function() {
                updateShortcodePreview();
                updatePreview();
            });

            // Update shortcode when detail page URL changes
            $('#detail-page-url').on('input', function() {
                updateShortcodePreview();
                updatePreview();
            });
            
            // Initialize on page load
            toggleColumnNameInputs();
            updateColorHexDisplays();
            updateShortcodePreview(); // Generate shortcode first
            updatePreview(); // Load initial preview
            
            // Instructions Modal
            $('#show-instructions').on('click', function() {
                $('#instructions-modal').fadeIn(200);
                $('body').addClass('modal-open');
            });
            
            $('#close-instructions, #close-instructions-footer, .modal-backdrop').on('click', function() {
                $('#instructions-modal').fadeOut(200);
                $('body').removeClass('modal-open');
            });
            
            // Prevent modal close when clicking inside modal content
            $('.modal-content').on('click', function(e) {
                e.stopPropagation();
            });
            
            // Copy shortcode to clipboard
            $('#copy-shortcode').on('click', function() {
                const shortcode = $('#generated-shortcode').text();
                navigator.clipboard.writeText(shortcode).then(function() {
                    $(this).text('Skopiowano!').addClass('button-primary');
                    setTimeout(() => {
                        $(this).text('Kopiuj').removeClass('button-primary');
                    }, 2000);
                }.bind(this));
            });
            
            // Removed save functionality - shortcode is generated dynamically only
            
            // Reset settings
            $('#reset-settings').on('click', function() {
                if (!confirm('Czy na pewno chcesz przywrócić domyślne ustawienia? Ta operacja jest nieodwracalna.')) {
                    return;
                }
                
                const $resetBtn = $(this);
                const originalText = $resetBtn.text();
                $resetBtn.text('Resetowanie...').prop('disabled', true);
                
                $.post(ajaxurl, {
                    action: 'ujc_reset_frontend_settings',
                    nonce: $('[name="nonce"]').val()
                }, function(response) {
                    if (response.success) {
                        alert('✅ ' + response.data.message);
                        if (response.data.reload) {
                            location.reload();
                        }
                    } else {
                        alert('❌ ' + response.data);
                    }
                }).fail(function() {
                    alert('❌ Błąd połączenia');
                }).always(function() {
                    $resetBtn.text(originalText).prop('disabled', false);
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render status styling options
     */
    private function render_status_styling($settings) {
        ?>
        <div class="status-styling-section">
            <!-- Display Style -->
            <div class="setting-row">
                <label for="status-display-style">Styl wyświetlania</label>
                <select id="status-display-style" name="status_display_style" class="status-input">
                    <option value="" <?php selected($settings['status_display_style'], ''); ?>>Brak (domyślny)</option>
                    <option value="badge" <?php selected($settings['status_display_style'], 'badge'); ?>>Badge (prostokąt z ramką)</option>
                    <option value="pill" <?php selected($settings['status_display_style'], 'pill'); ?>>Pill (zaokrąglony)</option>
                    <option value="underline" <?php selected($settings['status_display_style'], 'underline'); ?>>Underline (podkreślenie)</option>
                    <option value="highlight" <?php selected($settings['status_display_style'], 'highlight'); ?>>Highlight (podświetlenie)</option>
                    <option value="plain" <?php selected($settings['status_display_style'], 'plain'); ?>>Plain (tylko tekst)</option>
                </select>
            </div>

            <!-- Status Colors -->
            <div class="setting-row">
                <label>Kolory statusów</label>
                <div class="status-colors-grid">
                    <div class="status-color-group">
                        <span class="status-label">Dostępny</span>
                        <div class="color-pair">
                            <div class="color-input-wrapper">
                                <label>Tło</label>
                                <input type="color" id="status-available-bg-color" name="status_available_bg_color" value="<?php echo esc_attr($settings['status_available_bg_color']); ?>" class="compact-color-input status-input">
                            </div>
                            <div class="color-input-wrapper">
                                <label>Tekst</label>
                                <input type="color" id="status-available-text-color" name="status_available_text_color" value="<?php echo esc_attr($settings['status_available_text_color']); ?>" class="compact-color-input status-input">
                            </div>
                        </div>
                    </div>

                    <div class="status-color-group">
                        <span class="status-label">Sprzedany</span>
                        <div class="color-pair">
                            <div class="color-input-wrapper">
                                <label>Tło</label>
                                <input type="color" id="status-sold-bg-color" name="status_sold_bg_color" value="<?php echo esc_attr($settings['status_sold_bg_color']); ?>" class="compact-color-input status-input">
                            </div>
                            <div class="color-input-wrapper">
                                <label>Tekst</label>
                                <input type="color" id="status-sold-text-color" name="status_sold_text_color" value="<?php echo esc_attr($settings['status_sold_text_color']); ?>" class="compact-color-input status-input">
                            </div>
                        </div>
                    </div>

                    <div class="status-color-group">
                        <span class="status-label">Zarezerwowany</span>
                        <div class="color-pair">
                            <div class="color-input-wrapper">
                                <label>Tło</label>
                                <input type="color" id="status-reserved-bg-color" name="status_reserved_bg_color" value="<?php echo esc_attr($settings['status_reserved_bg_color']); ?>" class="compact-color-input status-input">
                            </div>
                            <div class="color-input-wrapper">
                                <label>Tekst</label>
                                <input type="color" id="status-reserved-text-color" name="status_reserved_text_color" value="<?php echo esc_attr($settings['status_reserved_text_color']); ?>" class="compact-color-input status-input">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Sizing -->
            <div class="setting-row">
                <label>Rozmiar i padding</label>
                <div class="status-sizing-grid">
                    <div class="sizing-input">
                        <label for="status-font-size">Rozmiar czcionki</label>
                        <input type="text" id="status-font-size" name="status_font_size" value="<?php echo esc_attr($settings['status_font_size']); ?>" class="small-text status-input" placeholder="0.875em">
                    </div>
                    <div class="sizing-input">
                        <label for="status-padding">Padding</label>
                        <input type="text" id="status-padding" name="status_padding" value="<?php echo esc_attr($settings['status_padding']); ?>" class="small-text status-input" placeholder="4px 8px">
                    </div>
                    <div class="sizing-input">
                        <label for="status-border-radius">Border radius</label>
                        <input type="text" id="status-border-radius" name="status_border_radius" value="<?php echo esc_attr($settings['status_border_radius']); ?>" class="small-text status-input" placeholder="4px">
                    </div>
                    <div class="sizing-input">
                        <label for="status-font-weight">Grubość czcionki</label>
                        <select id="status-font-weight" name="status_font_weight" class="status-input">
                            <option value="" <?php selected($settings['status_font_weight'], ''); ?>>Domyślna</option>
                            <option value="300" <?php selected($settings['status_font_weight'], '300'); ?>>Light (300)</option>
                            <option value="400" <?php selected($settings['status_font_weight'], '400'); ?>>Normal (400)</option>
                            <option value="500" <?php selected($settings['status_font_weight'], '500'); ?>>Medium (500)</option>
                            <option value="600" <?php selected($settings['status_font_weight'], '600'); ?>>Semi-bold (600)</option>
                            <option value="700" <?php selected($settings['status_font_weight'], '700'); ?>>Bold (700)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .status-styling-section .setting-row {
            margin-bottom: 20px;
        }

        .status-styling-section .setting-row > label {
            display: block;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .status-colors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .status-color-group {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 12px;
            background: #f9f9f9;
        }

        .status-label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #555;
        }

        .color-pair {
            display: flex;
            gap: 8px;
        }

        .color-pair .color-input-wrapper {
            flex: 1;
        }

        .color-pair .color-input-wrapper label {
            display: block;
            font-size: 12px;
            margin-bottom: 4px;
            color: #666;
        }

        .status-sizing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .sizing-input label {
            display: block;
            font-size: 13px;
            margin-bottom: 4px;
            font-weight: 500;
        }

        .sizing-input input,
        .sizing-input select {
            width: 100%;
        }
        </style>
        <?php
    }

    /**
     * Render button styling options
     */
    private function render_button_styling($settings) {
        ?>
        <div class="button-styling-section">
            <div class="button-tabs">
                <button type="button" class="button-tab active" data-tab="historia">Przycisk Historia</button>
                <button type="button" class="button-tab" data-tab="karta">Przycisk Karta Lokalu</button>
            </div>

            <!-- Historia Button Settings -->
            <div class="button-tab-content" id="historia-tab" style="display: block;">
                <div class="button-settings-grid">
                    <div class="button-text-setting">
                        <label for="historia-btn-text">Nazwa przycisku</label>
                        <input type="text" id="historia-btn-text" name="historia_btn_text" value="<?php echo esc_attr($settings['historia_btn_text']); ?>" class="regular-text button-input" placeholder="Historia">
                    </div>


                    <div class="button-colors">
                        <div class="color-input-wrapper">
                            <label>Kolor tła</label>
                            <input type="color" id="historia-btn-bg-color" name="historia_btn_bg_color" value="<?php echo esc_attr($settings['historia_btn_bg_color']); ?>" class="compact-color-input button-input">
                        </div>
                        <div class="color-input-wrapper">
                            <label>Kolor tekstu</label>
                            <input type="color" id="historia-btn-text-color" name="historia_btn_text_color" value="<?php echo esc_attr($settings['historia_btn_text_color']); ?>" class="compact-color-input button-input">
                        </div>
                    </div>

                </div>
            </div>

            <!-- Karta Lokalu Button Settings -->
            <div class="button-tab-content" id="karta-tab" style="display: none;">
                <div class="button-settings-grid">
                    <div class="button-text-setting">
                        <label for="karta-btn-text">Nazwa przycisku</label>
                        <input type="text" id="karta-btn-text" name="karta_btn_text" value="<?php echo esc_attr($settings['karta_btn_text']); ?>" class="regular-text button-input" placeholder="Karta lokalu">
                    </div>


                    <div class="button-colors">
                        <div class="color-input-wrapper">
                            <label>Kolor tła</label>
                            <input type="color" id="karta-btn-bg-color" name="karta_btn_bg_color" value="<?php echo esc_attr($settings['karta_btn_bg_color']); ?>" class="compact-color-input button-input">
                        </div>
                        <div class="color-input-wrapper">
                            <label>Kolor tekstu</label>
                            <input type="color" id="karta-btn-text-color" name="karta_btn_text_color" value="<?php echo esc_attr($settings['karta_btn_text_color']); ?>" class="compact-color-input button-input">
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <style>
        .button-styling-section {
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
        }

        .button-tabs {
            display: flex;
            background: #f1f1f1;
            border-bottom: 1px solid #ddd;
        }

        .button-tab {
            flex: 1;
            padding: 12px 16px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #666;
            transition: all 0.2s;
        }

        .button-tab.active {
            background: #ffffff;
            color: #333;
            border-bottom: 2px solid #007cba;
        }

        .button-tab-content {
            padding: 20px;
            background: #ffffff;
        }

        .button-settings-grid {
            display: grid;
            gap: 20px;
        }

        .button-text-setting,
        .button-style-setting {
            display: grid;
            grid-template-columns: 120px 1fr;
            align-items: center;
            gap: 12px;
        }

        .button-colors {
            display: flex;
            gap: 20px;
        }

        .button-colors .color-input-wrapper {
            flex: 1;
        }

        .button-typography {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
        }

        .typo-input label {
            display: block;
            font-size: 13px;
            margin-bottom: 4px;
            font-weight: 500;
        }

        .button-spacing {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .spacing-input label {
            display: block;
            font-size: 13px;
            margin-bottom: 4px;
            font-weight: 500;
        }

        .button-settings-grid label {
            font-weight: 500;
            color: #555;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Button tab switching
            $('.button-tab').on('click', function() {
                const tabId = $(this).data('tab');

                // Update active tab
                $('.button-tab').removeClass('active');
                $(this).addClass('active');

                // Show/hide content
                $('.button-tab-content').hide();
                $('#' + tabId + '-tab').show();
            });
        });
        </script>
        <?php
    }
}