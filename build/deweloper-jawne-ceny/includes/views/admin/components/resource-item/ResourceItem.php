<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Komponent pojedynczego itemu zasobu na liście
 */
class ResourceItem {

    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets() {
        $viewPath = JAWNECENY_PLUGIN_URL . 'includes/views/admin/components/resource-item/';

        wp_enqueue_style(
            'resource-item',
            $viewPath . 'ResourceItem.css',
            [],
            JAWNECENY_VERSION
        );
    }
    
    /**
     * Renderuje HTML dla pojedynczego zasobu
     */
    public static function render_item_html(PresentableResource $resource) {
        // Formatowanie cen (ukryj puste)
        $cena_calkowita = ($resource->cena_calkowita > 0) ? number_format($resource->cena_calkowita, 2, ',', ' ') . ' zł' : '—';
        $cena_z_dodatkami = ($resource->cena_z_dodatkami > 0) ? number_format($resource->cena_z_dodatkami, 2, ',', ' ') . ' zł' : '—';
        $cena_m2 = ($resource->cena_m2 !== null && $resource->cena_m2 > 0) ? number_format($resource->cena_m2, 2, ',', ' ') . ' zł' : '—';
        $powierzchnia = ($resource->powierzchnia_uzytkowa !== null && $resource->powierzchnia_uzytkowa > 0) ? number_format($resource->powierzchnia_uzytkowa, 2, ',', ' ') . ' m²' : '—';
        
        // Daty już sformatowane w GetAllResourcesUseCase
        $data_zmiany = $resource->data_zmiany;
        $data_cena_z_dodatkami = $resource->data_cena_z_dodatkami;
        
        // Status badge - semantic CSS classes
        $status_class = match($resource->status) {
            'Dostępny' => 'ujc-status-available',
            'Zarezerwowany' => 'ujc-status-reserved',
            'Sprzedany' => 'ujc-status-sold',
            default => 'ujc-status-available'
        };
        
        return "
            <div class=\"ujc-resource-item-row\">
                <div class=\"ujc-resource-identity\">
                    <div class=\"ujc-resource-title\">
                        <strong>" . esc_html($resource->rodzaj_nieruchomosci) . "</strong>
                        <span class=\"ujc-resource-number\">#" . esc_html($resource->nr_lokalu) . "</span>
                    </div>
                    <div class=\"ujc-resource-surface\">" . esc_html($powierzchnia) . "</div>
                </div>

                <div class=\"ujc-resource-pricing\">
                    <div class=\"ujc-price-main\">
                        <span class=\"ujc-price-label\">Cena m²:</span>
                        <span class=\"ujc-price-value\">" . esc_html($cena_m2) . "/m²</span>
                    </div>
                    <div class=\"ujc-price-secondary\">
                        <span class=\"ujc-price-label\">Cena lokalu:</span>
                        <span class=\"ujc-price-value\">" . esc_html($cena_calkowita) . "</span>
                    </div>
                    <div class=\"ujc-price-secondary\">
                        <span class=\"ujc-price-label\">Cena pełna:</span>
                        <span class=\"ujc-price-value\">" . esc_html($cena_z_dodatkami) . "</span>
                    </div>
                </div>

                <div class=\"ujc-resource-status\">
                    <span class=\"ujc-status-badge " . esc_attr($status_class) . "\">" . esc_html($resource->status) . "</span>
                    <span class=\"ujc-date-info\" title=\"Data ostatniej aktualizacji cen\">Akt: " . esc_html($data_zmiany) . "</span>
                </div>

                <div class=\"ujc-resource-actions\">
                    <button type=\"button\" class=\"button button-small\" onclick=\"showResourceHistory(" . absint($resource->id) . ")\">Historia</button>
                    <button type=\"button\" class=\"button button-small button-primary\" onclick=\"openResourceModal('edit', " . absint($resource->id) . ")\">Edytuj</button>
                </div>
            </div>
        ";
    }
    
    /**
     * Renderuje pusty skrypt - usunięto duplikację JavaScript
     */
    public static function render_item_script() {
        // JavaScript usunięty - używamy tylko PHP rendering
    }
    
    /**
     * Renderuje CSS dla itemów - usunięto, style ładowane przez enqueue_assets
     */
    public static function render_item_styles() {
        // CSS przeniesiony do ResourceItem.css
    }
}