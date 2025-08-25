<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generator CSV - podstawowa implementacja MVP
 */
class UJC_CSV_Generator {
    
    public function generate_daily_csv() {
        $developer_repo = new UJC_Developer_Repository();
        $developer = $developer_repo->read();
        $resource_repo = new UJC_Resource_Repository();
        $properties = $resource_repo->readAll();
        
        if (!$developer || empty($properties)) {
            return false;
        }
        
        // Przygotuj dane CSV
        $csv_data = [];
        $csv_data[] = $this->get_csv_headers();
        
        foreach ($properties as $property) {
            $csv_data[] = $this->property_to_csv_row($developer, $property);
        }
        
        // Zapisz CSV
        $upload_dir = wp_upload_dir();
        $ujc_dir = $upload_dir['basedir'] . '/ujc-data';
        if (!file_exists($ujc_dir)) {
            wp_mkdir_p($ujc_dir);
        }
        
        // NAZWA zgodna z wymaganiami ustawy: Ceny-ofertowe-mieszkan-dewelopera-{nazwa dewelopera}-{YYYY-MM-DD}.csv
        $developer_name_clean = $this->sanitize_filename_part($developer['nazwa']);
        $date_string = date('Y-m-d');
        $filename = "Ceny-ofertowe-mieszkan-dewelopera-{$developer_name_clean}-{$date_string}.csv";
        $filepath = $ujc_dir . '/' . $filename;
        
        $file = fopen($filepath, 'w');
        if (!$file) {
            return false;
        }
        
        foreach ($csv_data as $row) {
            fputcsv($file, $row, ';');
        }
        fclose($file);
        
        // MD5 NIE jest wymagane dla CSV (tylko dla XML)
        
        return [
            'success' => true,
            'filepath' => $filepath,
            'filename' => $filename,
            'url' => $upload_dir['baseurl'] . '/ujc-data/' . $filename
        ];
    }
    
    /**
     * Czyści część nazwy pliku (usuwa niebezpieczne znaki) zgodnie z wymaganiami ustawy
     */
    private function sanitize_filename_part($name) {
        // Usuń polskie znaki i zastąp bezpiecznymi
        $name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        // Usuń wszystko oprócz liter, cyfr, myślników
        $name = preg_replace('/[^a-zA-Z0-9\-]/', '-', $name);
        // Usuń wielokrotne myślniki
        $name = preg_replace('/-+/', '-', $name);
        // Usuń myślniki z początku i końca
        return trim($name, '-');
    }
    
    private function get_csv_headers() {
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
            'Powiat adresu siedziby/głównego miejsca wykonywania działalności gospodarczej dewelopera',
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
            'Rodzaj nieruchomości: lokal mieszkalny, dom jednorodzinny',
            'Nr lokalu lub domu jednorodzinnego nadany przez dewelopera',
            'Cena m 2 powierzchni użytkowej lokalu mieszkalnego / domu jednorodzinnego [zł]',
            'Data od której cena obowiązuje cena m 2 powierzchni użytkowej lokalu mieszkalnego / domu jednorodzinnego',
            'Cena lokalu mieszkalnego lub domu jednorodzinnego będących przedmiotem umowy stanowiąca iloczyn ceny m2 oraz powierzchni [zł]',
            'Data od której cena obowiązuje cena lokalu mieszkalnego lub domu jednorodzinnego będących przedmiotem umowy stanowiąca iloczyn ceny m2 oraz powierzchni',
            'Cena lokalu mieszkalnego lub domu jednorodzinnego uwzględniająca cenę lokalu stanowiącą iloczyn powierzchni oraz metrażu i innych składowych ceny, o których mowa w art. 19a ust. 1 pkt 1), 2) lub 3) [zł]',
            'Data od której obowiązuje cena lokalu mieszkalnego lub domu jednorodzinnego uwzględniająca cenę lokalu stanowiącą iloczyn powierzchni oraz metrażu i innych składowych ceny, o których mowa w art. 19a ust. 1 pkt 1), 2) lub 3)',
            'Rodzaj części nieruchomości będących przedmiotem umowy',
            'Oznaczenie części nieruchomości nadane przez dewelopera',
            'Cena części nieruchomości, będących przedmiotem umowy [zł]',
            'Data od której obowiązuje cena części nieruchomości, będących przedmiotem umowy',
            'Rodzaj pomieszczeń przynależnych, o których mowa w art. 2 ust. 4 ustawy z dnia 24 czerwca 1994 r. o własności lokali',
            'Oznaczenie pomieszczeń przynależnych, o których mowa w art. 2 ust. 4 ustawy z dnia 24 czerwca 1994 r. o własności lokali',
            'Wyszczególnienie cen pomieszczeń przynależnych, o których mowa w art. 2 ust. 4 ustawy z dnia 24 czerwca 1994 r. o własności lokali [zł]',
            'Data od której obowiązuje cena wyszczególnionych pomieszczeń przynależnych, o których mowa w art. 2 ust. 4 ustawy z dnia 24 czerwca 1994 r. o własności lokali',
            'Wyszczególnienie praw niezbędnych do korzystania z lokalu mieszkalnego lub domu jednorodzinnego',
            'Wartość praw niezbędnych do korzystania z lokalu mieszkalnego lub domu jednorodzinnego [zł]',
            'Data od której obowiązuje cena wartości praw niezbędnych do korzystania z lokalu mieszkalnego lub domu jednorodzinnego',
            'Wyszczególnienie rodzajów innych świadczeń pieniężnych, które nabywca zobowiązany jest spełnić na rzecz dewelopera w wykonaniu umowy przenoszącej własność',
            'Wartość innych świadczeń pieniężnych, które nabywca zobowiązany jest spełnić na rzecz dewelopera w wykonaniu umowy przenoszącej własność [zł]',
            'Data od której obowiązuje cena wartości innych świadczeń pieniężnych, które nabywca zobowiązany jest spełnić na rzecz dewelopera w wykonaniu umowy przenoszącej własność',
            'Adres strony internetowej, pod którym dostępny jest prospekt informacyjny'
        ];
    }
    
    private function property_to_csv_row($developer, $property) {
        // Pobierz pierwszy dodatek (dla MVP)
        $first_extra = $property['extras'][0] ?? [];
        
        return [
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
            
            // Adres siedziby
            $developer['siedz_wojewodztwo'] ?? '',
            $developer['siedz_powiat'] ?? '',
            $developer['siedz_gmina'] ?? '',
            $developer['siedz_miejscowosc'] ?? '',
            $developer['siedz_ulica'] ?? '',
            $developer['siedz_nr'] ?? '',
            $developer['siedz_lokal'] ?? '',
            $developer['siedz_kod'] ?? '',
            
            // Adres sprzedaży
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
            
            // Projekt
            $property['proj_wojewodztwo'] ?? '',
            $property['proj_powiat'] ?? '',
            $property['proj_gmina'] ?? '',
            $property['proj_miejscowosc'] ?? '',
            $property['proj_ulica'] ?? '',
            $property['proj_nr'] ?? '',
            $property['proj_kod'] ?? '',
            
            // Nieruchomość - używamy aktualnych cen z historii lub podstawowych
            $property['rodzaj_nieruchomosci'] ?? '',
            $property['nr_lokalu'] ?? '',
            $property['current_cena_m2'] ?? $property['cena_m2'] ?? '',
            UJC_Date_Helper::format_for_csv($property['current_data_zmiany'] ?? $property['data_cena_m2']),
            
            // Cena całkowita
            $property['current_cena_calkowita'] ?? $property['cena_calkowita'] ?? '',
            UJC_Date_Helper::format_for_csv($property['current_data_zmiany'] ?? $property['data_cena_calkowita']),
            $property['current_cena_z_dodatkami'] ?? $property['cena_z_dodatkami'] ?? '',
            UJC_Date_Helper::format_for_csv($property['current_data_zmiany'] ?? $property['data_cena_z_dodatkami']),
            
            // Części nieruchomości (pobierz z tabeli parts)
            $property['first_part']['rodzaj_czesci'] ?? '',
            $property['first_part']['oznaczenie_czesci'] ?? '', 
            $property['first_part']['cena_czesci'] ?? '',
            UJC_Date_Helper::format_for_csv($property['first_part']['data_cena_czesci'] ?? null),
            
            // Pomieszczenia przynależne
            $first_extra['typ_dodatku'] ?? '',
            $first_extra['oznaczenie_dodatku'] ?? '',
            $first_extra['cena_dodatku'] ?? '',
            UJC_Date_Helper::format_for_csv($first_extra['data_cena_dodatku'] ?? null),
            
            // Prawa
            $first_extra['typ_prawa'] ?? '',
            $first_extra['wartosc_prawa'] ?? '',
            UJC_Date_Helper::format_for_csv($first_extra['data_wartosc_prawa'] ?? null),
            
            // Inne świadczenia
            $first_extra['typ_swiadczenia'] ?? '',
            $first_extra['wartosc_swiadczenia'] ?? '',
            UJC_Date_Helper::format_for_csv($first_extra['data_wartosc_swiadczenia'] ?? null),
            
            $developer['prospekt_url'] ?? ''
        ];
        
        // Zastąp wszystkie puste wartości na 'X' zgodnie z szablonem ustawy
        return $this->apply_csv_empty_rules($row);
    }
    
    /**
     * Zastępuje puste wartości na 'X' zgodnie z szablonem CSV ustawy
     */
    private function apply_csv_empty_rules($row) {
        return array_map(fn($value) => empty($value) ? 'X' : $value, $row);
    }
}