<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * File management service - handles file operations
 * Separated from business logic for clean architecture
 */
class FileManager {
    
    private $baseDirectory;
    private $baseUrl;
    
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->baseDirectory = $upload_dir['basedir'] . '/ujc-data'; // Will be configurable later
        $this->baseUrl = $upload_dir['baseurl'] . '/ujc-data';
    }
    
    /**
     * Save CSV from generator to file
     * 
     * @param Generator $csvRows CSV rows generator
     * @param string $filename Filename to save
     * @return array Result with filepath and url
     */
    public function saveCSV(Generator $csvRows, string $filename): array {
        $this->ensureDirectoryExists();
        
        $filepath = $this->getFilePath($filename);
        
        $file = fopen($filepath, 'w');
        if (!$file) {
            throw new Exception("Cannot create file: {$filepath}");
        }
        
        foreach ($csvRows as $row) {
            fputcsv($file, $row, ';');
        }
        fclose($file);
        
        return [
            'filepath' => $filepath,
            'filename' => $filename,
            'url' => $this->getPublicUrl($filename)
        ];
    }
    
    /**
     * Save XML content to file with MD5
     * 
     * @param string $xmlContent XML content
     * @param string $filename Filename to save
     * @return array Result with filepath and url
     */
    public function saveXML(string $xmlContent, string $filename): array {
        $this->ensureDirectoryExists();
        
        $filepath = $this->getFilePath($filename);
        
        // Save XML file
        if (file_put_contents($filepath, $xmlContent) === false) {
            throw new Exception("Cannot write XML file: {$filepath}");
        }
        
        // Generate and save MD5
        $md5_filename = str_replace('.xml', '.md5', $filename);
        $md5_filepath = $this->getFilePath($md5_filename);
        $md5_content = md5_file($filepath) . '  ' . $filename;
        
        if (file_put_contents($md5_filepath, $md5_content) === false) {
            throw new Exception("Cannot write MD5 file: {$md5_filepath}");
        }
        
        return [
            'filepath' => $filepath,
            'filename' => $filename,
            'url' => $this->getPublicUrl($filename),
            'md5_filepath' => $md5_filepath,
            'md5_filename' => $md5_filename,
            'md5_url' => $this->getPublicUrl($md5_filename)
        ];
    }
    
    /**
     * Generate CSV filename according to law requirements
     */
    public function generateCSVFilename(array $developer): string {
        $developer_name_clean = $this->sanitizeFilename($developer['nazwa'] ?? 'developer');
        $date_string = date('Y-m-d');
        return "Ceny-ofertowe-mieszkan-dewelopera-{$developer_name_clean}-{$date_string}.csv";
    }
    
    /**
     * Generate XML filename
     */
    public function generateXMLFilename(): string {
        return 'katalog-danych.xml';
    }
    
    /**
     * Ensure base directory exists
     */
    public function ensureDirectoryExists(): bool {
        if (!is_dir($this->baseDirectory)) {
            return wp_mkdir_p($this->baseDirectory);
        }
        
        if (!is_writable($this->baseDirectory)) {
            throw new Exception("Directory not writable: {$this->baseDirectory}");
        }
        
        return true;
    }
    
    /**
     * Get full file path
     */
    public function getFilePath(string $filename): string {
        return $this->baseDirectory . '/' . $filename;
    }
    
    /**
     * Get public URL for file
     */
    public function getPublicUrl(string $filename): string {
        return $this->baseUrl . '/' . $filename;
    }
    
    /**
     * Check if file exists
     */
    public function fileExists(string $filename): bool {
        return file_exists($this->getFilePath($filename));
    }
    
    /**
     * Get file modification time
     */
    public function getFileModTime(string $filename): int|false {
        $filepath = $this->getFilePath($filename);
        return file_exists($filepath) ? filemtime($filepath) : false;
    }
    
    /**
     * Sanitize filename part
     */
    private function sanitizeFilename(string $name): string {
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