<?php

if (!defined('ABSPATH')) {
    exit;
}

class ToggleAutomationUseCase {
    
    public static function execute($enabled) {
        try {
            $enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
            
            update_option('ujc_automation_enabled', $enabled);
            
            $message = $enabled ? 'Automatyzacja została włączona' : 'Automatyzacja została wyłączona';
            
            return [
                'success' => true,
                'message' => $message
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Błąd serwera: ' . $e->getMessage()
            ];
        }
    }
}