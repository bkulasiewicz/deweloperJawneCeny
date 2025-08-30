<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Immutable model for publication history entry
 */
readonly class PublicationHistoryEntry {
    
    public function __construct(
        public int $id,
        public int $timestamp,
        public string $status,
        public string $message,
        public string $trigger_type
    ) {}
    
    /**
     * Create from database row
     */
    public static function fromArray(array $data): self {
        return new self(
            (int) $data['id'],
            (int) $data['timestamp'],
            $data['status'],
            $data['message'] ?? '',
            $data['trigger_type']
        );
    }
    
    /**
     * Get formatted date for display
     */
    public function getFormattedDate(): string {
        return DateHelper::formatTimestampForUser($this->timestamp);
    }
    
    /**
     * Get status icon
     */
    public function getStatusIcon(): string {
        return $this->status === 'success' ? '✅' : '❌';
    }
    
    /**
     * Get status label
     */
    public function getStatusLabel(): string {
        return $this->status === 'success' ? 'Sukces' : 'Błąd';
    }
    
    /**
     * Get trigger type label
     */
    public function getTriggerTypeLabel(): string {
        return match($this->trigger_type) {
            'manual' => 'Ręczne',
            'external_cron' => 'External Cron',
            default => $this->trigger_type
        };
    }
    
}