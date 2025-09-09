<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Komponent modalu dla zasobów - Single Responsibility Principle
 * Odpowiada tylko za zarządzanie modalem dodawania/edycji zasobów
 */
class ResourceModal extends UJC_Admin_Page {
    
    private $createResourceUseCase;
    private $updateResourceUseCase;
    private $getResourceByIdUseCase;
    private $deleteResourceUseCase;
    
    public function __construct() {
        $this->createResourceUseCase = new CreateResourceUseCase();
        $this->updateResourceUseCase = new UpdateResourceUseCase();
        $this->getResourceByIdUseCase = new GetResourceByIdUseCase();
        $this->deleteResourceUseCase = new DeleteResourceUseCase();
        
        add_action('wp_ajax_ujc_save_resource', [$this, 'ajax_save_resource']);
        add_action('wp_ajax_ujc_get_resource', [$this, 'ajax_get_resource']);
        add_action('wp_ajax_ujc_update_resource', [$this, 'ajax_update_resource']);
        add_action('wp_ajax_ujc_delete_resource', [$this, 'ajax_delete_resource']);
        
        parent::__construct();
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
                    <?php wp_nonce_field('ujc_admin_nonce', 'nonce'); ?>
                    <input type="hidden" id="resource-id" name="resource_id" value="">
                    <input type="hidden" id="modal-action" name="modal_action" value="add">
                    
                    <div class="ujc-modal-body">
                        <table class="form-table">
                            <tr>
                                <th><label for="modal-rodzaj_nieruchomosci">Rodzaj zasobu *</label></th>
                                <td>
                                    <select id="modal-rodzaj_nieruchomosci" name="rodzaj_nieruchomosci" required>
                                        <?php foreach (PropertyType::cases() as $type): ?>
                                            <option value="<?php echo esc_attr($type->value); ?>" <?php echo $type === PropertyType::RESIDENTIAL_UNIT ? 'selected' : ''; ?>>
                                                <?php echo esc_html($type->getDisplayText()); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
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
                                        <?php foreach (ResourceStatus::cases() as $status): ?>
                                            <option value="<?php echo esc_attr($status->value); ?>">
                                                <?php echo esc_html($status->getDisplayText()); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                        <!-- Dynamic component sections based on investment configuration -->
                        
                        <!-- Component add buttons -->
                        <div id="component-buttons" style="margin-top: 20px;">
                            <button type="button" id="add-property-part-btn" class="component-add-btn">
                                + Część nieruchomości będąca przedmiotem umowy
                            </button>
                            <button type="button" id="add-belonging-room-btn" class="component-add-btn">
                                + Pomieszczenie przynależne
                            </button>
                            <button type="button" id="add-usage-rights-btn" class="component-add-btn">
                                + Prawa niezbędne do korzystania z lokalu lub domu
                            </button>
                            <button type="button" id="add-other-services-btn" class="component-add-btn">
                                + Inne świadczenia pieniężne na rzecz dewelopera
                            </button>
                        </div>
                        
                        <!-- Części nieruchomości będące przedmiotem umowy -->
                        <div id="property-parts-section" class="component-section" style="display: none; margin-top: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h3 style="margin: 0;">Część nieruchomości będąca przedmiotem umowy</h3>
                                <button type="button" class="remove-section-btn" data-section="property-parts" style="background: #0073aa; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px;">
                                    Usuń
                                </button>
                            </div>
                            <div style="padding: 15px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">
                                <table class="form-table">
                                    <tr>
                                        <th><label for="property-part-title">Rodzaj części *</label></th>
                                        <td>
                                            <input type="text" id="property-part-title" name="property_part_title" class="regular-text" 
                                                   placeholder="np. Miejsce postojowe">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="property-part-designation">Oznaczenie</label></th>
                                        <td>
                                            <input type="text" id="property-part-designation" name="property_part_designation" class="regular-text" 
                                                   placeholder="np. MP-15">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="property-part-price">Cena części</label></th>
                                        <td>
                                            <input type="number" id="property-part-price" name="property_part_price" step="0.01" min="0" class="regular-text"> zł
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Pomieszczenia przynależne -->
                        <div id="belonging-rooms-section" class="component-section" style="display: none; margin-top: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h3 style="margin: 0;">Pomieszczenie przynależne</h3>
                                <button type="button" class="remove-section-btn" data-section="belonging-rooms" style="background: #0073aa; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px;">
                                    Usuń
                                </button>
                            </div>
                            <div style="padding: 15px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">
                                <table class="form-table">
                                    <tr>
                                        <th><label for="belonging-room-title">Rodzaj pomieszczenia *</label></th>
                                        <td>
                                            <input type="text" id="belonging-room-title" name="belonging_room_title" class="regular-text" 
                                                   placeholder="np. Komórka lokatorska">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="belonging-room-designation">Oznaczenie</label></th>
                                        <td>
                                            <input type="text" id="belonging-room-designation" name="belonging_room_designation" class="regular-text" 
                                                   placeholder="np. KL-2">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="belonging-room-price">Cena pomieszczenia</label></th>
                                        <td>
                                            <input type="number" id="belonging-room-price" name="belonging_room_price" step="0.01" min="0" class="regular-text"> zł
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Prawa niezbędne do korzystania z lokalu/domu -->
                        <div id="usage-rights-section" class="component-section" style="display: none; margin-top: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h3 style="margin: 0;">Prawa niezbędne do korzystania z lokalu lub domu</h3>
                                <button type="button" class="remove-section-btn" data-section="usage-rights" style="background: #0073aa; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px;">
                                    Usuń
                                </button>
                            </div>
                            <div style="padding: 15px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">
                                <table class="form-table">
                                    <tr>
                                        <th><label for="usage-rights-title">Opis praw *</label></th>
                                        <td>
                                            <textarea id="usage-rights-title" name="usage_right_title" class="regular-text" rows="3" 
                                                      placeholder="Opisz prawa niezbędne do korzystania z lokalu/domu"></textarea>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="usage-rights-price">Wartość praw</label></th>
                                        <td>
                                            <input type="number" id="usage-rights-price" name="usage_right_price" step="0.01" min="0" class="regular-text"> zł
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Inne świadczenia pieniężne na rzecz dewelopera -->
                        <div id="other-services-section" class="component-section" style="display: none; margin-top: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <h3 style="margin: 0;">Inne świadczenia pieniężne na rzecz dewelopera</h3>
                                <button type="button" class="remove-section-btn" data-section="other-services" style="background: #0073aa; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px;">
                                    Usuń
                                </button>
                            </div>
                            <div style="padding: 15px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">
                                <table class="form-table">
                                    <tr>
                                        <th><label for="other-services-title">Opis świadczeń *</label></th>
                                        <td>
                                            <textarea id="other-services-title" name="other_service_title" class="regular-text" rows="3" 
                                                      placeholder="Opisz inne świadczenia pieniężne"></textarea>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="other-services-price">Wartość świadczeń</label></th>
                                        <td>
                                            <input type="number" id="other-services-price" name="other_service_price" step="0.01" min="0" class="regular-text"> zł
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Cena uwzględniająca inne składowe -->
                        <div id="final-price-section" style="margin-top: 20px;">
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
                        <div class="ujc-modal-footer-left">
                            <button type="button" class="button button-secondary ujc-delete-btn" id="modal-delete-btn" style="display: none; color: #d63638; border-color: #d63638;">Usuń</button>
                        </div>
                        <div class="ujc-modal-footer-right">
                            <button type="button" class="button ujc-modal-cancel">Anuluj</button>
                            <button type="submit" class="button-primary" id="modal-submit-btn">Zapisz</button>
                        </div>
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
        
        .ujc-delete-btn:hover {
            background-color: #d63638 !important;
            color: #fff !important;
        }
        
        /* Component add buttons styling */
        .component-add-btn {
            display: none;
            width: 100%;
            margin-bottom: 10px;
            padding: 12px 15px;
            text-align: left;
            border: 1px solid #0073aa;
            background: linear-gradient(135deg, #f8f9fa 0%, #f1f3f4 100%);
            color: #0073aa;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .component-add-btn:hover {
            background: linear-gradient(135deg, #e8f4f8 0%, #d4edda 100%);
            border-color: #005a87;
            color: #005a87;
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        
        .component-add-btn:active {
            transform: translateY(0);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        #component-buttons {
            margin-top: 20px;
            margin-bottom: 10px;
        }
        </style>
        
        <!-- JavaScript dla modala -->
        <script>
        jQuery(document).ready(function($) {
            // Załaduj konfigurację inwestycji
            function loadInvestmentConfiguration() {
                const nonce = typeof ujc_ajax !== 'undefined' ? ujc_ajax.nonce : ($('#ujc-nonce').length ? $('#ujc-nonce').val() : '');
                
                return $.post(typeof ujc_ajax !== 'undefined' ? ujc_ajax.ajax_url : ajaxurl, {
                    action: 'ujc_get_investment',
                    nonce: nonce
                }).done(function(response) {
                    if (response.success) {
                        const config = response.data;
                        
                        // Show/hide component buttons based on investment config
                        $('#add-property-part-btn').toggle(config.has_property_parts == '1');
                        $('#add-belonging-room-btn').toggle(config.has_belonging_rooms == '1');
                        $('#add-usage-rights-btn').toggle(config.has_usage_rights == '1');
                        $('#add-other-services-btn').toggle(config.has_other_services == '1');
                        
                        // Final price section is always visible now
                        $('#final-price-section').show();
                        
                        console.log('Investment config loaded:', config);
                        window.investmentConfig = config; // Store globally for later use
                    } else {
                        console.error('Error loading investment configuration:', response.data);
                    }
                }).fail(function(xhr, status, error) {
                    console.error('AJAX error loading investment config:', xhr, status, error);
                });
            }
            
            // Otwórz modal - dodawanie
            window.openResourceModal = function(mode = 'add', resourceId = null) {
                // Load investment configuration first
                loadInvestmentConfiguration().always(function() {
                    if (mode === 'add') {
                        $('#modal-title').text('Dodaj Zasób');
                        $('#modal-submit-btn').text('Dodaj Zasób');
                        $('#modal-action').val('add');
                        $('#resource-id').val('');
                        $('#resource-modal-form')[0].reset();
                        $('#modal-delete-btn').hide(); // Ukryj przycisk usuń w trybie dodawania
                    } else if (mode === 'edit' && resourceId) {
                        $('#modal-title').text('Edytuj Zasób');
                        $('#modal-submit-btn').text('Zapisz Zmiany');
                        $('#modal-action').val('edit');
                        $('#resource-id').val(resourceId);
                        $('#modal-delete-btn').show(); // Pokaż przycisk usuń w trybie edycji
                        
                        // Załaduj dane zasobu
                        loadResourceData(resourceId);
                    }
                    
                    $('#ujc-resource-modal').show();
                    setupAutoCalculations();
                });
            };
            
            // Załaduj dane zasobu do edycji
            function loadResourceData(resourceId) {
                const nonce = typeof ujc_ajax !== 'undefined' ? ujc_ajax.nonce : ($('#ujc-nonce').length ? $('#ujc-nonce').val() : '');
                
                console.log('Loading resource data for ID:', resourceId, 'nonce:', nonce);
                
                $.post(typeof ujc_ajax !== 'undefined' ? ujc_ajax.ajax_url : ajaxurl, {
                    action: 'ujc_get_resource',
                    resource_id: resourceId,
                    nonce: nonce
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
                        $('#modal-status').val(data.status || '<?php echo ResourceStatus::cases()[0]->value; ?>');
                        
                        // Load component data and show sections/buttons based on data availability
                        if (data.property_part_title) {
                            $('#property-part-title').val(data.property_part_title);
                            $('#property-part-designation').val(data.property_part_designation || '');
                            $('#property-part-price').val(data.property_part_price || '');
                            // Show section, hide button
                            $('#property-parts-section').show();
                            $('#add-property-part-btn').hide();
                        } else {
                            $('#property-part-title, #property-part-designation, #property-part-price').val('');
                            // Hide section, show button (if enabled by investment config)
                            $('#property-parts-section').hide();
                            if (window.investmentConfig && window.investmentConfig.has_property_parts == '1') {
                                $('#add-property-part-btn').show();
                            }
                        }
                        
                        if (data.belonging_room_title) {
                            $('#belonging-room-title').val(data.belonging_room_title);
                            $('#belonging-room-designation').val(data.belonging_room_designation || '');
                            $('#belonging-room-price').val(data.belonging_room_price || '');
                            // Show section, hide button
                            $('#belonging-rooms-section').show();
                            $('#add-belonging-room-btn').hide();
                        } else {
                            $('#belonging-room-title, #belonging-room-designation, #belonging-room-price').val('');
                            // Hide section, show button (if enabled by investment config)
                            $('#belonging-rooms-section').hide();
                            if (window.investmentConfig && window.investmentConfig.has_belonging_rooms == '1') {
                                $('#add-belonging-room-btn').show();
                            }
                        }
                        
                        if (data.usage_right_title) {
                            $('#usage-rights-title').val(data.usage_right_title || '');
                            $('#usage-rights-price').val(data.usage_right_price || '');
                            // Show section, hide button
                            $('#usage-rights-section').show();
                            $('#add-usage-rights-btn').hide();
                        } else {
                            $('#usage-rights-title, #usage-rights-price').val('');
                            // Hide section, show button (if enabled by investment config)
                            $('#usage-rights-section').hide();
                            if (window.investmentConfig && window.investmentConfig.has_usage_rights == '1') {
                                $('#add-usage-rights-btn').show();
                            }
                        }
                        
                        if (data.other_service_title) {
                            $('#other-services-title').val(data.other_service_title || '');
                            $('#other-services-price').val(data.other_service_price || '');
                            // Show section, hide button
                            $('#other-services-section').show();
                            $('#add-other-services-btn').hide();
                        } else {
                            $('#other-services-title, #other-services-price').val('');
                            // Hide section, show button (if enabled by investment config)
                            $('#other-services-section').hide();
                            if (window.investmentConfig && window.investmentConfig.has_other_services == '1') {
                                $('#add-other-services-btn').show();
                            }
                        }
                        
                        // Przelicz cenę finalną
                        calculateFinalPrice();
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
                // Reset component buttons and sections
                $('.component-add-btn').hide();
                $('.component-section').hide();
            }
            
            $('.ujc-modal-close, .ujc-modal-cancel').on('click', closeModal);
            
            // Component button click handlers
            $('#add-property-part-btn').on('click', function() {
                $(this).hide();
                $('#property-parts-section').show();
                calculateFinalPrice();
            });
            
            $('#add-belonging-room-btn').on('click', function() {
                $(this).hide();
                $('#belonging-rooms-section').show();
                calculateFinalPrice();
            });
            
            $('#add-usage-rights-btn').on('click', function() {
                $(this).hide();
                $('#usage-rights-section').show();
                calculateFinalPrice();
            });
            
            $('#add-other-services-btn').on('click', function() {
                $(this).hide();
                $('#other-services-section').show();
                calculateFinalPrice();
            });
            
            // Zamknij modal kliknięciem poza nim
            $('#ujc-resource-modal').on('click', function(e) {
                if (e.target === this) {
                    closeModal();
                }
            });
            
            // Obsługa przycisku usuń
            $('#modal-delete-btn').on('click', function() {
                const resourceId = $('#resource-id').val();
                
                if (!resourceId) {
                    alert('❌ Brak ID zasobu do usunięcia');
                    return;
                }
                
                // Potwierdzenie usunięcia
                const confirmation = confirm('Czy na pewno chcesz usunąć ten zasób? Ta operacja jest nieodwracalna.');
                if (!confirmation) {
                    return;
                }
                
                const nonce = typeof ujc_ajax !== 'undefined' ? ujc_ajax.nonce : ($('#ujc-nonce').length ? $('#ujc-nonce').val() : '');
                const $deleteBtn = $(this);
                const originalText = $deleteBtn.text();
                
                $deleteBtn.text('Usuwanie...').prop('disabled', true);
                
                $.post(typeof ujc_ajax !== 'undefined' ? ujc_ajax.ajax_url : ajaxurl, {
                    action: 'ujc_delete_resource',
                    resource_id: resourceId,
                    nonce: nonce
                }, function(response) {
                    if (response.success) {
                        alert('✅ ' + response.data);
                        closeModal();
                        if (typeof loadResourcesList === 'function') {
                            loadResourcesList();
                        }
                    } else {
                        alert('❌ ' + (response.data || 'Błąd podczas usuwania'));
                    }
                }).fail(function() {
                    alert('❌ Błąd połączenia');
                }).always(function() {
                    $deleteBtn.text(originalText).prop('disabled', false);
                });
            });
            
            // Remove section button click handlers
            $('.remove-section-btn').on('click', function() {
                const sectionType = $(this).data('section');
                
                // Hide the section
                $(`#${sectionType}-section`).hide();
                
                // Clear form data for this section
                clearSectionData(sectionType);
                
                // Show the corresponding add button
                showAddButtonIfEnabled(sectionType);
                
                // Recalculate final price
                calculateFinalPrice();
            });
            
            // Clear form data for a specific section type
            function clearSectionData(sectionType) {
                switch(sectionType) {
                    case 'property-parts':
                        $('#property-part-title, #property-part-designation, #property-part-price').val('');
                        break;
                    case 'belonging-rooms':
                        $('#belonging-room-title, #belonging-room-designation, #belonging-room-price').val('');
                        break;
                    case 'usage-rights':
                        $('#usage-rights-title, #usage-rights-price').val('');
                        break;
                    case 'other-services':
                        $('#other-services-title, #other-services-price').val('');
                        break;
                }
            }
            
            // Show add button for the section type
            function showAddButtonIfEnabled(sectionType) {
                switch(sectionType) {
                    case 'property-parts':
                        $('#add-property-part-btn').show();
                        break;
                    case 'belonging-rooms':
                        $('#add-belonging-room-btn').show();
                        break;
                    case 'usage-rights':
                        $('#add-usage-rights-btn').show();
                        break;
                    case 'other-services':
                        $('#add-other-services-btn').show();
                        break;
                }
            }
            
            // Dynamic component sections are now controlled by investment configuration
            
            // Automatyczne przeliczanie ceny finalnej
            function calculateFinalPrice() {
                const cenaCalkowita = parseFloat($('#modal-cena_calkowita').val()) || 0;
                
                let totalExtra = 0;
                
                // Collect prices from visible component sections
                if ($('#property-parts-section').is(':visible')) {
                    totalExtra += parseFloat($('#property-part-price').val()) || 0;
                }
                if ($('#belonging-rooms-section').is(':visible')) {
                    totalExtra += parseFloat($('#belonging-room-price').val()) || 0;
                }
                if ($('#usage-rights-section').is(':visible')) {
                    totalExtra += parseFloat($('#usage-rights-price').val()) || 0;
                }
                if ($('#other-services-section').is(':visible')) {
                    totalExtra += parseFloat($('#other-services-price').val()) || 0;
                }
                
                // Always calculate final price if we have base price and any extras
                if (cenaCalkowita > 0 && totalExtra > 0) {
                    const cenaFinalna = cenaCalkowita + totalExtra;
                    $('#modal-cena_z_dodatkami').val(cenaFinalna.toFixed(2));
                } else {
                    $('#modal-cena_z_dodatkami').val('');
                }
            }
            
            // Automatyczne przeliczanie przy zmianie cen
            $('#modal-cena_calkowita, #property-part-price, #belonging-room-price, #usage-rights-price, #other-services-price').on('input', calculateFinalPrice);
            
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
                    calculateFinalPrice();
                }
                
                // Przelicz cenę m² na podstawie ceny całkowitej
                function calculateFromTotal() {
                    const powierzchnia = parseFloat($powierzchnia.val()) || 0;
                    const cenaCalkowita = parseFloat($cenaCalkowita.val()) || 0;
                    
                    if (powierzchnia > 0 && cenaCalkowita > 0) {
                        const calculated = (cenaCalkowita / powierzchnia).toFixed(2);
                        $cenaM2.val(calculated);
                    }
                    calculateFinalPrice();
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
            
            // Dynamic form fields based on property type
            function setupPropertyTypeLogic() {
                const $propertyType = $('#modal-rodzaj_nieruchomosci');
                const $cenaM2Field = $('#modal-cena_m2');
                const $cenaM2Row = $cenaM2Field.closest('tr');
                
                function toggleFieldsByPropertyType(propertyType) {
                    switch(propertyType) {
                        case '<?php echo PropertyType::PARKING_SPACE->value; ?>':
                            // Hide cena m² for parking spaces - usually sold per unit
                            $cenaM2Row.hide();
                            $cenaM2Field.removeAttr('required');
                            $cenaM2Field.val(''); // Clear value
                            break;
                            
                        case '<?php echo PropertyType::STORAGE_ROOM->value; ?>':
                            // Make cena m² optional for storage rooms
                            $cenaM2Row.show();
                            $cenaM2Field.removeAttr('required');
                            break;
                            
                        case '<?php echo PropertyType::RESIDENTIAL_UNIT->value; ?>':
                        case '<?php echo PropertyType::SINGLE_FAMILY_HOUSE->value; ?>': 
                        case '<?php echo PropertyType::GARAGE->value; ?>':
                        default:
                            // Show and require cena m² for residential properties and garages
                            $cenaM2Row.show();
                            $cenaM2Field.attr('required', 'required');
                            break;
                    }
                }
                
                // Handle property type change
                $propertyType.on('change', function() {
                    const selectedType = $(this).val();
                    toggleFieldsByPropertyType(selectedType);
                });
                
                // Initialize on modal open
                toggleFieldsByPropertyType($propertyType.val());
            }
            
            // Initialize all functionality
            setupAutoCalculations();
            setupPropertyTypeLogic();
        });
        </script>
        <?php
    }
    
    private function sanitize_resource_data() {
        return $this->sanitize_post_data([
            'rodzaj_nieruchomosci' => 'sanitize_text_field',
            'nr_lokalu' => 'sanitize_text_field',
            'powierzchnia_uzytkowa' => 'floatval',
            'cena_m2' => [$this, 'sanitize_nullable_float'],
            'cena_calkowita' => 'floatval',
            'cena_z_dodatkami' => 'floatval',
            'status' => 'sanitize_text_field',
            'extra_type' => 'sanitize_text_field',
            'extra_oznaczenie' => 'sanitize_text_field',
            'extra_cena' => 'floatval',
            
            // Component fields
            'property_part_title' => 'sanitize_text_field',
            'property_part_designation' => 'sanitize_text_field',
            'property_part_price' => [$this, 'sanitize_nullable_float'],
            
            'belonging_room_title' => 'sanitize_text_field',
            'belonging_room_designation' => 'sanitize_text_field',
            'belonging_room_price' => [$this, 'sanitize_nullable_float'],
            
            'usage_right_title' => 'sanitize_textarea_field',
            'usage_right_price' => [$this, 'sanitize_nullable_float'],
            
            'other_service_title' => 'sanitize_textarea_field',
            'other_service_price' => [$this, 'sanitize_nullable_float']
        ]);
    }
    
    private function sanitize_nullable_float($value) {
        // If empty string or whitespace, return null
        if (empty($value) || trim($value) === '') {
            return null;
        }
        return floatval($value);
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
        if (!$this->verify_nonce()) {
            wp_send_json_error('Błąd weryfikacji bezpieczeństwa.');
            return;
        }
        
        if (!$this->check_permissions()) {
            wp_send_json_error('Brak uprawnień.');
            return;
        }
        
        try {
            $data = $this->sanitize_resource_data();
            
            // Create component models first
            $propertyPart = null;
            if (!empty($data['property_part_title']) && isset($data['property_part_price'])) {
                $propertyPart = new PropertyPartFormData(
                    title: $data['property_part_title'],
                    designation: $data['property_part_designation'] ?? '',
                    price: (float)$data['property_part_price']
                );
            }
            
            $belongingRoom = null;
            if (!empty($data['belonging_room_title']) && isset($data['belonging_room_price'])) {
                $belongingRoom = new BelongingRoomFormData(
                    title: $data['belonging_room_title'],
                    designation: $data['belonging_room_designation'] ?? '',
                    price: (float)$data['belonging_room_price']
                );
            }
            
            $usageRight = null;
            if (!empty($data['usage_right_title']) && isset($data['usage_right_price'])) {
                $usageRight = new UsageRightFormData(
                    title: $data['usage_right_title'],
                    price: (float)$data['usage_right_price']
                );
            }
            
            $otherService = null;
            if (!empty($data['other_service_title']) && isset($data['other_service_price'])) {
                $otherService = new OtherServiceFormData(
                    title: $data['other_service_title'],
                    price: (float)$data['other_service_price']
                );
            }
            
            // Create ResourceFormData with components included
            $formData = new ResourceFormData(
                rodzaj_nieruchomosci: PropertyType::from($data['rodzaj_nieruchomosci']),
                nr_lokalu: $data['nr_lokalu'],
                powierzchnia_uzytkowa: (float)$data['powierzchnia_uzytkowa'],
                cena_m2: $data['cena_m2'] !== null ? (float)$data['cena_m2'] : null,
                cena_calkowita: (float)$data['cena_calkowita'],
                cena_z_dodatkami: (float)$data['cena_z_dodatkami'],
                status: ResourceStatus::from($data['status']),
                property_part: $propertyPart,
                belonging_room: $belongingRoom,
                usage_right: $usageRight,
                other_service: $otherService
            );
            
            $result = $this->createResourceUseCase->execute($formData);
            
            if ($result->isSuccess) {
                wp_send_json_success($result->message);
            } else {
                wp_send_json_error($result->message);
            }
        } catch (Exception $e) {
            wp_send_json_error('Błąd serwera: ' . $e->getMessage());
        }
    }
    
    public function ajax_get_resource() {
        Logger::info('UJC: ajax_get_resource started');
        
        if (!$this->verify_nonce()) {
            Logger::error('UJC: nonce verification failed');
            return;
        }
        
        if (!$this->check_permissions()) {
            Logger::error('UJC: permission check failed');
            return;
        }
        
        try {
            $resource_id = intval($_POST['resource_id'] ?? 0);
            Logger::info('UJC: Loading resource ID: ' . $resource_id);
            
            $resource = $this->getResourceByIdUseCase->execute($resource_id);
            Logger::info('UJC: Resource data retrieved: ' . print_r($resource, true));
            
            if ($resource) {
                wp_send_json_success($resource);
            } else {
                Logger::error('UJC: No resource found for ID: ' . $resource_id);
                wp_send_json_error('Nie znaleziono zasobu');
            }
        } catch (Exception $e) {
            Logger::error('UJC: Exception in ajax_get_resource: ' . $e->getMessage());
            wp_send_json_error('Błąd serwera: ' . $e->getMessage());
        }
    }
    
    public function ajax_update_resource() {
        if (!$this->verify_nonce()) {
            wp_send_json_error('Błąd weryfikacji bezpieczeństwa.');
            return;
        }
        
        if (!$this->check_permissions()) {
            wp_send_json_error('Brak uprawnień.');
            return;
        }
        
        try {
            $resource_id = intval($_POST['resource_id'] ?? 0);
            
            if (!$resource_id) {
                wp_send_json_error('Nieprawidłowy ID zasobu');
                return;
            }
            
            $data = $this->sanitize_resource_data();
            
            // Create component models first
            $propertyPart = null;
            if (!empty($data['property_part_title']) && isset($data['property_part_price'])) {
                $propertyPart = new PropertyPartFormData(
                    title: $data['property_part_title'],
                    designation: $data['property_part_designation'] ?? '',
                    price: (float)$data['property_part_price']
                );
            }
            
            $belongingRoom = null;
            if (!empty($data['belonging_room_title']) && isset($data['belonging_room_price'])) {
                $belongingRoom = new BelongingRoomFormData(
                    title: $data['belonging_room_title'],
                    designation: $data['belonging_room_designation'] ?? '',
                    price: (float)$data['belonging_room_price']
                );
            }
            
            $usageRight = null;
            if (!empty($data['usage_right_title']) && isset($data['usage_right_price'])) {
                $usageRight = new UsageRightFormData(
                    title: $data['usage_right_title'],
                    price: (float)$data['usage_right_price']
                );
            }
            
            $otherService = null;
            if (!empty($data['other_service_title']) && isset($data['other_service_price'])) {
                $otherService = new OtherServiceFormData(
                    title: $data['other_service_title'],
                    price: (float)$data['other_service_price']
                );
            }
            
            // Create ResourceFormData with components included
            $formData = new ResourceFormData(
                rodzaj_nieruchomosci: PropertyType::from($data['rodzaj_nieruchomosci']),
                nr_lokalu: $data['nr_lokalu'],
                powierzchnia_uzytkowa: (float)$data['powierzchnia_uzytkowa'],
                cena_m2: $data['cena_m2'] !== null ? (float)$data['cena_m2'] : null,
                cena_calkowita: (float)$data['cena_calkowita'],
                cena_z_dodatkami: (float)$data['cena_z_dodatkami'],
                status: ResourceStatus::from($data['status']),
                property_part: $propertyPart,
                belonging_room: $belongingRoom,
                usage_right: $usageRight,
                other_service: $otherService
            );
            
            $result = $this->updateResourceUseCase->execute($formData, $resource_id);
            
            if ($result->isSuccess) {
                wp_send_json_success($result->message);
            } else {
                wp_send_json_error($result->message);
            }
        } catch (Exception $e) {
            wp_send_json_error('Błąd serwera: ' . $e->getMessage());
        }
    }
    
    public function ajax_delete_resource() {
        if (!$this->verify_nonce()) {
            wp_send_json_error('Błąd weryfikacji bezpieczeństwa.');
            return;
        }
        
        if (!$this->check_permissions()) {
            wp_send_json_error('Brak uprawnień.');
            return;
        }
        
        try {
            $resource_id = intval($_POST['resource_id'] ?? 0);
            
            if (!$resource_id) {
                wp_send_json_error('Nieprawidłowy ID zasobu');
                return;
            }
            
            $result = $this->deleteResourceUseCase->execute($resource_id);
            
            if ($result->isSuccess) {
                wp_send_json_success($result->message);
            } else {
                wp_send_json_error($result->message);
            }
        } catch (Exception $e) {
            wp_send_json_error('Błąd serwera: ' . $e->getMessage());
        }
    }
    
    public function render() {
        // Nie używane - to jest komponent modalny
    }
}