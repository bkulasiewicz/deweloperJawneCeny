<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Komponent pojedynczego itemu zasobu na liście
 */
class UJC_Resource_Item {
    
    /**
     * Renderuje HTML dla pojedynczego zasobu
     */
    public static function render_item_html($resource) {
        // Formatowanie cen (ukryj puste)
        $cena_calkowita = (!empty($resource['cena_calkowita']) && $resource['cena_calkowita'] > 0) ? number_format($resource['cena_calkowita'], 2, ',', ' ') . ' zł' : '—';
        $cena_z_dodatkami = (!empty($resource['cena_z_dodatkami']) && $resource['cena_z_dodatkami'] > 0) ? number_format($resource['cena_z_dodatkami'], 2, ',', ' ') . ' zł' : '—';
        $cena_m2 = number_format($resource['cena_m2'], 2, ',', ' ') . ' zł';
        $powierzchnia = number_format($resource['powierzchnia_uzytkowa'], 2, ',', ' ') . ' m²';
        
        // Formatowanie dat
        $data_cena_m2 = DateHelper::formatForUser($resource['data_cena_m2']);
        $data_cena_calkowita = DateHelper::formatForUser($resource['data_cena_calkowita']);
        $data_cena_z_dodatkami = DateHelper::formatForUser($resource['data_cena_z_dodatkami']);
        $created_at = DateHelper::formatForUser($resource['created_at']);
        $updated_at = DateHelper::formatForUser($resource['updated_at']);
        
        // Status badge
        $status_class = match($resource['status']) {
            'dostepny' => 'ujc-status-available',
            'rezerwacja' => 'ujc-status-reserved', 
            'sprzedany' => 'ujc-status-sold',
            default => 'ujc-status-default'
        };
        
        return "
            <div class=\"ujc-resource-item-compact\">
                <!-- Pierwszy rząd: Nazwa i status -->
                <div class=\"ujc-resource-row-1\">
                    <div class=\"ujc-resource-title\">
                        <strong>{$resource['rodzaj_nieruchomosci']} {$resource['nr_lokalu']}</strong>
                        <span class=\"ujc-surface\">{$powierzchnia}</span>
                    </div>
                    <span class=\"ujc-status-badge {$status_class}\">{$resource['status']}</span>
                </div>
                
                <!-- Drugi rząd: Główne ceny i akcje -->
                <div class=\"ujc-resource-row-2\">
                    <div class=\"ujc-price-main\">{$cena_m2}/m²</div>
                    <div class=\"ujc-price-total\">{$cena_calkowita}</div>
                    <div class=\"ujc-price-extra\">{$cena_z_dodatkami}</div>
                    <div class=\"ujc-resource-actions\">
                        <button type=\"button\" class=\"button button-small\" onclick=\"showResourceHistory({$resource['id']})\">Historia</button>
                        <button type=\"button\" class=\"button button-small button-primary\" onclick=\"openResourceModal('edit', {$resource['id']})\">Edytuj</button>
                    </div>
                </div>
                
                <!-- Tooltip z dodatkowymi informacjami -->
                <div class=\"ujc-resource-tooltip\">
                    <div><strong>Ceny obowiązują od:</strong></div>
                    <div>• Cena m²: {$data_cena_m2}</div>
                    " . ($data_cena_calkowita != '—' ? "<div>• Cena całkowita: {$data_cena_calkowita}</div>" : '') . "
                    " . ($data_cena_z_dodatkami != '—' ? "<div>• Cena z dodatkami: {$data_cena_z_dodatkami}</div>" : '') . "
                    <div><strong>Utworzono:</strong> {$created_at}</div>
                    <div><strong>Ostatnia zmiana:</strong> {$updated_at}</div>
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
        /* Kompaktowy grid dla dużej liczby zasobów */
        .ujc-resources-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }
        
        .ujc-resource-item-compact {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: relative;
            transition: all 0.2s ease;
        }
        
        .ujc-resource-item-compact:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            border-color: #007cba;
        }
        
        /* Pierwszy rząd: Nazwa i status */
        .ujc-resource-row-1 {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        
        .ujc-resource-title {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .ujc-resource-title strong {
            font-size: 14px;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .ujc-surface {
            font-size: 12px;
            color: #6c757d;
            font-weight: 500;
        }
        
        /* Drugi rząd: Ceny i akcje */
        .ujc-resource-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 8px;
            align-items: center;
            padding-top: 8px;
            border-top: 1px solid #e9ecef;
        }
        
        .ujc-price-main {
            font-size: 13px;
            font-weight: 600;
            color: #007cba;
        }
        
        .ujc-price-total {
            font-size: 13px;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .ujc-price-extra {
            font-size: 13px;
            font-weight: 500;
            color: #6c757d;
        }
        
        .ujc-resource-actions {
            display: flex;
            gap: 4px;
        }
        
        .ujc-resource-actions .button {
            padding: 4px 8px;
            font-size: 11px;
            line-height: 1.2;
            min-height: auto;
            white-space: nowrap;
        }
        
        /* Tooltip z dodatkowymi informacjami */
        .ujc-resource-tooltip {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #2c3e50;
            color: #fff;
            padding: 12px;
            border-radius: 6px;
            font-size: 12px;
            line-height: 1.4;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s ease;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .ujc-resource-item-compact:hover .ujc-resource-tooltip {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .ujc-resource-tooltip div {
            margin-bottom: 4px;
        }
        
        .ujc-resource-tooltip div:last-child {
            margin-bottom: 0;
        }
        
        .ujc-status-badge {
            padding: 3px 8px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
        }
        
        .ujc-status-available {
            background: #d4edda;
            color: #155724;
        }
        
        .ujc-status-reserved {
            background: #fff3cd;
            color: #856404;
        }
        
        .ujc-status-sold {
            background: #f8d7da;
            color: #721c24;
        }
        
        .ujc-status-default {
            background: #e9ecef;
            color: #6c757d;
        }
        
        /* Responsive dla mniejszych ekranów */
        @media (max-width: 1200px) {
            .ujc-resources-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 12px;
            }
        }
        
        @media (max-width: 768px) {
            .ujc-resources-grid {
                grid-template-columns: 1fr;
                gap: 8px;
            }
            
            .ujc-resource-row-2 {
                grid-template-columns: 1fr;
                gap: 4px;
            }
            
            .ujc-resource-actions {
                justify-self: end;
                grid-column: 1;
            }
        }

        .ujc-no-resources {
            grid-column: 1 / -1;
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