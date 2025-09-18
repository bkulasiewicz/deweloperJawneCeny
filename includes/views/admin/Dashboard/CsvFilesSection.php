<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * CSV Files Section for Publication Page
 * Shows list of generated CSV files with download links
 */
class CsvFilesSection {

    private $xmlResourceRepo;

    public function __construct(XmlResourceRepository $xmlResourceRepository) {
        $this->xmlResourceRepo = $xmlResourceRepository;
    }
    
    /**
     * Render CSV files section
     */
    public function render(): void {
        $csvFiles = $this->getCsvFiles();
        ?>
        <div class="ujc-csv-files-section">
            <h3>📄 Wygenerowane pliki CSV</h3>
            
            <?php if (empty($csvFiles)): ?>
                <div class="ujc-no-files">
                    <p>Brak wygenerowanych plików CSV.</p>
                    <p><em>Pliki pojawią się po pierwszej publikacji.</em></p>
                </div>
            <?php else: ?>
                <div class="ujc-csv-files-list">
                    <?php foreach ($csvFiles as $file): ?>
                        <div class="ujc-csv-file-item">
                            <div class="ujc-csv-file-info">
                                <div class="ujc-csv-file-date">
                                    <strong><?php echo esc_html(DateHelper::formatDateOnly($file->data_date)); ?></strong>
                                </div>
                                <div class="ujc-csv-file-time">
                                    <small><?php echo esc_html(DateHelper::formatForUser($file->created_at)); ?></small>
                                </div>
                            </div>
                            <div class="ujc-csv-file-actions">
                                <a href="<?php echo esc_url($file->csv_url); ?>" 
                                   target="_blank" 
                                   class="button button-secondary">
                                    📥 Pobierz
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="ujc-csv-files-summary">
                    <p><small>Łącznie: <strong><?php echo count($csvFiles); ?></strong> plików</small></p>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
        .ujc-csv-files-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .ujc-csv-files-section h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #1d2327;
        }
        
        .ujc-no-files {
            text-align: center;
            padding: 20px;
            color: #646970;
        }
        
        .ujc-csv-files-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .ujc-csv-file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f1;
        }
        
        .ujc-csv-file-item:last-child {
            border-bottom: none;
        }
        
        .ujc-csv-file-info {
            flex: 1;
        }
        
        .ujc-csv-file-date {
            font-size: 14px;
            color: #1d2327;
        }
        
        .ujc-csv-file-time {
            color: #646970;
            font-size: 12px;
            margin-top: 2px;
        }
        
        .ujc-csv-file-actions {
            flex-shrink: 0;
        }
        
        .ujc-csv-files-summary {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #f0f0f1;
            text-align: center;
        }
        </style>
        <?php
    }
    
    /**
     * Get CSV files from database
     */
    private function getCsvFiles(): array {
        try {
            $files = $this->xmlResourceRepo->readAll();
            
            // Sort by date descending (newest first)
            usort($files, function($a, $b) {
                return strcmp($b->data_date, $a->data_date);
            });
            
            return $files;
        } catch (Exception $e) {
            return [];
        }
    }
    
}