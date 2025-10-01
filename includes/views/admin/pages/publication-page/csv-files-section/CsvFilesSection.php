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
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }
    
    /**
     * Enqueue CSS assets
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'ujc-csv-files-section',
            plugins_url('CsvFilesSection.css', __FILE__),
            [],
            VERSION
        );
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