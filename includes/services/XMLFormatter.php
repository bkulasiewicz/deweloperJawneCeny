<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pure XML formatting service - only handles XML generation logic
 * File management is handled by FileManager
 */
class XMLFormatter {
    
    /**
     * Generate XML content from CSV URL
     * 
     * @param string|null $csv_url URL to CSV file for reference
     * @return string XML content
     */
    public function generateXML($csv_url = null): string {
        $developer_repo = new DeveloperRepository(); // Will be updated to use dependency injection
        $developer = $developer_repo->read();
        
        if (!$developer) {
            throw new Exception('No developer data found');
        }
        
        // Create XML structure
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        // Root element
        $root = $xml->createElement('katalog-danych');
        $xml->appendChild($root);
        
        // Metadata
        $this->addMetadata($xml, $root, $developer, $csv_url);
        
        // Developer data
        $this->addDeveloperData($xml, $root, $developer);
        
        return $xml->saveXML();
    }
    
    /**
     * Add metadata to XML
     */
    private function addMetadata(DOMDocument $xml, DOMElement $root, array $developer, $csv_url): void {
        $metadata = $xml->createElement('metadata');
        $root->appendChild($metadata);
        
        // Generation date
        $generation_date = $xml->createElement('data-generowania', date('Y-m-d\TH:i:s'));
        $metadata->appendChild($generation_date);
        
        // Version
        $version = $xml->createElement('wersja', '1.13');
        $metadata->appendChild($version);
        
        // CSV reference if provided
        if ($csv_url) {
            $csv_ref = $xml->createElement('plik-csv', htmlspecialchars($csv_url));
            $metadata->appendChild($csv_ref);
        }
        
        // Developer identification
        $dev_id = $xml->createElement('deweloper-id', htmlspecialchars($developer['nr_nip'] ?? ''));
        $metadata->appendChild($dev_id);
    }
    
    /**
     * Add developer data to XML
     */
    private function addDeveloperData(DOMDocument $xml, DOMElement $root, array $developer): void {
        $dev_element = $xml->createElement('deweloper');
        $root->appendChild($dev_element);
        
        // Basic developer info
        $this->addElementIfNotEmpty($xml, $dev_element, 'nazwa', $developer['nazwa'] ?? '');
        $this->addElementIfNotEmpty($xml, $dev_element, 'forma-prawna', $developer['forma_prawna'] ?? '');
        $this->addElementIfNotEmpty($xml, $dev_element, 'nip', $developer['nr_nip'] ?? '');
        $this->addElementIfNotEmpty($xml, $dev_element, 'regon', $developer['nr_regon'] ?? '');
        $this->addElementIfNotEmpty($xml, $dev_element, 'krs', $developer['nr_krs'] ?? '');
        $this->addElementIfNotEmpty($xml, $dev_element, 'ceidg', $developer['nr_ceidg'] ?? '');
        
        // Contact info
        $contact = $xml->createElement('kontakt');
        $dev_element->appendChild($contact);
        
        $this->addElementIfNotEmpty($xml, $contact, 'telefon', $developer['telefon'] ?? '');
        $this->addElementIfNotEmpty($xml, $contact, 'email', $developer['email'] ?? '');
        $this->addElementIfNotEmpty($xml, $contact, 'fax', $developer['fax'] ?? '');
        $this->addElementIfNotEmpty($xml, $contact, 'strona-www', $developer['strona_www'] ?? '');
        
        // Headquarters address
        if ($this->hasAddressData($developer, 'siedz_')) {
            $address = $xml->createElement('adres-siedziby');
            $dev_element->appendChild($address);
            
            $this->addAddressElements($xml, $address, $developer, 'siedz_');
        }
        
        // Sales address
        if ($this->hasAddressData($developer, 'sprzed_')) {
            $sales_address = $xml->createElement('adres-sprzedazy');
            $dev_element->appendChild($sales_address);
            
            $this->addAddressElements($xml, $sales_address, $developer, 'sprzed_');
        }
    }
    
    /**
     * Add element to XML only if value is not empty
     */
    private function addElementIfNotEmpty(DOMDocument $xml, DOMElement $parent, string $name, string $value): void {
        if (!empty($value)) {
            $element = $xml->createElement($name, htmlspecialchars($value));
            $parent->appendChild($element);
        }
    }
    
    /**
     * Check if address data exists for given prefix
     */
    private function hasAddressData(array $data, string $prefix): bool {
        $address_fields = ['wojewodztwo', 'powiat', 'gmina', 'miejscowosc', 'ulica', 'nr', 'kod'];
        
        foreach ($address_fields as $field) {
            if (!empty($data[$prefix . $field])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Add address elements to XML
     */
    private function addAddressElements(DOMDocument $xml, DOMElement $parent, array $data, string $prefix): void {
        $this->addElementIfNotEmpty($xml, $parent, 'wojewodztwo', $data[$prefix . 'wojewodztwo'] ?? '');
        $this->addElementIfNotEmpty($xml, $parent, 'powiat', $data[$prefix . 'powiat'] ?? '');
        $this->addElementIfNotEmpty($xml, $parent, 'gmina', $data[$prefix . 'gmina'] ?? '');
        $this->addElementIfNotEmpty($xml, $parent, 'miejscowosc', $data[$prefix . 'miejscowosc'] ?? '');
        $this->addElementIfNotEmpty($xml, $parent, 'ulica', $data[$prefix . 'ulica'] ?? '');
        $this->addElementIfNotEmpty($xml, $parent, 'nr', $data[$prefix . 'nr'] ?? '');
        $this->addElementIfNotEmpty($xml, $parent, 'lokal', $data[$prefix . 'lokal'] ?? '');
        $this->addElementIfNotEmpty($xml, $parent, 'kod-pocztowy', $data[$prefix . 'kod'] ?? '');
    }
}