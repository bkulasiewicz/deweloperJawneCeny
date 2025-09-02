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
        $this->baseUrl = get_site_url() . '/wp-content/uploads/ujc-data';
    }
    
    /**
     * Save CSV from generator to file
     * 
     * @param Generator $csvRows CSV rows generator
     * @param string $filename Filename to save
     * @return array Result with filepath and url
     */
    public function saveCSV(Generator $csvRows, string $filename): array {
        error_log('FileManager: Starting CSV save - filename: ' . $filename);
        
        $this->ensureDirectoryExists();
        
        $filepath = $this->getFilePath($filename);
        error_log('FileManager: CSV filepath: ' . $filepath);
        
        $file = fopen($filepath, 'w');
        if (!$file) {
            error_log('FileManager: FAILED to create CSV file: ' . $filepath);
            throw new Exception("Cannot create file: {$filepath}");
        }
        
        $row_count = 0;
        foreach ($csvRows as $row) {
            fputcsv($file, $row, ';');
            $row_count++;
        }
        fclose($file);
        
        $file_size = filesize($filepath);
        error_log('FileManager: CSV saved successfully - rows: ' . $row_count . ', size: ' . $file_size . ' bytes');
        
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
        error_log('FileManager: Starting XML save - filename: ' . $filename . ', content size: ' . strlen($xmlContent) . ' bytes');
        
        $this->ensureDirectoryExists();
        
        $filepath = $this->getFilePath($filename);
        error_log('FileManager: XML filepath: ' . $filepath);
        
        // Save XML file
        if (file_put_contents($filepath, $xmlContent) === false) {
            error_log('FileManager: FAILED to write XML file: ' . $filepath);
            throw new Exception("Cannot write XML file: {$filepath}");
        }
        
        error_log('FileManager: XML file saved successfully');
        
        // Generate and save MD5
        $md5_filename = str_replace('.xml', '.md5', $filename);
        $md5_filepath = $this->getFilePath($md5_filename);
        $md5_content = md5_file($filepath);
        
        error_log('FileManager: Generated MD5: ' . $md5_content);
        
        if (file_put_contents($md5_filepath, $md5_content) === false) {
            error_log('FileManager: FAILED to write MD5 file: ' . $md5_filepath);
            throw new Exception("Cannot write MD5 file: {$md5_filepath}");
        }
        
        error_log('FileManager: MD5 file saved successfully: ' . $md5_filename);
        
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
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        
        // CSV files use WordPress handler for proper download headers
        if ($extension === 'csv') {
            return get_site_url() . '/?file=' . $filename;
        }
        
        // XML and MD5 files use direct paths for dane.gov.pl compliance
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