<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Publication status enum
 */
enum PublicationStatus: string {
    case Success = 'success';
    case Error = 'error';
}