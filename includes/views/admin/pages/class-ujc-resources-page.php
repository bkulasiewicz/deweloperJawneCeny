<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Strona zasobów
 */
class UJC_Resources_Page {
    
    public function __construct() {
        // Dodaj AJAX handlers dla zasobów
        add_action('wp_ajax_ujc_get_resources', [$this, 'ajax_get_resources']);
        add_action('wp_ajax_ujc_save_investment', [$this, 'ajax_save_investment']);
        add_action('wp_ajax_ujc_import_resources', [$this, 'ajax_import_resources']);
    }
    
    public function render() {
        $developer_repo = new DeveloperRepository();
        $investment_repo = new InvestmentRepository();
        $developer = $developer_repo->read();
        $investment = $investment_repo->read();
        
        // Sprawdź czy dane dostawcy są wypełnione
        if (!$developer) {
            ?>
            <div class="wrap">
                <h1>Zasoby</h1>
                <div class="notice notice-warning">
                    <p>Najpierw musisz <a href="<?php echo admin_url('admin.php?page=ujc-developer'); ?>">uzupełnić dane dostawcy</a>.</p>
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
                <?php wp_nonce_field('ujc_investment_nonce', 'ujc_nonce'); ?>
                
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
        // Załaduj komponenty modalowe i itemów
        require_once PLUGIN_DIR . 'includes/views/admin/components/class-ujc-resource-modal.php';
        require_once PLUGIN_DIR . 'includes/views/admin/components/class-ujc-investment-modal.php';
        require_once PLUGIN_DIR . 'includes/views/admin/components/class-ujc-resource-item.php';
        $resourceModal = new UJC_Resource_Modal();
        $investmentModal = new UJC_Investment_Modal();
        
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
            UJC_Resource_Item::render_item_styles();
            UJC_Resource_Item::render_item_script();
            UJC_History_Modal::render_history_script();
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
                    formData.append('ujc_nonce', '<?php echo wp_create_nonce('ujc_admin_nonce'); ?>');
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
        $resource_repo = new ResourceRepository();
        $resources = $resource_repo->readAll();
        
        if (empty($resources)) {
            ?>
            <div class="ujc-no-resources">
                <p>Brak zasobów. Kliknij "Dodaj Zasób" aby rozpocząć.</p>
                <p style="margin-top: 15px; font-size: 14px; color: #666;">
                    Możesz również zaimportować zasoby z pliku CSV. Import pozwoli Ci szybko dodać wiele nieruchomości jednocześnie.
                </p>
                <button type="button" class="button button-secondary" onclick="openImportDialog()" style="margin-top: 10px; display: flex; align-items: center; gap: 5px;">
                    <span class="dashicons dashicons-upload" style="font-size: 16px; line-height: 1;"></span>
                    <span>Importuj z CSV</span>
                </button>
            </div>
            <?php
            return;
        }
        
        foreach ($resources as $resource) {
            echo UJC_Resource_Item::render_item_html($resource);
        }
    }
    
    /**
     * AJAX: Pobiera listę wszystkich zasobów
     */
    public function ajax_get_resources() {
        check_ajax_referer('ujc_admin_nonce', 'ujc_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
        }
        
        $resource_repo = new ResourceRepository();
        $resources = $resource_repo->readAll();
        
        // Przekaż surowe dane - formatowanie w JavaScript
        $formatted_resources = [];
        foreach ($resources as $resource) {
            $formatted_resources[] = [
                'id' => $resource['id'],
                'rodzaj_nieruchomosci' => $resource['rodzaj_nieruchomosci'],
                'nr_lokalu' => $resource['nr_lokalu'],
                'powierzchnia_uzytkowa' => $resource['powierzchnia_uzytkowa'],
                'cena_m2' => $resource['cena_m2'],
                'cena_calkowita' => $resource['cena_calkowita'],
                'cena_z_dodatkami' => $resource['cena_z_dodatkami'],
                'status' => $resource['status'],
                'data_cena_m2' => $resource['data_cena_m2'],
                'data_cena_calkowita' => $resource['data_cena_calkowita'],
                'data_cena_z_dodatkami' => $resource['data_cena_z_dodatkami'],
                'created_at' => $resource['created_at'],
                'updated_at' => $resource['updated_at']
            ];
        }
        
        wp_send_json_success($formatted_resources);
    }
    
    public function ajax_save_investment() {
        check_ajax_referer('ujc_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Brak uprawnień');
        
        require_once PLUGIN_DIR . 'includes/UseCases/SaveInvestmentInfoUseCase.php';
        
        $result = SaveInvestmentInfoUseCase::execute($_POST);
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    public function ajax_import_resources() {
        check_ajax_referer('ujc_admin_nonce', 'ujc_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
        }
        
        try {
            $importUseCase = new ImportResourcesUseCase();
            $result = $importUseCase->execute($_FILES['import_file']);
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            error_log('UJC Import Error: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }
}