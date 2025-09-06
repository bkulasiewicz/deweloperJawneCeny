<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * External Cron Repository
 * Handles all API communication with cron-job.org service
 */
class ExternalCronRepository {
    
    const API_BASE_URL = 'https://api.cron-job.org';
    const MASTER_API_TOKEN = 'wo9C6djkZHyARTbeKiLN69/0DcQDeEfx4hZG2N47qio=';
    
    /**
     * Create new cron job
     * 
     * @param string $endpoint The endpoint URL to call
     * @param array $schedule_config Schedule configuration (hours, minutes, etc.)
     * @param string $title Job title
     * @return array ['success' => bool, 'job_id' => string|null, 'message' => string]
     */
    public function createJob($endpoint, $schedule_config, $title) {
        $job_data = [
            'job' => [
                'url' => $endpoint,
                'enabled' => true,
                'title' => $title,
                'schedule' => [
                    'timezone' => 'UTC',
                    'hours' => $schedule_config['hours'] ?? [6],
                    'minutes' => $schedule_config['minutes'] ?? [0],
                    'mdays' => [-1],
                    'months' => [-1],
                    'wdays' => [-1]
                ]
            ]
        ];
        
        $response = wp_remote_request(self::API_BASE_URL . '/jobs', [
            'method' => 'PUT',
            'headers' => [
                'Authorization' => 'Bearer ' . self::MASTER_API_TOKEN,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($job_data),
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'job_id' => null,
                'message' => 'Request error: ' . $response->get_error_message()
            ];
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($response_code === 200 && isset($body['jobId'])) {
            return [
                'success' => true,
                'job_id' => $body['jobId'],
                'message' => 'Job created successfully'
            ];
        }
        
        return [
            'success' => false,
            'job_id' => null,
            'message' => 'Failed to create job: ' . wp_remote_retrieve_body($response)
        ];
    }
    
    /**
     * Update existing job schedule
     * 
     * @param string $job_id Job ID to update
     * @param array $schedule_config Schedule configuration
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateJobSchedule($job_id, $schedule_config) {
        $job_data = [
            'job' => [
                'schedule' => [
                    'timezone' => 'UTC',
                    'hours' => $schedule_config['hours'],
                    'minutes' => $schedule_config['minutes'],
                    'mdays' => [-1],
                    'months' => [-1],
                    'wdays' => [-1]
                ]
            ]
        ];
        
        $response = wp_remote_request(self::API_BASE_URL . "/jobs/{$job_id}", [
            'method' => 'PATCH',
            'headers' => [
                'Authorization' => 'Bearer ' . self::MASTER_API_TOKEN,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($job_data),
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Request error: ' . $response->get_error_message()
            ];
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code === 200) {
            return [
                'success' => true,
                'message' => 'Schedule updated successfully'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to update schedule: ' . wp_remote_retrieve_body($response)
        ];
    }
    
    /**
     * Delete cron job
     * 
     * @param string $job_id Job ID to delete
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteJob($job_id) {
        $response = wp_remote_request(self::API_BASE_URL . "/jobs/{$job_id}", [
            'method' => 'DELETE',
            'headers' => [
                'Authorization' => 'Bearer ' . self::MASTER_API_TOKEN
            ],
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Request error: ' . $response->get_error_message()
            ];
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code === 200 || $response_code === 404) {
            return [
                'success' => true,
                'message' => 'Job deleted successfully'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to delete job: ' . wp_remote_retrieve_body($response)
        ];
    }
    
    
    /**
     * Get available schedule configurations
     * 
     * @return array Available schedule configurations
     */
    public function getAvailableSchedules() {
        return [
            '1min' => ['hours' => [-1], 'minutes' => [-1], 'interval_text' => 'Co minutę'],
            '15min' => ['hours' => [-1], 'minutes' => [0, 15, 30, 45], 'interval_text' => 'Co 15 minut'],
            '1hour' => ['hours' => [-1], 'minutes' => [0], 'interval_text' => 'Co godzinę'],
            '6hour' => ['hours' => [0, 6, 12, 18], 'minutes' => [0], 'interval_text' => 'Co 6 godzin'],
            '12hour' => ['hours' => [0, 12], 'minutes' => [0], 'interval_text' => 'Co 12 godzin'],
            '24hour' => ['hours' => [22], 'minutes' => [0], 'interval_text' => 'Codziennie o 22:00 UTC']
        ];
    }
}