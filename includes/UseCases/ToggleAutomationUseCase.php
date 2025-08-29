<?php

if (!defined('ABSPATH')) {
    exit;
}

class ToggleAutomationUseCase {
    
    public static function execute($enabled) {
        try {
            $enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
            
            $settings_repository = new SettingsRepository();
            $settings_repository->setAutomationEnabled($enabled);
            
            // External Cron handles automation - this just tracks the preference
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