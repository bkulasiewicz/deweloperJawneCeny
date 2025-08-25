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
        return "
            <div class=\"ujc-resource-item\">
                <div class=\"ujc-resource-header\">
                    <div class=\"ujc-resource-name\">{$resource['rodzaj_nieruchomosci']} {$resource['nr_lokalu']}</div>
                    <div class=\"ujc-resource-status\">Status: {$resource['status']}</div>
                </div>
                <div class=\"ujc-resource-details\">
                    <div class=\"ujc-resource-row\">
                        <div class=\"ujc-resource-field\">
                            <label>Powierzchnia użytkowa:</label>
                            <span>{$resource['powierzchnia_uzytkowa']} m²</span>
                        </div>
                        <div class=\"ujc-resource-field\">
                            <label>Cena za metr kwadratowy:</label>
                            <span>{$resource['cena_m2']} zł</span>
                        </div>
                        <div class=\"ujc-resource-field\">
                            <label>Cena całkowita:</label>
                            <span>{$resource['cena_calkowita']} zł</span>
                        </div>
                    </div>
                    <div class=\"ujc-resource-row\">
                        <div class=\"ujc-resource-field\">
                            <label>Cena z dodatkami:</label>
                            <span>{$resource['cena_z_dodatkami']} zł</span>
                        </div>
                        <div class=\"ujc-resource-field\">
                            <label>Data aktualizacji ceny m²:</label>
                            <span>{$resource['data_cena_m2']}</span>
                        </div>
                        <div class=\"ujc-resource-actions\">
                            <button type=\"button\" class=\"button button-small\" onclick=\"showResourceHistory({$resource['id']})\">Historia cen</button>
                            <button type=\"button\" class=\"button button-small button-primary\" onclick=\"openResourceModal('edit', {$resource['id']})\">Edytuj zasób</button>
                        </div>
                    </div>
                </div>
            </div>
        ";
    }
    
    /**
     * Renderuje JavaScript dla obsługi itemów
     */
    public static function render_item_script() {
        ?>
        <script>
        // JavaScript component dla itemów zasobów
        window.UJC_Resource_Item = {
            renderHTML: function(resource) {
                return `
                    <div class="ujc-resource-item">
                        <div class="ujc-resource-header">
                            <div class="ujc-resource-name">${resource.rodzaj_nieruchomosci} ${resource.nr_lokalu}</div>
                            <div class="ujc-resource-status">Status: ${resource.status}</div>
                        </div>
                        <div class="ujc-resource-details">
                            <div class="ujc-resource-row">
                                <div class="ujc-resource-field">
                                    <label>Powierzchnia użytkowa:</label>
                                    <span>${resource.powierzchnia_uzytkowa} m²</span>
                                </div>
                                <div class="ujc-resource-field">
                                    <label>Cena za metr kwadratowy:</label>
                                    <span>${resource.cena_m2} zł</span>
                                </div>
                                <div class="ujc-resource-field">
                                    <label>Cena całkowita:</label>
                                    <span>${resource.cena_calkowita} zł</span>
                                </div>
                            </div>
                            <div class="ujc-resource-row">
                                <div class="ujc-resource-field">
                                    <label>Cena z dodatkami:</label>
                                    <span>${resource.cena_z_dodatkami} zł</span>
                                </div>
                                <div class="ujc-resource-field">
                                    <label>Data aktualizacji ceny m²:</label>
                                    <span>${resource.data_cena_m2}</span>
                                </div>
                                <div class="ujc-resource-actions">
                                    <button type="button" class="button button-small" onclick="showResourceHistory(${resource.id})">Historia cen</button>
                                    <button type="button" class="button button-small button-primary" onclick="openResourceModal('edit', ${resource.id})">Edytuj zasób</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
        };
        
        
        </script>
        <?php
    }
    
    /**
     * Renderuje CSS dla itemów
     */
    public static function render_item_styles() {
        ?>
        <style>
        /* Nowy styl itemów zasobów - 2 rzędy pionowo */
        .ujc-resource-item {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: box-shadow 0.2s ease;
        }

        .ujc-resource-item:hover {
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }

        .ujc-resource-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }

        .ujc-resource-name {
            font-weight: 600;
            font-size: 16px;
            color: #0073aa;
        }

        .ujc-resource-status {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }

        .ujc-resource-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .ujc-resource-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .ujc-resource-field {
            display: flex;
            flex-direction: column;
            gap: 2px;
            flex: 1;
        }

        .ujc-resource-field label {
            font-size: 12px;
            color: #666;
            font-weight: 500;
        }

        .ujc-resource-field span {
            font-size: 14px;
            color: #333;
            font-weight: 600;
        }

        .ujc-resource-actions {
            display: flex;
            gap: 8px;
            flex: none;
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