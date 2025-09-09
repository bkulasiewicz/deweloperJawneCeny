<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WordPress Cron Fallback UseCase
 * Handles fallback file generation when External Cron fails
 * Runs daily at 13:00 UTC to check if generation was successful in last 24 hours
 */
class WpCronFallbackUseCase {
    
    private $settingsRepository;
    private $getPublicationHistoryUseCase;
    private $generateFilesUseCase;
    
    public function __construct() {
        $this->settingsRepository = new SettingsRepository();
        $this->getPublicationHistoryUseCase = new GetPublicationHistoryUseCase();
        $this->generateFilesUseCase = new GenerateFilesUseCase();
    }
    
    /**
     * Execute fallback check and generation if needed
     * 
     * @return array ['success' => bool, 'message' => string, 'action_taken' => string]
     */
    public function execute() {
        try {
            Logger::info('WP Cron Fallback: Starting fallback check at ' . gmdate('Y-m-d H:i:s') . ' UTC');
            
            // Check if External Cron is enabled - only run fallback if External Cron is active
            if (!ExternalCronController::is_external_cron_enabled()) {
                Logger::info('WP Cron Fallback: External Cron is disabled, skipping fallback check');
                return [
                    'success' => true,
                    'message' => 'External Cron is disabled',
                    'action_taken' => 'skipped'
                ];
            }
            
            // Get last entry from publication history
            $history = $this->getPublicationHistoryUseCase->execute(1);
            
            if (empty($history)) {
                Logger::info('WP Cron Fallback: No history found, executing fallback');
                return $this->executeFallbackGeneration('no_history');
            }
            
            $last_entry = $history[0];
            Logger::info("WP Cron Fallback: Last entry status: {$last_entry->status}, trigger: {$last_entry->trigger_type}");
            
            // If last entry is error, execute fallback immediately
            if ($last_entry->status !== 'success') {
                Logger::info('WP Cron Fallback: Last entry is error, executing fallback');
                return $this->executeFallbackGeneration('last_entry_error');
            }
            
            // If last entry is success, check if it's older than 24 hours
            $current_time = time();
            $time_since_last_success = $current_time - $last_entry->timestamp;
            $hours_since_last_success = round($time_since_last_success / 3600, 1);
            
            Logger::info("WP Cron Fallback: Last successful generation was {$hours_since_last_success} hours ago");
            
            // If more than 24 hours since last successful generation, execute fallback
            if ($time_since_last_success > 86400) {
                Logger::info('WP Cron Fallback: More than 24 hours since last success, executing fallback');
                return $this->executeFallbackGeneration('timeout_24h');
            }
            
            Logger::info('WP Cron Fallback: Recent successful generation found, no action needed');
            return [
                'success' => true,
                'message' => "Last successful generation was {$hours_since_last_success} hours ago",
                'action_taken' => 'no_action_needed'
            ];
            
        } catch (Exception $e) {
            Logger::error('WP Cron Fallback Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Fallback check failed: ' . $e->getMessage(),
                'action_taken' => 'error'
            ];
        }
    }
    
    /**
     * Execute fallback file generation
     * 
     * @param string $reason Reason for fallback execution
     * @return array Result of fallback generation
     */
    private function executeFallbackGeneration($reason) {
        Logger::info("WP Cron Fallback: Executing fallback generation (reason: {$reason})");
        
        // Generate files using existing UseCase with WpCronFallback trigger
        $result = $this->generateFilesUseCase->execute(TriggerType::WpCronFallback);
        
        if ($result->isSuccess) {
            Logger::success('WP Cron Fallback: Fallback generation completed successfully');
            return [
                'success' => true,
                'message' => "Fallback generation successful (reason: {$reason})",
                'action_taken' => 'fallback_generated',
                'generation_result' => ['success' => $result->isSuccess, 'message' => $result->message]
            ];
        } else {
            Logger::error('WP Cron Fallback: Fallback generation failed - ' . $result->message);
            return [
                'success' => false,
                'message' => "Fallback generation failed: " . $result->message,
                'action_taken' => 'fallback_failed',
                'generation_result' => ['success' => $result->isSuccess, 'message' => $result->message]
            ];
        }
    }
}