<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Strona zasobów
 */
class ResourcesPage {
    
    private $developerRepository;
    private $investmentRepository;
    private $resourceRepository;
    private $importResourcesUseCase;
    private $getAllResourcesUseCase;
    private $historyModal;
    private $resourceModal;
    private $investmentModal;
    
    public function __construct() {
        Logger::info('UJC: ResourcesPage constructor started');
        // Wszystkie instancje w konstruktorze
        $this->developerRepository = new DeveloperRepository();
        $this->investmentRepository = new InvestmentRepository();
        $this->resourceRepository = new ResourceRepository();
        $this->importResourcesUseCase = new ImportResourcesUseCase();
        $this->getAllResourcesUseCase = new GetAllResourcesUseCase();
        $this->historyModal = new HistoryModal();
        $this->resourceModal = new ResourceModal();
        Logger::info('UJC: ResourcesPage about to create InvestmentModal');
        $this->investmentModal = new InvestmentModal();
        Logger::info('UJC: ResourcesPage InvestmentModal created successfully');
        
        // Dodaj AJAX handlers dla zasobów
        add_action('wp_ajax_ujc_get_resources', [$this, 'ajax_get_resources']);
        add_action('wp_ajax_ujc_save_investment', [$this, 'ajax_save_investment']);
        add_action('wp_ajax_ujc_import_resources', [$this, 'ajax_import_resources']);
    }
    
    public function render() {
        $developer = $this->developerRepository->read();
        $investment = $this->investmentRepository->read();
        
        // Sprawdź czy dane dostawcy są wypełnione
        if (!$developer) {
            ?>
            <div class="wrap">
                <h1>Zasoby</h1>
                <div class="notice notice-warning">
                    <p>Najpierw musisz <a href="<?php echo esc_url(admin_url('admin.php?page=ujc-developer')); ?>">uzupełnić dane dostawcy</a>.</p>
                </div>
            </div>
            <?php
            return;
        }
        
        // Jeśli brak inwestycji, pokaż formularz dodawania inwestycji
        if (!$investment) {
            $this->render_investment_form();
            return;
        }
        
        // Jeśli inwestycja istnieje, pokaż formularz dodawania zasobów
        $this->render_resources_form($investment);
    }
    
    private function render_investment_form() {
        ?>
        <div class="wrap">
            <h1>Zasoby</h1>
            <div class="notice notice-info">
                <p><strong>Krok 1:</strong> Najpierw dodaj dane o inwestycji</p>
            </div>
            
            <h2>Dodaj Inwestycję</h2>
            <form id="investment-form" method="post">
                <?php wp_nonce_field('ujc_investment_nonce', 'nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="investment_name">Nazwa Inwestycji *</label></th>
                        <td><input type="text" id="investment_name" name="investment_name" required class="regular-text" placeholder="np. Osiedle Słoneczne"></td>
                    </tr>
                    <tr>
                        <th><label for="proj_wojewodztwo">Województwo *</label></th>
                        <td><input type="text" id="proj_wojewodztwo" name="proj_wojewodztwo" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="proj_powiat">Powiat</label></th>
                        <td><input type="text" id="proj_powiat" name="proj_powiat" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="proj_gmina">Gmina</label></th>
                        <td><input type="text" id="proj_gmina" name="proj_gmina" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="proj_miejscowosc">Miejscowość *</label></th>
                        <td><input type="text" id="proj_miejscowosc" name="proj_miejscowosc" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="proj_ulica">Ulica</label></th>
                        <td><input type="text" id="proj_ulica" name="proj_ulica" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="proj_nr">Nr nieruchomości</label></th>
                        <td><input type="text" id="proj_nr" name="proj_nr" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="proj_kod">Kod pocztowy</label></th>
                        <td><input type="text" id="proj_kod" name="proj_kod" class="regular-text" pattern="[0-9]{2}-[0-9]{3}" placeholder="00-000"></td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="Dodaj Inwestycję">
                </p>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#investment-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = $(this).serialize();
                formData += '&action=ujc_save_investment&nonce=' + ujc_ajax.nonce;
                
                var $submit = $(this).find('input[type="submit"]');
                var originalText = $submit.val();
                $submit.val('Dodawanie...').prop('disabled', true);
                
                $.post(ujc_ajax.ajax_url, formData, function(response) {
                    if (response.success) {
                        alert('✅ Inwestycja została dodana!');
                        location.reload();
                    } else {
                        alert('❌ ' + (response.data || 'Błąd podczas dodawania'));
                    }
                }).fail(function() {
                    alert('❌ Błąd połączenia');
                }).always(function() {
                    $submit.val(originalText).prop('disabled', false);
                });
            });
        });
        </script>
        <?php
    }
    
    private function render_resources_form($investment) {
        $resourceModal = $this->resourceModal;
        $investmentModal = $this->investmentModal;
        
        ?>
        <div class="wrap">
            <h1>Zasoby</h1>
            
            <div class="notice notice-success">
                <p><strong>Krok 2:</strong> Teraz możesz dodać zasoby do zgłaszania</p>
            </div>
            
            <!-- Przycisk informacji o inwestycji -->
            <div style="margin: 20px 0;">
                <button type="button" class="button" onclick="openInvestmentModal()" style="display: flex; align-items: center; gap: 5px;">
                    <span class="dashicons dashicons-info" style="font-size: 16px; line-height: 1;"></span>
                    <span>Dane Inwestycji</span>
                </button>
            </div>
            
            <!-- Nagłówek z przyciskami po prawej stronie -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin: 20px 0;">
                <h2 style="margin: 0;">Lista Zasobów</h2>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="button-primary" onclick="openResourceModal('add')" style="display: flex; align-items: center; gap: 5px;">
                        <span class="dashicons dashicons-plus-alt2" style="font-size: 16px; line-height: 1;"></span>
                        <span>Dodaj Zasób</span>
                    </button>
                </div>
            </div>
            
            <!-- Lista zasobów z ulepszonym layoutem -->
            <div id="resources-list" class="ujc-resources-grid">
                <?php $this->render_resources_list(); ?>
            </div>
            
            <!-- Hidden file input for import -->
            <input type="file" id="ujc-import-file" accept=".csv" style="display: none;">
            
            <!-- Renderuj modale -->
            <?php 
            $resourceModal->render_modal(); 
            $investmentModal->render_modal();
            ResourceItem::render_item_styles();
            ResourceItem::render_item_script();
            HistoryModal::render_history_script();
            ?>
            
            
            <script>
            jQuery(document).ready(function($) {
                // Lista renderowana w PHP - odświeżenie strony po zmianach
                window.loadResourcesList = function() {
                    location.reload();
                };
                
                // Import functionality
                window.openImportDialog = function() {
                    $('#ujc-import-file').click();
                };
                
                $('#ujc-import-file').on('change', function(e) {
                    const file = e.target.files[0];
                    if (!file) return;
                    
                    // Sprawdź format pliku - tylko CSV
                    if (!file.type.includes('csv') && !file.name.toLowerCase().endsWith('.csv')) {
                        alert('❌ Obsługiwany tylko format CSV');
                        return;
                    }
                    
                    // Pokaż progress
                    const originalHtml = $('#resources-list').html();
                    $('#resources-list').html('<div class="ujc-resource-item">⏳ Importowanie pliku: ' + file.name + '...</div>');
                    
                    // Przygotuj FormData
                    const formData = new FormData();
                    formData.append('action', 'ujc_import_resources');
                    formData.append('nonce', '<?php echo esc_attr(wp_create_nonce('ujc_admin_nonce')); ?>');
                    formData.append('import_file', file);
                    
                    // Wyślij plik
                    $.ajax({
                        url: typeof ujc_ajax !== 'undefined' ? ujc_ajax.ajax_url : ajaxurl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        timeout: 60000, // 60 sekund
                        success: function(response) {
                            if (response.success) {
                                alert('✅ Importowano ' + (response.data.imported || 0) + ' zasobów!');
                                location.reload();
                            } else {
                                alert('❌ Błąd importu: ' + (response.data || 'Nieznany błąd'));
                                $('#resources-list').html(originalHtml);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Import error:', error);
                            alert('❌ Błąd połączenia podczas importu');
                            $('#resources-list').html(originalHtml);
                        },
                        complete: function() {
                            // Wyczyść input
                            $('#ujc-import-file').val('');
                        }
                    });
                });
            });
            </script>
        </div>
        <?php
    }
    
    /**
     * Renderuje listę zasobów bezpośrednio w PHP
     */
    private function render_resources_list() {
        try {
            $resources = $this->getAllResourcesUseCase->execute();
        } catch (Exception $e) {
            ?>
            <div class="ujc-no-resources">
                <p>Błąd podczas pobierania zasobów: <?php echo esc_html($e->getMessage()); ?></p>
            </div>
            <?php
            return;
        }
        
        if (empty($resources)) {
            ?>
            <div class="ujc-no-resources">
                <p>Brak zasobów. Kliknij "Dodaj Zasób" aby rozpocząć.</p>
                <p style="margin-top: 15px; font-size: 14px; color: #666;">
                    Możesz również zaimportować zasoby z pliku CSV. Import pozwoli Ci szybko dodać wiele nieruchomości jednocześnie.
                </p>
                <button type="button" class="button button-secondary" onclick="openImportDialog()" style="margin-top: 10px; display: none; align-items: center; gap: 5px;">
                    <span class="dashicons dashicons-upload" style="font-size: 16px; line-height: 1;"></span>
                    <span>Importuj z CSV</span>
                </button>
            </div>
            <?php
            return;
        }
        
        foreach ($resources as $resource) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Resource item output contains safe onclick attributes
            echo ResourceItem::render_item_html($resource);
        }
    }
    
    /**
     * AJAX: Pobiera listę wszystkich zasobów
     */
    public function ajax_get_resources() {
        Logger::info("ResourcesPage::ajax_get_resources - AJAX REQUEST RECEIVED");
        
        check_ajax_referer('ujc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            Logger::error("ResourcesPage::ajax_get_resources - Access denied");
            wp_send_json_error('Brak uprawnień');
        }
        
        try {
            Logger::info("ResourcesPage::ajax_get_resources - Starting");
            
            $resources = $this->getAllResourcesUseCase->execute();
            
            Logger::info("ResourcesPage::ajax_get_resources - Got " . count($resources) . " resources, sending JSON response");
            
            wp_send_json_success($resources);
        } catch (Exception $e) {
            Logger::error("ResourcesPage::ajax_get_resources - Exception: " . $e->getMessage());
            wp_send_json_error('Błąd podczas pobierania zasobów: ' . $e->getMessage());
        }
    }
    
    public function ajax_save_investment() {
        Logger::info('UJC: ajax_save_investment started. POST data: ' . print_r($_POST, true));
        
        check_ajax_referer('ujc_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Brak uprawnień');
        
        try {
            // Convert POST data to InvestmentDto format
            $data = [
                'name' => sanitize_text_field($_POST['investment_name'] ?? ''),
                'proj_wojewodztwo' => sanitize_text_field($_POST['proj_wojewodztwo'] ?? ''),
                'proj_powiat' => sanitize_text_field($_POST['proj_powiat'] ?? ''),
                'proj_gmina' => sanitize_text_field($_POST['proj_gmina'] ?? ''),
                'proj_miejscowosc' => sanitize_text_field($_POST['proj_miejscowosc'] ?? ''),
                'proj_ulica' => sanitize_text_field($_POST['proj_ulica'] ?? ''),
                'proj_nr' => sanitize_text_field($_POST['proj_nr'] ?? ''),
                'proj_kod' => sanitize_text_field($_POST['proj_kod'] ?? ''),
                'has_property_parts' => isset($_POST['has_property_parts']) ? 1 : 0,
                'has_belonging_rooms' => isset($_POST['has_belonging_rooms']) ? 1 : 0,
                'has_usage_rights' => isset($_POST['has_usage_rights']) ? 1 : 0,
                'has_other_services' => isset($_POST['has_other_services']) ? 1 : 0,
            ];
            
            Logger::info('UJC: Sanitized data: ' . print_r($data, true));
            
            // Create InvestmentDto
            $investmentDto = new InvestmentDto(
                0, // ID will be generated by database
                $data['name'],
                $data['proj_wojewodztwo'],
                $data['proj_powiat'],
                $data['proj_gmina'],
                $data['proj_miejscowosc'],
                $data['proj_ulica'],
                $data['proj_nr'],
                $data['proj_kod'],
                (bool)$data['has_property_parts'],
                (bool)$data['has_belonging_rooms'],
                (bool)$data['has_usage_rights'],
                (bool)$data['has_other_services']
            );
            
            // Use CreateInvestmentUseCase
            $createUseCase = new CreateInvestmentUseCase();
            $result = $createUseCase->execute($investmentDto);
            
            Logger::info('UJC: CreateInvestmentUseCase result: ' . ($result->isSuccess ? 'SUCCESS' : 'FAILURE') . ' - ' . $result->message);
            
            if ($result->isSuccess) {
                wp_send_json_success($result->message);
            } else {
                wp_send_json_error($result->message);
            }
            
        } catch (Exception $e) {
            Logger::error('UJC: Exception in ajax_save_investment: ' . $e->getMessage());
            wp_send_json_error('Błąd podczas zapisywania inwestycji: ' . $e->getMessage());
        }
    }
    
    public function ajax_import_resources() {
        check_ajax_referer('ujc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
        }
        
        try {
            $result = $this->importResourcesUseCase->execute($_FILES['import_file']);
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            Logger::error('UJC Import Error: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }
}