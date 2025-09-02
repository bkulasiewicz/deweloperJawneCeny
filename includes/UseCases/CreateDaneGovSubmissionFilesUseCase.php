<?php

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
    
    public function __construct() {
        $this->xmlFormatter = new XMLFormatter();
        $this->fileManager = new FileManager();
        $this->developerRepository = new DeveloperRepository();
    }
    
    /**
     * Create submission files (XML + MD5) for dane.gov.pl portal
     * 
     * @param string|null $csv_url URL to CSV file that will be referenced in XML metadata
     * @return array ['success' => bool, 'message' => string, 'files' => array|null, 'error' => string|null]
     */
    public function createSubmissionFiles($csv_url = null) {
        error_log('CreateDaneGov: Starting XML/MD5 files creation with CSV URL: ' . ($csv_url ?? 'null'));
        
        try {
            // KRYTYCZNA WALIDACJA - wszystko w jednym miejscu
            error_log('CreateDaneGov: Starting validation...');
            $validation_result = $this->validateDataForSubmission($csv_url);
            if (!$validation_result['valid']) {
                $error_msg = implode('. ', $validation_result['errors']);
                error_log('CreateDaneGov: Validation FAILED: ' . $error_msg);
                return [
                    'success' => false,
                    'error' => $error_msg
                ];
            }
            error_log('CreateDaneGov: Validation SUCCESS');
            
            // Pobierz zwalidowane dane
            $developer_data = $validation_result['data'];
            error_log('CreateDaneGov: Developer data validated - Name: ' . ($developer_data['developer_name'] ?? 'unknown') . ', NIP: ' . ($developer_data['nip'] ?? 'unknown'));
            
            // Utwórz model danych z zwalidowanymi danymi
            error_log('CreateDaneGov: Creating dataset model...');
            $dataset = new DaneGovXmlDataset(
                $developer_data['developer_name'],
                $developer_data['nip']
            );
            $dataset->addResource(
                $developer_data['developer_name'],
                $developer_data['nip'],
                $developer_data['csv_url']
            );
            
            // Generuj XML - XMLFormatter TYLKO formatuje, NIE waliduje
            error_log('CreateDaneGov: Generating XML content...');
            $xmlContent = $this->xmlFormatter->formatDatasetToXML($dataset);
            
            // Generate filename
            $filename = $this->fileManager->generateXMLFilename();
            error_log('CreateDaneGov: Generated XML filename: ' . $filename);
            
            // Save XML to file (with MD5)
            error_log('CreateDaneGov: Saving XML and MD5 files...');
            $xml_result = $this->fileManager->saveXML($xmlContent, $filename);
            
            error_log('CreateDaneGov: Files saved successfully');
            
            error_log('CreateDaneGov: Returning SUCCESS response');
            
            return [
                'success' => true,
                'message' => 'Pliki zgłoszenia dla dane.gov.pl zostały utworzone pomyślnie',
                'files' => $xml_result
            ];
            
        } catch (Exception $e) {
            $error_msg = $this->getDetailedErrorMessage($e);
            error_log('CreateDaneGov: EXCEPTION caught: ' . $error_msg);
            
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
            } else {
                $validated_data['csv_url'] = $csv_url;
            }
        }
        
        // 2. WALIDACJA DANYCH DEWELOPERA - KRYTYCZNE
        $developer = $this->developerRepository->read();
        
        if (!$developer) {
            $errors[] = 'Brak danych dewelopera w bazie. Proszę najpierw uzupełnić dane firmy';
        } else {
            // 2a. Walidacja nazwy dewelopera
            $developer_name = trim($developer['nazwa'] ?? '');
            if (empty($developer_name)) {
                $errors[] = 'Nazwa dewelopera jest wymagana';
            } else {
                $validated_data['developer_name'] = $developer_name;
            }
            
            // 2b. Walidacja NIP - KRYTYCZNE dla identyfikatorów
            $nip = trim($developer['nr_nip'] ?? '');
            if (empty($nip)) {
                $errors[] = 'NIP dewelopera jest wymagany';
            } else {
                // Usuń wszystko oprócz cyfr dla walidacji
                $clean_nip = preg_replace('/[^0-9]/', '', $nip);
                
                // Sprawdź długość (NIP w Polsce ma zawsze 10 cyfr)
                if (strlen($clean_nip) !== 10) {
                    $errors[] = 'NIP musi zawierać dokładnie 10 cyfr (podano: ' . strlen($clean_nip) . ')';
                } else {
                    // Zachowaj oryginalny NIP (może mieć myślniki)
                    $validated_data['nip'] = $nip;
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
        
        if (file_exists($data_dir) && !is_writable($data_dir)) {
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