<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Model danych dla XML dane.gov.pl
 * Zawiera wszystkie STAŁE wartości wymagane przez ustawę
 * Tylko dane zmienne (developer, data, URL) są przekazywane z zewnątrz
 */
class DaneGovXmlDataset {
    
    // ===== STAŁE WARTOŚCI ZGODNE Z WYMAGANIAMI PRAWNYMI =====
    
    /** @var string Status publikacji - zawsze 'published' */
    public string $status = 'published';
    
    /** @var string Częstotliwość aktualizacji - zawsze 'daily' */  
    public string $updateFrequency = 'daily';
    
    /** @var array Kategorie - zawsze ['ECON'] dla deweloperów */
    public array $categories = ['ECON'];
    
    /** @var array Tagi - zawsze ['Deweloper'] */
    public array $tags = ['Deweloper'];
    
    // Flagi boolean - STAŁE dla deweloperów zgodnie z instrukcją
    public bool $hasDynamicData = false;
    public bool $hasHighValueData = true;
    public bool $hasHighValueDataFromEuropeanCommissionList = false;
    public bool $hasResearchData = false;
    
    // ===== DANE DYNAMICZNE =====
    
    /** @var string Unikalny identyfikator zbioru danych (36 znaków, STAŁY dla dewelopera) */
    public string $extIdent;
    
    /** @var DaneGovTitle Tytuł w językach polskim i angielskim */
    public DaneGovTitle $title;
    
    /** @var DaneGovDescription Opis w językach polskim i angielskim */
    public DaneGovDescription $description;
    
    /** @var DaneGovResource[] Lista zasobów (plików CSV) */
    public array $resources = [];
    
    /**
     * Konstruktor - przyjmuje tylko dane zmienne
     */
    public function __construct(string $developerName, string $nip) {
        $this->extIdent = $this->generateStableExtIdent($nip);
        $this->title = new DaneGovTitle($developerName);
        $this->description = new DaneGovDescription($developerName);
    }
    
    /**
     * Generuje STAŁY identyfikator dla dewelopera (36 znaków)
     */
    private function generateStableExtIdent(string $nip): string {
        $base = "deweloper_{$nip}_ceny_mieszkan";
        return str_pad($base, 36, '_', STR_PAD_RIGHT);
    }
    
    /**
     * Dodaje nowy zasób (publikację CSV) z gotowym ext_ident
     * URL do CSV i ext_ident są WYMAGANE
     */
    public function addResource(string $developerName, string $ext_ident, string $csv_url, string $data_date): void {
        $this->resources[] = new DaneGovResource($developerName, $ext_ident, $csv_url, $data_date);
    }
}

/**
 * Model tytułu w dwóch językach - STAŁY format, tylko nazwa dewelopera zminna
 */
class DaneGovTitle {
    public string $polish;
    public string $english;
    
    public function __construct(string $developerName) {
        $year = gmdate('Y');
        
        $this->polish = "Ceny ofertowe mieszkań dewelopera {$developerName} w {$year} r.";
        $this->english = "Offer prices of apartments of developer {$developerName} in {$year}.";
    }
}

/**
 * Model opisu w dwóch językach - STAŁY tekst prawny, tylko nazwa dewelopera zmienna
 */
class DaneGovDescription {
    public string $polish;
    public string $english;
    
    // STAŁY tekst zgodny z wymaganiami prawnymi
    private const LEGAL_TEXT_PL = "zgodnie z art. 19b. ust. 1 Ustawy z dnia 20 maja 2021 r. o ochronie praw nabywcy lokalu mieszkalnego lub domu jednorodzinnego oraz Deweloperskim Funduszu Gwarancyjnym (Dz. U. z 2024 r. poz. 695).";
    
    private const LEGAL_TEXT_EN = "in accordance with art. 19b. ust. 1 Ustawy z dnia 20 maja 2021 r. o ochronie praw nabywcy lokalu mieszkalnego lub domu jednorodzinnego oraz Deweloperskim Funduszu Gwarancyjnym (Dz. U. z 2024 r. poz. 695).";
    
    public function __construct(string $developerName) {
        $this->polish = "Zbiór danych zawiera informacje o cenach ofertowych mieszkań dewelopera {$developerName} udostępniane " . self::LEGAL_TEXT_PL;
        
        $this->english = "The dataset contains information on offer prices of apartments of the developer {$developerName} made available " . self::LEGAL_TEXT_EN;
    }
}

/**
 * Model pojedynczego zasobu (pliku CSV)
 * Zawiera STAŁE wartości zgodne z wymaganiami prawnymi
 */
class DaneGovResource {
    
    // ===== STAŁE WARTOŚCI ZGODNE Z WYMAGANIAMI PRAWNYMI =====
    
    /** @var string Status publikacji - zawsze 'published' */
    public string $status = 'published';
    
    /** @var string Dostępność - zawsze 'local' dla deweloperów */
    public string $availability = 'local';
    
    /** @var array Znaki specjalne - zawsze ['X'] zgodnie z instrukcją */
    public array $specialSigns = ['X'];
    
    // Flagi boolean - STAŁE dla deweloperów zgodnie z instrukcją
    public bool $hasDynamicData = false;
    public bool $hasHighValueData = true;
    public bool $hasHighValueDataFromEuropeanCommissionList = false;
    public bool $hasResearchData = false;
    public bool $containsProtectedData = false;
    
    // ===== DANE DYNAMICZNE =====
    
    /** @var string Unikalny identyfikator zasobu (36 znaków, ZMIENNY codziennie) */
    public string $extIdent;
    
    /** @var string URL do pliku CSV */
    public string $url;
    
    /** @var DaneGovResourceTitle Tytuł zasobu w dwóch językach */
    public DaneGovResourceTitle $title;
    
    /** @var DaneGovResourceDescription Opis zasobu w dwóch językach */
    public DaneGovResourceDescription $description;
    
    /** @var string Data publikacji (YYYY-MM-DD) */
    public string $dataDate;
    
    /**
     * Konstruktor - przyjmuje gotowe dane z bazy (bez generowania)
     * Wszystkie parametry są wymagane
     */
    public function __construct(string $developerName, string $ext_ident, string $csv_url, string $data_date) {
        $this->extIdent = $ext_ident;
        $this->url = $csv_url;
        $this->dataDate = $data_date;
        $this->title = new DaneGovResourceTitle($developerName, $data_date);
        $this->description = new DaneGovResourceDescription($developerName, $data_date);
    }
}

/**
 * Model tytułu zasobu w dwóch językach - STAŁY format
 */
class DaneGovResourceTitle {
    public string $polish;
    public string $english;
    
    public function __construct(string $developerName, string $date) {
        $this->polish = "Ceny ofertowe mieszkań dewelopera {$developerName} {$date}";
        $this->english = "Offer prices for developer's apartments {$developerName} {$date}";
    }
}

/**
 * Model opisu zasobu w dwóch językach - STAŁY tekst prawny
 */
class DaneGovResourceDescription {
    public string $polish;
    public string $english;
    
    // STAŁY tekst zgodny z wymaganiami prawnymi
    private const LEGAL_TEXT_PL = "zgodnie z art. 19b. ust. 1 Ustawy z dnia 20 maja 2021 r. o ochronie praw nabywcy lokalu mieszkalnego lub domu jednorodzinnego oraz Deweloperskim Funduszu Gwarancyjnym (Dz. U. z 2024 r. poz. 695)\"[1].";
    
    private const LEGAL_TEXT_EN = "in accordance with art. 19b. ust. 1 Ustawy z dnia 20 maja 2021 r. o ochronie praw nabywcy lokalu mieszkalnego lub domu jednorodzinnego oraz Deweloperskim Funduszu Gwarancyjnym (Dz. U. z 2024 r. poz. 695).";
    
    public function __construct(string $developerName, string $date) {
        $this->polish = "Dane dotyczące cen ofertowych mieszkań dewelopera {$developerName} udostępnione {$date} " . self::LEGAL_TEXT_PL;
        
        $this->english = "Data on offer prices of apartments of the developer {$developerName} made available {$date} " . self::LEGAL_TEXT_EN;
    }
}