<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Komponent modalu dla inwestycji - Single Responsibility Principle
 * Odpowiada tylko za zarządzanie modalem wyświetlania/edycji danych inwestycji
 */
class InvestmentModal extends UJC_Admin_Page {
    
    private $createInvestmentUseCase;
    private $updateInvestmentUseCase;
    private $getInvestmentUseCase;
    
    public function __construct() {
        $this->createInvestmentUseCase = new CreateInvestmentUseCase();
        $this->updateInvestmentUseCase = new UpdateInvestmentUseCase();
        $this->getInvestmentUseCase = new GetInvestmentUseCase();
        
        add_action('wp_ajax_ujc_get_investment', [$this, 'ajax_get_investment']);
        add_action('wp_ajax_ujc_update_investment', [$this, 'ajax_update_investment']);
        add_action('wp_ajax_ujc_log_debug', [$this, 'ajax_log_debug']);
        
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
                    <div class="modal-columns">
                        <!-- Lewa kolumna - Dane podstawowe -->
                        <div class="modal-column-left">
                            <h3 style="margin-top: 0;">Dane inwestycji</h3>
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
                        </div>
                        
                        <!-- Prawa kolumna - Konfiguracja -->
                        <div class="modal-column-right">
                            <h3 style="margin-top: 0;">Konfiguracja komponentów</h3>
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
                            
                            <h3 style="margin-top: 20px;">Pola marketingowe</h3>
                            <p class="description">Dodatkowe pola dostępne przy dodawaniu zasobów:</p>
                            
                            <table class="form-table">
                                <tr>
                                    <th>Piętro:</th>
                                    <td id="view-show_floor_field">-</td>
                                </tr>
                                <tr>
                                    <th>Liczba pokoi:</th>
                                    <td id="view-show_rooms_field">-</td>
                                </tr>
                                <tr>
                                    <th>Dodatkowy opis:</th>
                                    <td id="view-show_description_field">-</td>
                                </tr>
                                <tr>
                                    <th>Ogród:</th>
                                    <td id="view-show_garden_field">-</td>
                                </tr>
                                <tr>
                                    <th>Plan mieszkania:</th>
                                    <td id="view-show_floor_plan_field">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Formularz edycji (ukryty) -->
                <div id="investment-edit" class="ujc-modal-body" style="display: none;">
                    <form id="investment-edit-form" method="post">
                        <?php wp_nonce_field('ujc_admin_nonce', 'nonce'); ?>
                        
                        <div class="modal-columns">
                            <!-- Lewa kolumna - Formularz podstawowy -->
                            <div class="modal-column-left">
                                <h3 style="margin-top: 0;">Dane inwestycji</h3>
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
                            </div>
                            
                            <!-- Prawa kolumna - Konfiguracja komponentów -->
                            <div class="modal-column-right">
                                <h3 style="margin-top: 0;">Konfiguracja komponentów</h3>
                                <p class="description">Wskaż jakie elementy zawiera inwestycja, których cena nie jest uwzględniona w cenie lokalu/domu:</p>
                                
                                <table class="form-table">
                                    <tr>
                                        <td>
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
                                
                                <h4 style="margin-top: 20px; margin-bottom: 10px;">Pola marketingowe (nieobowiązkowe)</h4>
                                <p class="description" style="margin-bottom: 15px;">Wybierz które dodatkowe pola będą dostępne przy dodawaniu zasobów (nie są raportowane do ministerstwa):</p>
                                
                                <table class="form-table">
                                    <tr>
                                        <td>
                                            <label style="display: block; margin-bottom: 8px;">
                                                <input type="checkbox" id="edit-show_floor_field" name="show_floor_field" value="1" style="margin-right: 8px;">
                                                <strong>Piętro</strong> - numer piętra nieruchomości
                                            </label>
                                            <label style="display: block; margin-bottom: 8px;">
                                                <input type="checkbox" id="edit-show_rooms_field" name="show_rooms_field" value="1" style="margin-right: 8px;">
                                                <strong>Liczba pokoi</strong> - ilość pokoi w mieszkaniu/domu
                                            </label>
                                            <label style="display: block; margin-bottom: 8px;">
                                                <input type="checkbox" id="edit-show_description_field" name="show_description_field" value="1" style="margin-right: 8px;">
                                                <strong>Dodatkowy opis</strong> - pole tekstowe na informacje marketingowe
                                            </label>
                                            <label style="display: block; margin-bottom: 8px;">
                                                <input type="checkbox" id="edit-show_garden_field" name="show_garden_field" value="1" style="margin-right: 8px;">
                                                <strong>Ogród</strong> - powierzchnia przynależnego ogrodu [m²]
                                            </label>
                                            <label style="display: block; margin-bottom: 8px;">
                                                <input type="checkbox" id="edit-show_floor_plan_field" name="show_floor_plan_field" value="1" style="margin-right: 8px;">
                                                <strong>Plan mieszkania</strong> - upload pliku PDF z planem
                                            </label>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="ujc-modal-footer">
                    <div id="investment-view-buttons">
                        <button type="button" class="button ujc-modal-cancel">Zamknij</button>
                        <button type="button" class="button-primary" id="edit-investment-btn">Edytuj</button>
                    </div>
                    <div id="investment-edit-buttons">
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
            window.openInvestmentModal = function(investmentId = null) {
                if (investmentId === null) {
                    showCreateMode();
                } else {
                    loadInvestmentData(investmentId);
                }
                $('#ujc-investment-modal').show();
            };
            
            // Pokaż tryb dodawania nowej inwestycji
            function showCreateMode() {
                currentInvestmentData = null;
                $('#investment-edit-form')[0].reset();
                $('#investment-view').hide();
                $('#investment-edit').show();
                $('#investment-modal-title').text('Dodaj Nową Inwestycję');
                $('#save-investment-btn').text('Dodaj Inwestycję');
                setEditMode();
            }
            
            // Załaduj dane inwestycji
            function loadInvestmentData(investmentId = null) {
                const nonce = typeof ujc_ajax !== 'undefined' ? ujc_ajax.nonce : ($('#ujc-nonce').length ? $('#ujc-nonce').val() : '');
                
                console.log('Loading investment data, investmentId:', investmentId, 'nonce:', nonce);
                
                const requestData = {
                    action: 'ujc_get_investment',
                    nonce: nonce
                };
                
                if (investmentId !== null) {
                    requestData.investment_id = investmentId;
                }
                
                $.post(typeof ujc_ajax !== 'undefined' ? ujc_ajax.ajax_url : ajaxurl, requestData, function(response) {
                    if (response.success) {
                        currentInvestmentData = response.data;
                        populateViewData(response.data);
                        populateEditForm(response.data);
                        // Show in VIEW mode with unified form structure
                        $('#investment-edit').show();
                        $('#investment-view').hide();
                        $('#investment-modal-title').text('Dane Inwestycji');
                        $('#save-investment-btn').text('Zapisz zmiany');
                        setViewMode();
                    } else {
                        alert('❌ Błąd ładowania danych inwestycji: ' + (response.data || 'Nieznany błąd'));
                    }
                }).fail(function(xhr, status, error) {
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
                
                // Pola marketingowe - show Tak/Nie based on boolean values
                $('#view-show_floor_field').text(data.show_floor_field == '1' ? 'Tak' : 'Nie');
                $('#view-show_rooms_field').text(data.show_rooms_field == '1' ? 'Tak' : 'Nie');
                $('#view-show_description_field').text(data.show_description_field == '1' ? 'Tak' : 'Nie');
                $('#view-show_garden_field').text(data.show_garden_field == '1' ? 'Tak' : 'Nie');
                $('#view-show_floor_plan_field').text(data.show_floor_plan_field == '1' ? 'Tak' : 'Nie');
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
                
                // Pola marketingowe - set checkbox states
                $('#edit-show_floor_field').prop('checked', data.show_floor_field == '1');
                $('#edit-show_rooms_field').prop('checked', data.show_rooms_field == '1');
                $('#edit-show_description_field').prop('checked', data.show_description_field == '1');
                $('#edit-show_garden_field').prop('checked', data.show_garden_field == '1');
                $('#edit-show_floor_plan_field').prop('checked', data.show_floor_plan_field == '1');
            }
            
            // Przełącz na tryb edycji
            $(document).off('click', '#edit-investment-btn').on('click', '#edit-investment-btn', function() {
                if (currentInvestmentData) {
                    $('#investment-modal-title').text('Edytuj Dane Inwestycji');
                    setEditMode();
                } else {
                    alert('Brak danych do edycji');
                }
            });
            
            // Anuluj edycję - wróć do trybu VIEW
            $(document).off('click', '#cancel-investment-edit').on('click', '#cancel-investment-edit', function() {
                if (currentInvestmentData) {
                    // Przywróć dane i wróć do trybu VIEW
                    populateEditForm(currentInvestmentData);
                    $('#investment-modal-title').text('Dane Inwestycji');
                    setViewMode();
                } else {
                    // Tryb CREATE - zamknij modal
                    closeInvestmentModal();
                }
            });
            
            // Zapisz zmiany
            $(document).off('click', '#save-investment-btn').on('click', '#save-investment-btn', function() {
                const nonce = typeof ujc_ajax !== 'undefined' ? ujc_ajax.nonce : ($('#ujc-nonce').length ? $('#ujc-nonce').val() : '');
                const formData = $('#investment-edit-form').serialize() + '&action=ujc_update_investment&nonce=' + nonce;
                
                const $btn = $(this);
                const originalText = $btn.text();
                $btn.text('Zapisywanie...').prop('disabled', true);
                
                $.post(typeof ujc_ajax !== 'undefined' ? ujc_ajax.ajax_url : ajaxurl, formData, function(response) {
                    if (response.success) {
                        alert('✅ ' + response.data);
                        if (currentInvestmentData === null) {
                            // CREATE mode - po zapisie zamknij modal i odśwież stronę
                            $('#ujc-investment-modal').hide();
                            location.reload();
                        } else {
                            // EDIT mode - odśwież dane i wróć do trybu VIEW
                            loadInvestmentData();
                        }
                    } else {
                        alert('❌ ' + (response.data || 'Błąd podczas zapisywania'));
                    }
                }).fail(function(xhr, status, error) {
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
            
            // Functions for setting VIEW and EDIT modes
            window.setViewMode = function() {
                console.log('Setting VIEW mode');
                const modal = $('#ujc-investment-modal');
                modal.removeClass('investment-edit-mode').addClass('investment-view-mode');
                
                // Disable all form elements
                modal.find('input, textarea, select').prop('disabled', true);
                
                // Show VIEW mode buttons: Close, Edit
                $('#investment-view-buttons').css('display', 'block');
                $('#investment-edit-buttons').css('display', 'none');
                $('.ujc-modal-close').show();
            };
            
            window.setEditMode = function() {
                console.log('Setting EDIT mode');
                const modal = $('#ujc-investment-modal');
                modal.removeClass('investment-view-mode').addClass('investment-edit-mode');
                
                // Enable all form elements
                modal.find('input, textarea, select').prop('disabled', false);
                
                // Show EDIT mode buttons: Save, Cancel
                $('#investment-view-buttons').css('display', 'none');
                $('#investment-edit-buttons').css('display', 'block');
                $('.ujc-modal-close').show();
            };
        });
        </script>
        
        <!-- Stylowanie modala -->
        <style>
        .ujc-modal {
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .ujc-modal-content {
            background-color: #fefefe;
            margin: 3% auto;
            padding: 0;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            width: 95%;
            max-width: 1200px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .ujc-modal-header {
            padding: 20px;
            border-bottom: 1px solid #ccd0d4;
            background: #f9f9f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .ujc-modal-header h2 {
            margin: 0;
            color: #23282d;
        }
        
        .ujc-modal-close {
            color: #666;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        
        .ujc-modal-close:hover,
        .ujc-modal-close:focus {
            color: #d63638;
        }
        
        .ujc-modal-body {
            padding: 20px;
        }
        
        .ujc-modal-footer {
            padding: 20px;
            border-top: 1px solid #ccd0d4;
            background: #f9f9f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .ujc-modal-footer-left {
            display: flex;
            align-items: center;
        }
        
        .ujc-modal-footer-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* 2-column layout */
        .modal-columns {
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }
        
        .modal-column-left,
        .modal-column-right {
            flex: 1;
        }
        
        .modal-column-left {
            border-right: 1px solid #e1e1e1;
            padding-right: 25px;
        }
        
        /* VIEW mode styles */
        .investment-view-mode input,
        .investment-view-mode textarea,
        .investment-view-mode select {
            background-color: #f9f9f9;
            color: #666;
            cursor: not-allowed;
        }
        
        .investment-view-mode input[type="checkbox"] {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* EDIT mode styles (default) */
        .investment-edit-mode input,
        .investment-edit-mode textarea,
        .investment-edit-mode select {
            background-color: #fff;
            color: #333;
            cursor: auto;
        }
        
        .investment-edit-mode input[type="checkbox"] {
            opacity: 1;
            cursor: pointer;
        }
        
        /* Initial button visibility */
        #investment-edit-buttons {
            display: none;
        }
        
        #investment-view-buttons {
            display: block;
        }
        </style>
        <?php
    }
    
    public function ajax_get_investment() {
        if (!$this->verify_nonce()) {
            return;
        }
        
        if (!$this->check_permissions()) {
            return;
        }
        
        try {
            $investmentId = isset($_POST['investment_id']) ? intval($_POST['investment_id']) : null;
            
            if ($investmentId !== null) {
                // Pobierz konkretną inwestycję po ID (przyszłość - gdy będzie więcej inwestycji)
                $investment = $this->getInvestmentUseCase->execute($investmentId);
            } else {
                // Pobierz domyślną inwestycję (obecne zachowanie)
                $investment = $this->getInvestmentUseCase->execute();
            }
            
            if ($investment) {
                wp_send_json_success($investment);
            } else {
                wp_send_json_error('Brak danych inwestycji');
            }
        } catch (Exception $e) {
            wp_send_json_error('Błąd serwera: ' . $e->getMessage());
        }
    }
    
    public function ajax_update_investment() {
        if (!$this->verify_nonce()) {
            wp_send_json_error('Weryfikacja bezpieczeństwa nie powiodła się.');
            return;
        }
        
        if (!$this->check_permissions()) {
            wp_send_json_error('Brak uprawnień do wykonania tej operacji.');
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
                'has_property_parts' => [$this, 'sanitize_checkbox'],
                'has_belonging_rooms' => [$this, 'sanitize_checkbox'],
                'has_usage_rights' => [$this, 'sanitize_checkbox'],
                'has_other_services' => [$this, 'sanitize_checkbox'],
                'show_floor_field' => [$this, 'sanitize_checkbox'],
                'show_rooms_field' => [$this, 'sanitize_checkbox'],
                'show_description_field' => [$this, 'sanitize_checkbox'],
                'show_garden_field' => [$this, 'sanitize_checkbox'],
                'show_floor_plan_field' => [$this, 'sanitize_checkbox']
            ]);
            
            $existingInvestment = $this->getInvestmentUseCase->execute();
            
            if ($existingInvestment) {
                $investmentDto = new InvestmentDto(
                    $existingInvestment->id,
                    $existingInvestment->developer_id, // Preserve existing developer_id
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
                    (bool)$data['has_other_services'],
                    (bool)$data['show_floor_field'],
                    (bool)$data['show_rooms_field'],
                    (bool)$data['show_description_field'],
                    (bool)$data['show_garden_field'],
                    (bool)$data['show_floor_plan_field']
                );
                
                $result = $this->updateInvestmentUseCase->execute($investmentDto, $existingInvestment->id);
            } else {
                $investmentDto = new InvestmentDto(
                    0,
                    1, // Default developer_id for compatibility
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
                    (bool)$data['has_other_services'],
                    (bool)$data['show_floor_field'],
                    (bool)$data['show_rooms_field'],
                    (bool)$data['show_description_field'],
                    (bool)$data['show_garden_field'],
                    (bool)$data['show_floor_plan_field']
                );
                
                $result = $this->createInvestmentUseCase->execute($investmentDto);
            }
            
            if ($result->isSuccess) {
                wp_send_json_success($result->message);
            } else {
                wp_send_json_error($result->message);
            }
        } catch (Exception $e) {
            wp_send_json_error('Błąd podczas przetwarzania danych: ' . $e->getMessage());
        }
    }
    
    public function sanitize_checkbox($value) {
        return isset($value) && $value ? true : false;
    }
    
    public function ajax_log_debug() {
        if (!$this->verify_nonce()) {
            wp_send_json_error('Nonce failed');
            return;
        }
        
        $message = sanitize_text_field($_POST['message'] ?? '');
        Logger::info('UJC DEBUG FROM JS: ' . $message);
        wp_send_json_success('Logged');
    }
    
    public function render() {
        // Nie używane - to jest komponent modalny
    }
}