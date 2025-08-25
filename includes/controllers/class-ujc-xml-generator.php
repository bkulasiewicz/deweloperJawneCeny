<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generator XML - podstawowa implementacja MVP zgodna ze specyfikacją
 */
class UJC_XML_Generator {
    
    public function generate_xml($csv_url = null) {
        $developer_repo = new UJC_Developer_Repository();
        $developer = $developer_repo->read();
        
        if (!$developer) {
            return false;
        }
        
        // Utwórz XML zgodnie ze specyfikacją dane.gov.pl
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        // Element nadrzędny
        $datasets = $xml->createElement('ns2:datasets');
        $datasets->setAttribute('xmlns:ns2', 'urn:otwarte-dane:harvester:1.13');
        $datasets->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml->appendChild($datasets);
        
        // Dataset
        $dataset = $xml->createElement('dataset');
        $dataset->setAttribute('status', 'published');
        $datasets->appendChild($dataset);
        
        // Identyfikator - STAŁY dla dataset
        $extIdent = $xml->createElement('extIdent', $this->generate_dataset_ext_ident($developer));
        $dataset->appendChild($extIdent);
        
        // Tytuł
        $title = $xml->createElement('title');
        $dataset->appendChild($title);
        
        $polish_title = $xml->createElement('polish', 'Ceny ofertowe mieszkań dewelopera ' . htmlspecialchars($developer['nazwa']) . ' w ' . date('Y') . ' r.');
        $title->appendChild($polish_title);
        
        $english_title = $xml->createElement('english', 'Offer prices of apartments of developer ' . htmlspecialchars($developer['nazwa']) . ' in ' . date('Y') . '.');
        $title->appendChild($english_title);
        
        // Opis
        $description = $xml->createElement('description');
        $dataset->appendChild($description);
        
        $polish_desc = $xml->createElement('polish', 'Zbiór danych zawiera informacje o cenach ofertowych mieszkań dewelopera ' . htmlspecialchars($developer['nazwa']) . ' udostępniane zgodnie z art. 19b. ust. 1 Ustawy z dnia 20 maja 2021 r. o ochronie praw nabywcy lokalu mieszkalnego lub domu jednorodzinnego oraz Deweloperskim Funduszu Gwarancyjnym (Dz. U. z 2024 r. poz. 695).');
        $description->appendChild($polish_desc);
        
        $english_desc = $xml->createElement('english', 'The dataset contains information on offer prices of apartments of the developer ' . htmlspecialchars($developer['nazwa']) . ' made available in accordance with art. 19b. ust. 1 Ustawy z dnia 20 maja 2021 r. o ochronie praw nabywcy lokalu mieszkalnego lub domu jednorodzinnego oraz Deweloperskim Funduszu Gwarancyjnym (Dz. U. z 2024 r. poz. 695).');
        $description->appendChild($english_desc);
        
        // Update Frequency - zgodnie z szablonem po description
        $updateFrequency = $xml->createElement('updateFrequency', 'daily');
        $dataset->appendChild($updateFrequency);
        
        // Dataset flags - zgodnie z szablonem
        $dataset_hasDynamicData = $xml->createElement('hasDynamicData', 'false');
        $dataset->appendChild($dataset_hasDynamicData);
        
        $dataset_hasHighValueData = $xml->createElement('hasHighValueData', 'true');
        $dataset->appendChild($dataset_hasHighValueData);
        
        $dataset_hasHighValueDataFromEuropean = $xml->createElement('hasHighValueDataFromEuropeanCommissionList', 'false');
        $dataset->appendChild($dataset_hasHighValueDataFromEuropean);
        
        $dataset_hasResearchData = $xml->createElement('hasResearchData', 'false');
        $dataset->appendChild($dataset_hasResearchData);
        
        // Kategoria ECON (wymagana dla deweloperów)
        $categories = $xml->createElement('categories');
        $dataset->appendChild($categories);
        $category = $xml->createElement('category', 'ECON');
        $categories->appendChild($category);
        
        // Resources
        $resources = $xml->createElement('resources');
        $dataset->appendChild($resources);
        
        $resource = $xml->createElement('resource');
        $resource->setAttribute('status', 'published');
        $resources->appendChild($resource);
        
        // Resource details
        $res_extIdent = $xml->createElement('extIdent', $this->generate_resource_ext_ident($developer));
        $resource->appendChild($res_extIdent);
        
        // URL do pliku CSV - zgodny z wymaganiami ustawy
        if (!$csv_url) {
            // Fallback - generuj nazwę zgodną z ustawą
            $upload_dir = wp_upload_dir();
            $developer_name_clean = $this->sanitize_filename_part($developer['nazwa']);
            $date_string = date('Y-m-d');
            $csv_filename = "Ceny-ofertowe-mieszkan-dewelopera-{$developer_name_clean}-{$date_string}.csv";
            $csv_url = $upload_dir['baseurl'] . '/ujc-data/' . $csv_filename;
        }
        $res_url = $xml->createElement('url', htmlspecialchars($csv_url));
        $resource->appendChild($res_url);
        
        // Resource title
        $res_title = $xml->createElement('title');
        $resource->appendChild($res_title);
        
        $res_polish_title = $xml->createElement('polish', 'Ceny ofertowe mieszkań dewelopera ' . htmlspecialchars($developer['nazwa']) . ' ' . UJC_Date_Helper::format_for_xml());
        $res_title->appendChild($res_polish_title);
        
        $res_english_title = $xml->createElement('english', 'Offer prices for developer\'s apartments ' . htmlspecialchars($developer['nazwa']) . ' ' . UJC_Date_Helper::format_for_xml());
        $res_title->appendChild($res_english_title);
        
        // Resource description
        $res_description = $xml->createElement('description');
        $resource->appendChild($res_description);
        
        $res_polish_desc = $xml->createElement('polish', 'Dane dotyczące cen ofertowych mieszkań dewelopera ' . htmlspecialchars($developer['nazwa']) . ' udostępnione ' . UJC_Date_Helper::format_for_xml() . ' zgodnie z art. 19b. ust. 1 Ustawy z dnia 20 maja 2021 r. o ochronie praw nabywcy lokalu mieszkalnego lub domu jednorodzinnego oraz Deweloperskim Funduszu Gwarancyjnym (Dz. U. z 2024 r. poz. 695).');
        $res_description->appendChild($res_polish_desc);
        
        $res_english_desc = $xml->createElement('english', 'Data on offer prices of apartments of the developer ' . htmlspecialchars($developer['nazwa']) . ' made available ' . UJC_Date_Helper::format_for_xml() . ' in accordance with art. 19b. ust. 1 Ustawy z dnia 20 maja 2021 r. o ochronie praw nabywcy lokalu mieszkalnego lub domu jednorodzinnego oraz Deweloperskim Funduszu Gwarancyjnym (Dz. U. z 2024 r. poz. 695).');
        $res_description->appendChild($res_english_desc);
        
        // Availability
        $availability = $xml->createElement('availability', 'local');
        $resource->appendChild($availability);
        
        // Data Date
        $dataDate = $xml->createElement('dataDate', UJC_Date_Helper::format_for_xml());
        $resource->appendChild($dataDate);
        
        // Special Signs
        $specialSigns = $xml->createElement('specialSigns');
        $resource->appendChild($specialSigns);
        $specialSign = $xml->createElement('specialSign', 'X');
        $specialSigns->appendChild($specialSign);
        
        // Flags zgodnie ze specyfikacją dla deweloperów
        $hasDynamicData = $xml->createElement('hasDynamicData', 'false');
        $resource->appendChild($hasDynamicData);
        
        $hasHighValueData = $xml->createElement('hasHighValueData', 'true');
        $resource->appendChild($hasHighValueData);
        
        $hasHighValueDataFromEuropean = $xml->createElement('hasHighValueDataFromEuropeanCommissionList', 'false');
        $resource->appendChild($hasHighValueDataFromEuropean);
        
        $hasResearchData = $xml->createElement('hasResearchData', 'false');
        $resource->appendChild($hasResearchData);
        
        $containsProtectedData = $xml->createElement('containsProtectedData', 'false');
        $resource->appendChild($containsProtectedData);
        
        // Tags - zgodnie z szablonem na końcu
        $tags = $xml->createElement('tags');
        $dataset->appendChild($tags);
        $tag = $xml->createElement('tag', 'Deweloper');
        $tag->setAttribute('lang', 'pl');
        $tags->appendChild($tag);
        
        // Zapisz XML
        $upload_dir = wp_upload_dir();
        $ujc_dir = $upload_dir['basedir'] . '/ujc-data';
        if (!file_exists($ujc_dir)) {
            wp_mkdir_p($ujc_dir);
        }
        
        $filename = 'katalog-danych.xml';
        $filepath = $ujc_dir . '/' . $filename;
        
        $result = $xml->save($filepath);
        
        // Generuj MD5 checksum
        $this->generate_md5_checksum($filepath);
        
        return $result;
    }
    
    /**
     * Generuje plik MD5 checksum dla pliku XML
     */
    private function generate_md5_checksum($filepath) {
        $md5_hash = md5_file($filepath);
        // MD5 ma taką samą nazwę jak XML, ale z rozszerzeniem .md5
        $md5_filepath = str_replace('.xml', '.md5', $filepath);
        file_put_contents($md5_filepath, $md5_hash . '  ' . basename($filepath) . "\n");
        return $md5_hash;
    }
    
    private function generate_dataset_ext_ident($developer) {
        // STAŁY identyfikator dla dataset - dokładnie 36 znaków zgodnie z szablonem
        $base = substr(md5($developer['nazwa'] . $developer['nr_nip']), 0, 31);
        return $base . '_DEV';
    }
    
    private function generate_resource_ext_ident($developer) {
        // Identyfikator zasobu z aktualną datą - dokładnie 36 znaków zgodnie z szablonem
        $base = substr(md5($developer['nazwa'] . $developer['nr_nip'] . 'resource'), 0, 27);
        return $base . '_' . UJC_Date_Helper::format_for_filename();
    }
    
    /**
     * Czyści część nazwy pliku (usuwa niebezpieczne znaki) - tak samo jak w CSV
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
}