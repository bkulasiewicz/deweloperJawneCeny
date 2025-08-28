<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once PLUGIN_DIR . 'includes/core/abstract-ujc-admin-page.php';

/**
 * Strona danych dostawcy - przeprojektowana z lepszym UX
 */
class UJC_Supplier_Data_Page extends UJC_Admin_Page {
    
    protected function init_hooks() {
        add_action('wp_ajax_ujc_save_developer', [$this, 'ajax_save_developer']);
        add_action('admin_head', [$this, 'add_custom_styles']);
    }
    
    public function add_custom_styles() {
        ?>
        <style>
        .ujc-form-section {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .ujc-form-section h3 {
            margin: 0 0 15px 0;
            padding: 0 0 10px 0;
            border-bottom: 2px solid #0073aa;
            color: #0073aa;
            font-size: 16px;
        }
        
        .ujc-form-grid {
            display: grid;
            gap: 20px;
            grid-template-columns: 1fr 1fr;
        }
        
        .ujc-form-grid-3 {
            display: grid;
            gap: 15px;
            grid-template-columns: 1fr 1fr 1fr;
        }
        
        .ujc-form-field {
            display: flex;
            flex-direction: column;
        }
        
        .ujc-form-field label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        
        .ujc-form-field input,
        .ujc-form-field select,
        .ujc-form-field textarea {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .ujc-form-field input:focus,
        .ujc-form-field select:focus,
        .ujc-form-field textarea:focus {
            border-color: #0073aa;
            box-shadow: 0 0 0 2px rgba(0,115,170,0.1);
            outline: none;
        }
        
        .ujc-required {
            color: #d63638;
        }
        
        .ujc-copy-address {
            background: #f0f6fc;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #0073aa;
            margin-bottom: 15px;
        }
        
        .ujc-readonly-section {
            background: #f9f9f9;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .ujc-readonly-section h3 {
            margin: 0 0 15px 0;
            color: #0073aa;
            border-bottom: 1px solid #ddd;
            padding-bottom: 8px;
        }
        
        .ujc-readonly-grid {
            display: grid;
            gap: 10px 30px;
            grid-template-columns: 1fr 1fr 1fr;
        }
        
        .ujc-readonly-item {
            display: flex;
            flex-direction: column;
        }
        
        .ujc-readonly-item strong {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        
        .ujc-readonly-item span {
            color: #333;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .ujc-form-grid,
            .ujc-form-grid-3,
            .ujc-readonly-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
    }
    
    public function ajax_save_developer() {
        if (!$this->verify_nonce()) return;
        if (!$this->check_permissions()) return;
        
        $result = SaveDeveloperInfoUseCase::execute($_POST);
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    public function render() {
        $developer_repo = new DeveloperRepository();
        $developer = $developer_repo->read();
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
                                <span><?php echo esc_html($developer['nazwa'] ?? ''); ?></span>
                            </div>
                            <div class="ujc-readonly-item">
                                <strong>Forma prawna</strong>
                                <span><?php echo esc_html($developer['forma_prawna'] ?? ''); ?></span>
                            </div>
                            <div class="ujc-readonly-item">
                                <strong>NIP</strong>
                                <span><?php echo esc_html($developer['nr_nip'] ?? ''); ?></span>
                            </div>
                            <div class="ujc-readonly-item">
                                <strong>REGON</strong>
                                <span><?php echo esc_html($developer['nr_regon'] ?? ''); ?></span>
                            </div>
                            <div class="ujc-readonly-item">
                                <strong>KRS</strong>
                                <span><?php echo esc_html($developer['nr_krs'] ?? ''); ?></span>
                            </div>
                            <div class="ujc-readonly-item">
                                <strong>CEiDG</strong>
                                <span><?php echo esc_html($developer['nr_ceidg'] ?? ''); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ujc-readonly-section">
                        <h3>📞 Dane kontaktowe</h3>
                        <div class="ujc-readonly-grid">
                            <div class="ujc-readonly-item">
                                <strong>Telefon</strong>
                                <span><?php echo esc_html($developer['telefon'] ?? ''); ?></span>
                            </div>
                            <div class="ujc-readonly-item">
                                <strong>Email</strong>
                                <span><?php echo esc_html($developer['email'] ?? ''); ?></span>
                            </div>
                            <div class="ujc-readonly-item">
                                <strong>Strona WWW</strong>
                                <span><a href="<?php echo esc_url($developer['strona_www'] ?? ''); ?>" target="_blank"><?php echo esc_html($developer['strona_www'] ?? ''); ?></a></span>
                            </div>
                            <?php if (!empty($developer['fax'])): ?>
                            <div class="ujc-readonly-item">
                                <strong>Fax</strong>
                                <span><?php echo esc_html($developer['fax']); ?></span>
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
                                    <span><?php echo esc_html($developer['siedz_wojewodztwo'] ?? ''); ?></span>
                                </div>
                                <div class="ujc-readonly-item">
                                    <strong>Miejscowość</strong>
                                    <span><?php echo esc_html($developer['siedz_miejscowosc'] ?? ''); ?></span>
                                </div>
                                <div class="ujc-readonly-item">
                                    <strong>Kod pocztowy</strong>
                                    <span><?php echo esc_html($developer['siedz_kod'] ?? ''); ?></span>
                                </div>
                                <?php if (!empty($developer['siedz_ulica'])): ?>
                                <div class="ujc-readonly-item">
                                    <strong>Ulica</strong>
                                    <span><?php echo esc_html($developer['siedz_ulica']); ?> <?php echo esc_html($developer['siedz_nr'] ?? ''); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="ujc-readonly-section">
                            <h3>🏪 Adres sprzedaży</h3>
                            <div class="ujc-readonly-grid">
                                <div class="ujc-readonly-item">
                                    <strong>Województwo</strong>
                                    <span><?php echo esc_html($developer['sprzed_wojewodztwo'] ?? ''); ?></span>
                                </div>
                                <div class="ujc-readonly-item">
                                    <strong>Miejscowość</strong>
                                    <span><?php echo esc_html($developer['sprzed_miejscowosc'] ?? ''); ?></span>
                                </div>
                                <div class="ujc-readonly-item">
                                    <strong>Kod pocztowy</strong>
                                    <span><?php echo esc_html($developer['sprzed_kod'] ?? ''); ?></span>
                                </div>
                                <?php if (!empty($developer['sprzed_ulica'])): ?>
                                <div class="ujc-readonly-item">
                                    <strong>Ulica</strong>
                                    <span><?php echo esc_html($developer['sprzed_ulica']); ?> <?php echo esc_html($developer['sprzed_nr'] ?? ''); ?></span>
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
                    <?php wp_nonce_field('ujc_admin_nonce', 'ujc_nonce'); ?>
                    
                    <!-- Dane podstawowe -->
                    <div class="ujc-form-section">
                        <h3>🏢 Dane podstawowe</h3>
                        <div class="ujc-form-grid">
                            <div class="ujc-form-field">
                                <label for="nazwa">Nazwa dostawcy <span class="ujc-required">*</span></label>
                                <input type="text" id="nazwa" name="nazwa" value="<?php echo esc_attr($developer['nazwa'] ?? ''); ?>" required>
                            </div>
                            <div class="ujc-form-field">
                                <label for="forma_prawna">Forma prawna <span class="ujc-required">*</span></label>
                                <select id="forma_prawna" name="forma_prawna" required>
                                    <option value="">Wybierz formę prawną</option>
                                    <option value="spółka z o.o." <?php selected($developer['forma_prawna'] ?? '', 'spółka z o.o.'); ?>>Spółka z o.o.</option>
                                    <option value="spółka akcyjna" <?php selected($developer['forma_prawna'] ?? '', 'spółka akcyjna'); ?>>Spółka akcyjna</option>
                                    <option value="działalność gospodarcza" <?php selected($developer['forma_prawna'] ?? '', 'działalność gospodarcza'); ?>>Działalność gospodarcza</option>
                                    <option value="inne" <?php selected($developer['forma_prawna'] ?? '', 'inne'); ?>>Inne</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="ujc-form-grid-3">
                            <div class="ujc-form-field">
                                <label for="nr_nip">NIP <span class="ujc-required">*</span></label>
                                <input type="text" id="nr_nip" name="nr_nip" value="<?php echo esc_attr($developer['nr_nip'] ?? ''); ?>" required pattern="[0-9]{10}" placeholder="0000000000">
                            </div>
                            <div class="ujc-form-field">
                                <label for="nr_regon">REGON <span class="ujc-required">*</span></label>
                                <input type="text" id="nr_regon" name="nr_regon" value="<?php echo esc_attr($developer['nr_regon'] ?? ''); ?>" required>
                            </div>
                            <div class="ujc-form-field">
                                <label for="nr_krs">KRS</label>
                                <input type="text" id="nr_krs" name="nr_krs" value="<?php echo esc_attr($developer['nr_krs'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="ujc-form-field">
                            <label for="nr_ceidg">Nr CEiDG</label>
                            <input type="text" id="nr_ceidg" name="nr_ceidg" value="<?php echo esc_attr($developer['nr_ceidg'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <!-- Dane kontaktowe -->
                    <div class="ujc-form-section">
                        <h3>📞 Dane kontaktowe</h3>
                        <div class="ujc-form-grid">
                            <div class="ujc-form-field">
                                <label for="telefon">Telefon <span class="ujc-required">*</span></label>
                                <input type="tel" id="telefon" name="telefon" value="<?php echo esc_attr($developer['telefon'] ?? ''); ?>" required>
                            </div>
                            <div class="ujc-form-field">
                                <label for="email">Email <span class="ujc-required">*</span></label>
                                <input type="email" id="email" name="email" value="<?php echo esc_attr($developer['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="ujc-form-grid">
                            <div class="ujc-form-field">
                                <label for="strona_www">Strona WWW <span class="ujc-required">*</span></label>
                                <input type="url" id="strona_www" name="strona_www" value="<?php echo esc_attr($developer['strona_www'] ?? ''); ?>" required>
                            </div>
                            <div class="ujc-form-field">
                                <label for="fax">Fax</label>
                                <input type="tel" id="fax" name="fax" value="<?php echo esc_attr($developer['fax'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Adresy -->
                    <div class="ujc-form-grid">
                        <!-- Adres siedziby -->
                        <div class="ujc-form-section">
                            <h3>🏠 Adres siedziby</h3>
                            <div class="ujc-form-field">
                                <label for="siedz_wojewodztwo">Województwo <span class="ujc-required">*</span></label>
                                <input type="text" id="siedz_wojewodztwo" name="siedz_wojewodztwo" value="<?php echo esc_attr($developer['siedz_wojewodztwo'] ?? ''); ?>" required>
                            </div>
                            <div class="ujc-form-grid">
                                <div class="ujc-form-field">
                                    <label for="siedz_powiat">Powiat</label>
                                    <input type="text" id="siedz_powiat" name="siedz_powiat" value="<?php echo esc_attr($developer['siedz_powiat'] ?? ''); ?>">
                                </div>
                                <div class="ujc-form-field">
                                    <label for="siedz_gmina">Gmina</label>
                                    <input type="text" id="siedz_gmina" name="siedz_gmina" value="<?php echo esc_attr($developer['siedz_gmina'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="ujc-form-field">
                                <label for="siedz_miejscowosc">Miejscowość <span class="ujc-required">*</span></label>
                                <input type="text" id="siedz_miejscowosc" name="siedz_miejscowosc" value="<?php echo esc_attr($developer['siedz_miejscowosc'] ?? ''); ?>" required>
                            </div>
                            <div class="ujc-form-grid">
                                <div class="ujc-form-field">
                                    <label for="siedz_ulica">Ulica</label>
                                    <input type="text" id="siedz_ulica" name="siedz_ulica" value="<?php echo esc_attr($developer['siedz_ulica'] ?? ''); ?>">
                                </div>
                                <div class="ujc-form-field">
                                    <label for="siedz_nr">Nr</label>
                                    <input type="text" id="siedz_nr" name="siedz_nr" value="<?php echo esc_attr($developer['siedz_nr'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="ujc-form-grid">
                                <div class="ujc-form-field">
                                    <label for="siedz_lokal">Nr lokalu</label>
                                    <input type="text" id="siedz_lokal" name="siedz_lokal" value="<?php echo esc_attr($developer['siedz_lokal'] ?? ''); ?>">
                                </div>
                                <div class="ujc-form-field">
                                    <label for="siedz_kod">Kod pocztowy</label>
                                    <input type="text" id="siedz_kod" name="siedz_kod" value="<?php echo esc_attr($developer['siedz_kod'] ?? ''); ?>" pattern="[0-9]{2}-[0-9]{3}" placeholder="00-000">
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
                                <label for="sprzed_wojewodztwo">Województwo <span class="ujc-required">*</span></label>
                                <input type="text" id="sprzed_wojewodztwo" name="sprzed_wojewodztwo" value="<?php echo esc_attr($developer['sprzed_wojewodztwo'] ?? ''); ?>" required>
                            </div>
                            <div class="ujc-form-grid">
                                <div class="ujc-form-field">
                                    <label for="sprzed_powiat">Powiat</label>
                                    <input type="text" id="sprzed_powiat" name="sprzed_powiat" value="<?php echo esc_attr($developer['sprzed_powiat'] ?? ''); ?>">
                                </div>
                                <div class="ujc-form-field">
                                    <label for="sprzed_gmina">Gmina</label>
                                    <input type="text" id="sprzed_gmina" name="sprzed_gmina" value="<?php echo esc_attr($developer['sprzed_gmina'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="ujc-form-field">
                                <label for="sprzed_miejscowosc">Miejscowość <span class="ujc-required">*</span></label>
                                <input type="text" id="sprzed_miejscowosc" name="sprzed_miejscowosc" value="<?php echo esc_attr($developer['sprzed_miejscowosc'] ?? ''); ?>" required>
                            </div>
                            <div class="ujc-form-grid">
                                <div class="ujc-form-field">
                                    <label for="sprzed_ulica">Ulica</label>
                                    <input type="text" id="sprzed_ulica" name="sprzed_ulica" value="<?php echo esc_attr($developer['sprzed_ulica'] ?? ''); ?>">
                                </div>
                                <div class="ujc-form-field">
                                    <label for="sprzed_nr">Nr</label>
                                    <input type="text" id="sprzed_nr" name="sprzed_nr" value="<?php echo esc_attr($developer['sprzed_nr'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="ujc-form-grid">
                                <div class="ujc-form-field">
                                    <label for="sprzed_lokal">Nr lokalu</label>
                                    <input type="text" id="sprzed_lokal" name="sprzed_lokal" value="<?php echo esc_attr($developer['sprzed_lokal'] ?? ''); ?>">
                                </div>
                                <div class="ujc-form-field">
                                    <label for="sprzed_kod">Kod pocztowy</label>
                                    <input type="text" id="sprzed_kod" name="sprzed_kod" value="<?php echo esc_attr($developer['sprzed_kod'] ?? ''); ?>" pattern="[0-9]{2}-[0-9]{3}" placeholder="00-000">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dodatkowe informacje -->
                    <div class="ujc-form-section">
                        <h3>📋 Dodatkowe informacje</h3>
                        <div class="ujc-form-field">
                            <label for="dodatkowe_lokalizacje">Dodatkowe lokalizacje sprzedaży</label>
                            <textarea id="dodatkowe_lokalizacje" name="dodatkowe_lokalizacje" rows="3"><?php echo esc_textarea($developer['dodatkowe_lokalizacje'] ?? ''); ?></textarea>
                        </div>
                        <div class="ujc-form-field">
                            <label for="sposob_kontaktu">Sposób kontaktu z nabywcą</label>
                            <textarea id="sposob_kontaktu" name="sposob_kontaktu" rows="3"><?php echo esc_textarea($developer['sposob_kontaktu'] ?? ''); ?></textarea>
                        </div>
                        <div class="ujc-form-field">
                            <label for="prospekt_url">URL prospektu informacyjnego</label>
                            <input type="url" id="prospekt_url" name="prospekt_url" value="<?php echo esc_attr($developer['prospekt_url'] ?? ''); ?>">
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
            
            <script>
            jQuery(document).ready(function($) {
                $('#edit-developer-btn').on('click', function() {
                    $('#developer-readonly').hide();
                    $('#developer-form-container').show();
                });
                
                $('#cancel-edit-btn').on('click', function() {
                    $('#developer-form-container').hide();
                    $('#developer-readonly').show();
                });
                
                $('#copy-address-btn').on('click', function() {
                    $('#sprzed_wojewodztwo').val($('#siedz_wojewodztwo').val());
                    $('#sprzed_powiat').val($('#siedz_powiat').val());
                    $('#sprzed_gmina').val($('#siedz_gmina').val());
                    $('#sprzed_miejscowosc').val($('#siedz_miejscowosc').val());
                    $('#sprzed_ulica').val($('#siedz_ulica').val());
                    $('#sprzed_nr').val($('#siedz_nr').val());
                    $('#sprzed_lokal').val($('#siedz_lokal').val());
                    $('#sprzed_kod').val($('#siedz_kod').val());
                });
                
                $('#developer-form').on('submit', function(e) {
                    e.preventDefault();
                    
                    var $button = $(this).find('input[type="submit"]');
                    var originalValue = $button.val();
                    $button.val('⏳ Zapisywanie...').prop('disabled', true);
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: $(this).serialize() + '&action=ujc_save_developer',
                        success: function(response) {
                            if (response.success) {
                                alert('✅ ' + response.data);
                                location.reload();
                            } else {
                                alert('❌ ' + response.data);
                            }
                        },
                        error: function() {
                            alert('❌ Błąd połączenia');
                        },
                        complete: function() {
                            $button.val(originalValue).prop('disabled', false);
                        }
                    });
                });
            });
            </script>
        </div>
        <?php
    }
}