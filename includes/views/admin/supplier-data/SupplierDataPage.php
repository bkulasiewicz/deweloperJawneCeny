<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Strona danych dostawcy - przeprojektowana z lepszym UX
 */
class SupplierDataPage extends UJC_Admin_Page {
    
    private $saveDeveloperInfoUseCase;
    private $getDeveloperInfoUseCase;

    public function __construct(
        SaveDeveloperInfoUseCase $saveDeveloperInfoUseCase,
        GetDeveloperInfoUseCase $getDeveloperInfoUseCase
    ) {
        $this->saveDeveloperInfoUseCase = $saveDeveloperInfoUseCase;
        $this->getDeveloperInfoUseCase = $getDeveloperInfoUseCase;

        add_action('wp_ajax_ujc_save_developer', [$this, 'ajax_save_developer']);
      //  add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        parent::__construct();
    }

    public function enqueue_assets() {
        $viewPath = PLUGIN_URL . 'includes/views/admin/supplier-data/';

        wp_enqueue_style(
            'supplier-data-page',
            $viewPath . 'SupplierDataPage.css',
            [],
            VERSION
        );

        wp_enqueue_script(
            'supplier-data-page',
            $viewPath . 'SupplierDataPage.js',
            ['jquery'],
            VERSION,
            true
        );

        wp_localize_script('supplier-data-page', 'supplierDataPageData', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ujc_supplier_nonce')
        ]);
    }
    
    public function ajax_save_developer() {
        if (!$this->verify_nonce()) {
            wp_send_json_error('Weryfikacja bezpieczeństwa nie powiodła się.');
            return;
        }
        
        if (!$this->check_permissions()) {
            wp_send_json_error('Brak uprawnień do wykonania tej operacji.');
            return;
        }
        
        try {
            // View tworzy DTO z sanityzacją
            $supplierDto = new SupplierDto(
                0, // ID will be set by repository
                sanitize_text_field($_POST['nazwa'] ?? ''),
                !empty($_POST['forma_prawna']) ? sanitize_text_field($_POST['forma_prawna']) : null,
                sanitize_text_field($_POST['nr_krs'] ?? ''),
                !empty($_POST['nr_ceidg']) ? sanitize_text_field($_POST['nr_ceidg']) : null,
                sanitize_text_field($_POST['nr_nip'] ?? ''),
                !empty($_POST['nr_regon']) ? sanitize_text_field($_POST['nr_regon']) : null,
                !empty($_POST['telefon']) ? sanitize_text_field($_POST['telefon']) : null,
                sanitize_email($_POST['email'] ?? ''),
                !empty($_POST['fax']) ? sanitize_text_field($_POST['fax']) : null,
                sanitize_text_field($_POST['strona_www'] ?? ''),
                sanitize_text_field($_POST['siedz_wojewodztwo'] ?? ''),
                !empty($_POST['siedz_powiat']) ? sanitize_text_field($_POST['siedz_powiat']) : null,
                !empty($_POST['siedz_gmina']) ? sanitize_text_field($_POST['siedz_gmina']) : null,
                sanitize_text_field($_POST['siedz_miejscowosc'] ?? ''),
                sanitize_text_field($_POST['siedz_ulica'] ?? ''),
                sanitize_text_field($_POST['siedz_nr'] ?? ''),
                !empty($_POST['siedz_lokal']) ? sanitize_text_field($_POST['siedz_lokal']) : null,
                sanitize_text_field($_POST['siedz_kod'] ?? ''),
                sanitize_text_field($_POST['sprzed_wojewodztwo'] ?? ''),
                !empty($_POST['sprzed_powiat']) ? sanitize_text_field($_POST['sprzed_powiat']) : null,
                !empty($_POST['sprzed_gmina']) ? sanitize_text_field($_POST['sprzed_gmina']) : null,
                sanitize_text_field($_POST['sprzed_miejscowosc'] ?? ''),
                sanitize_text_field($_POST['sprzed_ulica'] ?? ''),
                sanitize_text_field($_POST['sprzed_nr'] ?? ''),
                !empty($_POST['sprzed_lokal']) ? sanitize_text_field($_POST['sprzed_lokal']) : null,
                sanitize_text_field($_POST['sprzed_kod'] ?? ''),
                !empty($_POST['dodatkowe_lokalizacje']) ? sanitize_textarea_field($_POST['dodatkowe_lokalizacje']) : null,
                !empty($_POST['sposob_kontaktu']) ? sanitize_textarea_field($_POST['sposob_kontaktu']) : null
            );
            
            $result = $this->saveDeveloperInfoUseCase->execute($supplierDto);
            
            if ($result->isSuccess) {
                wp_send_json_success($result->message);
            } else {
                wp_send_json_error($result->message);
            }
        } catch (Exception $e) {
            wp_send_json_error('Błąd podczas przetwarzania danych: ' . $e->getMessage());
        }
    }
    
    public function render() {
        $developer = $this->getDeveloperInfoUseCase->execute();
        $is_saved = !empty($developer);
        
        ?>
        <div class="wrap">
            <h1>Dane Dostawcy</h1>
            <p>Uzupełnij wszystkie wymagane dane zgodnie z ustawą o jawności cen.</p>
            
            <?php if ($is_saved): ?>
                <!-- Widok readonly -->
                <div id="developer-readonly">
                    <div class="ujc-readonly-section">
                        <h3>🏢 Dane podstawowe</h3>
                        <div class="ujc-readonly-grid">
                            <div class="ujc-readonly-item">
                                <strong>Nazwa</strong>
                                <span><?php echo esc_html($developer->nazwa ?? ''); ?></span>
                            </div>
                            <div class="ujc-readonly-item">
                                <strong>Forma prawna</strong>
                                <span><?php echo esc_html($developer->forma_prawna ?? ''); ?></span>
                            </div>
                            <div class="ujc-readonly-item">
                                <strong>NIP</strong>
                                <span><?php echo esc_html($developer->nr_nip ?? ''); ?></span>
                            </div>
                            <div class="ujc-readonly-item">
                                <strong>REGON</strong>
                                <span><?php echo esc_html($developer->nr_regon ?? ''); ?></span>
                            </div>
                            <div class="ujc-readonly-item">
                                <strong>KRS</strong>
                                <span><?php echo esc_html($developer->nr_krs ?? ''); ?></span>
                            </div>
                            <div class="ujc-readonly-item">
                                <strong>CEiDG</strong>
                                <span><?php echo esc_html($developer->nr_ceidg ?? ''); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ujc-readonly-section">
                        <h3>📞 Dane kontaktowe</h3>
                        <div class="ujc-readonly-grid">
                            <div class="ujc-readonly-item">
                                <strong>Telefon</strong>
                                <span><?php echo esc_html($developer->telefon ?? ''); ?></span>
                            </div>
                            <div class="ujc-readonly-item">
                                <strong>Email</strong>
                                <span><?php echo esc_html($developer->email ?? ''); ?></span>
                            </div>
                            <div class="ujc-readonly-item">
                                <strong>Strona WWW</strong>
                                <span><a href="<?php echo esc_attr($developer->strona_www ?? ''); ?>" target="_blank"><?php echo esc_html($developer->strona_www ?? ''); ?></a></span>
                            </div>
                            <?php if (!empty($developer->fax)): ?>
                            <div class="ujc-readonly-item">
                                <strong>Fax</strong>
                                <span><?php echo esc_html($developer->fax); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="ujc-form-grid">
                        <div class="ujc-readonly-section">
                            <h3>🏠 Adres siedziby</h3>
                            <div class="ujc-readonly-grid">
                                <div class="ujc-readonly-item">
                                    <strong>Województwo</strong>
                                    <span><?php echo esc_html($developer->siedz_wojewodztwo ?? ''); ?></span>
                                </div>
                                <div class="ujc-readonly-item">
                                    <strong>Miejscowość</strong>
                                    <span><?php echo esc_html($developer->siedz_miejscowosc ?? ''); ?></span>
                                </div>
                                <div class="ujc-readonly-item">
                                    <strong>Kod pocztowy</strong>
                                    <span><?php echo esc_html($developer->siedz_kod ?? ''); ?></span>
                                </div>
                                <?php if (!empty($developer->siedz_ulica)): ?>
                                <div class="ujc-readonly-item">
                                    <strong>Ulica</strong>
                                    <span><?php echo esc_html($developer->siedz_ulica); ?> <?php echo esc_html($developer->siedz_nr ?? ''); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="ujc-readonly-section">
                            <h3>🏪 Adres sprzedaży</h3>
                            <div class="ujc-readonly-grid">
                                <div class="ujc-readonly-item">
                                    <strong>Województwo</strong>
                                    <span><?php echo esc_html($developer->sprzed_wojewodztwo ?? ''); ?></span>
                                </div>
                                <div class="ujc-readonly-item">
                                    <strong>Miejscowość</strong>
                                    <span><?php echo esc_html($developer->sprzed_miejscowosc ?? ''); ?></span>
                                </div>
                                <div class="ujc-readonly-item">
                                    <strong>Kod pocztowy</strong>
                                    <span><?php echo esc_html($developer->sprzed_kod ?? ''); ?></span>
                                </div>
                                <?php if (!empty($developer->sprzed_ulica)): ?>
                                <div class="ujc-readonly-item">
                                    <strong>Ulica</strong>
                                    <span><?php echo esc_html($developer->sprzed_ulica); ?> <?php echo esc_html($developer->sprzed_nr ?? ''); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <p class="submit">
                        <button type="button" id="edit-developer-btn" class="button-primary">✏️ Edytuj dane</button>
                    </p>
                </div>
                
                <!-- Formularz edycji -->
                <div id="developer-form-container" style="display: none;">
            <?php else: ?>
                <div id="developer-form-container">
            <?php endif; ?>
                
                <form id="developer-form" method="post">
                    <?php wp_nonce_field('ujc_admin_nonce', 'nonce'); ?>
                    
                    <!-- Dane podstawowe -->
                    <div class="ujc-form-section">
                        <h3>🏢 Dane podstawowe</h3>
                        <div class="ujc-form-grid">
                            <div class="ujc-form-field">
                                <label for="nazwa">Nazwa dostawcy <span class="required">*</span></label>
                                <input type="text" id="nazwa" name="nazwa" value="<?php echo esc_attr($developer->nazwa ?? ''); ?>" required>
                            </div>
                            <div class="ujc-form-field">
                                <label for="forma_prawna">Forma prawna</label>
                                <select id="forma_prawna" name="forma_prawna">
                                    <option value="spółka z o.o." <?php selected($developer->forma_prawna ?? '', 'spółka z o.o.'); ?>>Spółka z o.o.</option>
                                    <option value="spółka akcyjna" <?php selected($developer->forma_prawna ?? '', 'spółka akcyjna'); ?>>Spółka akcyjna</option>
                                    <option value="działalność gospodarcza" <?php selected($developer->forma_prawna ?? '', 'działalność gospodarcza'); ?>>Działalność gospodarcza</option>
                                    <option value="inne" <?php selected($developer->forma_prawna ?? '', 'inne'); ?>>Inne</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="ujc-form-grid-3">
                            <div class="ujc-form-field">
                                <label for="nr_nip">NIP <span class="required">*</span></label>
                                <input type="text" id="nr_nip" name="nr_nip" value="<?php echo esc_attr($developer->nr_nip ?? ''); ?>" required pattern="[0-9]{10}" placeholder="0000000000">
                            </div>
                            <div class="ujc-form-field">
                                <label for="nr_regon">REGON</label>
                                <input type="text" id="nr_regon" name="nr_regon" value="<?php echo esc_attr($developer->nr_regon ?? ''); ?>">
                            </div>
                            <div class="ujc-form-field">
                                <label for="nr_krs">KRS <span class="required">*</span></label>
                                <input type="text" id="nr_krs" name="nr_krs" value="<?php echo esc_attr($developer->nr_krs ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="ujc-form-field">
                            <label for="nr_ceidg">Nr CEiDG</label>
                            <input type="text" id="nr_ceidg" name="nr_ceidg" value="<?php echo esc_attr($developer->nr_ceidg ?? ''); ?>">
                        </div>
                    </div>
                    
                    <!-- Dane kontaktowe -->
                    <div class="ujc-form-section">
                        <h3>📞 Dane kontaktowe</h3>
                        <div class="ujc-form-grid">
                            <div class="ujc-form-field">
                                <label for="telefon">Telefon</label>
                                <input type="tel" id="telefon" name="telefon" value="<?php echo esc_attr($developer->telefon ?? ''); ?>">
                            </div>
                            <div class="ujc-form-field">
                                <label for="email">Email <span class="required">*</span></label>
                                <input type="email" id="email" name="email" value="<?php echo esc_attr($developer->email ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="ujc-form-grid">
                            <div class="ujc-form-field">
                                <label for="strona_www">Strona WWW <span class="required">*</span></label>
                                <input type="text" id="strona_www" name="strona_www" value="<?php echo esc_attr($developer->strona_www ?? ''); ?>" required placeholder="www.example.com">
                            </div>
                            <div class="ujc-form-field">
                                <label for="fax">Fax</label>
                                <input type="tel" id="fax" name="fax" value="<?php echo esc_attr($developer->fax ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Adresy -->
                    <div class="ujc-form-grid">
                        <!-- Adres siedziby -->
                        <div class="ujc-form-section">
                            <h3>🏠 Adres siedziby</h3>
                            <div class="ujc-form-field">
                                <label for="siedz_wojewodztwo">Województwo <span class="required">*</span></label>
                                <input type="text" id="siedz_wojewodztwo" name="siedz_wojewodztwo" value="<?php echo esc_attr($developer->siedz_wojewodztwo ?? ''); ?>" required>
                            </div>
                            <div class="ujc-form-grid">
                                <div class="ujc-form-field">
                                    <label for="siedz_powiat">Powiat</label>
                                    <input type="text" id="siedz_powiat" name="siedz_powiat" value="<?php echo esc_attr($developer->siedz_powiat ?? ''); ?>">
                                </div>
                                <div class="ujc-form-field">
                                    <label for="siedz_gmina">Gmina</label>
                                    <input type="text" id="siedz_gmina" name="siedz_gmina" value="<?php echo esc_attr($developer->siedz_gmina ?? ''); ?>">
                                </div>
                            </div>
                            <div class="ujc-form-field">
                                <label for="siedz_miejscowosc">Miejscowość <span class="required">*</span></label>
                                <input type="text" id="siedz_miejscowosc" name="siedz_miejscowosc" value="<?php echo esc_attr($developer->siedz_miejscowosc ?? ''); ?>" required>
                            </div>
                            <div class="ujc-form-grid">
                                <div class="ujc-form-field">
                                    <label for="siedz_ulica">Ulica <span class="required">*</span></label>
                                    <input type="text" id="siedz_ulica" name="siedz_ulica" value="<?php echo esc_attr($developer->siedz_ulica ?? ''); ?>" required>
                                </div>
                                <div class="ujc-form-field">
                                    <label for="siedz_nr">Nr <span class="required">*</span></label>
                                    <input type="text" id="siedz_nr" name="siedz_nr" value="<?php echo esc_attr($developer->siedz_nr ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="ujc-form-grid">
                                <div class="ujc-form-field">
                                    <label for="siedz_lokal">Nr lokalu</label>
                                    <input type="text" id="siedz_lokal" name="siedz_lokal" value="<?php echo esc_attr($developer->siedz_lokal ?? ''); ?>">
                                </div>
                                <div class="ujc-form-field">
                                    <label for="siedz_kod">Kod pocztowy <span class="required">*</span></label>
                                    <input type="text" id="siedz_kod" name="siedz_kod" value="<?php echo esc_attr($developer->siedz_kod ?? ''); ?>" pattern="[0-9]{2}-[0-9]{3}" placeholder="00-000" required>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Adres sprzedaży -->
                        <div class="ujc-form-section">
                            <h3>🏪 Adres lokalu sprzedaży</h3>
                            <div class="ujc-copy-address">
                                <button type="button" id="copy-address-btn" class="button">📋 Kopiuj adres siedziby</button>
                            </div>
                            <div class="ujc-form-field">
                                <label for="sprzed_wojewodztwo">Województwo <span class="required">*</span></label>
                                <input type="text" id="sprzed_wojewodztwo" name="sprzed_wojewodztwo" value="<?php echo esc_attr($developer->sprzed_wojewodztwo ?? ''); ?>" required>
                            </div>
                            <div class="ujc-form-grid">
                                <div class="ujc-form-field">
                                    <label for="sprzed_powiat">Powiat</label>
                                    <input type="text" id="sprzed_powiat" name="sprzed_powiat" value="<?php echo esc_attr($developer->sprzed_powiat ?? ''); ?>">
                                </div>
                                <div class="ujc-form-field">
                                    <label for="sprzed_gmina">Gmina</label>
                                    <input type="text" id="sprzed_gmina" name="sprzed_gmina" value="<?php echo esc_attr($developer->sprzed_gmina ?? ''); ?>">
                                </div>
                            </div>
                            <div class="ujc-form-field">
                                <label for="sprzed_miejscowosc">Miejscowość <span class="required">*</span></label>
                                <input type="text" id="sprzed_miejscowosc" name="sprzed_miejscowosc" value="<?php echo esc_attr($developer->sprzed_miejscowosc ?? ''); ?>" required>
                            </div>
                            <div class="ujc-form-grid">
                                <div class="ujc-form-field">
                                    <label for="sprzed_ulica">Ulica <span class="required">*</span></label>
                                    <input type="text" id="sprzed_ulica" name="sprzed_ulica" value="<?php echo esc_attr($developer->sprzed_ulica ?? ''); ?>" required>
                                </div>
                                <div class="ujc-form-field">
                                    <label for="sprzed_nr">Nr <span class="required">*</span></label>
                                    <input type="text" id="sprzed_nr" name="sprzed_nr" value="<?php echo esc_attr($developer->sprzed_nr ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="ujc-form-grid">
                                <div class="ujc-form-field">
                                    <label for="sprzed_lokal">Nr lokalu</label>
                                    <input type="text" id="sprzed_lokal" name="sprzed_lokal" value="<?php echo esc_attr($developer->sprzed_lokal ?? ''); ?>">
                                </div>
                                <div class="ujc-form-field">
                                    <label for="sprzed_kod">Kod pocztowy <span class="required">*</span></label>
                                    <input type="text" id="sprzed_kod" name="sprzed_kod" value="<?php echo esc_attr($developer->sprzed_kod ?? ''); ?>" pattern="[0-9]{2}-[0-9]{3}" placeholder="00-000" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dodatkowe informacje -->
                    <div class="ujc-form-section">
                        <h3>📋 Dodatkowe informacje</h3>
                        <div class="ujc-form-field">
                            <label for="dodatkowe_lokalizacje">Dodatkowe lokalizacje sprzedaży</label>
                            <textarea id="dodatkowe_lokalizacje" name="dodatkowe_lokalizacje" rows="3"><?php echo esc_textarea($developer->dodatkowe_lokalizacje ?? ''); ?></textarea>
                        </div>
                        <div class="ujc-form-field">
                            <label for="sposob_kontaktu">Sposób kontaktu z nabywcą</label>
                            <textarea id="sposob_kontaktu" name="sposob_kontaktu" rows="3"><?php echo esc_textarea($developer->sposob_kontaktu ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php echo $is_saved ? '💾 Zapisz zmiany' : '💾 Zapisz'; ?>">
                        <?php if ($is_saved): ?>
                            <button type="button" id="cancel-edit-btn" class="button">❌ Anuluj</button>
                        <?php endif; ?>
                    </p>
                </form>
                
                </div>
        </div>
        <?php
    }
}