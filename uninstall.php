<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Czyszczenie zebranych plików logów po usunięciu wtyczki UJC
 */

// Usuń pliki logów zebrane przez wtyczkę (jeśli istnieją)
$upload_dir = wp_upload_dir();
$log_files_pattern = $upload_dir['basedir'] . '/ujc-debug-logs-*.txt';

// Znajdź i usuń wszystkie pliki logów UJC
$log_files = glob($log_files_pattern);
if ($log_files) {
    foreach ($log_files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}

// Usuń również z katalogu tymczasowego jeśli istnieje
$temp_log_pattern = sys_get_temp_dir() . '/ujc-debug-logs-*.txt';
$temp_files = glob($temp_log_pattern);
if ($temp_files) {
    foreach ($temp_files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}