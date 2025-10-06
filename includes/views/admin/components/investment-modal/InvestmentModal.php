<?php

namespace JawneCeny;

use JawneCeny_AdminPage;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Komponent modalu dla inwestycji - Single Responsibility Principle
 * Odpowiada tylko za zarządzanie modalem wyświetlania/edycji danych inwestycji
 */
class InvestmentModal extends JawneCeny_AdminPage {
    
    private $createInvestmentUseCase;
    private $updateInvestmentUseCase;
    private $getInvestmentUseCase;
    
    public function __construct(
        CreateInvestmentUseCase $createInvestmentUseCase,
        UpdateInvestmentUseCase $updateInvestmentUseCase,
        GetInvestmentUseCase $getInvestmentUseCase
    ) {
        $this->createInvestmentUseCase = $createInvestmentUseCase;
        $this->updateInvestmentUseCase = $updateInvestmentUseCase;
        $this->getInvestmentUseCase = $getInvestmentUseCase;
        
        add_action('wp_ajax_jawneceny_get_investment', [$this, 'ajax_get_investment']);
        add_action('wp_ajax_jawneceny_update_investment', [$this, 'ajax_update_investment']);
        add_action('wp_ajax_jawneceny_log_debug', [$this, 'ajax_log_debug']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        parent::__construct();
    }

    public function enqueue_assets() {
        $viewPath = JAWNECENY_PLUGIN_URL . 'includes/views/admin/components/investment-modal/';

        wp_enqueue_style(
            'investment-modal',
            $viewPath . 'InvestmentModal.css',
            [],
            JAWNECENY_VERSION
        );

        wp_enqueue_script(
            'investment-modal',
            $viewPath . 'InvestmentModal.js',
            ['jquery'],
            JAWNECENY_VERSION,
            true
        );

        wp_localize_script('investment-modal', 'investmentModalData', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('jawneceny_investment_modal')
        ]);
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
        <?php
    }
    
    public function ajax_get_investment() {
        check_ajax_referer('jawneceny_investment_modal', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień.');
            return;
        }

        try {
            // Pobierz inwestycję (obecnie system obsługuje tylko jedną inwestycję)
            $investment = $this->getInvestmentUseCase->execute();
            
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
        check_ajax_referer('jawneceny_investment_modal', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień do wykonania tej operacji.');
            return;
        }

        try {
            // Direct sanitization - clear and compliant with WordPress.org guidelines
            $data = [
                'name' => sanitize_text_field($_POST['name'] ?? ''),
                'proj_wojewodztwo' => sanitize_text_field($_POST['proj_wojewodztwo'] ?? ''),
                'proj_powiat' => sanitize_text_field($_POST['proj_powiat'] ?? ''),
                'proj_gmina' => sanitize_text_field($_POST['proj_gmina'] ?? ''),
                'proj_miejscowosc' => sanitize_text_field($_POST['proj_miejscowosc'] ?? ''),
                'proj_ulica' => sanitize_text_field($_POST['proj_ulica'] ?? ''),
                'proj_nr' => sanitize_text_field($_POST['proj_nr'] ?? ''),
                'proj_kod' => sanitize_text_field($_POST['proj_kod'] ?? ''),
                'has_property_parts' => $this->sanitize_checkbox($_POST['has_property_parts'] ?? ''),
                'has_belonging_rooms' => $this->sanitize_checkbox($_POST['has_belonging_rooms'] ?? ''),
                'has_usage_rights' => $this->sanitize_checkbox($_POST['has_usage_rights'] ?? ''),
                'has_other_services' => $this->sanitize_checkbox($_POST['has_other_services'] ?? ''),
                'show_floor_field' => $this->sanitize_checkbox($_POST['show_floor_field'] ?? ''),
                'show_rooms_field' => $this->sanitize_checkbox($_POST['show_rooms_field'] ?? ''),
                'show_description_field' => $this->sanitize_checkbox($_POST['show_description_field'] ?? ''),
                'show_garden_field' => $this->sanitize_checkbox($_POST['show_garden_field'] ?? ''),
                'show_floor_plan_field' => $this->sanitize_checkbox($_POST['show_floor_plan_field'] ?? '')
            ];
            
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
        check_ajax_referer('jawneceny_investment_modal', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień.');
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