<?php

namespace JawneCeny;

use JawneCeny_AdminPage;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Komponent modalu dla zasobów - Single Responsibility Principle
 * Odpowiada tylko za zarządzanie modalem dodawania/edycji zasobów
 */
class ResourceModal extends JawneCeny_AdminPage {
    
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
        
        add_action('wp_ajax_jawneceny_save_resource', [$this, 'ajax_save_resource']);
        add_action('wp_ajax_jawneceny_get_resource', [$this, 'ajax_get_resource']);
        add_action('wp_ajax_jawneceny_update_resource', [$this, 'ajax_update_resource']);
        add_action('wp_ajax_jawneceny_delete_resource', [$this, 'ajax_delete_resource']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        parent::__construct();
    }

    public function enqueue_assets() {
        $viewPath = JAWNECENY_PLUGIN_URL . 'includes/views/admin/components/resource-modal/';

        wp_enqueue_style(
            'resource-modal',
            $viewPath . 'ResourceModal.css',
            [],
            JAWNECENY_VERSION
        );

        wp_enqueue_script(
            'resource-modal',
            $viewPath . 'ResourceModal.js',
            ['jquery'],
            JAWNECENY_VERSION,
            true
        );

        wp_localize_script('resource-modal', 'resourceModalData', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('jawneceny_resource_modal'),
            'investment_nonce' => wp_create_nonce('jawneceny_investment_modal'),
            'defaultStatus' => esc_js(ResourceStatus::cases()[0]->value),
            'propertyTypes' => [
                'PARKING_SPACE' => esc_js(PropertyType::PARKING_SPACE->value),
                'SERVICE_PREMISES' => esc_js(PropertyType::SERVICE_PREMISES->value),
                'STORAGE_ROOM' => esc_js(PropertyType::STORAGE_ROOM->value),
                'RESIDENTIAL_UNIT' => esc_js(PropertyType::RESIDENTIAL_UNIT->value),
                'SINGLE_FAMILY_HOUSE' => esc_js(PropertyType::SINGLE_FAMILY_HOUSE->value),
                'GARAGE' => esc_js(PropertyType::GARAGE->value)
            ]
        ]);
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

        <?php
    }
    
    private function sanitize_resource_data() {
        return [
            'rodzaj_nieruchomosci' => sanitize_text_field($_POST['rodzaj_nieruchomosci'] ?? ''),
            'nr_lokalu' => sanitize_text_field($_POST['nr_lokalu'] ?? ''),
            'powierzchnia_uzytkowa' => $this->sanitize_nullable_float($_POST['powierzchnia_uzytkowa'] ?? ''),
            'cena_m2' => $this->sanitize_nullable_float($_POST['cena_m2'] ?? ''),
            'cena_calkowita' => floatval($_POST['cena_calkowita'] ?? 0),
            'cena_z_dodatkami' => floatval($_POST['cena_z_dodatkami'] ?? 0),
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'extra_type' => sanitize_text_field($_POST['extra_type'] ?? ''),
            'extra_oznaczenie' => sanitize_text_field($_POST['extra_oznaczenie'] ?? ''),
            'extra_cena' => floatval($_POST['extra_cena'] ?? 0),

            // Component fields
            'property_part_title' => sanitize_text_field($_POST['property_part_title'] ?? ''),
            'property_part_designation' => sanitize_text_field($_POST['property_part_designation'] ?? ''),
            'property_part_price' => $this->sanitize_nullable_float($_POST['property_part_price'] ?? ''),

            'belonging_room_title' => sanitize_text_field($_POST['belonging_room_title'] ?? ''),
            'belonging_room_designation' => sanitize_text_field($_POST['belonging_room_designation'] ?? ''),
            'belonging_room_price' => $this->sanitize_nullable_float($_POST['belonging_room_price'] ?? ''),

            'usage_right_title' => sanitize_textarea_field($_POST['usage_right_title'] ?? ''),
            'usage_right_price' => $this->sanitize_nullable_float($_POST['usage_right_price'] ?? ''),

            'other_service_title' => sanitize_textarea_field($_POST['other_service_title'] ?? ''),
            'other_service_price' => $this->sanitize_nullable_float($_POST['other_service_price'] ?? ''),

            // Marketing fields
            'floor_number' => $this->sanitize_nullable_int($_POST['floor_number'] ?? ''),
            'room_count' => $this->sanitize_nullable_int($_POST['room_count'] ?? ''),
            'additional_description' => sanitize_textarea_field($_POST['additional_description'] ?? ''),
            'garden_area' => $this->sanitize_nullable_float($_POST['garden_area'] ?? '')
        ];
    }
    
    private function sanitize_nullable_float($value) {
        Logger::info('UJC: sanitize_nullable_float called with value: ' . esc_html(var_export($value, true)));
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
        Logger::info('UJC: sanitize_nullable_int called with value: ' . esc_html(var_export($value, true)));
        // If empty string or whitespace, return null
        if ($value === '' || $value === null || trim($value) === '') {
            Logger::info('UJC: sanitize_nullable_int returning null for empty value');
            return null;
        }
        $result = absint($value);
        Logger::info('UJC: sanitize_nullable_int returning: ' . $result);
        return $result;
    }

    private function sanitize_nullable_text($value) {
        Logger::info('UJC: sanitize_nullable_text called with value: ' . esc_html(var_export($value, true)));
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
     * Creates component models from sanitized data
     */
    private function createComponentModels($data): array {
        Logger::info('UJC: createComponentModels - starting');
        $components = [];
        
        // Property Part
        $propertyPartPrice = isset($data['property_part_price']) ? (float)$data['property_part_price'] : 0;
        Logger::info('UJC: createComponentModels - property part price: ' . $propertyPartPrice);
        Logger::info('UJC: createComponentModels - property part title: ' . esc_html($data['property_part_title'] ?? 'NOT_SET'));
        
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
        Logger::info('UJC: createResourceFormData - rodzaj_nieruchomosci value: ' . esc_html($data['rodzaj_nieruchomosci']));
        Logger::info('UJC: createResourceFormData - status value: ' . esc_html($data['status']));
        
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
        Logger::info('UJC: createResourceFormData - Data for constructor: nr_lokalu=' . esc_html($data['nr_lokalu']) . ', powierzchnia=' . esc_html($data['powierzchnia_uzytkowa']) . ', cena_m2=' . esc_html($data['cena_m2'] ?? 'NULL'));
        
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

        check_ajax_referer('jawneceny_resource_modal', 'nonce');
        if (!current_user_can('manage_options')) {
            Logger::error('UJC: Unauthorized user');
            wp_send_json_error('Brak uprawnień.');
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

        check_ajax_referer('jawneceny_resource_modal', 'nonce');
        if (!current_user_can('manage_options')) {
            Logger::error('UJC: Unauthorized user');
            wp_send_json_error('Brak uprawnień.');
            return;
        }

        try {
            $resource_id = absint($_POST['resource_id'] ?? 0);
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
        check_ajax_referer('jawneceny_resource_modal', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień.');
            return;
        }

        try {
            $resource_id = absint($_POST['resource_id'] ?? 0);

            if (!$resource_id) {
                wp_send_json_error('Nieprawidłowy ID zasobu');
                return;
            }

            $data = $this->sanitize_resource_data();

            // Handle PDF field in 3 scenarios
            $shouldRemovePdf = (sanitize_text_field($_POST['shouldRemovePdfFloorPlan'] ?? 'false')) === 'true';

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
        check_ajax_referer('jawneceny_resource_modal', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień.');
            return;
        }

        try {
            $resource_id = absint($_POST['resource_id'] ?? 0);

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
        Logger::info('UJC: handle_pdf_upload - File upload detected: ' . sanitize_file_name($file['name']) . ' (' . intval($file['size']) . ' bytes)');

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
        $file_extension = strtolower(pathinfo(sanitize_file_name($file['name']), PATHINFO_EXTENSION));

        if (!in_array($file_type, $allowed_types) || $file_extension !== 'pdf') {
            Logger::error('UJC: handle_pdf_upload - Invalid file type: ' . esc_html($file_type) . ', extension: ' . esc_html($file_extension));
            throw new Exception('Można przesyłać tylko pliki PDF');
        }

        // Validate file size (10MB limit)
        $max_size = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $max_size) {
            Logger::error('UJC: handle_pdf_upload - File too large: ' . intval($file['size']) . ' bytes');
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