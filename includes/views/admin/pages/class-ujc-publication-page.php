<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Strona Publikacja Danych - status publikacji, historia, generowanie
 */
class UJC_Publication_Page {
    
    public function __construct() {
        add_action('wp_ajax_ujc_publication_generate', [$this, 'ajax_generate_files']);
    }
    
    public function ajax_generate_files() {
        check_ajax_referer('ujc_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Brak uprawnień');
        
        require_once PLUGIN_DIR . 'includes/UseCases/GenerateFilesUseCase.php';
        
        $result = GenerateFilesUseCase::execute();
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    public function render() {
        $publication_status = $this->get_publication_status();
        
        ?>
        <div class="wrap">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                <h1>Publikacja Danych</h1>
                <div style="text-align: right; font-size: 14px; line-height: 1.4;">
                    <?php $this->render_public_urls(); ?>
                </div>
            </div>
            
            <div class="ujc-publication">
                
                <!-- Ręczne Generowanie -->
                <div class="ujc-manual-generation">
                    <h2>Generowanie Plików</h2>
                    <?php $this->render_manual_generation(); ?>
                </div>
                
                <!-- Historia z możliwością pobrania -->
                <div class="ujc-publication-history">
                    <h2>Historia Publikacji</h2>
                    <?php $this->render_publication_history(); ?>
                </div>
            </div>
        </div>
        
        <script>
        function generateFiles() {
            const button = document.getElementById('generate-files-btn');
            const originalText = button.textContent;
            button.textContent = '⏳ Generowanie...';
            button.disabled = true;
            
            const nonce = typeof ujc_ajax !== 'undefined' ? ujc_ajax.nonce : '<?php echo wp_create_nonce('ujc_admin_nonce'); ?>';
            
            jQuery.post(ajaxurl, {
                action: 'ujc_publication_generate',
                nonce: nonce
            }, function(response) {
                if (response.success) {
                    alert('✅ ' + response.data);
                    location.reload();
                } else {
                    alert('❌ Błąd: ' + (response.data || 'Nieznany błąd'));
                    button.textContent = originalText;
                    button.disabled = false;
                }
            }).fail(function(xhr, status, error) {
                console.error('Publication generate AJAX Error:', xhr, status, error);
                alert('❌ Błąd połączenia: ' + error);
                button.textContent = originalText;
                button.disabled = false;
            });
        }
        </script>
        <?php
    }
    
    private function get_publication_status() {
        $upload_dir = wp_upload_dir();
        $ujc_dir = $upload_dir['basedir'] . '/ujc-data';
        
        $xml_file = $ujc_dir . '/katalog-danych.xml';
        $md5_file = $ujc_dir . '/katalog-danych.md5';
        
        $status = [
            'xml_exists' => file_exists($xml_file),
            'md5_exists' => file_exists($md5_file),
            'xml_valid' => false,
            'md5_valid' => false,
            'urls_accessible' => false,
            'last_modified' => null
        ];
        
        if ($status['xml_exists']) {
            $status['last_modified'] = filemtime($xml_file);
            
            // Sprawdź czy XML jest poprawny
            libxml_use_internal_errors(true);
            $xml_content = simplexml_load_file($xml_file);
            $status['xml_valid'] = ($xml_content !== false);
        }
        
        if ($status['md5_exists'] && $status['xml_exists']) {
            // Sprawdź czy MD5 jest poprawny
            $actual_md5 = md5_file($xml_file);
            $stored_md5 = trim(explode(' ', file_get_contents($md5_file))[0]);
            $status['md5_valid'] = (strtolower($actual_md5) === strtolower($stored_md5));
        }
        
        // Sprawdź dostępność URL (uproszczony test)
        if ($status['xml_exists'] && $status['md5_exists']) {
            $status['urls_accessible'] = true; // Można dodać faktyczne sprawdzenie HTTP
        }
        
        return $status;
    }
    
    private function render_publication_status($status) {
        echo '<div style="background: #f0f0f1; border-radius: 4px; padding: 20px;">';
        
        $overall_status = $status['xml_exists'] && $status['md5_exists'] && 
                         $status['xml_valid'] && $status['md5_valid'] && 
                         $status['urls_accessible'];
        
        if ($overall_status) {
            echo '<div style="color: #007600; font-size: 18px; margin-bottom: 15px;">';
            echo '<strong>✅ Dane poprawnie opublikowane</strong>';
            echo '</div>';
        } else {
            echo '<div style="color: #d63638; font-size: 18px; margin-bottom: 15px;">';
            echo '<strong>❌ Problemy z publikacją danych</strong>';
            echo '</div>';
        }
        
        echo '<div class="status-details">';
        echo '<p><strong>Plik XML:</strong> ' . ($status['xml_exists'] ? '✅ Istnieje' : '❌ Brak') . 
             ($status['xml_exists'] && !$status['xml_valid'] ? ' (niepoprawny format)' : '') . '</p>';
        
        echo '<p><strong>Plik MD5:</strong> ' . ($status['md5_exists'] ? '✅ Istnieje' : '❌ Brak') . 
             ($status['md5_exists'] && !$status['md5_valid'] ? ' (niepoprawny checksum)' : '') . '</p>';
        
        echo '<p><strong>Dostępność URL:</strong> ' . ($status['urls_accessible'] ? '✅ Dostępne' : '❌ Niedostępne') . '</p>';
        
        if ($status['last_modified']) {
            echo '<p><strong>Ostatnia aktualizacja:</strong> ' . DateHelper::formatTimestampForUser($status['last_modified']) . '</p>';
        }
        echo '</div>';
        
        echo '</div>';
    }
    
    private function render_public_urls() {
        $upload_dir = wp_upload_dir();
        $base_url = $upload_dir['baseurl'] . '/ujc-data';
        
        $xml_url = $base_url . '/katalog-danych.xml';
        $md5_url = $base_url . '/katalog-danych.md5';
        
        echo '<div style="border: 1px solid #ddd; border-radius: 4px; padding: 15px; background: #fafafa; min-width: 400px;">';
        echo '<h4 style="margin: 0 0 10px 0; font-size: 14px; color: #333;">Publiczne adresy plików:</h4>';
        echo '<div style="font-size: 13px; line-height: 1.6;">';
        echo '<div style="margin-bottom: 8px;"><strong>Plik XML:</strong><br>';
        echo '<a href="' . esc_url($xml_url) . '" target="_blank" style="color: #0073aa; text-decoration: none;">' . esc_html($xml_url) . '</a></div>';
        echo '<div><strong>Plik MD5:</strong><br>';
        echo '<a href="' . esc_url($md5_url) . '" target="_blank" style="color: #0073aa; text-decoration: none;">' . esc_html($md5_url) . '</a></div>';
        echo '</div>';
        echo '</div>';
    }
    
    private function render_manual_generation() {
        echo '<div style="background: #f0f0f1; border-radius: 4px; padding: 20px;">';
        echo '<p>Wygeneruj i opublikuj najnowsze pliki danych:</p>';
        echo '<button type="button" class="button button-primary" onclick="generateFiles()" id="generate-files-btn" style="margin-top: 10px;">';
        echo '🔄 Generuj i Publikuj Pliki';
        echo '</button>';
        echo '</div>';
    }
    
    private function render_publication_history() {
        // Pobierz historię generowania
        $settings_repo = new SettingsRepository();
        $history = $settings_repo->getGenerationHistory();
        
        if (empty($history)) {
            echo '<div style="padding: 20px; background: #f0f0f1; border-radius: 4px; text-align: center; color: #666;">';
            echo '<p>Brak historii publikacji.<br><small>Historia będzie widoczna po pierwszym generowaniu plików.</small></p>';
            echo '</div>';
            return;
        }
        
        // Sortuj od najnowszych
        usort($history, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        // Pokaż ostatnie 20 wpisów
        $history = array_slice($history, 0, 20);
        
        echo '<div style="background: #f0f0f1; border-radius: 4px; padding: 20px;">';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>Data</th><th>Typ</th><th>Status</th><th>Pliki</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($history as $entry) {
            $status_icon = $entry['status'] === 'success' ? '✅' : '❌';
            $type_label = isset($entry['type']) ? $entry['type'] : 'automatyczne';
            
            echo '<tr>';
            echo '<td>' . DateHelper::formatTimestampForUser($entry['timestamp']) . '</td>';
            echo '<td>' . esc_html($type_label) . '</td>';
            echo '<td>' . $status_icon . ' ';
            
            if ($entry['status'] === 'success') {
                echo '<span style="color: #007600;">Sukces</span>';
            } else {
                echo '<span style="color: #d63638;">Błąd</span>';
                if (!empty($entry['message'])) {
                    echo '<br><small>' . esc_html($entry['message']) . '</small>';
                }
            }
            echo '</td>';
            
            echo '<td>';
            if ($entry['status'] === 'success') {
                $upload_dir = wp_upload_dir();
                $base_url = $upload_dir['baseurl'] . '/ujc-data';
                
                echo '<a href="' . $base_url . '/katalog-danych.xml" class="button button-small" target="_blank">XML</a> ';
                echo '<a href="' . $base_url . '/katalog-danych.md5" class="button button-small" target="_blank">MD5</a>';
            } else {
                echo '-';
            }
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
    }
}