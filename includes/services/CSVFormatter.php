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
     * @param array $developer Developer data
     * @param array $investment Investment data
     * @param array $resources Array of resource data
     * @return Generator CSV rows as generator for memory efficiency
     */
    public function generate(array $developer, array $investment, array $resources): Generator {
        // Yield headers first
        yield $this->getCsvHeaders();
        
        // Yield data rows
        foreach ($resources as $resource) {
            yield $this->resourceToRow($developer, $investment, $resource);
        }
    }
    
    /**
     * Get CSV headers according to Polish law requirements
     */
    private function getCsvHeaders(): array {
        return [
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
            'Data od której obowiązuje cena lokalu mieszkalnego lub domu jednorodzinnego uwzględniająca cenę lokalu stanowiącą iloczyn powierzchni oraz metrażu i innych składowych ceny, o których mowa w art. 19a ust. 1 pkt 1), 2) lub 3)',
            'Rodzaj części nieruchomości będących przedmiotem umowy',
            'Oznaczenie części nieruchomości nadane przez dewelopera',
            'Cena części nieruchomości, będących przedmiotem umowy [zł]',
            'Data od której obowiązuje cena części nieruchomości, będących przedmiotem umowy'
        ];
    }
    
    /**
     * Convert resource data to CSV row
     */
    private function resourceToRow(array $developer, array $investment, array $resource): array {
        $row = [
            $developer['nazwa'] ?? '',
            $developer['forma_prawna'] ?? '',
            $developer['nr_krs'] ?? '',
            $developer['nr_ceidg'] ?? '',
            $developer['nr_nip'] ?? '',
            $developer['nr_regon'] ?? '',
            $developer['telefon'] ?? '',
            $developer['email'] ?? '',
            $developer['fax'] ?? '',
            $developer['strona_www'] ?? '',
            
            // Headquarters address
            $developer['siedz_wojewodztwo'] ?? '',
            $developer['siedz_powiat'] ?? '',
            $developer['siedz_gmina'] ?? '',
            $developer['siedz_miejscowosc'] ?? '',
            $developer['siedz_ulica'] ?? '',
            $developer['siedz_nr'] ?? '',
            $developer['siedz_lokal'] ?? '',
            $developer['siedz_kod'] ?? '',
            
            // Sales address
            $developer['sprzed_wojewodztwo'] ?? $developer['siedz_wojewodztwo'] ?? '',
            $developer['sprzed_powiat'] ?? $developer['siedz_powiat'] ?? '',
            $developer['sprzed_gmina'] ?? $developer['siedz_gmina'] ?? '',
            $developer['sprzed_miejscowosc'] ?? $developer['siedz_miejscowosc'] ?? '',
            $developer['sprzed_ulica'] ?? $developer['siedz_ulica'] ?? '',
            $developer['sprzed_nr'] ?? $developer['siedz_nr'] ?? '',
            $developer['sprzed_lokal'] ?? $developer['siedz_lokal'] ?? '',
            $developer['sprzed_kod'] ?? $developer['siedz_kod'] ?? '',
            
            $developer['dodatkowe_lokalizacje'] ?? '',
            $developer['sposob_kontaktu'] ?? '',
            
            // Project location
            $investment['proj_wojewodztwo'] ?? '',
            $investment['proj_powiat'] ?? '',
            $investment['proj_gmina'] ?? '',
            $investment['proj_miejscowosc'] ?? '',
            $investment['proj_ulica'] ?? '',
            $investment['proj_nr'] ?? '',
            $investment['proj_kod'] ?? '',
            
            // Property - use current prices from history or basic ones
            $resource['rodzaj_nieruchomosci'] ?? '',
            $resource['nr_lokalu'] ?? '',
            $resource['current_cena_m2'] ?? $resource['cena_m2'] ?? '',
            DateHelper::formatForCsv($resource['current_data_zmiany'] ?? $resource['data_cena_m2']),
            
            // Total price
            $resource['current_cena_calkowita'] ?? $resource['cena_calkowita'] ?? '',
            DateHelper::formatForCsv($resource['current_data_zmiany'] ?? $resource['data_cena_calkowita']),
            $resource['current_cena_z_dodatkami'] ?? $resource['cena_z_dodatkami'] ?? '',
            DateHelper::formatForCsv($resource['current_data_zmiany'] ?? $resource['data_cena_z_dodatkami']),
            
            // Property parts (direct from resources table)
            $resource['extra_rodzaj_czesci'] ?? '',
            $resource['extra_oznaczenie_czesci'] ?? '', 
            $resource['extra_cena_czesci'] ?? '',
            DateHelper::formatForCsv($resource['extra_data_cena_czesci'] ?? null)
        ];
        
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