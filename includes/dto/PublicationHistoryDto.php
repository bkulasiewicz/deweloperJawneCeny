<?php

if (!defined('ABSPATH')) {
    exit;
}

class PublicationHistoryDto extends ModelDto {
    
    public const FIELD_TIMESTAMP = 'timestamp';
    public const FIELD_STATUS = 'status';
    public const FIELD_MESSAGE = 'message';
    public const FIELD_TRIGGER_TYPE = 'trigger_type';
    
    public DateTime $timestamp;
    public string $status;
    public string $message;
    public string $trigger_type;
    
    public function __construct(
        DateTime $timestamp,
        string $status,
        string $message,
        string $trigger_type
    ) {
        $this->timestamp = $timestamp;
        $this->status = $status;
        $this->message = $message;
        $this->trigger_type = $trigger_type;
    }
    
    public static function databaseToModel(array $data): static {
        return new static(
            new DateTime($data[self::FIELD_TIMESTAMP]),
            $data[self::FIELD_STATUS],
            $data[self::FIELD_MESSAGE] ?? '',
            $data[self::FIELD_TRIGGER_TYPE]
        );
    }
    
    public function modelToDatabase(): array {
        return [
            self::FIELD_TIMESTAMP => $this->timestamp,
            self::FIELD_STATUS => $this->status,
            self::FIELD_MESSAGE => $this->message,
            self::FIELD_TRIGGER_TYPE => $this->trigger_type
        ];
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
            'wp_cron_fallback' => 'WP Cron Fallback',
            default => $this->trigger_type
        };
    }
}