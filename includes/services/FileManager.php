<?php

namespace JawneCeny;

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
        $this->baseDirectory = $upload_dir['basedir'] . '/ujc-data';
        $this->baseUrl = get_site_url() . '/wp-content/uploads/ujc-data';
    }
    
    /**
     * Save CSV from array to file
     * 
     * @param array $csvRows CSV rows array
     * @param string $filename Filename to save
     * @return array Result with filepath and url
     */
    public function saveCSV(array $csvRows, string $filename): array {
        Logger::info('FileManager: Starting CSV save - filename: ' . $filename);
        
        $this->ensureDirectoryExists();
        
        $filepath = $this->getFilePath($filename);
        Logger::info('FileManager: CSV filepath: ' . $filepath);
        
        $csv_content = '';
        $row_count = 0;
        
        // Przechodzimy przez każdy wiersz z generatora (identycznie jak oryginał)
        foreach ($csvRows as $row) {
            $csv_line = '';
            
            // Formatujemy każde pole w wierszu (zastępuje fputcsv logic)
            foreach ($row as $i => $field) {
                // Dodaj separator średnik między polami (nie przed pierwszym)
                if ($i > 0) $csv_line .= ';';
                
                // Escape cudzysłowy przez podwojenie i otocz pole cudzysłowami
                // (identyczna logika jak fputcsv dla bezpieczeństwa)
                $csv_line .= '"' . str_replace('"', '""', $field) . '"';
            }
            
            // Dodaj wiersz do buffera (separator nowej linii między wierszami)
            if ($row_count > 0) $csv_content .= "\n";
            $csv_content .= $csv_line;
            $row_count++;  // Zachowujemy counting dla logowania
        }
        
        // Zapisz cały CSV jednorazowo przez WordPress (zastępuje fopen/fwrite/fclose)
        global $wp_filesystem;
        if (!$wp_filesystem->put_contents($filepath, $csv_content, FS_CHMOD_FILE)) {
            Logger::error('FileManager: FAILED to create CSV file: ' . $filepath);
            throw new Exception(sprintf('Cannot create file: %s', esc_html($filepath)));
        }
        
        // Rozmiar z zawartości string (zastępuje filesize)
        $file_size = strlen($csv_content);
        Logger::success('FileManager: CSV saved successfully - rows: ' . $row_count . ', size: ' . $file_size . ' bytes');
        
        return [
            'filepath' => $filepath,
            'filename' => $filename,
            'url' => $this->getPublicUrl($filename),
            'relative_path' => $this->getRelativePath($filename)
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
        global $wp_filesystem;
        if (!$wp_filesystem->put_contents($filepath, $xmlContent, FS_CHMOD_FILE)) {
            throw new Exception(sprintf('Cannot write XML file: %s', esc_html($filepath)));
        }
        
        // Generate and save MD5
        $md5_filename = str_replace('.xml', '.md5', $filename);
        $md5_filepath = $this->getFilePath($md5_filename);
        $md5_content = md5_file($filepath);

        if (!$wp_filesystem->put_contents($md5_filepath, $md5_content, FS_CHMOD_FILE)) {
            throw new Exception(sprintf('Cannot write MD5 file: %s', esc_html($md5_filepath)));
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
    public function generateCSVFilename(SupplierDto $developer): string {
        $developer_name_clean = $this->sanitizeFilename($developer->nazwa ?? 'developer');
        $date_string = DateHelper::getCurrentDateYmd();
        return "Ceny-ofertowe-mieszkan-dewelopera-{$developer_name_clean}-{$date_string}.csv";
    }
    
    /**
     * Generate XML filename
     */
    public function generateXMLFilename(): string {
        return 'katalog-danych.xml';
    }
    
    /**
     * Ensure base directory exists and has proper .htaccess for no-cache XML
     */
    public function ensureDirectoryExists(): bool {
        if (!is_dir($this->baseDirectory)) {
            wp_mkdir_p($this->baseDirectory);
        }

        global $wp_filesystem;
        if (!$wp_filesystem->is_writable($this->baseDirectory)) {
            throw new Exception(sprintf('Directory not writable: %s', esc_html($this->baseDirectory)));
        }

        // Create .htaccess to prevent XML caching
        $this->ensureHtaccessExists();

        return true;
    }

    /**
     * Get upload directory configuration for ujc-data
     * Used by wp_handle_upload() filter
     *
     * @return array Upload directory config
     */
    public function getUploadDirConfig(): array {
        $this->ensureDirectoryExists();

        return [
            'path' => $this->baseDirectory,
            'url' => $this->baseUrl,
            'subdir' => '',
            'basedir' => $this->baseDirectory,
            'baseurl' => $this->baseUrl,
            'error' => false,
        ];
    }
    
    /**
     * Create or update .htaccess file to prevent caching of XML and MD5 files and set CSV encoding
     */
    private function ensureHtaccessExists(): void {
        $htaccess_path = $this->baseDirectory . '/.htaccess';

        // Define expected .htaccess content
        $expected_content = "# Prevent caching of XML files for data freshness\n";
        $expected_content .= "<Files \"*.xml\">\n";
        $expected_content .= "    Header set Cache-Control \"no-cache, no-store, must-revalidate\"\n";
        $expected_content .= "    Header set Pragma \"no-cache\"\n";
        $expected_content .= "    Header set Expires 0\n";
        $expected_content .= "</Files>\n\n";
        $expected_content .= "# Force CSV files to use UTF-8 encoding\n";
        $expected_content .= "<Files \"*.csv\">\n";
        $expected_content .= "    Header set Content-Type \"text/csv; charset=UTF-8\"\n";
        $expected_content .= "</Files>\n\n";
        $expected_content .= "# Prevent caching of MD5 checksum files\n";
        $expected_content .= "<Files \"*.md5\">\n";
        $expected_content .= "    Header set Cache-Control \"no-cache, no-store, must-revalidate\"\n";
        $expected_content .= "    Header set Pragma \"no-cache\"\n";
        $expected_content .= "    Header set Expires 0\n";
        $expected_content .= "    Header set Content-Type \"text/plain; charset=UTF-8\"\n";
        $expected_content .= "</Files>\n\n";
        $expected_content .= "# Allow direct access to all files\n";
        $expected_content .= "Order Allow,Deny\n";
        $expected_content .= "Allow from all\n";

        global $wp_filesystem;

        // Check if file exists and if content matches
        $needs_update = false;

        if (!$wp_filesystem->exists($htaccess_path)) {
            // File doesn't exist - create it
            $needs_update = true;
        } else {
            // File exists - check if content is outdated
            $current_content = $wp_filesystem->get_contents($htaccess_path);
            if ($current_content !== $expected_content) {
                // Content differs - update needed
                $needs_update = true;
            }
        }

        if ($needs_update) {
            $wp_filesystem->put_contents($htaccess_path, $expected_content, FS_CHMOD_FILE);
        }
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
        // All files use direct paths for dane.gov.pl compliance
        return $this->baseUrl . '/' . $filename;
    }

    /**
     * Get relative path for file (for database storage)
     * Returns path without domain for portability between environments
     */
    public function getRelativePath(string $filename): string {
        return 'wp-content/uploads/ujc-data/' . $filename;
    }

    /**
     * Build full URL from stored path/URL
     * Handles relative paths and legacy ?file= format
     *
     * @param string $storedValue Value from database (relative path or legacy ?file= URL)
     * @return string Full URL with current domain
     */
    public function buildFullUrlFromStoredPath(string $storedValue): string {
        $siteUrl = get_site_url();

        // 1. Ścieżka względna (po migracji) - nie zaczyna się od http
        if (strpos($storedValue, 'http') !== 0) {
            return $siteUrl . '/' . ltrim($storedValue, '/');
        }

        // 2. Legacy format ?file= (przed migracją tego formatu)
        if (strpos($storedValue, '?file=') !== false) {
            $filename = urldecode(substr($storedValue, strpos($storedValue, '?file=') + 6));
            return $siteUrl . '/wp-content/uploads/ujc-data/' . $filename;
        }

        // Fallback - zwróć jak jest
        return $storedValue;
    }
    
    /**
     * Check if file exists
     */
    public function fileExists(string $filename): bool {
        global $wp_filesystem;
        return $wp_filesystem->exists($this->getFilePath($filename));
    }
    
    /**
     * Get file modification time
     */
    public function getFileModTime(string $filename): int|false {
        $filepath = $this->getFilePath($filename);
        global $wp_filesystem;
        return $wp_filesystem->exists($filepath) ? $wp_filesystem->mtime($filepath) : false;
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