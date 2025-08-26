<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper do wyświetlania tooltip z informacjami o częściach nieruchomości
 * Używany w komponencie resource-modal
 */
class UJC_Property_Parts_Tooltip {
    
    /**
     * Zwraca treść tooltip jako string
     */
    public static function get_tooltip_content() {
        return "UWAGA: Te informacje mają charakter wyłącznie edukacyjny i nie stanowią porady prawnej. W przypadku wątpliwości skonsultuj się z prawnikiem.

CZĘŚCI NIERUCHOMOŚCI - orientacyjne wytyczne:

🏠 JAKO CZĘŚĆ GŁÓWNEGO ZASOBU:
• Balkon, loggia, taras - zawsze część mieszkania
• Komórka/piwnica gdy cena wliczona w m² mieszkania  
• Parking gdy cena wliczona w m² mieszkania
• Pomieszczenia z wspólną księgą wieczystą

📋 JAKO ODDZIELNE ZASOBY:
• Parking za dodatkową opłatą (np. +50k zł)
• Komórka opcjonalna (można kupić mieszkanie bez niej)
• Garaż wolnostojący z osobną ceną
• Elementy z różnymi terminami dostępności

⚖️ KRYTERIUM GŁÓWNE: 
Czy cena części jest uwzględniona w cenie za m² powierzchni użytkowej mieszkania?

⚠️ ZASTRZEŻENIE: Plugin nie dostarcza porad prawnych. Za interpretację przepisów i zgodność z prawem odpowiada użytkownika.";
    }
    
    /**
     * Renderuje ikonę tooltip z informacją
     */
    public static function render_icon() {
        $tooltip_content = esc_attr(self::get_tooltip_content());
        
        return sprintf(
            '<span style="cursor: help; color: #666;" title="%s">ℹ️ Kiedy stosować \'Część nieruchomości\'?</span>',
            $tooltip_content
        );
    }
}