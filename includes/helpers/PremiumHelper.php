<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Premium Helper
 * 
 * Provides utility methods for premium feature detection and messaging.
 */
class PremiumHelper {
    
    /**
     * Checks if premium version is active and licensed
     *
     * Returns true only if:
     * 1. Premium folder exists (premium version installed)
     * 2. License is valid (activated and not expired/domain-changed)
     *
     * @return bool True if premium features are available.
     */
    public static function is_premium() {
        // Check if premium folder exists
        if (!file_exists(JAWNECENY_PLUGIN_DIR . 'includes/premium/')) {
            return false;
        }

        // Check if license is valid
        $licenseRepo = DIContainer::get(LicenseRepository::class);
        return $licenseRepo->isValid();
    }
    
    public static function get_upgrade_message() {
        $message = 'Funkcje automatycznego publikowania danych dostępne na
                <a href="https://www.deweloperjawneceny.pl/?utm_source=wordpress&utm_medium=free-version-banner&utm_campaign=promotion" target="_blank">www.deweloperjawneceny.pl</a>';

        $allowed_html = [
            'a' => [
                'href' => true,
                'target' => true
            ]
        ];

        return wp_kses($message, $allowed_html);
    }
}