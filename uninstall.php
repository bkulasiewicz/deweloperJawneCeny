<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Czyszczenie zebranych plików logów po usunięciu wtyczki UJC
 */


function jawneceny_uninstall_cleanup_logs(): void {
    // Usuń pliki logów zebrane przez wtyczkę (jeśli istnieją)
    $ujc_upload_dir = wp_upload_dir();
    $ujc_log_files_pattern = $ujc_upload_dir['basedir'] . '/ujc-debug-logs-*.txt';

    // Znajdź i usuń wszystkie pliki logów UJC
    $ujc_log_files = glob($ujc_log_files_pattern);
    if ($ujc_log_files) {
    foreach ($ujc_log_files as $file) {
        if (is_file($file)) {
            wp_delete_file($file);
        }
        }
    }

    // Usuń również z katalogu tymczasowego jeśli istnieje
    $ujc_temp_log_pattern = sys_get_temp_dir() . '/ujc-debug-logs-*.txt';
    $ujc_temp_files = glob($ujc_temp_log_pattern);
    if ($ujc_temp_files) {
        foreach ($temp_files as $file) {
            if (is_file($file)) {
                wp_delete_file($file);
            }
        }
    }
}

jawneceny_uninstall_cleanup_logs();