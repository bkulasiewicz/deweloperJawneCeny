<?php

if (!defined('ABSPATH')) {
    exit;
}

class GenerateFilesManuallyUseCase {
    
    public static function execute() {
        try {
            $generator = new UJC_Automated_Generator();
            $result = $generator->generate_files_manual('ręczne - główny');
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => $result['message']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Błąd: ' . $result['error']
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Błąd serwera: ' . $e->getMessage()
            ];
        }
    }
}