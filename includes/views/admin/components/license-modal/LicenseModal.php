<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * License Modal Component
 *
 * Full-screen modal for license activation.
 * Displays on all plugin admin pages when license is invalid.
 * Cannot be closed - forces license activation.
 */
class LicenseModal {

	/**
	 * Activate license use case
	 *
	 * @var ActivateLicenseUseCase
	 */
	private $activateUseCase;

	/**
	 * License repository
	 *
	 * @var LicenseRepository
	 */
	private $licenseRepo;

	/**
	 * Constructor
	 *
	 * @param ActivateLicenseUseCase $activateUseCase Activate license use case.
	 * @param LicenseRepository      $licenseRepo     License repository.
	 */
	public function __construct( ActivateLicenseUseCase $activateUseCase, LicenseRepository $licenseRepo ) {
		$this->activateUseCase = $activateUseCase;
		$this->licenseRepo     = $licenseRepo;

		// Register AJAX handlers.
		add_action( 'wp_ajax_jawneceny_activate_license', array( $this, 'handleActivation' ) );

		// Enqueue assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAssets' ) );
	}

	/**
	 * Checks if modal should be shown
	 *
	 * Shows modal if:
	 * 1. Premium folder exists (premium version installed)
	 * 2. License is invalid (not activated or validation failed)
	 *
	 * @return bool True if modal should be displayed.
	 */
	public function shouldShow() {
		// Check if premium folder exists.
		if (! file_exists( JAWNECENY_PLUGIN_DIR . 'includes/premium/' )) {
			return false;
		}

		// Check if license is valid.
		return ! $this->licenseRepo->isValid();
	}

	/**
	 * Renders the license activation modal
	 *
	 * Full-screen overlay with activation form.
	 * Cannot be closed (no X button, ESC blocked).
	 */
	public function render() {
		if (! $this->shouldShow()) {
			return;
		}

		// Get license status and context for messages.
		$status  = $this->licenseRepo->getStatus();
		$context = $this->licenseRepo->getStatusContext();

		// Determine if this is a warning state (expired or domain mismatch).
		$is_warning = in_array( $status, array( LicenseStatus::EXPIRED, LicenseStatus::DOMAIN_MISMATCH ), true );

		?>
		<div id="jawneceny-license-modal" class="jawneceny-license-overlay">
			<div class="jawneceny-license-content">
				<div class="jawneceny-license-header">
					<h2><?php echo esc_html( $status->getModalTitle() ); ?></h2>
				</div>

				<div class="jawneceny-license-body">
					<p class="license-intro <?php echo $is_warning ? 'license-warning' : ''; ?>">
						<?php echo esc_html( $status->getModalMessage( $context['expires_at'], $context['domain'] ) ); ?>
					</p>

					<form id="jawneceny-license-activation-form">
						<div class="license-form-group">
							<label for="license_key">
								<?php esc_html_e( 'Klucz licencji:', 'deweloper-jawne-ceny' ); ?>
							</label>
							<input
								type="text"
								id="license_key"
								name="license_key"
								class="license-key-input"
								placeholder="XXXX-XXXX-XXXX-XXXX"
								maxlength="19"
								required
								autocomplete="off"
							/>
						</div>

						<button type="submit" class="button button-primary button-large">
							<?php esc_html_e( 'Aktywuj Licencję', 'deweloper-jawne-ceny' ); ?>
						</button>
					</form>

					<div id="jawneceny-license-message" class="license-message"></div>

					<div class="license-help">
						<div class="help-item">
							<strong><?php esc_html_e( 'Gdzie znajdę klucz licencji?', 'deweloper-jawne-ceny' ); ?></strong>
							<p><?php esc_html_e( 'Klucz licencji został wysłany na adres e-mail podany przy zakupie. Sprawdź folder "Spam" jeśli nie widzisz wiadomości.', 'deweloper-jawne-ceny' ); ?></p>
						</div>

						<div class="help-item">
							<strong><?php esc_html_e( 'Nie masz jeszcze licencji?', 'deweloper-jawne-ceny' ); ?></strong>
							<p>
								<a href="<?php echo esc_url( LicenseConfig::SHOP_URL ); ?>" target="_blank" class="button button-secondary">
									<?php esc_html_e( 'Kup Licencje', 'deweloper-jawne-ceny' ); ?> →
								</a>
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Enqueues modal CSS and JavaScript
	 *
	 * Only enqueues when modal should be shown.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueueAssets( $hook ) {
		// Only enqueue on plugin pages.
		if (false === strpos( $hook, 'ujc-' ) && false === strpos( $hook, 'jawne-ceny' )) {
			return;
		}

		// Only enqueue if modal should show.
		if (! $this->shouldShow()) {
			return;
		}

		$asset_path = JAWNECENY_PLUGIN_URL . 'includes/views/admin/components/license-modal/';

		// Enqueue CSS.
		wp_enqueue_style(
			'jawneceny-license-modal-css',
			$asset_path . 'LicenseModal.css',
			array(),
			JAWNECENY_VERSION
		);

		// Enqueue JS.
		wp_enqueue_script(
			'jawneceny-license-modal-js',
			$asset_path . 'LicenseModal.js',
			array( 'jquery' ),
			JAWNECENY_VERSION,
			true
		);

		// Localize script with AJAX data.
		wp_localize_script(
			'jawneceny-license-modal-js',
			'jawnecenyLicenseModal',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'jawneceny_license_activation' ),
				'strings' => array(
					'activating' => __( 'Aktywacja...', 'deweloper-jawne-ceny' ),
					'error'      => __( 'Wystąpił błąd. Spróbuj ponownie.', 'deweloper-jawne-ceny' ),
				),
			)
		);
	}

	/**
	 * AJAX handler for license activation
	 *
	 * Validates nonce, permissions, and calls ActivateLicenseUseCase.
	 */
	public function handleActivation() {
		// Verify nonce.
		check_ajax_referer( 'jawneceny_license_activation', 'nonce' );

		// Check permissions.
		if (! current_user_can( 'manage_options' )) {
			wp_send_json_error( __( 'Brak uprawnień do aktywacji licencji.', 'deweloper-jawne-ceny' ) );
			return;
		}

		// Get license key from request.
		$license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( $_POST['license_key'] ) : '';

		if (empty( $license_key )) {
			wp_send_json_error( __( 'Klucz licencji nie może być pusty.', 'deweloper-jawne-ceny' ) );
			return;
		}

		// Execute activation use case.
		$result = $this->activateUseCase->execute( $license_key );

		if ($result->isSuccess) {
			wp_send_json_success( $result->message );
		} else {
			wp_send_json_error( $result->message );
		}
	}
}
