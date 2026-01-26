<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Setup Checklist Tile Component
 * Displays a to-do list for plugin setup with manual checkbox controls
 */
class SetupChecklistTile {

    private const OPTION_KEY = 'jawneceny_setup_checklist';

    private const CHECKLIST_ITEMS = [
        'dane_gov_registration' => [
            'label' => 'Zarejestruj się na dane.gov.pl',
            'description' => 'Załóż konto użytkownika na portalu rządowym.',
            'order' => 1,
            'premium_only' => false,
        ],
        'dane_gov_supplier_status' => [
            'label' => 'Uzyskaj status Dostawcy Danych',
            'description' => 'Wyślij email z danymi firmy na kontakt@dane.gov.pl.',
            'order' => 2,
            'premium_only' => false,
        ],
        'supplier_data_filled' => [
            'label' => 'Uzupełnij dane dostawcy',
            'description' => 'Wypełnij formularz w zakładce "Dane dostawcy".',
            'order' => 3,
            'premium_only' => false,
        ],
        'investment_added' => [
            'label' => 'Dodaj inwestycję',
            'description' => 'Utwórz inwestycję w zakładce "Zasoby".',
            'order' => 4,
            'premium_only' => false,
        ],
        'resources_added' => [
            'label' => 'Dodaj zasoby',
            'description' => 'Dodaj mieszkania/lokale do inwestycji.',
            'order' => 5,
            'premium_only' => false,
        ],
        'display_on_website' => [
            'label' => 'Dodaj ofertę na stronę',
            'description' => 'Użyj bloku Gutenberg lub shortcode. Szczegóły w zakładce "Instrukcja".',
            'order' => 6,
            'premium_only' => false,
        ],
        'automation_enabled' => [
            'label' => 'Włącz automatyzację',
            'description' => 'Kliknij przycisk "Włącz External Cron" w kafelku "Automatyzacja" na Pulpicie.',
            'order' => 7,
            'premium_only' => true,
        ],
        'ministry_form_sent' => [
            'label' => 'Wyślij formularz do Ministerstwa',
            'description' => 'Przekaż adresy plików XML/MD5 do Ministerstwa.',
            'order' => 8,
            'premium_only' => true,
        ],
    ];

    public function __construct() {
        add_action('wp_ajax_jawneceny_toggle_checklist_item', [$this, 'ajax_toggle_item']);
        add_action('wp_ajax_jawneceny_dismiss_checklist', [$this, 'ajax_dismiss_checklist']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Enqueue JavaScript assets
     */
    public function enqueue_assets($hook) {
        // Only load on dashboard page
        if (strpos($hook, 'jawneceny') === false && strpos($hook, 'ujc') === false) {
            return;
        }

        wp_enqueue_script(
            'jawneceny-setup-checklist-tile',
            JAWNECENY_PLUGIN_URL . 'includes/views/admin/Dashboard/SetupChecklistTile.js',
            ['jquery'],
            JAWNECENY_VERSION,
            true
        );

        wp_localize_script('jawneceny-setup-checklist-tile', 'jawneceny_checklist_ajax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('jawneceny_admin_nonce')
        ]);
    }

    /**
     * Check if all checklist items are completed
     */
    public function isAllCompleted(): bool {
        $state = $this->getChecklistState();
        $items = $this->getVisibleItems();

        foreach ($items as $key => $item) {
            if (empty($state[$key])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if tile should be hidden (all complete or dismissed)
     */
    public function shouldHide(): bool {
        $state = $this->getChecklistState();

        // Check if explicitly dismissed
        if (!empty($state['hidden_at'])) {
            return true;
        }

        return $this->isAllCompleted();
    }

    /**
     * Get visible items based on premium status
     */
    private function getVisibleItems(): array {
        $items = self::CHECKLIST_ITEMS;
        $isPremium = file_exists(JAWNECENY_PLUGIN_DIR . 'includes/premium/');

        if (!$isPremium) {
            $items = array_filter($items, function ($item) {
                return empty($item['premium_only']);
            });
        }

        // Sort by order
        uasort($items, fn($a, $b) => $a['order'] <=> $b['order']);

        return $items;
    }

    /**
     * Get current checklist state from wp_options
     */
    private function getChecklistState(): array {
        $state = get_option(self::OPTION_KEY, []);
        return is_array($state) ? $state : [];
    }

    /**
     * Save checklist state to wp_options
     */
    private function saveChecklistState(array $state): bool {
        return update_option(self::OPTION_KEY, $state);
    }

    /**
     * AJAX handler for toggling checklist item
     */
    public function ajax_toggle_item() {
        check_ajax_referer('jawneceny_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
            return;
        }

        $item_key = sanitize_key($_POST['item_key'] ?? '');
        $checked = filter_var($_POST['checked'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // Validate item exists
        if (!isset(self::CHECKLIST_ITEMS[$item_key])) {
            wp_send_json_error('Nieprawidłowy element');
            return;
        }

        $state = $this->getChecklistState();
        $state[$item_key] = $checked;

        if ($this->saveChecklistState($state)) {
            $allCompleted = $this->isAllCompleted();

            wp_send_json_success([
                'message' => $checked ? 'Oznaczono jako ukończone' : 'Oznaczono jako nieukończone',
                'all_completed' => $allCompleted,
            ]);
        } else {
            wp_send_json_error('Błąd zapisu');
        }
    }

    /**
     * AJAX handler for dismissing the checklist tile
     */
    public function ajax_dismiss_checklist() {
        check_ajax_referer('jawneceny_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Brak uprawnień');
            return;
        }

        $state = $this->getChecklistState();
        $state['hidden_at'] = time();

        if ($this->saveChecklistState($state)) {
            wp_send_json_success('Checklist ukryty');
        } else {
            wp_send_json_error('Błąd zapisu');
        }
    }

    /**
     * Render the complete checklist tile
     */
    public function render() {
        // Don't render if all completed or dismissed
        if ($this->shouldHide()) {
            return;
        }

        $items = $this->getVisibleItems();
        $state = $this->getChecklistState();
        ?>
        <div class="automation-control setup-checklist-tile" id="setup-checklist-tile">
            <div class="checklist-header">
                <h2>Lista kontrolna konfiguracji</h2>
                <button type="button"
                        class="checklist-dismiss"
                        onclick="dismissChecklist()"
                        title="Ukryj listę kontrolną">
                    &times;
                </button>
            </div>

            <ul class="checklist-items">
                <?php foreach ($items as $key => $item):
                    $isChecked = !empty($state[$key]);
                ?>
                    <li class="checklist-item <?php echo $isChecked ? 'completed' : ''; ?>">
                        <label>
                            <input type="checkbox"
                                   class="checklist-checkbox"
                                   data-key="<?php echo esc_attr($key); ?>"
                                   <?php checked($isChecked); ?>
                                   onchange="toggleChecklistItem(this)">
                            <span class="item-content">
                                <span class="item-label"><?php echo esc_html($item['label']); ?></span>
                                <?php if (!empty($item['description'])): ?>
                                    <small class="item-description"><?php echo esc_html($item['description']); ?></small>
                                <?php endif; ?>
                            </span>
                        </label>
                    </li>
                <?php endforeach; ?>
            </ul>

            <p class="checklist-footer">
                <small>Szczegółowy opis znajdziesz w zakładce <strong>Instrukcja</strong></small>
            </p>
        </div>
        <?php
    }
}
