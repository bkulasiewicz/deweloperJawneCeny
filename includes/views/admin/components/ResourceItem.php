<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Komponent pojedynczego itemu zasobu na liście
 */
class ResourceItem {
    
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
                        <strong>{$resource->rodzaj_nieruchomosci}</strong>
                        <span class=\"ujc-resource-number\">#{$resource->nr_lokalu}</span>
                    </div>
                    <div class=\"ujc-resource-surface\">{$powierzchnia}</div>
                </div>
                
                <div class=\"ujc-resource-pricing\">
                    <div class=\"ujc-price-main\">
                        <span class=\"ujc-price-label\">Cena m²:</span>
                        <span class=\"ujc-price-value\">{$cena_m2}/m²</span>
                    </div>
                    <div class=\"ujc-price-secondary\">
                        <span class=\"ujc-price-label\">Cena lokalu:</span>
                        <span class=\"ujc-price-value\">{$cena_calkowita}</span>
                    </div>
                    <div class=\"ujc-price-secondary\">
                        <span class=\"ujc-price-label\">Cena pełna:</span>
                        <span class=\"ujc-price-value\">{$cena_z_dodatkami}</span>
                    </div>
                </div>
                
                <div class=\"ujc-resource-status\">
                    <span class=\"ujc-status-badge {$status_class}\">{$resource->status}</span>
                    <span class=\"ujc-date-info\" title=\"Data ostatniej aktualizacji cen\">Akt: {$data_zmiany}</span>
                </div>
                
                <div class=\"ujc-resource-actions\">
                    <button type=\"button\" class=\"button button-small\" onclick=\"showResourceHistory({$resource->id})\">Historia</button>
                    <button type=\"button\" class=\"button button-small button-primary\" onclick=\"openResourceModal('edit', {$resource->id})\">Edytuj</button>
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
     * Renderuje CSS dla itemów
     */
    public static function render_item_styles() {
        ?>
        <style>
        /* Lista jednocolumnowa dla wielu zasobów */
        .ujc-resources-grid {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 20px;
            max-height: calc(100vh - 200px);
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .ujc-resource-item-row {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-height: 60px;
            transition: all 0.2s ease;
        }
        
        .ujc-resource-item-row:hover {
            background: #f7f9fc;
            border-color: #2271b1;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        /* Sekcja identyfikacji */
        .ujc-resource-identity {
            flex: 0 0 250px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .ujc-resource-title {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .ujc-resource-title strong {
            font-size: 14px;
            color: #1d2327;
        }
        
        .ujc-resource-number {
            color: #646970;
            font-size: 13px;
        }
        
        .ujc-resource-surface {
            font-size: 13px;
            color: #50575e;
        }
        
        /* Sekcja cen */
        .ujc-resource-pricing {
            flex: 1;
            display: flex;
            gap: 40px;
            align-items: center;
            padding: 0 20px;
        }
        
        .ujc-price-main {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .ujc-price-secondary {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .ujc-price-label {
            font-size: 11px;
            color: #8c8f94;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .ujc-price-value {
            font-size: 14px;
            font-weight: 600;
            color: #135e96;
        }
        
        /* Sekcja statusu */
        .ujc-resource-status {
            flex: 0 0 150px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }
        
        .ujc-status-badge {
            padding: 4px 12px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .ujc-status-badge.ujc-status-available {
            background: #d6f4d6;
            color: #0a4f0a;
        }
        
        .ujc-status-badge.ujc-status-reserved {
            background: #fff3cd;
            color: #856404;
        }
        
        .ujc-status-badge.ujc-status-sold {
            background: #f8d7da;
            color: #721c24;
        }
        
        .ujc-date-info {
            font-size: 11px;
            color: #8c8f94;
        }
        
        /* Sekcja akcji */
        .ujc-resource-actions {
            flex: 0 0 180px;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }
        
        .ujc-resource-actions .button-small {
            padding: 4px 8px;
            font-size: 12px;
            line-height: 1.4;
        }
        
        /* Scrollbar styling */
        .ujc-resources-grid::-webkit-scrollbar {
            width: 8px;
        }
        
        .ujc-resources-grid::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .ujc-resources-grid::-webkit-scrollbar-thumb {
            background: #c3c4c7;
            border-radius: 4px;
        }
        
        .ujc-resources-grid::-webkit-scrollbar-thumb:hover {
            background: #8c8f94;
        }
        
        /* Responsive dla mniejszych ekranów */
        @media (max-width: 1200px) {
            .ujc-resource-identity {
                flex: 0 0 200px;
            }
            
            .ujc-resource-pricing {
                gap: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .ujc-resource-item-row {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
                padding: 12px;
            }
            
            .ujc-resource-identity,
            .ujc-resource-pricing,
            .ujc-resource-status,
            .ujc-resource-actions {
                flex: none;
                width: 100%;
            }
            
            .ujc-resource-pricing {
                flex-direction: column;
                gap: 8px;
                padding: 0;
            }
            
            .ujc-resource-actions {
                justify-content: center;
            }
        }

        .ujc-no-resources {
            text-align: center;
            padding: 40px 20px;
            color: #666;
            font-style: italic;
            background: #f9f9f9;
            border: 1px dashed #ddd;
            border-radius: 4px;
        }
        </style>
        <?php
    }
}