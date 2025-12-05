<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Creates submission files (XML metadata + MD5 checksum) for dane.gov.pl portal
 * Handles validation, XML generation and file storage in one workflow
 */
class CreateDaneGovSubmissionFilesUseCase {

    private $xmlFormatter;
    private $fileManager;
    private $developerRepository;
    private $xmlResourceRepository;

    public function __construct(
        XMLFormatter $xmlFormatter,
        FileManager $fileManager,
        DeveloperRepository $developerRepository,
        XmlResourceRepository $xmlResourceRepository
    ) {
        $this->xmlFormatter = $xmlFormatter;
        $this->fileManager = $fileManager;
        $this->developerRepository = $developerRepository;
        $this->xmlResourceRepository = $xmlResourceRepository;
    }
    
    /**
     * Create submission files (XML + MD5) for dane.gov.pl portal
     * 
     * @param string|null $csv_url URL to CSV file that will be referenced in XML metadata
     * @return array ['success' => bool, 'message' => string, 'files' => array|null, 'error' => string|null]
     */
    public function createSubmissionFiles($csv_url = null) {
        try {
            // KRYTYCZNA WALIDACJA - wszystko w jednym miejscu
            $validation_result = $this->validateDataForSubmission($csv_url);
            if (!$validation_result['valid']) {
                $error_msg = implode('. ', $validation_result['errors']);
                return [
                    'success' => false,
                    'error' => $error_msg
                ];
            }
            
            // Pobierz zwalidowane dane
            $developer_data = $validation_result['data'];
            
            // Utwórz model danych z wszystkimi resources z bazy
            $dataset = new DaneGovXmlDataset(
                $developer_data['developer_name'],
                $developer_data['nip'],
                $developer_data['website_url']
            );

            // Pobierz wszystkie XML resources z bazy i dodaj do dataset
            $allXmlResources = $this->xmlResourceRepository->readAll();

            foreach ($allXmlResources as $xmlResource) {
                // Build full URL from stored path (handles relative paths and legacy ?file= format)
                $csv_url = $this->fileManager->buildFullUrlFromStoredPath($xmlResource->csv_url);

                $dataset->addResource(
                    $developer_data['developer_name'],
                    $xmlResource->ext_ident,
                    $csv_url,
                    $xmlResource->data_date
                );
            }

            // Generuj XML - XMLFormatter TYLKO formatuje, NIE waliduje
            $xmlContent = $this->xmlFormatter->formatDatasetToXML($dataset);

            // Generate filename
            $filename = $this->fileManager->generateXMLFilename();

            // Save XML to file (with MD5)
            $xml_result = $this->fileManager->saveXML($xmlContent, $filename);
            
            return [
                'success' => true,
                'message' => 'Pliki zgłoszenia dla dane.gov.pl zostały utworzone pomyślnie',
                'files' => $xml_result
            ];
            
        } catch (Exception $e) {
            $error_msg = $this->getDetailedErrorMessage($e);

            return [
                'success' => false,
                'error' => $error_msg
            ];
        }
    }
    
    /**
     * Validate all requirements for dane.gov.pl submission files creation
     * Centralizes validation logic in one place
     * 
     * @param string|null $csv_url URL to CSV file
     * @return array ['valid' => bool, 'errors' => array, 'data' => array]
     */
    private function validateDataForSubmission($csv_url = null) {
        $errors = [];
        $validated_data = [];

        // 1. WALIDACJA CSV URL - KRYTYCZNE
        if (empty($csv_url)) {
            $errors[] = 'URL pliku CSV jest wymagany dla generacji XML';
        } else {
            // Walidacja formatu URL
            if (!filter_var($csv_url, FILTER_VALIDATE_URL)) {
                $errors[] = 'Nieprawidłowy format URL: ' . $csv_url;
                Logger::error('CreateDaneGov: Invalid CSV URL format: ' . $csv_url);
            } else {
                $validated_data['csv_url'] = $csv_url;
                Logger::info('CreateDaneGov: CSV URL validation SUCCESS');
            }
        }
        
        // 2. WALIDACJA DANYCH DEWELOPERA - KRYTYCZNE
        Logger::info('CreateDaneGov: Loading developer data...');
        $developer = $this->developerRepository->read();
        Logger::info('CreateDaneGov: Developer loaded: ' . ($developer ? 'SUCCESS' : 'FAILED'));
        
        if (!$developer) {
            $errors[] = 'Brak danych dewelopera w bazie. Proszę najpierw uzupełnić dane firmy';
        } else {
            Logger::info('CreateDaneGov: Starting developer field validation...');
            // 2a. Walidacja nazwy dewelopera
            $developer_name = trim($developer->nazwa ?? '');
            if (empty($developer_name)) {
                $errors[] = 'Nazwa dewelopera jest wymagana';
                Logger::error('CreateDaneGov: Developer name is empty');
            } else {
                $validated_data['developer_name'] = $developer_name;
                Logger::info('CreateDaneGov: Developer name validated: ' . $developer_name);
            }
            
            // 2b. Walidacja NIP - KRYTYCZNE dla identyfikatorów
            $nip = trim($developer->nr_nip ?? '');
            if (empty($nip)) {
                $errors[] = 'NIP dewelopera jest wymagany';
                Logger::error('CreateDaneGov: NIP is empty');
            } else {
                Logger::info('CreateDaneGov: Validating NIP: ' . $nip);
                // Usuń wszystko oprócz cyfr dla walidacji
                $clean_nip = preg_replace('/[^0-9]/', '', $nip);
                
                // Sprawdź długość (NIP w Polsce ma zawsze 10 cyfr)
                if (strlen($clean_nip) !== 10) {
                    $errors[] = 'NIP musi zawierać dokładnie 10 cyfr (podano: ' . strlen($clean_nip) . ')';
                    Logger::error('CreateDaneGov: Invalid NIP length: ' . strlen($clean_nip));
                } else {
                    // Zachowaj oryginalny NIP (może mieć myślniki)
                    $validated_data['nip'] = $nip;
                    Logger::info('CreateDaneGov: NIP validation SUCCESS');
                }
            }

            // 2c. Walidacja strony WWW - wymagana dla URL zbioru
            $website_url = trim($developer->strona_www ?? '');
            if (empty($website_url)) {
                $errors[] = 'Strona WWW dewelopera jest wymagana dla elementu URL zbioru';
                Logger::error('CreateDaneGov: Website URL is empty');
            } else {
                $url_length = strlen($website_url);
                if ($url_length < 10 || $url_length > 1000) {
                    $errors[] = 'URL strony WWW musi mieć od 10 do 1000 znaków (podano: ' . $url_length . ')';
                    Logger::error('CreateDaneGov: Invalid website URL length: ' . $url_length);
                } else {
                    $validated_data['website_url'] = $website_url;
                    Logger::info('CreateDaneGov: Website URL validation SUCCESS');
                }
            }
        }
        
        // 3. WALIDACJA SYSTEMU PLIKÓW
        try {
            $this->fileManager->ensureDirectoryExists();
        } catch (Exception $e) {
            $errors[] = 'Błąd systemu plików: ' . $e->getMessage();
        }
        
        // 4. WALIDACJA UPRAWNIEŃ
        $upload_dir = wp_upload_dir();
        $data_dir = $upload_dir['basedir'] . '/ujc-data';
        
        global $wp_filesystem;
        if ($wp_filesystem->exists($data_dir) && !$wp_filesystem->is_writable($data_dir)) {
            $errors[] = 'Brak uprawnień do zapisu w katalogu: ' . $data_dir;
        }
        
        // Zwróć wynik walidacji
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $validated_data
        ];
    }
    
    /**
     * Get detailed XML error description
     */
    private function getXmlErrorDetails() {
        try {
            $this->fileManager->ensureDirectoryExists();
        } catch (Exception $e) {
            return "Błąd XML: " . $e->getMessage();
        }
        
        return "Błąd XML: Nieznany błąd podczas tworzenia pliku XML lub MD5";
    }
    
    /**
     * Create detailed error message from exception
     */
    private function getDetailedErrorMessage(Exception $e) {
        $message = $e->getMessage();
        
        // Check permission errors
        if (strpos($message, 'Permission denied') !== false || strpos($message, 'permission') !== false) {
            return "Błąd uprawnień: " . $message;
        }
        
        // Check disk errors
        if (strpos($message, 'No space left') !== false || strpos($message, 'disk full') !== false) {
            return "Błąd dysku: Brak miejsca na dysku";
        }
        
        // Check database errors
        if (strpos($message, 'database') !== false || strpos($message, 'MySQL') !== false) {
            return "Błąd bazy danych: " . $message;
        }
        
        // Default description
        return $message;
    }

}