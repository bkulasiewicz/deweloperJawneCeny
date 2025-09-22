<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pure CSV formatting service - only handles CSV generation logic
 * File management is handled by FileManager
 */
class CSVFormatter {
    
    /**
     * Generate CSV rows from developer, investment and resources data
     * 
     * @param SupplierDto $developer Developer data
     * @param InvestmentDto $investment Investment data
     * @param ResourceDto[] $resources Array of ResourceDto objects
     * @return Generator CSV rows as generator for memory efficiency
     */
    public function generate(SupplierDto $developer, InvestmentDto $investment, array $resources, array $priceHistory = []): array {
        // Detect which components exist in resources
        $componentFlags = $this->detectComponentsInResources($resources);
        
        $csvRows = [];
        
        // Add headers first
        $csvRows[] = $this->getCsvHeaders($componentFlags);
        
        // Add data rows
        foreach ($resources as $resource) {
            $resourcePrices = $priceHistory[$resource->id];
            $csvRows[] = $this->resourceToRow($developer, $investment, $componentFlags, $resource, $resourcePrices);
        }
        
        return $csvRows;
    }
    
    /**
     * Detect which components exist in resources
     */
    private function detectComponentsInResources(array $resources): array {
        $flags = [
            'has_property_parts' => false,
            'has_belonging_rooms' => false,
            'has_usage_rights' => false,
            'has_other_services' => false
        ];
        
        foreach ($resources as $resource) {
            // Check for property parts
            if (!empty($resource->property_part_title)) {
                $flags['has_property_parts'] = true;
            }
            
            // Check for belonging rooms
            if (!empty($resource->belonging_room_title)) {
                $flags['has_belonging_rooms'] = true;
            }
            
            // Check for usage rights
            if (!empty($resource->usage_right_title)) {
                $flags['has_usage_rights'] = true;
            }
            
            // Check for other services
            if (!empty($resource->other_service_title)) {
                $flags['has_other_services'] = true;
            }
            
            // Early exit if all components detected
            if ($flags['has_property_parts'] && $flags['has_belonging_rooms'] && 
                $flags['has_usage_rights'] && $flags['has_other_services']) {
                break;
            }
        }
        
        return $flags;
    }
    
    /**
     * Get CSV headers according to Polish law requirements
     * Dynamic headers based on detected components in resources
     */
    private function getCsvHeaders(array $componentFlags): array {
        // Basic headers - always included
        $headers = [
            'Nazwa dewelopera',
            'Forma prawna dewelopera',
            'Nr KRS',
            'Nr wpisu do CEiDG',
            'Nr NIP',
            'Nr REGON',
            'Nr telefonu',
            'Adres poczty elektronicznej',
            'Nr faxu',
            'Adres strony internetowej dewelopera',
            'Województwo adresu siedziby/głównego miejsca wykonywania działalności gospodarczej dewelopera',
            'Powiat adresu siedziby/głównego miejsca wykonywania działalności gospodarczej dewelopera ',
            'Gmina adresu siedziby/głównego miejsca wykonywania działalności gospodarczej dewelopera',
            'Miejscowość adresu siedziby/głównego miejsca wykonywania działalności gospodarczej dewelopera',
            'Ulica adresu siedziby/głównego miejsca wykonywania działalności gospodarczej dewelopera',
            'Nr nieruchomości adresu siedziby/głównego miejsca wykonywania działalności gospodarczej dewelopera',
            'Nr lokalu adresu siedziby/głównego miejsca wykonywania działalności gospodarczej dewelopera',
            'Kod pocztowy adresu siedziby/głównego miejsca wykonywania działalności gospodarczej dewelopera',
            'Województwo adresu lokalu, w którym prowadzona jest sprzedaż',
            'Powiat adresu lokalu, w którym prowadzona jest sprzedaż',
            'Gmina adresu lokalu, w którym prowadzona jest sprzedaż',
            'Miejscowość adresu lokalu, w którym prowadzona jest sprzedaż',
            'Ulica adresu lokalu, w którym prowadzona jest sprzedaż',
            'Nr nieruchomości adresu lokalu, w którym prowadzona jest sprzedaż',
            'Nr lokalu adresu lokalu, w którym prowadzona jest sprzedaż',
            'Kod pocztowy adresu lokalu, w którym prowadzona jest sprzedaż',
            'Dodatkowe lokalizacje, w których prowadzona jest sprzedaż',
            'Sposób kontaktu nabywcy z deweloperem',
            'Nazwa przedsięwzięcia deweloperskiego lub zadania inwestycyjnego',
            'Województwo lokalizacji przedsięwzięcia deweloperskiego lub zadania inwestycyjnego',
            'Powiat lokalizacji przedsięwzięcia deweloperskiego lub zadania inwestycyjnego',
            'Gmina lokalizacji przedsięwzięcia deweloperskiego lub zadania inwestycyjnego',
            'Miejscowość lokalizacji przedsięwzięcia deweloperskiego lub zadania inwestycyjnego',
            'Ulica lokalizacji przedsięwzięcia deweloperskiego lub zadania inwestycyjnego',
            'Nr nieruchomości lokalizacji przedsięwzięcia deweloperskiego lub zadania inwestycyjnego',
            'Kod pocztowy lokalizacji przedsięwzięcia deweloperskiego lub zadania inwestycyjnego',
            'Rodzaj nieruchomości',
            'Nr produktu nadany przez dewelopera',
            'Powierzchnia użytkowa [m2]',
            'Cena brutto/m2 powierzchni użytkowej produktu [zł]',
            'Data od której obowiązuje cena brutto/m2 powierzchni użytkowej produktu',
            'Cena brutto produktu będącego przedmiotem umowy stanowiąca iloczyn ceny m2 oraz powierzchni użytkowej [zł]',
            'Data od której obowiązuje cena brutto produktu będącego przedmiotem umowy stanowiąca iloczyn ceny m2 oraz powierzchni użytkowej',
            'Cena brutto produktu uwzględniająca cenę produktu stanowiącą iloczyn powierzchni użytkowej oraz metrażu i innych składowych ceny, o których mowa w art. 19a ust. 1 pkt 1), 2) lub 3) [zł]',
            'Data od której obowiązuje cena brutto produktu uwzględniająca cenę produktu stanowiącą iloczyn powierzchni użytkowej oraz metrażu i innych składowych ceny, o których mowa w art. 19a ust. 1 pkt 1), 2) lub 3)'
        ];
        
        // Dynamic component headers based on detected components
        if ($componentFlags['has_property_parts']) {
            $headers = array_merge($headers, [
                'Rodzaj części nieruchomości będących przedmiotem umowy',
                'Oznaczenie części nieruchomości nadane przez dewelopera',
                'Cena części nieruchomości, będących przedmiotem umowy [zł]',
                'Data od której obowiązuje cena części nieruchomości, będących przedmiotem umowy'
            ]);
        }
        
        if ($componentFlags['has_belonging_rooms']) {
            $headers = array_merge($headers, [
                'Rodzaj pomieszczeń przynależnych, o których mowa w art. 2 ust. 4 ustawy z dnia 24 czerwca 1994 r. o własności lokali',
                'Oznaczenie pomieszczeń przynależnych, o których mowa w art. 2 ust. 4 ustawy z dnia 24 czerwca 1994 r. o własności lokali',
                'Wyszczególnienie cen pomieszczeń przynależnych, o których mowa w art. 2 ust. 4 ustawy z dnia 24 czerwca 1994 r. o własności lokali [zł]',
                'Data od której obowiązuje cena wyszczególnionych pomieszczeń przynależnych, o których mowa w art. 2 ust. 4 ustawy z dnia 24 czerwca 1994 r. o własności lokali'
            ]);
        }
        
        if ($componentFlags['has_usage_rights']) {
            $headers = array_merge($headers, [
                'Wyszczególnienie praw niezbędnych do korzystania z lokalu mieszkalnego lub domu jednorodzinnego',
                'Wartość praw niezbędnych do korzystania z lokalu mieszkalnego lub domu jednorodzinnego [zł]',
                'Data od której obowiązuje cena wartości praw niezbędnych do korzystania z lokalu mieszkalnego lub domu jednorodzinnego'
            ]);
        }
        
        if ($componentFlags['has_other_services']) {
            $headers = array_merge($headers, [
                'Wyszczególnienie rodzajów innych świadczeń pieniężnych, które nabywca zobowiązany jest spełnić na rzecz dewelopera w wykonaniu umowy przenoszącej własność',
                'Wartość innych świadczeń pieniężnych, które nabywca zobowiązany jest spełnić na rzecz dewelopera w wykonaniu umowy przenoszącej własność [zł]',
                'Data od której obowiązuje cena wartości innych świadczeń pieniężnych, które nabywca zobowiązany jest spełnić na rzecz dewelopera w wykonaniu umowy przenoszącej własność'
            ]);
        }
        
        return $headers;
    }
    
    /**
     * Convert resource data to CSV row
     * Dynamic row generation based on detected components
     */
    private function resourceToRow(SupplierDto $developer, InvestmentDto $investment, array $componentFlags, ResourceDto $resource, PriceHistoryDto $prices): array {
        // Basic row data - always included
        $row = [
            $developer->nazwa,
            $developer->forma_prawna ?? '',
            $developer->nr_krs,
            $developer->nr_ceidg ?? '',
            $developer->nr_nip,
            $developer->nr_regon ?? '',
            $developer->telefon ?? '',
            $developer->email,
            $developer->fax ?? '',
            $developer->strona_www,
            
            // Headquarters address
            $developer->siedz_wojewodztwo,
            $developer->siedz_powiat ?? '',
            $developer->siedz_gmina ?? '',
            $developer->siedz_miejscowosc,
            $developer->siedz_ulica,
            $developer->siedz_nr,
            $developer->siedz_lokal ?? '',
            $developer->siedz_kod,
            
            // Sales address
            $developer->sprzed_wojewodztwo,
            $developer->sprzed_powiat ?? '',
            $developer->sprzed_gmina ?? '',
            $developer->sprzed_miejscowosc,
            $developer->sprzed_ulica,
            $developer->sprzed_nr,
            $developer->sprzed_lokal ?? '',
            $developer->sprzed_kod,
            
            $developer->dodatkowe_lokalizacje ?? '',
            $developer->sposob_kontaktu ?? '',
            $investment->name,

            // Project location
            $investment->proj_wojewodztwo,
            $investment->proj_powiat,
            $investment->proj_gmina,
            $investment->proj_miejscowosc,
            $investment->proj_ulica,
            $investment->proj_nr,
            $investment->proj_kod,
            
            // Property - use current prices from history
            $resource->rodzaj_nieruchomosci->getDisplayText(),
            $resource->nr_lokalu,
            $resource->powierzchnia_uzytkowa ?? '',
            $prices->cena_m2 ?? '',
            DateHelper::formatForCsv($prices->data_zmiany),
            
            // Total price
            $prices->cena_calkowita ?? '',
            DateHelper::formatForCsv($prices->data_zmiany),
            $prices->cena_z_dodatkami ?? '',
            DateHelper::formatForCsv($prices->data_cena_z_dodatkami)
        ];
        
        // Dynamic component data based on detected components
        if ($componentFlags['has_property_parts']) {
            $row = array_merge($row, [
                $resource->property_part_title ?? '',
                $resource->property_part_designation ?? '',
                $resource->property_part_price ?? '',
                $this->safeFormatDate($resource->property_part_price_date, 'property_part_price_date')
            ]);
        }
        
        if ($componentFlags['has_belonging_rooms']) {
            $row = array_merge($row, [
                $resource->belonging_room_title ?? '',
                $resource->belonging_room_designation ?? '',
                $resource->belonging_room_price ?? '',
                $this->safeFormatDate($resource->belonging_room_price_date, 'belonging_room_price_date')
            ]);
        }
        
        if ($componentFlags['has_usage_rights']) {
            $row = array_merge($row, [
                $resource->usage_right_title ?? '',
                $resource->usage_right_price ?? '',
                $this->safeFormatDate($resource->usage_right_price_date, 'usage_right_price_date')
            ]);
        }
        
        if ($componentFlags['has_other_services']) {
            $row = array_merge($row, [
                $resource->other_service_title ?? '',
                $resource->other_service_price ?? '',
                $this->safeFormatDate($resource->other_service_price_date, 'other_service_price_date')
            ]);
        }
        
        // Replace all empty values with 'X' according to law template
        return $this->applyCsvEmptyRules($row);
    }
    
    /**
     * Replace empty values with 'X' according to CSV law template
     */
    private function applyCsvEmptyRules(array $row): array {
        return array_map(fn($value) => empty($value) ? 'X' : $value, $row);
    }
    
    /**
     * Safely format date with logging for debugging
     */
    private function safeFormatDate($date, $field_name): string {
        try {
            if (empty($date)) {
                return '';
            }
            
            return DateHelper::formatForCsv($date);
        } catch (Exception $e) {
            Logger::error("CSVFormatter: Exception formatting date for field '{$field_name}': " . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Clean filename part (remove unsafe characters) according to law requirements
     */
    public function sanitizeFilenamePart(string $name): string {
        // Remove Polish characters and replace with safe ones
        $name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        // Remove everything except letters, numbers, hyphens
        $name = preg_replace('/[^a-zA-Z0-9\\-]/', '-', $name);
        // Remove multiple hyphens
        $name = preg_replace('/-+/', '-', $name);
        // Remove hyphens from start and end
        return trim($name, '-');
    }
}