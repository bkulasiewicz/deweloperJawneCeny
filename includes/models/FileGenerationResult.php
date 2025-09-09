<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Result model for file generation operations
 * Provides structured response with file data for complex use cases
 */
class FileGenerationResult {
    
    public readonly bool $isSuccess;
    public readonly string $message;
    public readonly ?FileData $csvFile;
    public readonly ?FileData $xmlFile;
    
    private function __construct(
        bool $isSuccess,
        string $message,
        ?FileData $csvFile = null,
        ?FileData $xmlFile = null
    ) {
        $this->isSuccess = $isSuccess;
        $this->message = $message;
        $this->csvFile = $csvFile;
        $this->xmlFile = $xmlFile;
    }
    
    /**
     * Create successful result with file data
     */
    public static function success(string $message, FileData $csvFile, FileData $xmlFile): self {
        return new self(true, $message, $csvFile, $xmlFile);
    }
    
    /**
     * Create failure result with error message
     */
    public static function failure(string $message): self {
        return new self(false, $message);
    }
}