<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * XML formatter for dane.gov.pl compliance
 * Uses DaneGovXmlDataset model with pre-configured static legal values
 * 
 * KRYTYCZNY KOD - MODEL ZAWIERA JUŻ WSZYSTKIE STAŁE WARTOŚCI PRAWNE
 * Formatter TYLKO konwertuje model na XML, nie dodaje dodatkowych wartości
 */
class XMLFormatter {
    
    /**
     * Format dataset model to XML compliant with dane.gov.pl
     * 
     * SINGLE RESPONSIBILITY: Tylko formatowanie, NIE walidacja!
     * Walidacja jest w CreateDaneGovSubmissionFilesUseCase::validateDataForSubmission()
     * 
     * @param DaneGovXmlDataset $dataset Zwalidowany model danych
     * @return string XML content
     */
    public function formatDatasetToXML(DaneGovXmlDataset $dataset): string {
        // TYLKO formatowanie - dane już zwalidowane w UseCase
        return $this->convertModelToXML($dataset);
    }
    
    
    /**
     * Convert data model to XML
     * UWAGA: Model już zawiera wszystkie stałe wartości - nie dodajemy nic więcej!
     */
    private function convertModelToXML(DaneGovXmlDataset $dataset): string {
        // Tworzenie dokumentu XML z właściwym namespace
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        // Root element z namespace zgodny z dane.gov.pl
        $root = $xml->createElementNS('urn:otwarte-dane:harvester:1.13', 'ns2:datasets');
        $xml->appendChild($root);
        
        // Dodanie dataset element
        $this->addDatasetFromModel($xml, $root, $dataset);
        
        return $xml->saveXML();
    }
    
    /**
     * Add dataset element - używa TYLKO wartości z modelu
     */
    private function addDatasetFromModel(DOMDocument $xml, DOMElement $parent, DaneGovXmlDataset $dataset): void {
        // Dataset element
        $datasetEl = $xml->createElement('dataset');
        $datasetEl->setAttribute('status', $dataset->status); // Ze STAŁEJ wartości modelu
        $parent->appendChild($datasetEl);
        
        // Podstawowe pola dataset - wszystkie z modelu
        $this->addTextElement($xml, $datasetEl, 'extIdent', $dataset->extIdent);
        
        // Title (dwujęzyczny) - z modelu
        $this->addTitleFromModel($xml, $datasetEl, $dataset->title);
        
        // Description (dwujęzyczny) - z modelu  
        $this->addDescriptionFromModel($xml, $datasetEl, $dataset->description);
        
        // STAŁE wartości z modelu
        $this->addTextElement($xml, $datasetEl, 'updateFrequency', $dataset->updateFrequency);
        
        // Boolean flags - MUSZĄ BYĆ PRZED categories zgodnie z szablonem!
        $this->addTextElement($xml, $datasetEl, 'hasDynamicData', $dataset->hasDynamicData ? 'true' : 'false');
        $this->addTextElement($xml, $datasetEl, 'hasHighValueData', $dataset->hasHighValueData ? 'true' : 'false');
        $this->addTextElement($xml, $datasetEl, 'hasHighValueDataFromEuropeanCommissionList', $dataset->hasHighValueDataFromEuropeanCommissionList ? 'true' : 'false');
        $this->addTextElement($xml, $datasetEl, 'hasResearchData', $dataset->hasResearchData ? 'true' : 'false');
        
        // Categories - z modelu (array -> XML) - PO boolean flags!
        $this->addCategoriesFromModel($xml, $datasetEl, $dataset->categories);
        
        // Resources - z modelu
        $this->addResourcesFromModel($xml, $datasetEl, $dataset->resources);
        
        // Tags - z modelu
        $this->addTagsFromModel($xml, $datasetEl, $dataset->tags);
    }
    
    /**
     * Add title element from model (Polish + English)
     */
    private function addTitleFromModel(DOMDocument $xml, DOMElement $parent, DaneGovTitle $title): void {
        $titleEl = $xml->createElement('title');
        $parent->appendChild($titleEl);
        
        $this->addTextElement($xml, $titleEl, 'polish', $title->polish);
        $this->addTextElement($xml, $titleEl, 'english', $title->english);
    }
    
    /**
     * Add description element from model (Polish + English)
     */
    private function addDescriptionFromModel(DOMDocument $xml, DOMElement $parent, DaneGovDescription $description): void {
        $descEl = $xml->createElement('description');
        $parent->appendChild($descEl);
        
        $this->addTextElement($xml, $descEl, 'polish', $description->polish);
        $this->addTextElement($xml, $descEl, 'english', $description->english);
    }
    
    /**
     * Add categories from model array
     */
    private function addCategoriesFromModel(DOMDocument $xml, DOMElement $parent, array $categories): void {
        $categoriesEl = $xml->createElement('categories');
        $parent->appendChild($categoriesEl);
        
        foreach ($categories as $category) {
            $this->addTextElement($xml, $categoriesEl, 'category', $category);
        }
    }
    
    /**
     * Add resources from model array
     */
    private function addResourcesFromModel(DOMDocument $xml, DOMElement $parent, array $resources): void {
        $resourcesEl = $xml->createElement('resources');
        $parent->appendChild($resourcesEl);
        
        foreach ($resources as $resource) {
            $this->addSingleResourceFromModel($xml, $resourcesEl, $resource);
        }
    }
    
    /**
     * Add single resource element - używa TYLKO wartości z modelu
     */
    private function addSingleResourceFromModel(DOMDocument $xml, DOMElement $parent, DaneGovResource $resource): void {
        // Resource element
        $resourceEl = $xml->createElement('resource');
        $resourceEl->setAttribute('status', $resource->status); // Ze STAŁEJ wartości modelu
        $parent->appendChild($resourceEl);
        
        // Podstawowe pola resource
        $this->addTextElement($xml, $resourceEl, 'extIdent', $resource->extIdent);
        $this->addTextElement($xml, $resourceEl, 'url', $resource->url);
        
        // Title i Description (dwujęzyczne) - z modelu
        $this->addResourceTitleFromModel($xml, $resourceEl, $resource->title);
        $this->addResourceDescriptionFromModel($xml, $resourceEl, $resource->description);
        
        // STAŁE wartości z modelu
        $this->addTextElement($xml, $resourceEl, 'availability', $resource->availability);
        $this->addTextElement($xml, $resourceEl, 'dataDate', $resource->dataDate);
        
        // Special signs - z modelu (array -> XML)
        $this->addSpecialSignsFromModel($xml, $resourceEl, $resource->specialSigns);
        
        // Boolean flags - WSZYSTKIE z modelu (stałe wartości prawne)
        $this->addTextElement($xml, $resourceEl, 'hasDynamicData', $resource->hasDynamicData ? 'true' : 'false');
        $this->addTextElement($xml, $resourceEl, 'hasHighValueData', $resource->hasHighValueData ? 'true' : 'false');
        $this->addTextElement($xml, $resourceEl, 'hasHighValueDataFromEuropeanCommissionList', $resource->hasHighValueDataFromEuropeanCommissionList ? 'true' : 'false');
        $this->addTextElement($xml, $resourceEl, 'hasResearchData', $resource->hasResearchData ? 'true' : 'false');
        $this->addTextElement($xml, $resourceEl, 'containsProtectedData', $resource->containsProtectedData ? 'true' : 'false');
    }
    
    /**
     * Add resource title from model (Polish + English)
     */
    private function addResourceTitleFromModel(DOMDocument $xml, DOMElement $parent, DaneGovResourceTitle $title): void {
        $titleEl = $xml->createElement('title');
        $parent->appendChild($titleEl);
        
        $this->addTextElement($xml, $titleEl, 'polish', $title->polish);
        $this->addTextElement($xml, $titleEl, 'english', $title->english);
    }
    
    /**
     * Add resource description from model (Polish + English)
     */
    private function addResourceDescriptionFromModel(DOMDocument $xml, DOMElement $parent, DaneGovResourceDescription $description): void {
        $descEl = $xml->createElement('description');
        $parent->appendChild($descEl);
        
        $this->addTextElement($xml, $descEl, 'polish', $description->polish);
        $this->addTextElement($xml, $descEl, 'english', $description->english);
    }
    
    /**
     * Add special signs from model array
     */
    private function addSpecialSignsFromModel(DOMDocument $xml, DOMElement $parent, array $specialSigns): void {
        $specialSignsEl = $xml->createElement('specialSigns');
        $parent->appendChild($specialSignsEl);
        
        foreach ($specialSigns as $sign) {
            $this->addTextElement($xml, $specialSignsEl, 'specialSign', $sign);
        }
    }
    
    /**
     * Add tags from model array
     */
    private function addTagsFromModel(DOMDocument $xml, DOMElement $parent, array $tags): void {
        $tagsEl = $xml->createElement('tags');
        $parent->appendChild($tagsEl);
        
        foreach ($tags as $tag) {
            $tagEl = $xml->createElement('tag', htmlspecialchars($tag, ENT_XML1, 'UTF-8'));
            $tagEl->setAttribute('lang', 'pl'); // Zgodnie z instrukcją
            $tagsEl->appendChild($tagEl);
        }
    }
    
    /**
     * Add simple text element with proper XML escaping
     * Pomocnicza metoda - używa TYLKO przekazanych wartości, nic nie dodaje
     */
    private function addTextElement(DOMDocument $xml, DOMElement $parent, string $name, string $value): void {
        $element = $xml->createElement($name, htmlspecialchars($value, ENT_XML1, 'UTF-8'));
        $parent->appendChild($element);
    }
}