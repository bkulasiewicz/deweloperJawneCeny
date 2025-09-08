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
    public function generate(SupplierDto $developer, InvestmentDto $investment, array $resources, array $priceHistory = []): Generator {
        // Yield headers first
        yield $this->getCsvHeaders($investment);
        
        // Yield data rows
        foreach ($resources as $resource) {
            $resourcePrices = $priceHistory[$resource->id];
            yield $this->resourceToRow($developer, $investment, $resource, $resourcePrices);
        }
    }
    
    /**
     * Get CSV headers according to Polish law requirements
     * Dynamic headers based on investment configuration
     */
    private function getCsvHeaders(InvestmentDto $investment): array {
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
            'Województwo lokalizacji przedsięwzięcia deweloperskiego lub zadania inwestycyjnego',
            'Powiat lokalizacji przedsięwzięcia deweloperskiego lub zadania inwestycyjnego',
            'Gmina lokalizacji przedsięwzięcia deweloperskiego lub zadania inwestycyjnego',
            'Miejscowość lokalizacji przedsięwzięcia deweloperskiego lub zadania inwestycyjnego',
            'Ulica lokalizacji przedsięwzięcia deweloperskiego lub zadania inwestycyjnego',
            'Nr nieruchomości lokalizacji przedsięwzięcia deweloperskiego lub zadania inwestycyjnego',
            'Kod pocztowy lokalizacji przedsięwzięcia deweloperskiego lub zadania inwestycyjnego',
            'Rodzaj nieruchomości: lokal mieszkalny, dom jednorodzinny ',
            'Nr lokalu lub domu jednorodzinnego nadany przez dewelopera',
            'Cena m 2 powierzchni użytkowej lokalu mieszkalnego / domu jednorodzinnego [zł]',
            'Data od której obowiązuje cena m 2 powierzchni użytkowej lokalu mieszkalnego / domu jednorodzinnego',
            'Cena lokalu mieszkalnego lub domu jednorodzinnego będących przedmiotem umowy stanowiąca iloczyn ceny m2 oraz powierzchni [zł]',
            'Data od której obowiązuje cena lokalu mieszkalnego lub domu jednorodzinnego będących przedmiotem umowy stanowiąca iloczyn ceny m2 oraz powierzchni',
            'Cena lokalu mieszkalnego lub domu jednorodzinnego uwzględniająca cenę lokalu stanowiącą iloczyn powierzchni oraz metrażu i innych składowych ceny, o których mowa w art. 19a ust. 1 pkt 1), 2) lub 3) [zł]',
            'Data od której obowiązuje cena lokalu mieszkalnego lub domu jednorodzinnego uwzględniająca cenę lokalu stanowiącą iloczyn powierzchni oraz metrażu i innych składowych ceny, o których mowa w art. 19a ust. 1 pkt 1), 2) lub 3)'
        ];
        
        // Dynamic component headers based on investment configuration
        if ($investment->has_property_parts) {
            $headers = array_merge($headers, [
                'Rodzaj części nieruchomości będących przedmiotem umowy',
                'Oznaczenie części nieruchomości nadane przez dewelopera',
                'Cena części nieruchomości, będących przedmiotem umowy [zł]',
                'Data od której obowiązuje cena części nieruchomości, będących przedmiotem umowy'
            ]);
        }
        
        if ($investment->has_belonging_rooms) {
            $headers = array_merge($headers, [
                'Rodzaj pomieszczeń przynależnych, o których mowa w art. 2 ust. 4 ustawy z dnia 24 czerwca 1994 r. o własności lokali',
                'Oznaczenie pomieszczeń przynależnych, o których mowa w art. 2 ust. 4 ustawy z dnia 24 czerwca 1994 r. o własności lokali',
                'Wyszczególnienie cen pomieszczeń przynależnych, o których mowa w art. 2 ust. 4 ustawy z dnia 24 czerwca 1994 r. o własności lokali [zł]',
                'Data od której obowiązuje cena wyszczególnionych pomieszczeń przynależnych, o których mowa w art. 2 ust. 4 ustawy z dnia 24 czerwca 1994 r. o własności lokali'
            ]);
        }
        
        if ($investment->has_usage_rights) {
            $headers = array_merge($headers, [
                'Wyszczególnienie praw niezbędnych do korzystania z lokalu mieszkalnego lub domu jednorodzinnego',
                'Wartość praw niezbędnych do korzystania z lokalu mieszkalnego lub domu jednorodzinnego [zł]',
                'Data od której obowiązuje cena wartości praw niezbędnych do korzystania z lokalu mieszkalnego lub domu jednorodzinnego'
            ]);
        }
        
        if ($investment->has_other_services) {
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
     * Dynamic row generation based on investment configuration
     */
    private function resourceToRow(SupplierDto $developer, InvestmentDto $investment, ResourceDto $resource, PriceHistoryDto $prices): array {
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
            
            // Project location
            $investment->proj_wojewodztwo,
            $investment->proj_powiat,
            $investment->proj_gmina,
            $investment->proj_miejscowosc,
            $investment->proj_ulica,
            $investment->proj_nr,
            $investment->proj_kod,
            
            // Property - use current prices from history
            $resource->rodzaj_nieruchomosci,
            $resource->nr_lokalu,
            $prices->cena_m2 ?? '',
            DateHelper::formatForCsv($prices->data_zmiany),
            
            // Total price
            $prices->cena_calkowita ?? '',
            DateHelper::formatForCsv($prices->data_zmiany),
            $prices->cena_z_dodatkami ?? '',
            DateHelper::formatForCsv($prices->data_cena_z_dodatkami)
        ];
        
        // Dynamic component data based on investment configuration
        if ($investment->has_property_parts) {
            $row = array_merge($row, [
                $this->getFirstComponentField($resource, 'property_parts', 'type'),
                $this->getFirstComponentField($resource, 'property_parts', 'designation'),
                $this->getFirstComponentField($resource, 'property_parts', 'price'),
                $this->getFirstComponentFieldDate($resource, 'property_parts', 'price_date')
            ]);
        }
        
        if ($investment->has_belonging_rooms) {
            $row = array_merge($row, [
                $this->getFirstComponentField($resource, 'belonging_rooms', 'type'),
                $this->getFirstComponentField($resource, 'belonging_rooms', 'designation'),
                $this->getFirstComponentField($resource, 'belonging_rooms', 'price'),
                $this->getFirstComponentFieldDate($resource, 'belonging_rooms', 'price_date')
            ]);
        }
        
        if ($investment->has_usage_rights) {
            $row = array_merge($row, [
                $this->getFirstComponentField($resource, 'usage_rights', 'description'),
                $this->getFirstComponentField($resource, 'usage_rights', 'price'),
                $this->getFirstComponentFieldDate($resource, 'usage_rights', 'price_date')
            ]);
        }
        
        if ($investment->has_other_services) {
            $row = array_merge($row, [
                $this->getFirstComponentField($resource, 'other_services', 'description'),
                $this->getFirstComponentField($resource, 'other_services', 'price'),
                $this->getFirstComponentFieldDate($resource, 'other_services', 'price_date')
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
     * Get first component field value from ResourceDto
     */
    private function getFirstComponentField(ResourceDto $resource, $componentType, $field) {
        // Check if resource has the component property
        if (!property_exists($resource, $componentType) || empty($resource->$componentType)) {
            return '';
        }
        
        $components = $resource->$componentType;
        if (!is_array($components) || empty($components)) {
            return '';
        }
        
        $firstComponent = $components[0] ?? null;
        if (!$firstComponent) {
            return '';
        }
        
        // Handle both model objects and arrays
        if (is_object($firstComponent)) {
            return $firstComponent->$field ?? '';
        } else {
            return $firstComponent[$field] ?? '';
        }
    }
    
    /**
     * Get first component date field value from ResourceDto
     */
    private function getFirstComponentFieldDate(ResourceDto $resource, $componentType, $field) {
        // Check if resource has the component property
        if (!property_exists($resource, $componentType) || empty($resource->$componentType)) {
            return '';
        }
        
        $components = $resource->$componentType;
        if (!is_array($components) || empty($components)) {
            return '';
        }
        
        $firstComponent = $components[0] ?? null;
        if (!$firstComponent) {
            return '';
        }
        
        // Handle both model objects and arrays
        if (is_object($firstComponent)) {
            $date = $firstComponent->$field ?? null;
            if ($date instanceof DateTime) {
                return DateHelper::formatForCsv($date->format('Y-m-d'));
            }
            return DateHelper::formatForCsv($date);
        } else {
            return DateHelper::formatForCsv($firstComponent[$field] ?? null);
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