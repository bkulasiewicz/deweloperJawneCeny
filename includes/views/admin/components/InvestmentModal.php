<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Komponent modalu dla inwestycji - Single Responsibility Principle
 * Odpowiada tylko za zarządzanie modalem wyświetlania/edycji danych inwestycji
 */
class InvestmentModal extends UJC_Admin_Page {
    
    private $investmentRepository;
    
    public function __construct() {
        $this->investmentRepository = new InvestmentRepository();
        
        add_action('wp_ajax_ujc_get_investment', [$this, 'ajax_get_investment']);
        add_action('wp_ajax_ujc_update_investment', [$this, 'ajax_update_investment']);
        
        parent::__construct();
    }
    
    
    /**
     * Renderuje modal HTML
     */
    public function render_modal() {
        ?>
        <!-- Modal danych inwestycji -->
        <div id="ujc-investment-modal" class="ujc-modal" style="display: none;">
            <div class="ujc-modal-content">
                <div class="ujc-modal-header">
                    <h2 id="investment-modal-title">Dane Inwestycji</h2>
                    <span class="ujc-modal-close">&times;</span>
                </div>
                
                <!-- Widok readonly -->
                <div id="investment-view" class="ujc-modal-body">
                    <table class="form-table">
                        <tr>
                            <th>Nazwa Inwestycji:</th>
                            <td id="view-investment-name">-</td>
                        </tr>
                        <tr>
                            <th>Województwo:</th>
                            <td id="view-proj-wojewodztwo">-</td>
                        </tr>
                        <tr>
                            <th>Powiat:</th>
                            <td id="view-proj-powiat">-</td>
                        </tr>
                        <tr>
                            <th>Gmina:</th>
                            <td id="view-proj-gmina">-</td>
                        </tr>
                        <tr>
                            <th>Miejscowość:</th>
                            <td id="view-proj-miejscowosc">-</td>
                        </tr>
                        <tr>
                            <th>Ulica:</th>
                            <td id="view-proj-ulica">-</td>
                        </tr>
                        <tr>
                            <th>Nr nieruchomości:</th>
                            <td id="view-proj-nr">-</td>
                        </tr>
                        <tr>
                            <th>Kod pocztowy:</th>
                            <td id="view-proj-kod">-</td>
                        </tr>
                    </table>
                    
                    <h3 style="margin-top: 20px;">Konfiguracja komponentów</h3>
                    <p class="description">Elementy inwestycji uwzględnione w cenach zasobów:</p>
                    
                    <table class="form-table">
                        <tr>
                            <th>Części nieruchomości:</th>
                            <td id="view-has_property_parts">-</td>
                        </tr>
                        <tr>
                            <th>Pomieszczenia przynależne:</th>
                            <td id="view-has_belonging_rooms">-</td>
                        </tr>
                        <tr>
                            <th>Prawa niezbędne:</th>
                            <td id="view-has_usage_rights">-</td>
                        </tr>
                        <tr>
                            <th>Inne świadczenia:</th>
                            <td id="view-has_other_services">-</td>
                        </tr>
                    </table>
                </div>
                
                <!-- Formularz edycji (ukryty) -->
                <div id="investment-edit" class="ujc-modal-body" style="display: none;">
                    <form id="investment-edit-form" method="post">
                        <?php wp_nonce_field('ujc_admin_nonce', 'nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th><label for="edit-investment-name">Nazwa Inwestycji *</label></th>
                                <td><input type="text" id="edit-investment-name" name="name" required class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="edit-proj-wojewodztwo">Województwo *</label></th>
                                <td><input type="text" id="edit-proj-wojewodztwo" name="proj_wojewodztwo" required class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="edit-proj-powiat">Powiat</label></th>
                                <td><input type="text" id="edit-proj-powiat" name="proj_powiat" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="edit-proj-gmina">Gmina</label></th>
                                <td><input type="text" id="edit-proj-gmina" name="proj_gmina" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="edit-proj-miejscowosc">Miejscowość *</label></th>
                                <td><input type="text" id="edit-proj-miejscowosc" name="proj_miejscowosc" required class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="edit-proj-ulica">Ulica</label></th>
                                <td><input type="text" id="edit-proj-ulica" name="proj_ulica" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="edit-proj-nr">Nr nieruchomości</label></th>
                                <td><input type="text" id="edit-proj-nr" name="proj_nr" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="edit-proj-kod">Kod pocztowy</label></th>
                                <td><input type="text" id="edit-proj-kod" name="proj_kod" class="regular-text" pattern="[0-9]{2}-[0-9]{3}" placeholder="00-000"></td>
                            </tr>
                        </table>
                        
                        <h3 style="margin-top: 30px;">Konfiguracja komponentów</h3>
                        <p class="description">Wskaż jakie elementy zawiera inwestycja, których cena nie jest uwzględniona w cenie lokalu/domu:</p>
                        
                        <table class="form-table">
                            <tr>
                                <td colspan="2">
                                    <label style="display: block; margin-bottom: 10px;">
                                        <input type="checkbox" id="edit-has_property_parts" name="has_property_parts" value="1" style="margin-right: 8px;">
                                        <strong>części nieruchomości będące przedmiotem umowy</strong> (np. miejsce postojowe)
                                    </label>
                                    <label style="display: block; margin-bottom: 10px;">
                                        <input type="checkbox" id="edit-has_belonging_rooms" name="has_belonging_rooms" value="1" style="margin-right: 8px;">
                                        <strong>pomieszczenia przynależne</strong> (np. komórka lokatorska)
                                    </label>
                                    <label style="display: block; margin-bottom: 10px;">
                                        <input type="checkbox" id="edit-has_usage_rights" name="has_usage_rights" value="1" style="margin-right: 8px;">
                                        <strong>prawa niezbędne do korzystania z lokalu lub domu</strong>
                                    </label>
                                    <label style="display: block; margin-bottom: 10px;">
                                        <input type="checkbox" id="edit-has_other_services" name="has_other_services" value="1" style="margin-right: 8px;">
                                        <strong>inne rodzaje świadczeń pieniężnych na rzecz dewelopera</strong>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                
                <div class="ujc-modal-footer">
                    <div id="investment-view-buttons">
                        <button type="button" class="button ujc-modal-cancel">Zamknij</button>
                        <button type="button" class="button-primary" id="edit-investment-btn">Edytuj</button>
                    </div>
                    <div id="investment-edit-buttons" style="display: none;">
                        <button type="button" class="button" id="cancel-investment-edit">Anuluj</button>
                        <button type="button" class="button-primary" id="save-investment-btn">Zapisz</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- JavaScript dla modala inwestycji -->
        <script>
        jQuery(document).ready(function($) {
            let currentInvestmentData = null;
            
            // Otwórz modal inwestycji
            window.openInvestmentModal = function() {
                loadInvestmentData();
                $('#ujc-investment-modal').show();
            };
            
            // Załaduj dane inwestycji
            function loadInvestmentData() {
                const nonce = typeof ujc_ajax !== 'undefined' ? ujc_ajax.nonce : ($('#ujc-nonce').length ? $('#ujc-nonce').val() : '');
                
                console.log('Loading investment data, nonce:', nonce);
                
                $.post(typeof ujc_ajax !== 'undefined' ? ujc_ajax.ajax_url : ajaxurl, {
                    action: 'ujc_get_investment',
                    nonce: nonce
                }, function(response) {
                    console.log('Investment data response:', response);
                    if (response.success) {
                        currentInvestmentData = response.data;
                        populateViewData(response.data);
                    } else {
                        console.error('Error loading investment data:', response.data);
                        alert('❌ Błąd ładowania danych inwestycji: ' + (response.data || 'Nieznany błąd'));
                    }
                }).fail(function(xhr, status, error) {
                    console.error('AJAX error:', xhr, status, error);
                    alert('❌ Błąd połączenia: ' + error);
                });
            }
            
            // Wypełnij widok readonly
            function populateViewData(data) {
                $('#view-investment-name').text(data.name || '-');
                $('#view-proj-wojewodztwo').text(data.proj_wojewodztwo || '-');
                $('#view-proj-powiat').text(data.proj_powiat || '-');
                $('#view-proj-gmina').text(data.proj_gmina || '-');
                $('#view-proj-miejscowosc').text(data.proj_miejscowosc || '-');
                $('#view-proj-ulica').text(data.proj_ulica || '-');
                $('#view-proj-nr').text(data.proj_nr || '-');
                $('#view-proj-kod').text(data.proj_kod || '-');
                
                // Komponenty - show Tak/Nie based on boolean values
                $('#view-has_property_parts').text(data.has_property_parts == '1' ? 'Tak' : 'Nie');
                $('#view-has_belonging_rooms').text(data.has_belonging_rooms == '1' ? 'Tak' : 'Nie');
                $('#view-has_usage_rights').text(data.has_usage_rights == '1' ? 'Tak' : 'Nie');
                $('#view-has_other_services').text(data.has_other_services == '1' ? 'Tak' : 'Nie');
            }
            
            // Wypełnij formularz edycji
            function populateEditForm(data) {
                $('#edit-investment-name').val(data.name || '');
                $('#edit-proj-wojewodztwo').val(data.proj_wojewodztwo || '');
                $('#edit-proj-powiat').val(data.proj_powiat || '');
                $('#edit-proj-gmina').val(data.proj_gmina || '');
                $('#edit-proj-miejscowosc').val(data.proj_miejscowosc || '');
                $('#edit-proj-ulica').val(data.proj_ulica || '');
                $('#edit-proj-nr').val(data.proj_nr || '');
                $('#edit-proj-kod').val(data.proj_kod || '');
                
                // Komponenty - set checkbox states
                $('#edit-has_property_parts').prop('checked', data.has_property_parts == '1');
                $('#edit-has_belonging_rooms').prop('checked', data.has_belonging_rooms == '1');
                $('#edit-has_usage_rights').prop('checked', data.has_usage_rights == '1');
                $('#edit-has_other_services').prop('checked', data.has_other_services == '1');
            }
            
            // Przełącz na tryb edycji
            $(document).off('click', '#edit-investment-btn').on('click', '#edit-investment-btn', function() {
                console.log('Edit button clicked', currentInvestmentData);
                if (currentInvestmentData) {
                    populateEditForm(currentInvestmentData);
                    $('#investment-view').hide();
                    $('#investment-view-buttons').hide();
                    $('#investment-edit').show();
                    $('#investment-edit-buttons').show();
                    $('#investment-modal-title').text('Edytuj Dane Inwestycji');
                } else {
                    alert('Brak danych do edycji');
                }
            });
            
            // Anuluj edycję
            $(document).off('click', '#cancel-investment-edit').on('click', '#cancel-investment-edit', function() {
                console.log('Cancel edit clicked');
                $('#investment-edit').hide();
                $('#investment-edit-buttons').hide();
                $('#investment-view').show();
                $('#investment-view-buttons').show();
                $('#investment-modal-title').text('Dane Inwestycji');
                $('#investment-edit-form')[0].reset();
            });
            
            // Zapisz zmiany
            $(document).off('click', '#save-investment-btn').on('click', '#save-investment-btn', function() {
                console.log('Save investment button clicked');
                
                const nonce = typeof ujc_ajax !== 'undefined' ? ujc_ajax.nonce : ($('#ujc-nonce').length ? $('#ujc-nonce').val() : '');
                const formData = $('#investment-edit-form').serialize() + '&action=ujc_update_investment&nonce=' + nonce;
                
                console.log('Saving with data:', formData);
                
                const $btn = $(this);
                const originalText = $btn.text();
                $btn.text('Zapisywanie...').prop('disabled', true);
                
                $.post(typeof ujc_ajax !== 'undefined' ? ujc_ajax.ajax_url : ajaxurl, formData, function(response) {
                    console.log('Save response:', response);
                    if (response.success) {
                        alert('✅ ' + response.data);
                        loadInvestmentData(); // Przeładuj dane
                        $('#cancel-investment-edit').click(); // Wróć do widoku
                    } else {
                        console.error('Save error:', response.data);
                        alert('❌ ' + (response.data || 'Błąd podczas zapisywania'));
                    }
                }).fail(function(xhr, status, error) {
                    console.error('Save AJAX error:', xhr, status, error);
                    alert('❌ Błąd połączenia: ' + error);
                }).always(function() {
                    $btn.text(originalText).prop('disabled', false);
                });
            });
            
            // Zamknij modal
            function closeInvestmentModal() {
                $('#ujc-investment-modal').hide();
                $('#cancel-investment-edit').click(); // Reset do widoku
            }
            
            $(document).off('click', '.ujc-modal-close').on('click', '.ujc-modal-close', closeInvestmentModal);
            $(document).off('click', '.ujc-modal-cancel').on('click', '.ujc-modal-cancel', closeInvestmentModal);
            
            // Zamknij modal kliknięciem poza nim
            $(document).off('click', '#ujc-investment-modal').on('click', '#ujc-investment-modal', function(e) {
                if (e.target === this) {
                    closeInvestmentModal();
                }
            });
        });
        </script>
        <?php
    }
    
    public function ajax_get_investment() {
        Logger::info('UJC: ajax_get_investment started');
        
        if (!$this->verify_nonce()) {
            Logger::error('UJC: nonce verification failed');
            return;
        }
        
        if (!$this->check_permissions()) {
            Logger::error('UJC: permission check failed');
            return;
        }
        
        try {
            
            $investment = $this->investmentRepository->read();
            Logger::info('UJC: Investment data retrieved: ' . print_r($investment, true));
            
            if ($investment) {
                wp_send_json_success($investment);
            } else {
                Logger::error('UJC: No investment data found');
                wp_send_json_error('Brak danych inwestycji');
            }
        } catch (Exception $e) {
            Logger::error('UJC: Exception in ajax_get_investment: ' . $e->getMessage());
            wp_send_json_error('Błąd serwera: ' . $e->getMessage());
        }
    }
    
    public function ajax_update_investment() {
        Logger::info('UJC: ajax_update_investment started');
        
        if (!$this->verify_nonce()) {
            Logger::error('UJC: nonce verification failed in update');
            return;
        }
        
        if (!$this->check_permissions()) {
            Logger::error('UJC: permission check failed in update');
            return;
        }
        
        try {
            $data = $this->sanitize_post_data([
                'name' => 'sanitize_text_field',
                'proj_wojewodztwo' => 'sanitize_text_field',
                'proj_powiat' => 'sanitize_text_field',
                'proj_gmina' => 'sanitize_text_field',
                'proj_miejscowosc' => 'sanitize_text_field',
                'proj_ulica' => 'sanitize_text_field',
                'proj_nr' => 'sanitize_text_field',
                'proj_kod' => 'sanitize_text_field',
                'has_property_parts' => function($value) { return isset($value) ? 1 : 0; },
                'has_belonging_rooms' => function($value) { return isset($value) ? 1 : 0; },
                'has_usage_rights' => function($value) { return isset($value) ? 1 : 0; },
                'has_other_services' => function($value) { return isset($value) ? 1 : 0; }
            ]);
            
            Logger::info('UJC: Investment data to update: ' . print_r($data, true));
            
            
            $result = $this->investmentRepository->save($data);
            Logger::info('UJC: Update result: ' . ($result !== false ? 'success' : 'failed'));
            
            if ($result !== false) {
                wp_send_json_success('Dane inwestycji zostały zaktualizowane!');
            } else {
                wp_send_json_error('Błąd podczas aktualizacji danych inwestycji.');
            }
        } catch (Exception $e) {
            Logger::error('UJC: Exception in ajax_update_investment: ' . $e->getMessage());
            wp_send_json_error('Błąd serwera: ' . $e->getMessage());
        }
    }
    
    public function render() {
        // Nie używane - to jest komponent modalny
    }
}