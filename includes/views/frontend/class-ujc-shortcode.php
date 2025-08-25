<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcodes - MVP
 */
class UJC_Shortcode {
    
    public function __construct() {
        add_shortcode('ujc_property_list', [$this, 'property_list_shortcode']);
    }
    
    public function property_list_shortcode($atts) {
        $atts = shortcode_atts([
            'project_id' => '',
            'limit' => 10
        ], $atts);
        
        $useCase = new GetAllResourcesUseCase();
        $resources = $useCase->execute();
        
        if (empty($resources)) {
            return '<p>Brak dostępnych nieruchomości.</p>';
        }
        
        $output = '<div class="ujc-resource-list">';
        $output .= '<h3>Dostępne Nieruchomości</h3>';
        
        foreach ($resources as $resource) {
            $output .= '<div class="ujc-property-item">';
            $output .= '<h4>' . esc_html($resource['rodzaj_nieruchomosci']) . ' ' . esc_html($resource['nr_lokalu']) . '</h4>';
            $output .= '<p><strong>Projekt:</strong> ' . esc_html($resource['project_name']) . '</p>';
            $output .= '<p><strong>Cena m²:</strong> ' . number_format($resource['cena_m2'], 2, ',', ' ') . ' zł</p>';
            
            if ($resource['cena_calkowita']) {
                $output .= '<p><strong>Cena całkowita:</strong> ' . number_format($resource['cena_calkowita'], 2, ',', ' ') . ' zł</p>';
            }
            
            if ($resource['cena_z_dodatkami']) {
                $output .= '<p><strong>Cena z dodatkami:</strong> ' . number_format($resource['cena_z_dodatkami'], 2, ',', ' ') . ' zł</p>';
            }
            
            $output .= '<p class="ujc-date">Aktualizacja cen: ' . UJC_Date_Helper::format_for_user($resource['data_cena_m2']) . '</p>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
}