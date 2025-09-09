<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Data model for file information
 * Encapsulates file paths, names and URLs in a structured way
 */
class FileData {
    
    public readonly string $filepath;
    public readonly string $filename;
    public readonly string $url;
    
    public function __construct(
        string $filepath,
        string $filename, 
        string $url
    ) {
        $this->filepath = $filepath;
        $this->filename = $filename;
        $this->url = $url;
    }
    
    /**
     * Create FileData from FileManager array response
     */
    public static function fromArray(array $data): self {
        return new self(
            $data['filepath'] ?? '',
            $data['filename'] ?? '',
            $data['url'] ?? ''
        );
    }
}