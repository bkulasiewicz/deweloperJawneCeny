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

    private GetAllResourcesUseCase $getAllResourcesUseCase;

    public function __construct(GetAllResourcesUseCase $getAllResourcesUseCase) {
        $this->getAllResourcesUseCase = $getAllResourcesUseCase;

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

        // Enqueue jQuery UI Sortable for drag & drop
        wp_enqueue_script('jquery-ui-sortable');

        wp_enqueue_script(
            'shortcode-generator-page',
            $viewPath . 'ShortcodeGeneratorPage.js',
            ['jquery', 'jquery-ui-sortable'],
            JAWNECENY_VERSION,
            true
        );

        wp_localize_script('shortcode-generator-page', 'jawnecenyShortcodeGeneratorPageData', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('jawneceny_frontend_settings_nonce')
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

            <!-- Tab Navigation -->
            <h2 class="nav-tab-wrapper">
                <a href="#" class="nav-tab nav-tab-active" data-tab="resources-list">Lista zasobów</a>
                <a href="#" class="nav-tab" data-tab="resource-single">Szczegóły zasobu</a>
            </h2>

            <form id="frontend-settings-form" method="post">
                <?php wp_nonce_field('jawneceny_frontend_settings_nonce', 'nonce'); ?>

                <!-- Tab Content: Resources List -->
                <div class="tab-content tab-content-list active" data-tab-content="resources-list">

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
                    <div class="left-column">
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
                                    <label>Sposób wyświetlania</label>
                                    <div>
                                        <label style="display: block; margin-bottom: 8px;">
                                            <input type="radio" name="display_mode" value="table" <?php checked($settings['display_mode'] ?? 'table', 'table'); ?>>
                                            Tabela (lista wierszy)
                                        </label>
                                        <label style="display: block;">
                                            <input type="radio" name="display_mode" value="cards" <?php checked($settings['display_mode'] ?? 'table', 'cards'); ?>>
                                            Kafelki (siatka kart)
                                        </label>
                                    </div>

                                    <!-- Mobile Switch Checkbox -->
                                    <div style="margin-top: 12px; padding: 12px; background: #f0f6fc; border-left: 3px solid #2271b1; border-radius: 4px;">
                                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                            <input type="checkbox"
                                                   id="mobile-switch-to-cards"
                                                   name="mobile_switch_to_cards"
                                                   value="1"
                                                   <?php checked($settings['mobile_switch_to_cards'] ?? false, true); ?>>
                                            <span style="font-weight: 500;">📱 Automatycznie przełącz na kafelki na mobile</span>
                                        </label>
                                        <p class="description" style="margin: 8px 0 0 28px; font-size: 12px; color: #666;">
                                            Na ekranach &lt;768px tabela automatycznie zamieni się w kafelki dla lepszej czytelności.
                                        </p>
                                    </div>

                                    <p class="description">Wybierz sposób wyświetlania zasobów na stronie.</p>
                                </div>

                                <div class="setting-group">
                                    <label>Widoczne kolumny</label>
                                    <?php $this->render_columns_with_names($settings); ?>
                                    <p class="description">Zaznacz kolumny które mają być wyświetlane w tabeli.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="right-column">
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

                    <!-- Nawigacja do szczegółów -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2>Nawigacja do szczegółów</h2>
                        </div>
                        <div class="inside">
                            <div class="setting-group">
                                <label>Sposób nawigacji</label>
                                <div>
                                    <label style="display: block; margin-bottom: 8px;">
                                        <input type="radio" name="navigation_mode" value="" <?php checked($settings['navigation_mode'], ''); ?>>
                                        Brak nawigacji
                                    </label>
                                    <label style="display: block; margin-bottom: 8px;">
                                        <input type="radio" name="navigation_mode" value="clickable" <?php checked($settings['navigation_mode'], 'clickable'); ?>>
                                        Klikalne wiersze/karty
                                    </label>
                                    <label style="display: block;">
                                        <input type="radio" name="navigation_mode" value="button" <?php checked($settings['navigation_mode'], 'button'); ?>>
                                        Przycisk "Zobacz więcej"
                                    </label>
                                </div>
                                <p class="description">Wybierz sposób nawigacji do strony szczegółów zasobu. Numer lokalu zostanie dodany automatycznie do aktualnego URL.</p>
                            </div>

                            <div class="setting-group" id="button-config-group" style="display: <?php echo ($settings['navigation_mode'] ?? '') === 'button' ? 'block' : 'none'; ?>;">
                                <h4>Konfiguracja przycisku "Zobacz więcej"</h4>
                                <div class="button-inputs-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div>
                                        <label for="zobacz-btn-text">Tekst przycisku</label>
                                        <input type="text" id="zobacz-btn-text" name="zobacz_btn_text" value="<?php echo esc_attr($settings['zobacz_btn_text'] ?? 'Zobacz więcej'); ?>" class="regular-text">
                                    </div>
                                    <div>
                                        <label for="zobacz-btn-bg-color">Kolor tła</label>
                                        <input type="color" id="zobacz-btn-bg-color" name="zobacz_btn_bg_color" value="<?php echo esc_attr($settings['zobacz_btn_bg_color'] ?? '#007cba'); ?>" class="compact-color-input">
                                        <span class="color-hex-display"><?php echo esc_html(strtoupper($settings['zobacz_btn_bg_color'] ?? '#007CBA')); ?></span>
                                    </div>
                                    <div>
                                        <label for="zobacz-btn-text-color">Kolor tekstu</label>
                                        <input type="color" id="zobacz-btn-text-color" name="zobacz_btn_text_color" value="<?php echo esc_attr($settings['zobacz_btn_text_color'] ?? '#ffffff'); ?>" class="compact-color-input">
                                        <span class="color-hex-display"><?php echo esc_html(strtoupper($settings['zobacz_btn_text_color'] ?? '#FFFFFF')); ?></span>
                                    </div>
                                    <div>
                                        <label for="zobacz-btn-padding">Padding</label>
                                        <input type="text" id="zobacz-btn-padding" name="zobacz_btn_padding" value="<?php echo esc_attr($settings['zobacz_btn_padding'] ?? '6px 12px'); ?>" class="regular-text" placeholder="6px 12px">
                                        <p class="description">Odstęp wewnętrzny (np. 6px 12px)</p>
                                    </div>
                                    <div>
                                        <label for="zobacz-btn-border-radius">Border radius</label>
                                        <input type="text" id="zobacz-btn-border-radius" name="zobacz_btn_border_radius" value="<?php echo esc_attr($settings['zobacz_btn_border_radius'] ?? '4px'); ?>" class="regular-text" placeholder="4px">
                                        <p class="description">Zaokrąglenie rogów (np. 4px)</p>
                                    </div>
                                    <div>
                                        <label for="zobacz-btn-font-size">Rozmiar czcionki</label>
                                        <input type="text" id="zobacz-btn-font-size" name="zobacz_btn_font_size" value="<?php echo esc_attr($settings['zobacz_btn_font_size'] ?? '0.875em'); ?>" class="regular-text" placeholder="0.875em">
                                        <p class="description">Rozmiar tekstu (np. 0.875em lub 14px)</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Konfiguracja kafelków -->
                    <div class="postbox" id="card-config-section" style="display: <?php echo (($settings['display_mode'] ?? 'table') === 'cards' || ($settings['mobile_switch_to_cards'] ?? false)) ? 'block' : 'none'; ?>;">
                        <div class="postbox-header">
                            <h2>Konfiguracja kafelków</h2>
                        </div>
                        <div class="inside">
                            <p class="description" style="margin-bottom: 16px; padding: 8px 12px; background: #fef7e0; border-left: 3px solid #f0b429; font-size: 13px;">
                                💡 <strong>Te ustawienia są używane gdy:</strong><br>
                                • Wybrano tryb "Kafelki" (desktop i mobile)<br>
                                • LUB włączono automatyczne przełączanie na mobile
                            </p>
                            <div class="setting-group">
                                <h4>Układ siatki</h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div>
                                        <label for="card-size">Rozmiar kafelków</label>
                                        <select id="card-size" name="card_size">
                                            <option value="small" <?php selected($settings['card_size'] ?? 'normal', 'small'); ?>>Małe (więcej kolumn)</option>
                                            <option value="normal" <?php selected($settings['card_size'] ?? 'normal', 'normal'); ?>>Normalne</option>
                                            <option value="large" <?php selected($settings['card_size'] ?? 'normal', 'large'); ?>>Duże (mniej kolumn)</option>
                                        </select>
                                        <p class="description" style="margin-top: 4px; font-size: 12px; color: #666;">
                                            💡 Liczba kolumn dostosowuje się automatycznie do szerokości ekranu
                                        </p>
                                    </div>
                                    <div>
                                        <label for="card-gap">Odstęp między kafelkami</label>
                                        <input type="text" id="card-gap" name="card_gap" value="<?php echo esc_attr($settings['card_gap'] ?? '20px'); ?>" class="regular-text" placeholder="20px">
                                    </div>
                                </div>
                            </div>

                            <div class="setting-group">
                                <h4>Stylizacja kafelków</h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div>
                                        <label for="card-bg-color">Kolor tła</label>
                                        <div class="color-input-wrapper">
                                            <input type="color" id="card-bg-color" name="card_bg_color" value="<?php echo esc_attr($settings['card_bg_color'] ?? '#ffffff'); ?>" class="compact-color-input">
                                            <span class="color-hex-display"><?php echo esc_html(strtoupper($settings['card_bg_color'] ?? '#FFFFFF')); ?></span>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="card-border-color">Kolor obramowania</label>
                                        <div class="color-input-wrapper">
                                            <input type="color" id="card-border-color" name="card_border_color" value="<?php echo esc_attr($settings['card_border_color'] ?? '#e0e0e0'); ?>" class="compact-color-input">
                                            <span class="color-hex-display"><?php echo esc_html(strtoupper($settings['card_border_color'] ?? '#E0E0E0')); ?></span>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="card-border-radius">Zaokrąglenie rogów</label>
                                        <input type="text" id="card-border-radius" name="card_border_radius" value="<?php echo esc_attr($settings['card_border_radius'] ?? '8px'); ?>" class="regular-text" placeholder="8px">
                                    </div>
                                    <div>
                                        <label for="card-padding">Padding wewnętrzny</label>
                                        <input type="text" id="card-padding" name="card_padding" value="<?php echo esc_attr($settings['card_padding'] ?? '20px'); ?>" class="regular-text" placeholder="20px">
                                    </div>
                                    <div>
                                        <label for="card-shadow">Cień</label>
                                        <input type="text" id="card-shadow" name="card_shadow" value="<?php echo esc_attr($settings['card_shadow'] ?? '0 2px 4px rgba(0,0,0,0.1)'); ?>" class="regular-text" placeholder="0 2px 4px rgba(0,0,0,0.1)">
                                    </div>
                                    <div>
                                        <label for="card-hover-shadow">Cień przy hover</label>
                                        <input type="text" id="card-hover-shadow" name="card_hover_shadow" value="<?php echo esc_attr($settings['card_hover_shadow'] ?? '0 4px 8px rgba(0,0,0,0.15)'); ?>" class="regular-text" placeholder="0 4px 8px rgba(0,0,0,0.15)">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                        <!-- Sortowanie i filtrowanie -->
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>Sortowanie i filtrowanie</h2>
                            </div>
                            <div class="inside">
                                <?php $this->render_sorting_and_filtering_options($settings); ?>
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
                            <code id="generated-shortcode"></code>
                            <button type="button" class="button button-primary" id="copy-shortcode">
                                <span class="dashicons dashicons-admin-page"></span>
                                Kopiuj shortcode
                            </button>
                        </div>
                        <p class="description">Skopiuj powyższy kod i wklej go tam gdzie chcesz wyświetlić tabelę z nieruchomościami.</p>
                    </div>
                </div>

                </div><!-- End Tab Content: Resources List -->

                <!-- Tab Content: Resource Single -->
                <div class="tab-content tab-content-single" data-tab-content="resource-single">

                <!-- Live Preview Section (Shared) -->
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
                        <p class="description">Zobacz jak będzie wyglądać karta zasobu na stronie. Podgląd aktualizuje się automatycznie po zmianie ustawień.</p>
                    </div>
                </div>

                <!-- Configuration Sections -->
                <div class="config-section-grid">
                    <div class="left-column">
                        <!-- Basic Settings -->
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>Podstawowe ustawienia</h2>
                            </div>
                            <div class="inside">
                                <div class="setting-group">
                                    <label>Widoczne pola</label>
                                    <?php $this->render_single_resource_fields($settings); ?>
                                    <p class="description">Zaznacz pola które mają być wyświetlane w karcie zasobu. Numer lokalu jest wykrywany automatycznie z URL.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="right-column">
                        <!-- Personalization -->
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>Personalizacja karty</h2>
                            </div>
                            <div class="inside">
                                <div class="setting-group">
                                    <h4>Kolory i odstępy</h4>
                                    <?php $this->render_single_card_styling($settings); ?>
                                </div>

                                <div class="setting-group">
                                    <h4>Stylizacja statusów</h4>
                                    <?php $this->render_status_styling($settings, '-single'); ?>
                                </div>

                                <div class="setting-group">
                                    <h4>Stylizacja przycisków</h4>
                                    <?php $this->render_button_styling($settings, '-single'); ?>
                                </div>
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
                            <code id="generated-shortcode-single"></code>
                            <button type="button" class="button button-primary" id="copy-shortcode-single">
                                <span class="dashicons dashicons-admin-page"></span>
                                Kopiuj shortcode
                            </button>
                        </div>
                        <p class="description">Skopiuj powyższy kod i wklej go na stronie szczegółów zasobu.</p>
                    </div>
                </div>

                </div><!-- End Tab Content: Resource Single -->

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
                        <li><code>[jawneceny_resources_list types="residential_unit"]</code> - tylko mieszkania</li>
                        <li><code>[jawneceny_resources_list types="parking_space"]</code> - tylko miejsca postojowe</li>
                        <li><code>[jawneceny_resources_list types="residential_unit,parking_space"]</code> - mieszkania i miejsca postojowe</li>
                    </ul>

                    <h3>Nawigacja do szczegółów (opcjonalne):</h3>
                    <p>Dodaj parametr <code>navigation_mode</code> aby włączyć nawigację do stron szczegółów:</p>
                    <ul>
                        <li><code>[jawneceny_resources_list navigation_mode="clickable"]</code> - klikalne wiersze/karty, kliknięcie przekieruje do /aktualna-strona/A1</li>
                        <li><code>[jawneceny_resources_list navigation_mode="button"]</code> - przycisk "Zobacz więcej" w każdym wierszu</li>
                        <li><code>[jawneceny_resources_list]</code> - brak nawigacji (domyślnie)</li>
                    </ul>
                    <p><strong>Uwagi:</strong></p>
                    <ul>
                        <li>Numer lokalu jest automatycznie dodawany do aktualnego URL strony</li>
                        <li>Kliknięcie otwiera nową kartę przeglądarki</li>
                        <li>Przyciski "Historia" i "Karta lokalu" nadal działają normalnie</li>
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
            // Get shortcode from POST - use wp_unslash to preserve quotes for do_shortcode()
            $shortcode = wp_unslash($_POST['shortcode'] ?? '');

            Logger::info('Preview shortcode received: ' . esc_html($shortcode));

            if (empty($shortcode)) {
                Logger::error('Preview shortcode is empty');
                wp_send_json_error('Brak shortcode do podglądu');
                return;
            }

            // TYMCZASOWA ZMIANA: Używamy WordPress do_shortcode() zamiast manual parsingu
            // żeby preview pokazywał dokładnie to samo co frontend (włącznie z problemami)
            // Oryginalny manual parsing zakomentowany poniżej:

            /*
            // Parse shortcode manually for preview to preserve colons
            Logger::info('Parsing shortcode manually for preview: ' . esc_html($shortcode));

            // Remove escape slashes added by WordPress
            $shortcode = stripslashes($shortcode);
            Logger::info('After stripslashes: ' . esc_html($shortcode));

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
            */

            // For jawneceny_resource_single preview, inject first resource nr_lokalu if missing
            if (strpos($shortcode, 'jawneceny_resource_single') !== false) {
                Logger::info('Preview: Detected jawneceny_resource_single shortcode');
                // Check if nr_lokalu parameter is missing
                if (strpos($shortcode, 'nr_lokalu=') === false) {
                    Logger::info('Preview: nr_lokalu parameter missing, will inject first resource');
                    try {
                        $all = $this->getAllResourcesUseCase->execute();
                        Logger::info('Preview: Got ' . count($all) . ' resources from use case');

                        if (!empty($all)) {
                            $first_nr_lokalu = $all[0]->nr_lokalu;
                            // Inject nr_lokalu parameter before the closing ]
                            $shortcode = str_replace(']', ' nr_lokalu="' . esc_attr($first_nr_lokalu) . '"]', $shortcode);
                            Logger::info("Preview: Injected nr_lokalu for single resource: {$first_nr_lokalu}");
                        } else {
                            Logger::warning('Preview: No resources available for single resource preview');
                            wp_send_json_success(['html' => '<div class="ujc-error">Brak zasobów w bazie. Dodaj zasób aby zobaczyć podgląd.</div>']);
                            return;
                        }
                    } catch (\Exception $e) {
                        Logger::error('Preview: Failed to get first resource: ' . $e->getMessage());
                        wp_send_json_error('Błąd podczas pobierania zasobu dla podglądu');
                        return;
                    }
                }
            }

            // Execute shortcode
            Logger::info('Using WordPress do_shortcode() for preview: ' . esc_html($shortcode));
            $preview_html = do_shortcode($shortcode);
            Logger::info('Preview result length: ' . strlen($preview_html));

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

            // Sortowanie - domyślnie wyłączone
            'sort' => '',
            'sort_order' => 'asc',

            // Filtrowanie - domyślnie wyłączone
            'filterBy' => '',
            'filterValue' => '',

            // Navigation mode - domyślnie klikalne wiersze
            'navigation_mode' => '',
            'zobacz_btn_text' => 'Zobacz więcej',
            'zobacz_btn_bg_color' => '#007cba',
            'zobacz_btn_text_color' => '#ffffff',
            'zobacz_btn_padding' => '6px 12px',
            'zobacz_btn_border_radius' => '4px',
            'zobacz_btn_font_size' => '0.875em',

            // Display mode and card configuration
            'display_mode' => 'table',
            'mobile_switch_to_cards' => false,
            'card_size' => 'normal',
            'card_gap' => '20px',
            'card_bg_color' => '#ffffff',
            'card_border_color' => '#e0e0e0',
            'card_border_radius' => '8px',
            'card_padding' => '20px',
            'card_shadow' => '0 2px 4px rgba(0,0,0,0.1)',
            'card_hover_shadow' => '0 4px 8px rgba(0,0,0,0.15)',

            // Single resource settings
            'visible_columns_single' => [
                ResourceDto::FIELD_NR_LOKALU,
                ResourceDto::FIELD_RODZAJ_NIERUCHOMOSCI,
                ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA,
                ResourceDto::FIELD_ROOM_COUNT,
                ResourceDto::FIELD_FLOOR_NUMBER,
                PriceHistoryDto::FIELD_CENA_CALKOWITA,
                PriceHistoryDto::FIELD_CENA_M2,
                'historia_cen'
            ],
            'column_names_single' => $this->getDefaultColumnNames(),
            'card_bg_color' => '#ffffff',
            'card_border_color' => '#e1e5e9',
            'card_border_radius' => '8px',
            'card_padding' => '24px',
            'card_text_color' => '#333333',
            'card_label_color' => '#666666',
            'field_spacing' => '12px',
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
            ResourceTableRenderer::COLUMN_HISTORIA_CEN => 'Historia cen',
            ResourceTableRenderer::COLUMN_ZOBACZ_WIECEJ => '',
            // Component fields
            ResourceDto::FIELD_PROPERTY_PART_TITLE => 'Część nieruchomości - tytuł',
            ResourceDto::FIELD_PROPERTY_PART_DESIGNATION => 'Część nieruchomości - oznaczenie',
            ResourceDto::FIELD_PROPERTY_PART_PRICE => 'Część nieruchomości - cena',
            ResourceDto::FIELD_BELONGING_ROOM_TITLE => 'Pomieszczenie przynależne - tytuł',
            ResourceDto::FIELD_BELONGING_ROOM_DESIGNATION => 'Pomieszczenie przynależne - oznaczenie',
            ResourceDto::FIELD_BELONGING_ROOM_PRICE => 'Pomieszczenie przynależne - cena',
            ResourceDto::FIELD_USAGE_RIGHT_TITLE => 'Prawo użytkowania - tytuł',
            ResourceDto::FIELD_USAGE_RIGHT_PRICE => 'Prawo użytkowania - cena',
            ResourceDto::FIELD_OTHER_SERVICE_TITLE => 'Inna usługa - tytuł',
            ResourceDto::FIELD_OTHER_SERVICE_PRICE => 'Inna usługa - cena'
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

        $shortcode = "[jawneceny_resources_list types=\"{$types_string}\"";

        if (!empty($columns_string)) {
            $shortcode .= " columns=\"{$columns_string}\"";
        }

        // Add sortowanie
        if (!empty($settings['sort'])) {
            $shortcode .= " sort=\"{$settings['sort']}\"";
            if ($settings['sort_order'] !== 'asc') {
                $shortcode .= " sort_order=\"{$settings['sort_order']}\"";
            }
        }

        // Add filtrowanie
        if (!empty($settings['filterBy']) && !empty($settings['filterValue'])) {
            $shortcode .= " filterBy=\"{$settings['filterBy']}\"";
            $shortcode .= " filterValue=\"{$settings['filterValue']}\"";
        }

        // Add styling parameters
        $shortcode .= " header_bg_color=\"{$settings['header_bg_color']}\"";
        $shortcode .= " header_text_color=\"{$settings['header_text_color']}\"";
        $shortcode .= " hover_bg_color=\"{$settings['hover_bg_color']}\"";
        $shortcode .= " text_color=\"{$settings['text_color']}\"";
        $shortcode .= " header_font_family=\"{$settings['header_font_family']}\"";
        $shortcode .= " content_font_family=\"{$settings['content_font_family']}\"";

        // Add display mode
        if (!empty($settings['display_mode']) && $settings['display_mode'] !== 'table') {
            $shortcode .= " display_mode=\"{$settings['display_mode']}\"";
        }

        // Add card configuration if display_mode is 'cards' OR mobile_switch_to_cards is enabled
        if (($settings['display_mode'] ?? 'table') === 'cards' || !empty($settings['mobile_switch_to_cards'])) {
            $shortcode .= " card_size=\"{$settings['card_size']}\"";
            $shortcode .= " card_gap=\"{$settings['card_gap']}\"";
            $shortcode .= " card_bg_color=\"{$settings['card_bg_color']}\"";
            $shortcode .= " card_border_color=\"{$settings['card_border_color']}\"";
            $shortcode .= " card_border_radius=\"{$settings['card_border_radius']}\"";
            $shortcode .= " card_padding=\"{$settings['card_padding']}\"";
            $shortcode .= " card_shadow=\"{$settings['card_shadow']}\"";
            $shortcode .= " card_hover_shadow=\"{$settings['card_hover_shadow']}\"";
        }

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
            ResourceTableRenderer::COLUMN_HISTORIA_CEN
        ];
        
        $default_names = $this->getDefaultColumnNames();

        echo '<div class="columns-reorder-container">';

        // Selected columns (sortable with drag & drop)
        echo '<div class="selected-columns-section">';
        echo '<p class="description">Przeciągnij kolumny aby zmienić kolejność. Kliknij × aby usunąć.</p>';
        echo '<div id="selected-columns-list" class="selected-columns-list">';

        foreach ($settings['visible_columns'] as $column) {
            if (in_array($column, $available_columns)) {
                $display_name = $default_names[$column] ?? ucfirst($column);
                $current_name = $settings['column_names'][$column] ?? ($default_names[$column] ?? ucfirst($column));

                echo '<div class="selected-column-item" data-column="' . esc_attr($column) . '">';
                echo '<span class="drag-handle">⋮⋮</span>';
                echo '<input type="checkbox" name="visible_columns[]" value="' . esc_attr($column) . '" checked class="column-checkbox" style="display:none;">';
                echo '<span class="column-label">' . esc_html($display_name) . '</span>';
                echo '<input type="text" name="column_names[' . esc_attr($column) . ']" value="' . esc_attr($current_name) . '" placeholder="Nazwa" class="column-name-input" data-column="' . esc_attr($column) . '">';
                echo '<button type="button" class="remove-column-btn" data-column="' . esc_attr($column) . '">×</button>';
                echo '</div>';
            }
        }

        echo '</div>';
        echo '</div>';

        // Available columns (to add)
        echo '<div class="available-columns-section">';
        echo '<p class="description">Dostępne kolumny do dodania:</p>';
        echo '<div class="available-columns-list">';

        foreach ($available_columns as $column) {
            if (!in_array($column, $settings['visible_columns'])) {
                $display_name = $default_names[$column] ?? ucfirst($column);
                echo '<button type="button" class="add-column-btn" data-column="' . esc_attr($column) . '">+ ' . esc_html($display_name) . '</button>';
            }
        }

        echo '</div>';
        echo '</div>';

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
    private function render_status_styling(array $settings, string $suffix = '') {
        echo '<div class="status-styling-options">';

        // Status: Dostępne
        echo '<div class="status-group">';
        echo '<h5>Status: Dostępne</h5>';
        echo '<div class="status-group-colors">';

        echo '<div class="status-color-item">';
        echo '<label for="status_available_bg_color' . esc_attr($suffix) . '">Kolor tła</label>';
        echo '<div class="color-input-wrapper">';
        echo '<input type="color" id="status_available_bg_color' . esc_attr($suffix) . '" name="status_available_bg_color" value="' . esc_attr($settings['status_available_bg_color'] ?? '#28a745') . '" class="compact-color-input">';
        echo '<span class="color-hex-display">' . esc_html(strtoupper($settings['status_available_bg_color'] ?? '#28A745')) . '</span>';
        echo '</div>';
        echo '</div>';

        echo '<div class="status-color-item">';
        echo '<label for="status_available_text_color' . esc_attr($suffix) . '">Kolor tekstu</label>';
        echo '<div class="color-input-wrapper">';
        echo '<input type="color" id="status_available_text_color' . esc_attr($suffix) . '" name="status_available_text_color" value="' . esc_attr($settings['status_available_text_color'] ?? '#ffffff') . '" class="compact-color-input">';
        echo '<span class="color-hex-display">' . esc_html(strtoupper($settings['status_available_text_color'] ?? '#FFFFFF')) . '</span>';
        echo '</div>';
        echo '</div>';

        echo '</div>';
        echo '</div>';

        // Status: Sprzedane
        echo '<div class="status-group">';
        echo '<h5>Status: Sprzedane</h5>';
        echo '<div class="status-group-colors">';

        echo '<div class="status-color-item">';
        echo '<label for="status_sold_bg_color' . esc_attr($suffix) . '">Kolor tła</label>';
        echo '<div class="color-input-wrapper">';
        echo '<input type="color" id="status_sold_bg_color' . esc_attr($suffix) . '" name="status_sold_bg_color" value="' . esc_attr($settings['status_sold_bg_color'] ?? '#dc3545') . '" class="compact-color-input">';
        echo '<span class="color-hex-display">' . esc_html(strtoupper($settings['status_sold_bg_color'] ?? '#DC3545')) . '</span>';
        echo '</div>';
        echo '</div>';

        echo '<div class="status-color-item">';
        echo '<label for="status_sold_text_color' . esc_attr($suffix) . '">Kolor tekstu</label>';
        echo '<div class="color-input-wrapper">';
        echo '<input type="color" id="status_sold_text_color' . esc_attr($suffix) . '" name="status_sold_text_color" value="' . esc_attr($settings['status_sold_text_color'] ?? '#ffffff') . '" class="compact-color-input">';
        echo '<span class="color-hex-display">' . esc_html(strtoupper($settings['status_sold_text_color'] ?? '#FFFFFF')) . '</span>';
        echo '</div>';
        echo '</div>';

        echo '</div>';
        echo '</div>';

        // Status: Zarezerwowane
        echo '<div class="status-group">';
        echo '<h5>Status: Zarezerwowane</h5>';
        echo '<div class="status-group-colors">';

        echo '<div class="status-color-item">';
        echo '<label for="status_reserved_bg_color' . esc_attr($suffix) . '">Kolor tła</label>';
        echo '<div class="color-input-wrapper">';
        echo '<input type="color" id="status_reserved_bg_color' . esc_attr($suffix) . '" name="status_reserved_bg_color" value="' . esc_attr($settings['status_reserved_bg_color'] ?? '#ffc107') . '" class="compact-color-input">';
        echo '<span class="color-hex-display">' . esc_html(strtoupper($settings['status_reserved_bg_color'] ?? '#FFC107')) . '</span>';
        echo '</div>';
        echo '</div>';

        echo '<div class="status-color-item">';
        echo '<label for="status_reserved_text_color' . esc_attr($suffix) . '">Kolor tekstu</label>';
        echo '<div class="color-input-wrapper">';
        echo '<input type="color" id="status_reserved_text_color' . esc_attr($suffix) . '" name="status_reserved_text_color" value="' . esc_attr($settings['status_reserved_text_color'] ?? '#000000') . '" class="compact-color-input">';
        echo '<span class="color-hex-display">' . esc_html(strtoupper($settings['status_reserved_text_color'] ?? '#000000')) . '</span>';
        echo '</div>';
        echo '</div>';

        echo '</div>';
        echo '</div>';

        // Dodatkowe opcje stylizacji
        echo '<div class="status-group status-advanced-group">';
        echo '<h5>Dodatkowe opcje stylizacji</h5>';
        echo '<div class="status-group-colors">';

        // Styl wyświetlania
        echo '<div class="status-color-item">';
        echo '<label for="status_display_style' . esc_attr($suffix) . '">Styl wyświetlania</label>';
        echo '<select id="status_display_style' . esc_attr($suffix) . '" name="status_display_style" class="status-input">';
        $display_style = $settings['status_display_style'] ?? 'badge';
        echo '<option value="badge"' . ($display_style === 'badge' ? ' selected' : '') . '>Badge (prostokąt z ramką)</option>';
        echo '<option value="pill"' . ($display_style === 'pill' ? ' selected' : '') . '>Pill (zaokrąglony)</option>';
        echo '<option value="highlight"' . ($display_style === 'highlight' ? ' selected' : '') . '>Highlight (podświetlenie)</option>';
        echo '<option value="underline"' . ($display_style === 'underline' ? ' selected' : '') . '>Underline (podkreślenie)</option>';
        echo '<option value="plain"' . ($display_style === 'plain' ? ' selected' : '') . '>Plain (tylko tekst)</option>';
        echo '</select>';
        echo '</div>';

        // Rozmiar czcionki
        echo '<div class="status-color-item">';
        echo '<label for="status_font_size' . esc_attr($suffix) . '">Rozmiar czcionki</label>';
        echo '<input type="text" id="status_font_size' . esc_attr($suffix) . '" name="status_font_size" class="status-input" value="' . esc_attr($settings['status_font_size'] ?? '0.875em') . '" placeholder="0.875em">';
        echo '</div>';

        // Padding
        echo '<div class="status-color-item">';
        echo '<label for="status_padding' . esc_attr($suffix) . '">Padding</label>';
        echo '<input type="text" id="status_padding' . esc_attr($suffix) . '" name="status_padding" class="status-input" value="' . esc_attr($settings['status_padding'] ?? '4px 8px') . '" placeholder="4px 8px">';
        echo '</div>';

        // Border radius
        echo '<div class="status-color-item">';
        echo '<label for="status_border_radius' . esc_attr($suffix) . '">Border radius</label>';
        echo '<input type="text" id="status_border_radius' . esc_attr($suffix) . '" name="status_border_radius" class="status-input" value="' . esc_attr($settings['status_border_radius'] ?? '4px') . '" placeholder="4px">';
        echo '</div>';

        // Grubość czcionki
        echo '<div class="status-color-item">';
        echo '<label for="status_font_weight' . esc_attr($suffix) . '">Grubość czcionki</label>';
        echo '<select id="status_font_weight' . esc_attr($suffix) . '" name="status_font_weight" class="status-input">';
        $font_weight = $settings['status_font_weight'] ?? '500';
        echo '<option value="normal"' . ($font_weight === 'normal' ? ' selected' : '') . '>Normalna</option>';
        echo '<option value="bold"' . ($font_weight === 'bold' ? ' selected' : '') . '>Pogrubiona</option>';
        echo '<option value="300"' . ($font_weight === '300' ? ' selected' : '') . '>Cienka</option>';
        echo '<option value="500"' . ($font_weight === '500' ? ' selected' : '') . '>Średnia</option>';
        echo '<option value="700"' . ($font_weight === '700' ? ' selected' : '') . '>Bardzo pogrubiona</option>';
        echo '</select>';
        echo '</div>';

        echo '</div>';
        echo '</div>';

        echo '</div>';
    }

    /**
     * Render button styling options
     */
    private function render_button_styling(array $settings, string $suffix = '') {
        echo '<div class="button-styling-options">';

        // Historia button styling
        echo '<div class="button-group">';
        echo '<h5>Przycisk "Historia cen"</h5>';
        echo '<div class="button-styling-row">';

        echo '<div class="button-text-input">';
        echo '<label for="historia_btn_text' . esc_attr($suffix) . '">Tekst przycisku</label>';
        echo '<input type="text" id="historia_btn_text' . esc_attr($suffix) . '" name="historia_btn_text" value="' . esc_attr($settings['historia_btn_text'] ?? 'Historia') . '">';
        echo '</div>';

        echo '<div class="button-color-inputs">';
        echo '<label for="historia_btn_bg_color' . esc_attr($suffix) . '">Kolor tła</label>';
        echo '<div class="color-input-wrapper">';
        echo '<input type="color" id="historia_btn_bg_color' . esc_attr($suffix) . '" name="historia_btn_bg_color" value="' . esc_attr($settings['historia_btn_bg_color'] ?? '#007cba') . '" class="compact-color-input">';
        echo '<span class="color-hex-display">' . esc_html(strtoupper($settings['historia_btn_bg_color'] ?? '#007CBA')) . '</span>';
        echo '</div>';
        echo '</div>';

        echo '<div class="button-color-inputs">';
        echo '<label for="historia_btn_text_color' . esc_attr($suffix) . '">Kolor tekstu</label>';
        echo '<div class="color-input-wrapper">';
        echo '<input type="color" id="historia_btn_text_color' . esc_attr($suffix) . '" name="historia_btn_text_color" value="' . esc_attr($settings['historia_btn_text_color'] ?? '#ffffff') . '" class="compact-color-input">';
        echo '<span class="color-hex-display">' . esc_html(strtoupper($settings['historia_btn_text_color'] ?? '#FFFFFF')) . '</span>';
        echo '</div>';
        echo '</div>';

        echo '</div>';
        echo '</div>';

        // Karta button styling
        echo '<div class="button-group">';
        echo '<h5>Przycisk "Karta lokalu"</h5>';
        echo '<div class="button-styling-row">';

        echo '<div class="button-text-input">';
        echo '<label for="karta_btn_text' . esc_attr($suffix) . '">Tekst przycisku</label>';
        echo '<input type="text" id="karta_btn_text' . esc_attr($suffix) . '" name="karta_btn_text" value="' . esc_attr($settings['karta_btn_text'] ?? 'Karta lokalu') . '">';
        echo '</div>';

        echo '<div class="button-color-inputs">';
        echo '<label for="karta_btn_bg_color' . esc_attr($suffix) . '">Kolor tła</label>';
        echo '<div class="color-input-wrapper">';
        echo '<input type="color" id="karta_btn_bg_color' . esc_attr($suffix) . '" name="karta_btn_bg_color" value="' . esc_attr($settings['karta_btn_bg_color'] ?? '#007cba') . '" class="compact-color-input">';
        echo '<span class="color-hex-display">' . esc_html(strtoupper($settings['karta_btn_bg_color'] ?? '#007CBA')) . '</span>';
        echo '</div>';
        echo '</div>';

        echo '<div class="button-color-inputs">';
        echo '<label for="karta_btn_text_color' . esc_attr($suffix) . '">Kolor tekstu</label>';
        echo '<div class="color-input-wrapper">';
        echo '<input type="color" id="karta_btn_text_color' . esc_attr($suffix) . '" name="karta_btn_text_color" value="' . esc_attr($settings['karta_btn_text_color'] ?? '#ffffff') . '" class="compact-color-input">';
        echo '<span class="color-hex-display">' . esc_html(strtoupper($settings['karta_btn_text_color'] ?? '#FFFFFF')) . '</span>';
        echo '</div>';
        echo '</div>';

        echo '</div>';
        echo '</div>';

        echo '</div>';
    }

    /**
     * Render single resource fields selection
     */
    private function render_single_resource_fields(array $settings) {
        $available_fields = [
            ResourceDto::FIELD_NR_LOKALU,
            ResourceDto::FIELD_RODZAJ_NIERUCHOMOSCI,
            ResourceDto::FIELD_POWIERZCHNIA_UZYTKOWA,
            ResourceDto::FIELD_STATUS,
            ResourceDto::FIELD_FLOOR_NUMBER,
            ResourceDto::FIELD_ROOM_COUNT,
            ResourceDto::FIELD_ADDITIONAL_DESCRIPTION,
            ResourceDto::FIELD_GARDEN_AREA,
            PriceHistoryDto::FIELD_CENA_M2,
            PriceHistoryDto::FIELD_CENA_CALKOWITA,
            PriceHistoryDto::FIELD_CENA_Z_DODATKAMI,
            'historia_cen',
            ResourceDto::FIELD_FLOOR_PLAN_PDF,
            // Component fields - only for single resource page
            ResourceDto::FIELD_PROPERTY_PART_TITLE,
            ResourceDto::FIELD_PROPERTY_PART_DESIGNATION,
            ResourceDto::FIELD_PROPERTY_PART_PRICE,
            ResourceDto::FIELD_BELONGING_ROOM_TITLE,
            ResourceDto::FIELD_BELONGING_ROOM_DESIGNATION,
            ResourceDto::FIELD_BELONGING_ROOM_PRICE,
            ResourceDto::FIELD_USAGE_RIGHT_TITLE,
            ResourceDto::FIELD_USAGE_RIGHT_PRICE,
            ResourceDto::FIELD_OTHER_SERVICE_TITLE,
            ResourceDto::FIELD_OTHER_SERVICE_PRICE
        ];

        $default_names = $this->getDefaultColumnNames();

        echo '<div class="columns-reorder-container">';

        // Selected columns (sortable with drag & drop)
        echo '<div class="selected-columns-section">';
        echo '<p class="description">Przeciągnij pola aby zmienić kolejność. Kliknij × aby usunąć.</p>';
        echo '<div id="selected-columns-list-single" class="selected-columns-list">';

        foreach ($settings['visible_columns_single'] as $field) {
            if (in_array($field, $available_fields)) {
                $display_name = $default_names[$field] ?? ucfirst($field);
                $current_name = $settings['column_names_single'][$field] ?? ($default_names[$field] ?? ucfirst($field));

                echo '<div class="selected-column-item" data-column="' . esc_attr($field) . '">';
                echo '<span class="drag-handle">⋮⋮</span>';
                echo '<input type="checkbox" name="visible_columns_single[]" value="' . esc_attr($field) . '" checked class="column-checkbox-single" style="display:none;">';
                echo '<span class="column-label">' . esc_html($display_name) . '</span>';
                echo '<input type="text" name="column_names_single[' . esc_attr($field) . ']" value="' . esc_attr($current_name) . '" placeholder="Nazwa" class="column-name-input-single" data-column="' . esc_attr($field) . '">';
                echo '<button type="button" class="remove-column-btn" data-column="' . esc_attr($field) . '">×</button>';
                echo '</div>';
            }
        }

        echo '</div>';
        echo '</div>';

        // Available fields (to add)
        echo '<div class="available-columns-section">';
        echo '<p class="description">Dostępne pola do dodania:</p>';
        echo '<div class="available-columns-list">';

        foreach ($available_fields as $field) {
            if (!in_array($field, $settings['visible_columns_single'] ?? [])) {
                $display_name = $default_names[$field] ?? ucfirst($field);
                echo '<button type="button" class="add-column-btn-single" data-column="' . esc_attr($field) . '">+ ' . esc_html($display_name) . '</button>';
            }
        }

        echo '</div>';
        echo '</div>';

        echo '</div>';
    }

    /**
     * Render single card styling options
     */
    private function render_single_card_styling(array $settings) {
        $styling_options = [
            'card_bg_color' => ['label' => 'Tło karty', 'type' => 'color', 'default' => '#ffffff'],
            'card_border_color' => ['label' => 'Obramowanie', 'type' => 'color', 'default' => '#e1e5e9'],
            'card_text_color' => ['label' => 'Tekst', 'type' => 'color', 'default' => '#333333'],
            'card_label_color' => ['label' => 'Etykiety', 'type' => 'color', 'default' => '#666666'],
            'card_border_radius' => ['label' => 'Zaokrąglenie', 'type' => 'text', 'default' => '8px', 'placeholder' => '8px'],
            'card_padding' => ['label' => 'Padding', 'type' => 'text', 'default' => '24px', 'placeholder' => '24px'],
            'field_spacing' => ['label' => 'Odstępy pól', 'type' => 'text', 'default' => '12px', 'placeholder' => '12px']
        ];

        echo '<div class="compact-colors">';

        foreach ($styling_options as $field => $config) {
            $value = $settings[$field] ?? $config['default'];
            echo '<div class="compact-color-item">';
            echo '<label for="' . esc_attr($field) . '">' . esc_html($config['label']) . '</label>';

            if ($config['type'] === 'color') {
                echo '<div class="color-input-wrapper">';
                echo '<input type="color" id="' . esc_attr($field) . '" name="' . esc_attr($field) . '" value="' . esc_attr($value) . '" class="compact-color-input">';
                echo '<span class="color-hex-display">' . esc_html(strtoupper($value)) . '</span>';
                echo '</div>';
            } else {
                echo '<input type="text" id="' . esc_attr($field) . '" name="' . esc_attr($field) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($config['placeholder'] ?? '') . '" class="regular-text">';
            }

            echo '</div>';
        }

        echo '</div>';
    }

    /**
     * Render sorting and filtering options
     */
    private function render_sorting_and_filtering_options(array $settings) {
        // Sortowanie
        echo '<div class="setting-group">';
        echo '<h4>Sortowanie</h4>';

        echo '<div style="display: flex; gap: 10px; align-items: center;">';
        echo '<select id="sort" name="sort" style="width: 200px;">';
        echo '<option value="">Bez sortowania</option>';
        echo '<option value="price"' . selected($settings['sort'], 'price', false) . '>Cena</option>';
        echo '<option value="area"' . selected($settings['sort'], 'area', false) . '>Powierzchnia</option>';
        echo '<option value="floor"' . selected($settings['sort'], 'floor', false) . '>Piętro</option>';
        echo '<option value="unit"' . selected($settings['sort'], 'unit', false) . '>Numer lokalu</option>';
        echo '<option value="status"' . selected($settings['sort'], 'status', false) . '>Status</option>';
        echo '<option value="rooms"' . selected($settings['sort'], 'rooms', false) . '>Pokoje</option>';
        echo '</select>';

        echo '<select id="sort_order" name="sort_order" style="width: 120px;">';
        echo '<option value="asc"' . selected($settings['sort_order'], 'asc', false) . '>Rosnąco</option>';
        echo '<option value="desc"' . selected($settings['sort_order'], 'desc', false) . '>Malejąco</option>';
        echo '</select>';
        echo '</div>';

        echo '</div>';

        // Filtrowanie
        echo '<div class="setting-group">';
        echo '<h4>Filtrowanie</h4>';

        echo '<div style="display: flex; gap: 10px; align-items: flex-start; flex-wrap: wrap;">';
        echo '<select id="filterBy" name="filterBy" style="width: 200px;">';
        echo '<option value="">Bez filtrowania</option>';
        echo '<option value="floor"' . selected($settings['filterBy'], 'floor', false) . '>Piętro</option>';
        echo '<option value="status"' . selected($settings['filterBy'], 'status', false) . '>Status</option>';
        echo '</select>';

        // Input tekstowy dla floor (ukryty gdy status wybrany)
        echo '<input type="text" id="filterValue" name="filterValue" value="' . esc_attr($settings['filterValue']) . '" placeholder="Wartość (np. 5)" class="regular-text" style="width: 150px;">';

        // Checkboxy dla statusu (ukryte domyślnie, pokazane gdy status wybrany)
        echo '<div id="filter-status-options" style="display: none;">';
        echo '<label style="display: block; margin-bottom: 4px;"><input type="checkbox" name="filterStatusValues[]" value="available"> Dostępne</label>';
        echo '<label style="display: block; margin-bottom: 4px;"><input type="checkbox" name="filterStatusValues[]" value="reserved"> Zarezerwowane</label>';
        echo '<label style="display: block;"><input type="checkbox" name="filterStatusValues[]" value="sold"> Sprzedane</label>';
        echo '</div>';
        echo '</div>';

        echo '</div>';
    }
}
