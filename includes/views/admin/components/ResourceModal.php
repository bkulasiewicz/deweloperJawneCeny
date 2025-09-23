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
    
    public function __construct(
        CreateResourceUseCase $createResourceUseCase,
        UpdateResourceUseCase $updateResourceUseCase,
        GetResourceByIdUseCase $getResourceByIdUseCase,
        DeleteResourceUseCase $deleteResourceUseCase
    ) {
        $this->createResourceUseCase = $createResourceUseCase;
        $this->updateResourceUseCase = $updateResourceUseCase;
        $this->getResourceByIdUseCase = $getResourceByIdUseCase;
        $this->deleteResourceUseCase = $deleteResourceUseCase;
        
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
                
                <form id="resource-modal-form" method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('ujc_admin_nonce', 'nonce'); ?>
                    <input type="hidden" id="resource-id" name="resource_id" value="">
                    <input type="hidden" id="modal-action" name="modal_action" value="add">
                    
                    <div class="ujc-modal-body">
                        <div class="modal-columns">
                            <!-- Lewa kolumna - Dane podstawowe i cenowe -->
                            <div class="modal-column-left">
                                <h3 style="margin-top: 0;">Dane podstawowe</h3>
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
                                            <div id="service-premises-info" style="display: none; margin-top: 8px; font-size: 14px; color: #555;">
                                                Lokale usługowe nie są raportowane do ministerstwa
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="modal-status">Status *</label></th>
                                        <td>
                                            <select id="modal-status" name="status" required>
                                                <?php foreach (ResourceStatus::cases() as $status): ?>
                                                    <option value="<?php echo esc_attr($status->value); ?>">
                                                        <?php echo esc_html($status->getDisplayText()); ?>
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
                                            <input type="number" id="modal-powierzchnia_uzytkowa" name="powierzchnia_uzytkowa" required step="0.01" min="0" class="regular-text" value=""> m²
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
                                </table>

                                <!-- Cena finalna na dole lewej kolumny -->
                                <div id="final-price-section" style="margin-top: 20px;">
                                    <h3>Cena finalna</h3>
                                    <table class="form-table">
                                        <tr>
                                            <th><label for="modal-cena_z_dodatkami">Cena uwzględniająca inne składowe</label></th>
                                            <td>
                                                <input type="number" id="modal-cena_z_dodatkami" name="cena_z_dodatkami" step="0.01" min="0" class="regular-text" readonly> zł
                                                <p class="description">Cena końcowa uwzględniająca wszystkie składowe zgodnie z art. 19a ust. 1 (opcjonalne)</p>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Prawa kolumna - Informacje dodatkowe -->
                            <div class="modal-column-right">
                                <h3 style="margin-top: 0;">Informacje dodatkowe</h3>

                                <!-- Marketing Fields Section - shown conditionally -->
                                <div id="marketing-fields-section" style="display: none; margin-top: 0px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; background: #f0f8ff;">
                                    <h4>Pola marketingowe</h4>
                                    <p class="description">Pola nieobowiązkowe - nie są raportowane do ministerstwa</p>
                            
                            <table class="form-table">
                                <tr id="floor-field-row" style="display: none;">
                                    <th><label for="modal-floor_number">Piętro (opcjonalne)</label></th>
                                    <td><input type="number" id="modal-floor_number" name="floor_number" class="regular-text" value="" placeholder="np. 2"></td>
                                </tr>
                                <tr id="rooms-field-row" style="display: none;">
                                    <th><label for="modal-room_count">Liczba pokoi (opcjonalne)</label></th>
                                    <td><input type="number" id="modal-room_count" name="room_count" class="regular-text" value="" placeholder="np. 3"></td>
                                </tr>
                                <tr id="description-field-row" style="display: none;">
                                    <th><label for="modal-additional_description">Dodatkowy opis (opcjonalne)</label></th>
                                    <td><textarea id="modal-additional_description" name="additional_description" class="regular-text" rows="3" placeholder="Dodatkowe informacje marketingowe"></textarea></td>
                                </tr>
                                <tr id="garden-field-row" style="display: none;">
                                    <th><label for="modal-garden_area">Ogród [m²] (opcjonalne)</label></th>
                                    <td><input type="number" id="modal-garden_area" name="garden_area" step="0.01" class="regular-text" value="" placeholder="np. 25.5"> m²</td>
                                </tr>
                                <tr id="floor-plan-field-row" style="display: none;">
                                    <th><label for="modal-floor_plan_pdf" id="floor-plan-label">Plan mieszkania (PDF) (opcjonalne)</label></th>
                                    <td>
                                        <input type="file" id="modal-floor_plan_pdf" name="floor_plan_pdf" accept=".pdf" class="regular-text">
                                        <p class="description">Akceptowane formaty: PDF (opcjonalne)</p>
                                        <div id="current-floor-plan" style="display: none; margin-top: 5px;">
                                            <strong>Aktualny plik:</strong> <span id="current-floor-plan-name"></span>
                                            <button type="button" id="remove-floor-plan" class="button-link-delete" style="margin-left: 10px;">Usuń</button>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                                </div>

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
                            </div>
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
        <!-- JavaScript dla modala -->
        <script>
        jQuery(document).ready(function($) {
            // Toggle marketing fields based on investment configuration
            function toggleMarketingFields(investmentConfig) {
                // Get currently selected property type
                const selectedPropertyType = $('#modal-rodzaj_nieruchomosci').val();
                
                // Types for which we show additional information section
                const allowedTypes = ['residential_unit', 'service_premises', 'single_family_house'];
                const isAllowedType = allowedTypes.includes(selectedPropertyType);
                
                const hasAnyMarketingField = investmentConfig.show_floor_field || 
                                           investmentConfig.show_rooms_field || 
                                           investmentConfig.show_description_field || 
                                           investmentConfig.show_garden_field || 
                                           investmentConfig.show_floor_plan_field;
                
                // Show section only if type is allowed AND fields are enabled
                const showSection = isAllowedType && hasAnyMarketingField;
                $('#marketing-fields-section').toggle(showSection);
                
                // Show/hide individual fields
                $('#floor-field-row').toggle(investmentConfig.show_floor_field);
                $('#rooms-field-row').toggle(investmentConfig.show_rooms_field);
                $('#description-field-row').toggle(investmentConfig.show_description_field);
                $('#garden-field-row').toggle(investmentConfig.show_garden_field);
                $('#floor-plan-field-row').toggle(investmentConfig.show_floor_plan_field);
                
            }
            
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
                        
                        // Show/hide marketing fields based on investment config
                        toggleMarketingFields(config);
                        
                        // Final price section is always visible now
                        $('#final-price-section').show();
                        
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
                        $('#current-floor-plan').hide(); // Hide existing file section
                        $('#modal-floor_plan_pdf').show().prop('disabled', false); // Show and enable file input for new resource
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

                    // For add mode, apply property type logic with default value
                    if (mode === 'add') {
                        applyPropertyTypeLogic($('#modal-rodzaj_nieruchomosci').val());
                    }
                });
            };
            
            // Załaduj dane zasobu do edycji
            function loadResourceData(resourceId) {
                const nonce = typeof ujc_ajax !== 'undefined' ? ujc_ajax.nonce : ($('#ujc-nonce').length ? $('#ujc-nonce').val() : '');
                
                
                $.post(typeof ujc_ajax !== 'undefined' ? ujc_ajax.ajax_url : ajaxurl, {
                    action: 'ujc_get_resource',
                    resource_id: resourceId,
                    nonce: nonce
                }, function(response) {
                    if (response.success) {
                        const data = response.data;
                        $('#modal-rodzaj_nieruchomosci').val(data.rodzaj_nieruchomosci);
                        $('#modal-nr_lokalu').val(data.nr_lokalu);
                        $('#modal-powierzchnia_uzytkowa').val(data.powierzchnia_uzytkowa || '');
                        $('#modal-cena_m2').val(data.cena_m2);
                        $('#modal-cena_calkowita').val(data.cena_calkowita || '');
                        $('#modal-cena_z_dodatkami').val(data.cena_z_dodatkami || '');
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Enum constant value is safe
                        $('#modal-status').val(data.status || '<?php echo esc_js(ResourceStatus::cases()[0]->value); ?>');
                        
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
                        
                        // Load marketing fields
                        $('#modal-floor_number').val(data.floor_number ?? '');
                        $('#modal-room_count').val(data.room_count ?? '');
                        $('#modal-additional_description').val(data.additional_description ?? '');
                        $('#modal-garden_area').val(data.garden_area ?? '');
                        
                        // Handle floor plan PDF if exists
                        if (data.floor_plan_pdf) {
                            $('#current-floor-plan').show();
                            $('#current-floor-plan-name').text(data.floor_plan_pdf);
                            $('#modal-floor_plan_pdf').hide().prop('disabled', true); // Hide and disable file input when file exists
                        } else {
                            $('#current-floor-plan').hide();
                            $('#modal-floor_plan_pdf').show().prop('disabled', false); // Show and enable file input when no file
                        }
                        
                        // Przelicz cenę finalną
                        calculateFinalPrice();

                        // Apply property type logic after loading data
                        applyPropertyTypeLogic(data.rodzaj_nieruchomosci);
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
            
            // Generic handler for add buttons - with proper ID mapping
            const buttonToSectionMap = {
                'add-property-part-btn': 'property-parts-section',
                'add-belonging-room-btn': 'belonging-rooms-section', 
                'add-usage-rights-btn': 'usage-rights-section',
                'add-other-services-btn': 'other-services-section'
            };
            
            $('[id^="add-"][id$="-btn"]').on('click', function() {
                const buttonId = $(this).attr('id');
                const sectionId = buttonToSectionMap[buttonId];
                
                if (sectionId) {
                    $(this).hide();
                    $('#' + sectionId).show();
                    calculateFinalPrice();
                }
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
            
            // Section configuration for data-driven approach
            const sectionConfig = {
                'property-parts': {
                    fields: ['#property-part-title', '#property-part-designation', '#property-part-price'],
                    button: '#add-property-part-btn'
                },
                'belonging-rooms': {
                    fields: ['#belonging-room-title', '#belonging-room-designation', '#belonging-room-price'],
                    button: '#add-belonging-room-btn'
                },
                'usage-rights': {
                    fields: ['#usage-rights-title', '#usage-rights-price'],
                    button: '#add-usage-rights-btn'
                },
                'other-services': {
                    fields: ['#other-services-title', '#other-services-price'],
                    button: '#add-other-services-btn'
                }
            };
            
            // Clear form data for a specific section type
            function clearSectionData(sectionType) {
                const config = sectionConfig[sectionType];
                if (config) {
                    $(config.fields.join(', ')).val('');
                }
            }
            
            // Show add button for the section type
            function showAddButtonIfEnabled(sectionType) {
                const config = sectionConfig[sectionType];
                if (config) {
                    $(config.button).show();
                }
            }
            
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
                
                // Always calculate final price if we have base price
                if (cenaCalkowita > 0) {
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
                
                // Create FormData to handle file uploads
                const formData = new FormData(this);
                formData.append('action', action);
                
                const $submitBtn = $('#modal-submit-btn');
                const originalText = $submitBtn.text();
                $submitBtn.text('Zapisywanie...').prop('disabled', true);
                
                $.ajax({
                    url: typeof ujc_ajax !== 'undefined' ? ujc_ajax.ajax_url : ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert('✅ ' + response.data);
                            closeModal();
                            if (typeof loadResourcesList === 'function') {
                                loadResourcesList();
                            }
                        } else {
                            alert('❌ ' + (response.data || 'Błąd podczas zapisywania'));
                        }
                    },
                    error: function() {
                        alert('❌ Błąd połączenia');
                    },
                    complete: function() {
                        $submitBtn.text(originalText).prop('disabled', false);
                    }
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
            
            // Property type field visibility and validation logic
            function applyPropertyTypeLogic(propertyType) {
                const $cenaM2Field = $('#modal-cena_m2');
                const $cenaM2Row = $cenaM2Field.closest('tr');
                const $servicePremisesInfo = $('#service-premises-info');
                const $powierzchniaField = $('#modal-powierzchnia_uzytkowa');
                const $powierzchniaRow = $powierzchniaField.closest('tr');

                // Hide service premises info by default
                $servicePremisesInfo.hide();

                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- All PropertyType enum values are safe
                switch(propertyType) {
                    case '<?php echo esc_js(PropertyType::PARKING_SPACE->value); ?>':
                        // Hide cena m² and powierzchnia for parking spaces
                        $cenaM2Row.hide();
                        $cenaM2Field.removeAttr('required');
                        $cenaM2Field.val(''); // Clear value
                        $powierzchniaRow.hide();
                        $powierzchniaField.removeAttr('required');
                        $powierzchniaField.val(''); // Clear value
                        // Labels: Pola ukryte - no label updates needed
                        break;

                    case '<?php echo esc_js(PropertyType::SERVICE_PREMISES->value); ?>':
                        // Show service premises info, show all other fields
                        $servicePremisesInfo.show();
                        $cenaM2Row.show();
                        $cenaM2Field.attr('required', 'required');
                        $powierzchniaRow.show();
                        $powierzchniaField.attr('required', 'required');
                        // Labels: default (required)
                        $('label[for="modal-powierzchnia_uzytkowa"]').html('Powierzchnia użytkowa [m²] *');
                        $('label[for="modal-cena_m2"]').html('Cena m² *');
                        break;

                    case '<?php echo esc_js(PropertyType::STORAGE_ROOM->value); ?>':
                        // Dla komórki powierzchnia i cena m² są opcjonalne
                        $cenaM2Row.show();
                        $cenaM2Field.removeAttr('required');
                        $powierzchniaRow.show();
                        $powierzchniaField.removeAttr('required');
                        // Labels: optional
                        $('label[for="modal-powierzchnia_uzytkowa"]').html('Powierzchnia użytkowa [m²] (opcjonalne)');
                        $('label[for="modal-cena_m2"]').html('Cena m² (opcjonalne)');
                        break;

                    case '<?php echo esc_js(PropertyType::RESIDENTIAL_UNIT->value); ?>':
                    case '<?php echo esc_js(PropertyType::SINGLE_FAMILY_HOUSE->value); ?>':
                        // Show and require all fields for residential properties
                        $cenaM2Row.show();
                        $cenaM2Field.attr('required', 'required');
                        $powierzchniaRow.show();
                        $powierzchniaField.attr('required', 'required');
                        // Labels: default (required)
                        $('label[for="modal-powierzchnia_uzytkowa"]').html('Powierzchnia użytkowa [m²] *');
                        $('label[for="modal-cena_m2"]').html('Cena m² *');
                        break;

                    case '<?php echo esc_js(PropertyType::GARAGE->value); ?>':
                        // Dla garażu powierzchnia i cena m² są opcjonalne
                        $cenaM2Row.show();
                        $cenaM2Field.removeAttr('required');
                        $powierzchniaRow.show();
                        $powierzchniaField.removeAttr('required');
                        // Labels: optional
                        $('label[for="modal-powierzchnia_uzytkowa"]').html('Powierzchnia użytkowa [m²] (opcjonalne)');
                        $('label[for="modal-cena_m2"]').html('Cena m² (opcjonalne)');
                        break;

                    default:
                        // Show and require all fields for residential properties
                        $cenaM2Row.show();
                        $cenaM2Field.attr('required', 'required');
                        $powierzchniaRow.show();
                        $powierzchniaField.attr('required', 'required');
                        // Labels: default (required)
                        $('label[for="modal-powierzchnia_uzytkowa"]').html('Powierzchnia użytkowa [m²] *');
                        $('label[for="modal-cena_m2"]').html('Cena m² *');
                        break;
                }
            }

            
            // Handle floor plan file removal
            $('#remove-floor-plan').on('click', function() {
                if (confirm('Czy na pewno chcesz usunąć aktualny plik planu mieszkania?')) {
                    $('#current-floor-plan').hide();
                    $('#current-floor-plan-name').text('');
                    $('#modal-floor_plan_pdf').val('').show().prop('disabled', false); // Show and enable file input after removal
                    // Set a hidden flag to indicate file should be removed
                    if (!$('#shouldRemovePdfFloorPlan-flag').length) {
                        $('#resource-modal-form').append('<input type="hidden" id="shouldRemovePdfFloorPlan-flag" name="shouldRemovePdfFloorPlan" value="true">');
                    }
                }
            });
            
            // Clear removal flag when new file is selected
            $('#modal-floor_plan_pdf').on('change', function() {
                $('#shouldRemovePdfFloorPlan-flag').remove();
            });
            
            // Initialize all functionality
            setupAutoCalculations();

            // Setup property type change event handler
            $('#modal-rodzaj_nieruchomosci').on('change', function() {
                const selectedType = $(this).val();
                applyPropertyTypeLogic(selectedType);

                // Also toggle marketing fields based on property type
                if (window.investmentConfig) {
                    toggleMarketingFields(window.investmentConfig);
                }
            });
        });
        </script>

        <!-- CSS styles for two-column layout -->
        <style>
        /* Two-column layout styles */
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

        /* Adjust modal width for two columns */
        #ujc-resource-modal .ujc-modal-content {
            max-width: 1200px;
            width: 95%;
        }

        /* Responsive behavior */
        @media (max-width: 768px) {
            .modal-columns {
                flex-direction: column;
                gap: 20px;
            }

            .modal-column-left {
                border-right: none;
                padding-right: 0;
                border-bottom: 1px solid #e1e1e1;
                padding-bottom: 20px;
            }
        }
        </style>
        <?php
    }
    
    /**
     * Sanitize POST data with debug logging for marketing fields
     */
    protected function sanitize_post_data($fields = []) {
        $data = [];
        foreach ($fields as $field => $sanitize_callback) {
            $value = $_POST[$field] ?? '';


            // Call sanitization function
            if (is_callable($sanitize_callback)) {
                $data[$field] = call_user_func($sanitize_callback, $value);
            } else {
                $data[$field] = sanitize_text_field($value);
            }

        }
        return $data;
    }

    private function sanitize_resource_data() {
        return $this->sanitize_post_data([
            'rodzaj_nieruchomosci' => 'sanitize_text_field',
            'nr_lokalu' => 'sanitize_text_field',
            'powierzchnia_uzytkowa' => [$this, 'sanitize_nullable_float'],
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
            'other_service_price' => [$this, 'sanitize_nullable_float'],
            
            // Marketing fields
            'floor_number' => [$this, 'sanitize_nullable_int'],
            'room_count' => [$this, 'sanitize_nullable_int'],
            'additional_description' => 'sanitize_textarea_field',
            'garden_area' => [$this, 'sanitize_nullable_float']
        ]);
    }
    
    private function sanitize_nullable_float($value) {
        Logger::info('UJC: sanitize_nullable_float called with value: ' . var_export($value, true));
        // If empty string or whitespace, return null
        if ($value === '' || $value === null || trim($value) === '') {
            Logger::info('UJC: sanitize_nullable_float returning null for empty value');
            return null;
        }
        $result = floatval($value);
        Logger::info('UJC: sanitize_nullable_float returning: ' . $result);
        return $result;
    }
    
    private function sanitize_nullable_int($value) {
        Logger::info('UJC: sanitize_nullable_int called with value: ' . var_export($value, true));
        // If empty string or whitespace, return null
        if ($value === '' || $value === null || trim($value) === '') {
            Logger::info('UJC: sanitize_nullable_int returning null for empty value');
            return null;
        }
        $result = intval($value);
        Logger::info('UJC: sanitize_nullable_int returning: ' . $result);
        return $result;
    }

    private function sanitize_nullable_text($value) {
        Logger::info('UJC: sanitize_nullable_text called with value: ' . var_export($value, true));
        // If empty string or whitespace, return null
        if (empty($value) || trim($value) === '') {
            Logger::info('UJC: sanitize_nullable_text returning null for empty value');
            return null;
        }
        $result = sanitize_text_field($value);
        Logger::info('UJC: sanitize_nullable_text returning: ' . $result);
        return $result;
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
    
    /**
     * Validates AJAX request (nonce and permissions)
     */
    private function validateAjaxRequest(): bool {
        if (!$this->verify_nonce()) {
            wp_send_json_error('Błąd weryfikacji bezpieczeństwa.');
            return false;
        }
        
        if (!$this->check_permissions()) {
            wp_send_json_error('Brak uprawnień.');
            return false;
        }
        
        return true;
    }
    
    /**
     * Creates component models from sanitized data
     */
    private function createComponentModels($data): array {
        Logger::info('UJC: createComponentModels - starting');
        $components = [];
        
        // Property Part
        $propertyPartPrice = isset($data['property_part_price']) ? (float)$data['property_part_price'] : 0;
        Logger::info('UJC: createComponentModels - property part price: ' . $propertyPartPrice);
        Logger::info('UJC: createComponentModels - property part title: ' . ($data['property_part_title'] ?? 'NOT_SET'));
        
        if ($propertyPartPrice > 0) {
            try {
                $components['property_part'] = new PropertyPartFormData(
                    title: $data['property_part_title'] ?? '',
                    designation: $data['property_part_designation'] ?? '',
                    price: $propertyPartPrice
                );
                Logger::info('UJC: createComponentModels - PropertyPartFormData created successfully');
            } catch (Exception $e) {
                Logger::error('UJC: createComponentModels - PropertyPartFormData creation failed: ' . $e->getMessage());
                throw $e;
            }
        } else {
            $components['property_part'] = null;
            Logger::info('UJC: createComponentModels - property part set to null (price <= 0)');
        }
        
        // Belonging Room
        $belongingRoomPrice = isset($data['belonging_room_price']) ? (float)$data['belonging_room_price'] : 0;
        $components['belonging_room'] = ($belongingRoomPrice > 0) ? new BelongingRoomFormData(
            title: $data['belonging_room_title'],
            designation: $data['belonging_room_designation'] ?? '',
            price: $belongingRoomPrice
        ) : null;
        
        // Usage Right
        $usageRightPrice = isset($data['usage_right_price']) ? (float)$data['usage_right_price'] : 0;
        $components['usage_right'] = ($usageRightPrice > 0) ? new UsageRightFormData(
            title: $data['usage_right_title'],
            price: $usageRightPrice
        ) : null;
        
        // Other Service
        $otherServicePrice = isset($data['other_service_price']) ? (float)$data['other_service_price'] : 0;
        $components['other_service'] = ($otherServicePrice > 0) ? new OtherServiceFormData(
            title: $data['other_service_title'],
            price: $otherServicePrice
        ) : null;
        
        return $components;
    }
    
    /**
     * Creates ResourceFormData from sanitized data and components
     */
    private function createResourceFormData($data): ResourceFormData {
        Logger::info('UJC: createResourceFormData - starting with data keys: ' . implode(', ', array_keys($data)));
        Logger::info('UJC: createResourceFormData - rodzaj_nieruchomosci value: ' . $data['rodzaj_nieruchomosci']);
        Logger::info('UJC: createResourceFormData - status value: ' . $data['status']);
        
        $components = $this->createComponentModels($data);
        Logger::info('UJC: createResourceFormData - components created successfully');
        
        try {
            $propertyType = PropertyType::from($data['rodzaj_nieruchomosci']);
            Logger::info('UJC: createResourceFormData - PropertyType conversion successful');
        } catch (Exception $e) {
            Logger::error('UJC: createResourceFormData - PropertyType conversion failed: ' . $e->getMessage());
            throw $e;
        }
        
        try {
            $resourceStatus = ResourceStatus::from($data['status']);
            Logger::info('UJC: createResourceFormData - ResourceStatus conversion successful');
        } catch (Exception $e) {
            Logger::error('UJC: createResourceFormData - ResourceStatus conversion failed: ' . $e->getMessage());
            throw $e;
        }
        
        Logger::info('UJC: createResourceFormData - About to create ResourceFormData object');
        Logger::info('UJC: createResourceFormData - Data for constructor: nr_lokalu=' . $data['nr_lokalu'] . ', powierzchnia=' . $data['powierzchnia_uzytkowa'] . ', cena_m2=' . ($data['cena_m2'] ?? 'NULL'));
        
        try {

            $resourceFormData = new ResourceFormData(
                rodzaj_nieruchomosci: $propertyType,
                nr_lokalu: $data['nr_lokalu'],
                powierzchnia_uzytkowa: $data['powierzchnia_uzytkowa'],
                cena_m2: $data['cena_m2'] !== null ? (float)$data['cena_m2'] : null,
                cena_calkowita: (float)$data['cena_calkowita'],
                cena_z_dodatkami: (float)$data['cena_z_dodatkami'],
                status: $resourceStatus,
                property_part: $components['property_part'],
                belonging_room: $components['belonging_room'],
                usage_right: $components['usage_right'],
                other_service: $components['other_service'],
                floor_number: $data['floor_number'] !== null ? (int)$data['floor_number'] : null,
                room_count: $data['room_count'] !== null ? (int)$data['room_count'] : null,
                additional_description: $data['additional_description'],
                garden_area: $data['garden_area'] !== null ? (float)$data['garden_area'] : null,
                floor_plan_pdf: ($data['floor_plan_pdf'] ?? null) === '' ? null : ($data['floor_plan_pdf'] ?? null)
            );

            
            Logger::info('UJC: createResourceFormData - ResourceFormData object created successfully');
            return $resourceFormData;
            
        } catch (Exception $e) {
            Logger::error('UJC: createResourceFormData - ResourceFormData constructor failed: ' . $e->getMessage());
            Logger::error('UJC: createResourceFormData - Exception trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
    
    public function ajax_save_resource() {
        Logger::info('UJC: ajax_save_resource started - handler is being called');
        
        if (!$this->validateAjaxRequest()) {
            Logger::error('UJC: validateAjaxRequest failed');
            return;
        }
        
        Logger::info('UJC: ajax_save_resource - validation passed, proceeding with save');
        
        try {
            $data = $this->sanitize_resource_data();
            
            // Handle file upload if present
            $uploaded_filename = $this->handle_pdf_upload();
            if ($uploaded_filename) {
                $data['floor_plan_pdf'] = $uploaded_filename;
                Logger::info('UJC: ajax_save_resource - PDF uploaded successfully: ' . $uploaded_filename);
            }
            
            $formData = $this->createResourceFormData($data);
            Logger::info('UJC: ajax_save_resource - formData created successfully');
            
            $result = $this->createResourceUseCase->execute($formData);
            
            if ($result->isSuccess) {
                wp_send_json_success($result->message);
            } else {
                wp_send_json_error($result->message);
            }
        } catch (Exception $e) {
            Logger::error('UJC: ajax_save_resource - Exception caught: ' . $e->getMessage());
            Logger::error('UJC: ajax_save_resource - Exception trace: ' . $e->getTraceAsString());
            wp_send_json_error('Błąd serwera: ' . $e->getMessage());
        }
    }
    
    public function ajax_get_resource() {
        Logger::info('UJC: ajax_get_resource started');
        
        if (!$this->validateAjaxRequest()) {
            Logger::error('UJC: validation failed');
            return;
        }
        
        try {
            $resource_id = intval($_POST['resource_id'] ?? 0);
            Logger::info('UJC: Loading resource ID: ' . $resource_id);
            
            $resource = $this->getResourceByIdUseCase->execute($resource_id);
            
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
        if (!$this->validateAjaxRequest()) {
            return;
        }
        
        try {
            $resource_id = intval($_POST['resource_id'] ?? 0);

            if (!$resource_id) {
                wp_send_json_error('Nieprawidłowy ID zasobu');
                return;
            }

            $data = $this->sanitize_resource_data();

            // Handle PDF field in 3 scenarios
            $shouldRemovePdf = ($_POST['shouldRemovePdfFloorPlan'] ?? 'false') === 'true';

            if ($shouldRemovePdf) {
                // Scenario 1: User wants to remove PDF file
                $data['floor_plan_pdf'] = null;
            } elseif (isset($_FILES['floor_plan_pdf']) && $_FILES['floor_plan_pdf']['error'] === 0) {
                // Scenario 2: New file uploaded
                $data['floor_plan_pdf'] = $this->handle_pdf_upload();
            } else {
                // Scenario 3: No changes - set marker for UseCase
                $data['floor_plan_pdf'] = '##NO_CHANGE##';
            }

            $formData = $this->createResourceFormData($data);
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
        if (!$this->validateAjaxRequest()) {
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
    
    /**
     * Handle PDF file upload for floor plans
     * 
     * @return string|null Uploaded filename or null if no file uploaded
     * @throws Exception If upload fails
     */
    private function handle_pdf_upload(): ?string {
        // Check if file was uploaded
        if (!isset($_FILES['floor_plan_pdf']) || $_FILES['floor_plan_pdf']['error'] === UPLOAD_ERR_NO_FILE) {
            Logger::info('UJC: handle_pdf_upload - No file uploaded');
            return null;
        }

        $file = $_FILES['floor_plan_pdf'];
        Logger::info('UJC: handle_pdf_upload - File upload detected: ' . $file['name'] . ' (' . $file['size'] . ' bytes)');

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'Plik jest za duży (limit serwera)',
                UPLOAD_ERR_FORM_SIZE => 'Plik jest za duży (limit formularza)',
                UPLOAD_ERR_PARTIAL => 'Plik został przesłany tylko częściowo',
                UPLOAD_ERR_NO_TMP_DIR => 'Brak folderu tymczasowego',
                UPLOAD_ERR_CANT_WRITE => 'Nie można zapisać pliku na dysku',
                UPLOAD_ERR_EXTENSION => 'Przesyłanie pliku zostało zatrzymane przez rozszerzenie'
            ];
            $error_message = $error_messages[$file['error']] ?? 'Nieznany błąd przesyłania';
            Logger::error('UJC: handle_pdf_upload - Upload error: ' . $error_message);
            throw new Exception(esc_html($error_message));
        }

        // Validate file type
        $allowed_types = ['application/pdf'];
        $file_type = $file['type'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_type, $allowed_types) || $file_extension !== 'pdf') {
            Logger::error('UJC: handle_pdf_upload - Invalid file type: ' . $file_type . ', extension: ' . $file_extension);
            throw new Exception('Można przesyłać tylko pliki PDF');
        }

        // Validate file size (10MB limit)
        $max_size = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $max_size) {
            Logger::error('UJC: handle_pdf_upload - File too large: ' . $file['size'] . ' bytes');
            throw new Exception('Plik jest za duży. Maksymalny rozmiar to 10MB');
        }

        // Generate unique filename
        $upload_dir = wp_upload_dir();
        $ujc_data_dir = $upload_dir['basedir'] . '/ujc-data';

        // Ensure directory exists
        if (!is_dir($ujc_data_dir)) {
            wp_mkdir_p($ujc_data_dir);
        }

        // Generate filename based on property type and identifier
        $property_type = sanitize_text_field($_POST['rodzaj_nieruchomosci'] ?? '');
        $identifier = sanitize_text_field($_POST['nr_lokalu'] ?? '');

        // Get property type name for filename
        $property_type_name = $this->getPropertyTypeNameForFilename($property_type);

        // Sanitize identifier for filename (remove special characters)
        $safe_identifier = preg_replace('/[^a-zA-Z0-9_-]/', '_', $identifier);

        // Generate filename: propertytype_identifier.pdf
        $new_filename = $property_type_name;
        if (!empty($safe_identifier)) {
            $new_filename .= '_' . $safe_identifier;
        }
        $new_filename .= '.pdf';

        $target_path = $ujc_data_dir . '/' . $new_filename;

        Logger::info('UJC: handle_pdf_upload - Target path: ' . $target_path);

        // Check if file already exists and handle overwrite
        if (file_exists($target_path)) {
            Logger::info('UJC: handle_pdf_upload - File already exists, will be overwritten: ' . $new_filename);
        }

        // Move uploaded file to target directory
        // Safe: move_uploaded_file is the only secure way to handle uploaded files
        // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found
        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
            Logger::error('UJC: handle_pdf_upload - Failed to move uploaded file');
            throw new Exception('Nie można zapisać przesłanego pliku');
        }

        // Set proper file permissions
        // Safe: Setting proper file permissions after upload
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
        chmod($target_path, 0644);

        Logger::info('UJC: handle_pdf_upload - File uploaded successfully: ' . $new_filename);
        return $new_filename;
    }
    
    /**
     * Get property type name for filename generation
     *
     * @param string $property_type Property type enum value
     * @return string Safe filename part
     */
    private function getPropertyTypeNameForFilename(string $property_type): string {
        switch ($property_type) {
            case PropertyType::RESIDENTIAL_UNIT->value:
                return 'mieszkanie';
            case PropertyType::SINGLE_FAMILY_HOUSE->value:
                return 'dom';
            case PropertyType::SERVICE_PREMISES->value:
                return 'lokal_uslugowy';
            case PropertyType::PARKING_SPACE->value:
                return 'miejsce_postojowe';
            case PropertyType::STORAGE_ROOM->value:
                return 'komorka';
            case PropertyType::GARAGE->value:
                return 'garaz';
            default:
                return 'lokal';
        }
    }

    /**
     * Remove old PDF file from ujc-data directory
     *
     * @param string $filename Filename to remove
     */
    private function remove_old_pdf_file(string $filename): void {
        if (!$filename) {
            return;
        }
        
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/ujc-data/' . $filename;
        
        if (file_exists($file_path)) {
            if (wp_delete_file($file_path)) {
                Logger::info('UJC: remove_old_pdf_file - Old file removed: ' . $filename);
            } else {
                Logger::error('UJC: remove_old_pdf_file - Failed to remove old file: ' . $filename);
            }
        }
    }


    public function render() {
        // Nie używane - to jest komponent modalny
    }
}