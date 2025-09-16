<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Strona zasobów
 */
class ResourcesPage {

    private $getAllResourcesUseCase;
    private $resourceModal;
    private $investmentModal;

    public function __construct(
        GetAllResourcesUseCase $getAllResourcesUseCase,
        ResourceModal $resourceModal,
        InvestmentModal $investmentModal
    ) {
        Logger::info('UJC: ResourcesPage constructor started with DI');

        $this->getAllResourcesUseCase = $getAllResourcesUseCase;
        $this->resourceModal = $resourceModal;
        $this->investmentModal = $investmentModal;

        // Dodaj AJAX handlers
        add_action('wp_ajax_ujc_import_resources', [$this, 'ajax_import_resources']);

        Logger::info('UJC: ResourcesPage initialized with DI successfully');
    }
    
    public function render() {
        $developerRepository = DIContainer::get(DeveloperRepository::class);
        $investmentRepository = DIContainer::get(InvestmentRepository::class);

        $developer = $developerRepository->read();
        $investment = $investmentRepository->read();
        
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
        
        // Jeśli brak inwestycji, pokaż przycisk dodawania inwestycji
        if (!$investment) {
            $this->render_no_investment_view();
            return;
        }
        
        // Jeśli inwestycja istnieje, pokaż formularz dodawania zasobów
        $this->render_resources_form($investment);
    }
    
    private function render_no_investment_view() {
        ?>
        <div class="wrap">
            <h1>Zasoby</h1>
            <div class="notice notice-info">
                <p><strong>Krok 1:</strong> Najpierw dodaj dane o inwestycji</p>
            </div>
            
            <div style="text-align: center; padding: 40px; background: #f9f9f9; border: 1px dashed #ccc; margin: 20px 0;">
                <h2>Brak danych o inwestycji</h2>
                <p style="margin-bottom: 20px;">Aby dodać zasoby, musisz najpierw utworzyć inwestycję.</p>
                <button type="button" class="button-primary" onclick="openInvestmentModal()" style="font-size: 16px; padding: 8px 20px;">
                    <span class="dashicons dashicons-plus-alt2" style="font-size: 16px; line-height: 1; margin-right: 5px;"></span>
                    Dodaj Inwestycję
                </button>
            </div>
            
            <!-- Renderuj modal inwestycji -->
            <?php $this->investmentModal->render_modal(); ?>
        </div>
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
                <button type="button" class="button" onclick="openInvestmentModal(<?php echo $investment->id; ?>)" style="display: flex; align-items: center; gap: 5px;">
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
        Logger::info("ResourcesPage::render_resources_list - Starting PHP rendering");
        
        try {
            Logger::info("ResourcesPage::render_resources_list - Calling getAllResourcesUseCase->execute()");
            $resources = $this->getAllResourcesUseCase->execute();
            Logger::info("ResourcesPage::render_resources_list - Got " . count($resources) . " resources from UseCase");
        } catch (Exception $e) {
            Logger::error("ResourcesPage::render_resources_list - Exception: " . $e->getMessage());
            ?>
            <div class="ujc-no-resources">
                <p>Błąd podczas pobierania zasobów: <?php echo esc_html($e->getMessage()); ?></p>
            </div>
            <?php
            return;
        }
        
        if (empty($resources)) {
            Logger::info("ResourcesPage::render_resources_list - No resources found, showing empty state");
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
        
        Logger::info("ResourcesPage::render_resources_list - Rendering " . count($resources) . " resource items");
        foreach ($resources as $resource) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Resource item output contains safe onclick attributes
            echo ResourceItem::render_item_html($resource);
        }
        Logger::info("ResourcesPage::render_resources_list - PHP rendering completed successfully");
    }
    
    
    public function ajax_import_resources() {
        check_ajax_referer('ujc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
        }

        try {
            $importResourcesUseCase = DIContainer::get(ImportResourcesUseCase::class);
            $result = $importResourcesUseCase->execute($_FILES['import_file']);

            wp_send_json_success($result);

        } catch (Exception $e) {
            Logger::error('UJC Import Error: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }
}