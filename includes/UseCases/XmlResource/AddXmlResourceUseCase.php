<?php

if (!defined('ABSPATH')) {
    exit;
}

class AddXmlResourceUseCase {
    
    private $xmlResourceRepo;
    private $developerRepo;
    
    public function __construct() {
        $this->xmlResourceRepo = new XmlResourceRepository();
        $this->developerRepo = new DeveloperRepository();
    }
    
    /**
     * Add new XML resource with auto-generated unique ext_ident
     * 
     * @param string $csv_url URL to CSV file
     * @param string|null $data_date Date (YYYY-MM-DD), defaults to today
     * @return Result Success or failure with message
     */
    public function execute(string $csv_url, ?string $data_date = null): Result {
        Logger::info('AddXmlResource: Starting with CSV URL: ' . $csv_url);
        
        try {
            // Validate CSV URL
            if (empty($csv_url)) {
                Logger::error('AddXmlResource: CSV URL is empty');
                return Result::failure('URL do pliku CSV jest wymagany');
            }
            
            // Set data_date
            $data_date = $data_date ?? gmdate('Y-m-d');
            Logger::info('AddXmlResource: Using data_date: ' . $data_date);
            
            // Generate unique ext_ident
            $ext_ident = $this->generateUniqueExtIdent($data_date);
            Logger::info('AddXmlResource: Generated ext_ident: ' . $ext_ident);
            
            // Create DTO
            $xmlResourceDto = new XmlResourceDto(
                0, // ID will be auto-generated
                $ext_ident,
                $csv_url,
                $data_date,
                DateHelper::currentDatetime()
            );
            
            // Save to database
            $success = $this->xmlResourceRepo->addResource($xmlResourceDto);
            
            if (!$success) {
                Logger::error('AddXmlResource: Database save failed');
                return Result::failure('Nie udało się zapisać XML resource do bazy danych');
            }
            
            Logger::success('AddXmlResource: Resource saved successfully with ext_ident: ' . $ext_ident);
            return Result::success('XML resource został zapisany pomyślnie');
            
        } catch (Exception $e) {
            Logger::error('AddXmlResource: Exception: ' . $e->getMessage());
            return Result::failure('Błąd podczas zapisywania XML resource: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate unique ext_ident (36 characters)
     * Uses timestamp + random component for uniqueness
     */
    private function generateUniqueExtIdent(string $data_date): string {
        Logger::info('AddXmlResource: Generating unique ext_ident for date: ' . $data_date);
        
        // Get developer data for NIP
        $developer = $this->developerRepo->read();
        if (!$developer) {
            throw new Exception('Brak danych dewelopera - wymagane dla generowania ext_ident');
        }
        
        $nip = trim($developer->nr_nip ?? '');
        if (empty($nip)) {
            throw new Exception('NIP dewelopera jest wymagany do generowania ext_ident');
        }
        
        // Generate unique identifier with timestamp and random component
        $formatted_date = gmdate('Ymd', strtotime($data_date));
        $timestamp = gmdate('His'); // HHMMSS for uniqueness
        $random = sprintf('%03d', mt_rand(0, 999)); // 3-digit random
        
        $base = "dew{$nip}{$formatted_date}{$timestamp}{$random}";
        
        // Ensure exactly 36 characters
        $ext_ident = substr(str_pad($base, 36, '0', STR_PAD_RIGHT), 0, 36);
        
        Logger::info('AddXmlResource: Generated base: ' . $base);
        Logger::info('AddXmlResource: Final ext_ident: ' . $ext_ident . ' (length: ' . strlen($ext_ident) . ')');
        
        return $ext_ident;
    }
}