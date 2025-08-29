<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Publication trigger type enum
 */
enum TriggerType: string {
    case Manual = 'manual';
    case ExternalCron = 'external_cron';
}