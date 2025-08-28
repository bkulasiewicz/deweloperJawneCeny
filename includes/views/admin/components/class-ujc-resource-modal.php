<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once PLUGIN_DIR . 'includes/core/abstract-ujc-admin-page.php';
require_once PLUGIN_DIR . 'includes/views/admin/components/class-ujc-property-parts-tooltip.php';

/**
 * Komponent modalu dla zasobów - Single Responsibility Principle
 * Odpowiada tylko za zarządzanie modalem dodawania/edycji zasobów
 */
class UJC_Resource_Modal extends UJC_Admin_Page {
    
    protected function init_hooks() {
        add_action('wp_ajax_ujc_save_resource', [$this, 'ajax_save_resource']);
        add_action('wp_ajax_ujc_get_resource', [$this, 'ajax_get_resource']);
        add_action('wp_ajax_ujc_update_resource', [$this, 'ajax_update_resource']);
    }
    
    /**
     * Renderuje modal HTML
     */
    public function render_modal() {
        ?>
        <!-- Modal dodawania/edycji zasobu -->
        <div id="ujc-resource-modal" class="ujc-modal" style="display: none;">
            <div class="ujc-modal-content">
                <div class="ujc-modal-header">
                    <h2 id="modal-title">Dodaj Zasób</h2>
                    <span class="ujc-modal-close">&times;</span>
                </div>
                
                <form id="resource-modal-form" method="post">
                    <?php wp_nonce_field('ujc_admin_nonce', 'ujc_nonce'); ?>
                    <input type="hidden" id="resource-id" name="resource_id" value="">
                    <input type="hidden" id="modal-action" name="modal_action" value="add">
                    
                    <div class="ujc-modal-body">
                        <table class="form-table">
                            <tr>
                                <th><label for="modal-rodzaj_nieruchomosci">Rodzaj zasobu *</label></th>
                                <td>
                                    <select id="modal-rodzaj_nieruchomosci" name="rodzaj_nieruchomosci" required>
                                        <option value="">Wybierz rodzaj</option>
                                        <option value="Lokal mieszkalny">Lokal mieszkalny</option>
                                        <option value="Dom jednorodzinny">Dom jednorodzinny</option>
                                        <option value="Miejsce postojowe">Miejsce postojowe</option>
                                        <option value="Komórka lokatorska">Komórka lokatorska</option>
                                        <option value="Część nieruchomości">Część nieruchomości</option>
                                        <option value="Garaż">Garaż</option>
                                    </select>
                                    <p class="description" style="margin-top: 5px;">
                                        <?php echo UJC_Property_Parts_Tooltip::render_icon(); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="modal-nr_lokalu">Identyfikator *</label></th>
                                <td><input type="text" id="modal-nr_lokalu" name="nr_lokalu" required class="regular-text" placeholder="np. A1, MP-15, KL-2, G3"></td>
                            </tr>
                            <tr>
                                <th><label for="modal-powierzchnia_uzytkowa">Powierzchnia użytkowa [m²] *</label></th>
                                <td>
                                    <input type="number" id="modal-powierzchnia_uzytkowa" name="powierzchnia_uzytkowa" required step="0.01" min="0" class="regular-text"> m²
                                </td>
                            </tr>
                            <tr>
                                <th><label for="modal-cena_m2">Cena m² *</label></th>
                                <td>
                                    <input type="number" id="modal-cena_m2" name="cena_m2" required step="0.01" min="0" class="regular-text"> zł
                                    <p class="description">Cena za metr kwadratowy powierzchni użytkowej</p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="modal-cena_calkowita">Cena Całkowita</label></th>
                                <td>
                                    <input type="number" id="modal-cena_calkowita" name="cena_calkowita" step="0.01" min="0" class="regular-text"> zł
                                </td>
                            </tr>
                            <tr>
                                <th><label for="modal-status">Status</label></th>
                                <td>
                                    <select id="modal-status" name="status">
                                        <option value="dostepny">Dostępny</option>
                                        <option value="rezerwacja">Rezerwacja</option>
                                        <option value="sprzedany">Sprzedany</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                        <!-- Sekcja części nieruchomości - generyczna -->
                        <div style="display: flex; align-items: center; margin-top: 20px;">
                            <h3 style="margin: 0; flex-grow: 1;">Części nieruchomości</h3>
                            <button type="button" id="add-extra-btn" class="button">Dodaj</button>
                        </div>
                        <div id="extras-container">
                            <div class="extra-item" style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; position: relative;">
                                <button type="button" class="remove-extra button" style="position: absolute; top: 5px; right: 5px;">Usuń</button>
                                <table class="form-table">
                                    <tr>
                                        <th style="width: 150px;">
                                            <label>Rodzaj części</label>
                                        </th>
                                        <td>
                                            <select class="extra-type-select" name="extras[0][typ]" style="width: 200px;">
                                                <option value="">-- wybierz typ --</option>
                                                <optgroup label="Pomieszczenia">
                                                    <option value="Miejsce postojowe">Miejsce postojowe</option>
                                                    <option value="Komórka lokatorska">Komórka lokatorska</option>
                                                    <option value="Garaż">Garaż</option>
                                                    <option value="Piwnica">Piwnica</option>
                                                    <option value="Strych">Strych</option>
                                                </optgroup>
                                                <optgroup label="Części nieruchomości">
                                                    <option value="Balkon">Balkon</option>
                                                    <option value="Taras">Taras</option>
                                                    <option value="Ogródek">Ogródek</option>
                                                    <option value="Udział w gruncie">Udział w gruncie</option>
                                                </optgroup>
                                                <optgroup label="Prawa">
                                                    <option value="Prawo do tarasu">Prawo do tarasu</option>
                                                    <option value="Prawo do ogródka">Prawo do ogródka</option>
                                                </optgroup>
                                                <optgroup label="Świadczenia">
                                                    <option value="Opłata rezerwacyjna">Opłata rezerwacyjna</option>
                                                    <option value="Opłata administracyjna">Opłata administracyjna</option>
                                                </optgroup>
                                                <option value="custom">Inny (wpisz własny)</option>
                                            </select>
                                            <input type="text" class="extra-custom-type" name="extras[0][custom_typ]" placeholder="Wpisz własny typ" style="display: none; margin-left: 10px; width: 200px;">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label>Oznaczenie</label></th>
                                        <td>
                                            <input type="text" name="extras[0][oznaczenie]" class="regular-text" placeholder="np. MP-15, KL-2, 1/100">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label>Cena [zł]</label></th>
                                        <td>
                                            <input type="number" name="extras[0][cena]" step="0.01" min="0" class="regular-text">
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Cena uwzględniająca inne składowe - pokazuje się tylko gdy są części -->
                        <div id="final-price-section" style="display: none; margin-top: 20px;">
                            <h3>Cena finalna</h3>
                            <table class="form-table">
                                <tr>
                                    <th><label for="modal-cena_z_dodatkami">Cena uwzględniająca inne składowe</label></th>
                                    <td>
                                        <input type="number" id="modal-cena_z_dodatkami" name="cena_z_dodatkami" step="0.01" min="0" class="regular-text"> zł
                                        <p class="description">Cena końcowa uwzględniająca wszystkie składowe zgodnie z art. 19a ust. 1 (opcjonalne)</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="ujc-modal-footer">
                        <button type="button" class="button ujc-modal-cancel">Anuluj</button>
                        <button type="submit" class="button-primary" id="modal-submit-btn">Zapisz</button>
                    </div>
                </form>
            </div>
        </div>
        
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
            margin: 5% auto;
            padding: 0;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
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
            text-align: right;
        }
        
        .ujc-modal-footer .button {
            margin-left: 10px;
        }
        </style>
        
        <!-- JavaScript dla modala -->
        <script>
        jQuery(document).ready(function($) {
            // Otwórz modal - dodawanie
            window.openResourceModal = function(mode = 'add', resourceId = null) {
                if (mode === 'add') {
                    $('#modal-title').text('Dodaj Zasób');
                    $('#modal-submit-btn').text('Dodaj Zasób');
                    $('#modal-action').val('add');
                    $('#resource-id').val('');
                    $('#resource-modal-form')[0].reset();
                } else if (mode === 'edit' && resourceId) {
                    $('#modal-title').text('Edytuj Zasób');
                    $('#modal-submit-btn').text('Zapisz Zmiany');
                    $('#modal-action').val('edit');
                    $('#resource-id').val(resourceId);
                    
                    // Załaduj dane zasobu
                    loadResourceData(resourceId);
                }
                
                $('#ujc-resource-modal').show();
                setupAutoCalculations();
            };
            
            // Załaduj dane zasobu do edycji
            function loadResourceData(resourceId) {
                const nonce = typeof ujc_ajax !== 'undefined' ? ujc_ajax.nonce : ($('#ujc-nonce').length ? $('#ujc-nonce').val() : '');
                
                console.log('Loading resource data for ID:', resourceId, 'nonce:', nonce);
                
                $.post(typeof ujc_ajax !== 'undefined' ? ujc_ajax.ajax_url : ajaxurl, {
                    action: 'ujc_get_resource',
                    resource_id: resourceId,
                    ujc_nonce: nonce
                }, function(response) {
                    console.log('Resource data response:', response);
                    if (response.success) {
                        const data = response.data;
                        console.log('Populating form with data:', data);
                        $('#modal-rodzaj_nieruchomosci').val(data.rodzaj_nieruchomosci);
                        $('#modal-nr_lokalu').val(data.nr_lokalu);
                        $('#modal-powierzchnia_uzytkowa').val(data.powierzchnia_uzytkowa);
                        $('#modal-cena_m2').val(data.cena_m2);
                        $('#modal-cena_calkowita').val(data.cena_calkowita || '');
                        $('#modal-cena_z_dodatkami').val(data.cena_z_dodatkami || '');
                        $('#modal-status').val(data.status || 'dostepny');
                        
                        // Załaduj dane dodatków jeśli istnieją
                        if (data.extras && data.extras.length > 0) {
                            $('#extras-container').empty();
                            data.extras.forEach((extra, index) => {
                                addExtraItem(index, extra);
                            });
                        }
                        
                        // Zaktualizuj widoczność sekcji ceny finalnej
                        toggleFinalPriceSection();
                    } else {
                        console.error('Error loading resource data:', response.data);
                        alert('❌ Błąd ładowania danych zasobu: ' + (response.data || 'Nieznany błąd'));
                    }
                }).fail(function(xhr, status, error) {
                    console.error('AJAX error loading resource:', xhr, status, error);
                    alert('❌ Błąd połączenia: ' + error);
                });
            }
            
            // Zamknij modal
            function closeModal() {
                $('#ujc-resource-modal').hide();
                $('#resource-modal-form')[0].reset();
            }
            
            $('.ujc-modal-close, .ujc-modal-cancel').on('click', closeModal);
            
            // Zamknij modal kliknięciem poza nim
            $('#ujc-resource-modal').on('click', function(e) {
                if (e.target === this) {
                    closeModal();
                }
            });
            
            // Obsługa dynamicznych części nieruchomości
            let extraIndex = 1;
            
            // Funkcja dodawania nowej części nieruchomości
            window.addExtraItem = function(index = null, data = null) {
                const currentIndex = index !== null ? index : extraIndex++;
                const extraHtml = `
                    <div class="extra-item" style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; position: relative;">
                        <button type="button" class="remove-extra button" style="position: absolute; top: 5px; right: 5px;">Usuń</button>
                        <table class="form-table">
                            <tr>
                                <th style="width: 150px;">
                                    <label>Rodzaj części</label>
                                </th>
                                <td>
                                    <select class="extra-type-select" name="extras[${currentIndex}][typ]" style="width: 200px;">
                                        <option value="">-- wybierz typ --</option>
                                        <optgroup label="Pomieszczenia">
                                            <option value="Miejsce postojowe">Miejsce postojowe</option>
                                            <option value="Komórka lokatorska">Komórka lokatorska</option>
                                            <option value="Garaż">Garaż</option>
                                            <option value="Piwnica">Piwnica</option>
                                            <option value="Strych">Strych</option>
                                        </optgroup>
                                        <optgroup label="Części nieruchomości">
                                            <option value="Balkon">Balkon</option>
                                            <option value="Taras">Taras</option>
                                            <option value="Ogródek">Ogródek</option>
                                            <option value="Udział w gruncie">Udział w gruncie</option>
                                        </optgroup>
                                        <optgroup label="Prawa">
                                            <option value="Prawo do tarasu">Prawo do tarasu</option>
                                            <option value="Prawo do ogródka">Prawo do ogródka</option>
                                        </optgroup>
                                        <optgroup label="Świadczenia">
                                            <option value="Opłata rezerwacyjna">Opłata rezerwacyjna</option>
                                            <option value="Opłata administracyjna">Opłata administracyjna</option>
                                        </optgroup>
                                        <option value="custom">Inny (wpisz własny)</option>
                                    </select>
                                    <input type="text" class="extra-custom-type" name="extras[${currentIndex}][custom_typ]" 
                                           placeholder="Wpisz własny typ" style="display: none; margin-left: 10px; width: 200px;">
                                </td>
                            </tr>
                            <tr>
                                <th><label>Oznaczenie</label></th>
                                <td>
                                    <input type="text" name="extras[${currentIndex}][oznaczenie]" class="regular-text" 
                                           placeholder="np. MP-15, KL-2, 1/100" value="${data ? data.oznaczenie_dodatku || '' : ''}">
                                </td>
                            </tr>
                            <tr>
                                <th><label>Cena [zł]</label></th>
                                <td>
                                    <input type="number" name="extras[${currentIndex}][cena]" step="0.01" min="0" class="regular-text"
                                           value="${data ? data.cena_dodatku || '' : ''}">
                                </td>
                            </tr>
                        </table>
                    </div>
                `;
                
                $('#extras-container').append(extraHtml);
                
                // Ustaw wartość typu jeśli istnieje
                if (data && data.typ_dodatku) {
                    $(`[name="extras[${currentIndex}][typ]"]`).val(data.typ_dodatku);
                }
            };
            
            // Funkcja sprawdzająca czy pokazać sekcję ceny finalnej
            function toggleFinalPriceSection() {
                const extrasCount = $('#extras-container .extra-item').length;
                if (extrasCount > 0) {
                    $('#final-price-section').show();
                } else {
                    $('#final-price-section').hide();
                    $('#modal-cena_z_dodatkami').val(''); // Wyczyść wartość
                }
            }
            
            // Dodaj nową część nieruchomości
            $('#add-extra-btn').on('click', function() {
                addExtraItem();
                toggleFinalPriceSection();
            });
            
            // Usuń część nieruchomości
            $(document).on('click', '.remove-extra', function() {
                $(this).closest('.extra-item').remove();
                toggleFinalPriceSection();
            });
            
            // Obsługa wyboru "Inny"
            $(document).on('change', '.extra-type-select', function() {
                const $customInput = $(this).siblings('.extra-custom-type');
                if ($(this).val() === 'custom') {
                    $customInput.show();
                } else {
                    $customInput.hide().val('');
                }
            });
            
            // Submit formularza
            $('#resource-modal-form').on('submit', function(e) {
                e.preventDefault();
                
                const mode = $('#modal-action').val();
                const action = mode === 'edit' ? 'ujc_update_resource' : 'ujc_save_resource';
                
                const formData = $(this).serialize() + '&action=' + action;
                
                const $submitBtn = $('#modal-submit-btn');
                const originalText = $submitBtn.text();
                $submitBtn.text('Zapisywanie...').prop('disabled', true);
                
                $.post(typeof ujc_ajax !== 'undefined' ? ujc_ajax.ajax_url : ajaxurl, formData, function(response) {
                    if (response.success) {
                        alert('✅ ' + response.data);
                        closeModal();
                        if (typeof loadResourcesList === 'function') {
                            loadResourcesList();
                        }
                    } else {
                        alert('❌ ' + (response.data || 'Błąd podczas zapisywania'));
                    }
                }).fail(function() {
                    alert('❌ Błąd połączenia');
                }).always(function() {
                    $submitBtn.text(originalText).prop('disabled', false);
                });
            });
            
            // Automatyczne obliczenia - powierzchnia stała, ceny przeliczane
            function setupAutoCalculations() {
                const $powierzchnia = $('#modal-powierzchnia_uzytkowa');
                const $cenaM2 = $('#modal-cena_m2');
                const $cenaCalkowita = $('#modal-cena_calkowita');
                
                let lastModified = 'cena_m2'; // Domyślnie przeliczamy od ceny m²
                
                // Przelicz cenę całkowitą na podstawie ceny m²
                function calculateFromM2() {
                    const powierzchnia = parseFloat($powierzchnia.val()) || 0;
                    const cenaM2 = parseFloat($cenaM2.val()) || 0;
                    
                    if (powierzchnia > 0 && cenaM2 > 0) {
                        const calculated = (powierzchnia * cenaM2).toFixed(2);
                        $cenaCalkowita.val(calculated);
                    }
                }
                
                // Przelicz cenę m² na podstawie ceny całkowitej
                function calculateFromTotal() {
                    const powierzchnia = parseFloat($powierzchnia.val()) || 0;
                    const cenaCalkowita = parseFloat($cenaCalkowita.val()) || 0;
                    
                    if (powierzchnia > 0 && cenaCalkowita > 0) {
                        const calculated = (cenaCalkowita / powierzchnia).toFixed(2);
                        $cenaM2.val(calculated);
                    }
                }
                
                // Event handlery
                $cenaM2.off('input change focus').on('input change focus', function() {
                    lastModified = 'cena_m2';
                    calculateFromM2();
                });
                
                $cenaCalkowita.off('input change focus').on('input change focus', function() {
                    lastModified = 'cena_calkowita';
                    calculateFromTotal();
                });
                
                // Przy zmianie powierzchni, przelicz na podstawie ostatnio modyfikowanej ceny
                $powierzchnia.off('input change').on('input change', function() {
                    if (lastModified === 'cena_calkowita') {
                        calculateFromTotal();
                    } else {
                        calculateFromM2();
                    }
                });
            }
        });
        </script>
        <?php
    }
    
    private function sanitize_resource_data() {
        return $this->sanitize_post_data([
            'rodzaj_nieruchomosci' => 'sanitize_text_field',
            'nr_lokalu' => 'sanitize_text_field',
            'powierzchnia_uzytkowa' => 'floatval',
            'cena_m2' => 'floatval',
            'cena_calkowita' => 'floatval',
            'cena_z_dodatkami' => 'floatval',
            'status' => 'sanitize_text_field'
        ]);
    }
    
    private function set_price_dates_for_new_resource($data, $current_datetime) {
        $data['data_cena_m2'] = $current_datetime;
        
        if (!empty($data['cena_calkowita'])) {
            $data['data_cena_calkowita'] = $current_datetime;
        }
        
        if (!empty($data['cena_z_dodatkami'])) {
            $data['data_cena_z_dodatkami'] = $current_datetime;
        }
        
        return $data;
    }
    
    private function update_price_dates_if_changed($data, $old_data, $current_datetime) {
        // Ustaw datę TYLKO gdy cena się zmieniła
        if ($old_data['cena_m2'] != $data['cena_m2']) {
            $data['data_cena_m2'] = $current_datetime;
        }
        
        if ($old_data['cena_calkowita'] != $data['cena_calkowita']) {
            $data['data_cena_calkowita'] = $current_datetime;
        }
        
        if ($old_data['cena_z_dodatkami'] != $data['cena_z_dodatkami']) {
            $data['data_cena_z_dodatkami'] = $current_datetime;
        }
        
        return $data;
    }
    
    public function ajax_save_resource() {
        error_log('UJC: ajax_save_resource started');
        error_log('UJC: POST data: ' . print_r($_POST, true));
        
        if (!$this->verify_nonce()) {
            error_log('UJC: save resource - nonce verification failed');
            wp_send_json_error('Błąd weryfikacji bezpieczeństwa.');
            return;
        }
        
        if (!$this->check_permissions()) {
            error_log('UJC: save resource - permission check failed'); 
            wp_send_json_error('Brak uprawnień.');
            return;
        }
        
        try {
            $data = $this->sanitize_resource_data();
            $current_datetime = DateHelper::currentDatetime();
            $data = $this->set_price_dates_for_new_resource($data, $current_datetime);
            
            error_log('UJC: Resource data to save: ' . print_r($data, true));
            
            $useCase = new SaveResourceUseCase();
            $result = $useCase->execute($data);
            error_log('UJC: Resource create result: ' . print_r($result, true));
            
            // Sprawdź czy result zawiera błąd walidacji
            if (is_array($result) && isset($result['error'])) {
                wp_send_json_error($result['error']);
            } elseif ($result) {
                wp_send_json_success('Zasób został dodany!');
            } else {
                wp_send_json_error('Błąd podczas dodawania zasobu.');
            }
        } catch (Exception $e) {
            wp_send_json_error('Błąd serwera: ' . $e->getMessage());
        }
    }
    
    public function ajax_get_resource() {
        error_log('UJC: ajax_get_resource started');
        
        if (!$this->verify_nonce()) {
            error_log('UJC: nonce verification failed');
            return;
        }
        
        if (!$this->check_permissions()) {
            error_log('UJC: permission check failed');
            return;
        }
        
        try {
            $resource_id = intval($_POST['resource_id'] ?? 0);
            error_log('UJC: Loading resource ID: ' . $resource_id);
            
            $useCase = new GetResourceByIdUseCase();
            $resource = $useCase->execute($resource_id);
            error_log('UJC: Resource data retrieved: ' . print_r($resource, true));
            
            if ($resource) {
                wp_send_json_success($resource);
            } else {
                error_log('UJC: No resource found for ID: ' . $resource_id);
                wp_send_json_error('Nie znaleziono zasobu');
            }
        } catch (Exception $e) {
            error_log('UJC: Exception in ajax_get_resource: ' . $e->getMessage());
            wp_send_json_error('Błąd serwera: ' . $e->getMessage());
        }
    }
    
    public function ajax_update_resource() {
        if (!$this->verify_nonce()) return;
        if (!$this->check_permissions()) return;
        
        try {
            $resource_id = intval($_POST['resource_id'] ?? 0);
            
            if (!$resource_id) {
                wp_send_json_error('Nieprawidłowy ID zasobu');
                return;
            }
            
            $data = $this->sanitize_resource_data();
            
            // Zaktualizuj daty tylko dla zmienionych cen
            $getUseCase = new GetResourceByIdUseCase();
            $old_data = $getUseCase->execute($resource_id);
            $current_datetime = DateHelper::currentDatetime();
            $data = $this->update_price_dates_if_changed($data, $old_data, $current_datetime);
            
            $saveUseCase = new SaveResourceUseCase();
            $result = $saveUseCase->execute($data, $resource_id);
            
            // Sprawdź czy result zawiera błąd walidacji
            if (is_array($result) && isset($result['error'])) {
                wp_send_json_error($result['error']);
            } elseif ($result !== false) {
                wp_send_json_success('Zasób został zaktualizowany!');
            } else {
                wp_send_json_error('Błąd podczas aktualizacji zasobu.');
            }
        } catch (Exception $e) {
            wp_send_json_error('Błąd serwera: ' . $e->getMessage());
        }
    }
    
    public function render() {
        // Nie używane - to jest komponent modalny
    }
}