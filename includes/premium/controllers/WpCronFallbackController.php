<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WordPress Cron Fallback Controller
 * Manages daily fallback checks at 13:00 UTC when automation is enabled
 */
class WpCronFallbackController {
    
    const CRON_HOOK = 'fallback_cron';
    const CRON_SCHEDULE = 'daily_13_utc';
    
    private $wpCronFallbackUseCase;
    
    public function __construct() {
        $this->wpCronFallbackUseCase = new WpCronFallbackUseCase();
        
        // Register custom cron schedule
        add_filter('cron_schedules', [$this, 'add_custom_cron_schedule']);
        
        // Register cron hook handler
        add_action(self::CRON_HOOK, [$this, 'handle_fallback_cron']);
        
        // Initialize cron job
        $this->initialize_cron();
    }
    
    /**
     * Add custom cron schedule for daily at 13:00 UTC
     */
    public function add_custom_cron_schedule($schedules) {
        $schedules[self::CRON_SCHEDULE] = [
            'interval' => DAY_IN_SECONDS,
            'display'  => 'Daily at 13:00 UTC'
        ];
        return $schedules;
    }
    
    /**
     * Initialize cron job - schedule if not already scheduled
     */
    public function initialize_cron() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            // Calculate next 13:00 UTC
            $next_run = $this->get_next_13_utc();
            wp_schedule_event($next_run, self::CRON_SCHEDULE, self::CRON_HOOK);
            Logger::info('WP Cron Fallback: Scheduled daily fallback check at ' . gmdate('Y-m-d H:i:s', $next_run) . ' UTC');
        }
    }
    
    /**
     * Handle fallback cron execution
     */
    public function handle_fallback_cron() {
        Logger::info('WP Cron Fallback: Cron job triggered at ' . gmdate('Y-m-d H:i:s') . ' UTC');
        
        try {
            $result = $this->wpCronFallbackUseCase->execute();
            
            if ($result['success']) {
                Logger::info("WP Cron Fallback: {$result['message']} (action: {$result['action_taken']})");
            } else {
                Logger::info("WP Cron Fallback Error: {$result['message']}");
            }
            
        } catch (Exception $e) {
            Logger::error('WP Cron Fallback Controller Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Calculate next 13:00 UTC timestamp
     */
    private function get_next_13_utc(): int {
        $now = time();
        $today_13_utc = strtotime('today 13:00:00 UTC');
        
        // If it's already past 13:00 UTC today, schedule for tomorrow
        if ($now >= $today_13_utc) {
            return strtotime('tomorrow 13:00:00 UTC');
        }
        
        return $today_13_utc;
    }
    
    /**
     * Enable fallback cron - called when automation is enabled
     */
    public static function enable_fallback_cron() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            $controller = new self();
            $controller->initialize_cron();
            Logger::info('WP Cron Fallback: Enabled fallback cron');
        }
    }
    
    /**
     * Disable fallback cron - called when automation is disabled
     */
    public static function disable_fallback_cron() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
            Logger::info('WP Cron Fallback: Disabled fallback cron');
        }
    }
    
    /**
     * Get fallback cron status
     */
    public static function get_fallback_status(): array {
        $next_run = wp_next_scheduled(self::CRON_HOOK);
        return [
            'enabled' => (bool) $next_run,
            'next_run' => $next_run ? gmdate('Y-m-d H:i:s', $next_run) . ' UTC' : null,
            'hook' => self::CRON_HOOK
        ];
    }
    
    /**
     * Manual trigger for testing purposes
     */
    public static function trigger_fallback_manually(): array {
        $useCase = new WpCronFallbackUseCase();
        return $useCase->execute();
    }
}