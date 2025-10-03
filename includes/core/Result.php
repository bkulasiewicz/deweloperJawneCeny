<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

class Result {
    public readonly bool $isSuccess;
    public readonly string $message;
    
    private function __construct(bool $isSuccess, string $message) {
        $this->isSuccess = $isSuccess;
        $this->message = $message;
    }
    
    public static function success(string $message = 'Operacja zakończona sukcesem'): self {
        return new self(true, $message);
    }
    
    public static function failure(string $message): self {
        return new self(false, $message);
    }
}