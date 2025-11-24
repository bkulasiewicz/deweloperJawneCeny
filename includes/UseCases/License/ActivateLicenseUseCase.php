<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Activate License Use Case
 *
 * Handles license activation through LMFWC API.
 * Validates license key, calls API, and stores license data.
 */
class ActivateLicenseUseCase {

	/**
	 * License validator service
	 *
	 * @var LicenseValidator
	 */
	private $validator;

	/**
	 * License repository
	 *
	 * @var LicenseRepository
	 */
	private $repository;

	/**
	 * Constructor
	 *
	 * @param LicenseValidator  $validator  License validator service.
	 * @param LicenseRepository $repository License repository.
	 */
	public function __construct( LicenseValidator $validator, LicenseRepository $repository ) {
		$this->validator  = $validator;
		$this->repository = $repository;
	}

	/**
	 * Executes license activation
	 *
	 * @param string $license_key License key to activate.
	 * @return Result Success or failure with message.
	 */
	public function execute( $license_key ) {
		// Sanitize and validate input.
		$license_key = sanitize_text_field( $license_key );

		if (empty( $license_key )) {
			return Result::failure( __( 'Klucz licencji nie może być pusty.', 'ustawa-jawnosci-cen' ) );
		}

		// Validate license key format (XXXX-XXXX-XXXX-XXXX).
		if (! preg_match( '/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/i', $license_key )) {
			return Result::failure( __( 'Nieprawidłowy format klucza licencji. Poprawny format: XXXX-XXXX-XXXX-XXXX', 'ustawa-jawnosci-cen' ) );
		}

		// Call LMFWC activation API.
		$api_response = $this->validator->activate( $license_key );

		if (! $api_response['success']) {
			return Result::failure( $api_response['message'] );
		}

		// Parse expiration from API response.
		$api_data   = $api_response['data'];
		$expires_at = null;

		// Log API response for debugging.
		Logger::info( 'License API response data: ' . wp_json_encode( $api_data ) );

		// Priority 1: Use explicit expiresAt if provided.
		if ( ! empty( $api_data['expiresAt'] ) ) {
			$expires_at = strtotime( $api_data['expiresAt'] );
		} elseif ( ! empty( $api_data['expires_at'] ) ) {
			$expires_at = strtotime( $api_data['expires_at'] );
		}
		// Priority 2: Calculate from validFor (days from activation).
		elseif ( ! empty( $api_data['validFor'] ) && is_numeric( $api_data['validFor'] ) ) {
			$valid_days = (int) $api_data['validFor'];
			$expires_at = time() + ( $valid_days * DAY_IN_SECONDS );
		}

		// Prepare license data for storage.
		$current_domain = $this->repository->getDomain();
		$license_data   = array(
			'key'             => strtoupper( $license_key ),
			'status'          => 'active',
			'domain'          => $current_domain,
			'activated_at'    => time(),
			'last_checked'    => time(),
			'last_api_status' => 'success',
			'expires_at'      => $expires_at,
		);

		// Save license data to repository.
		$saved = $this->repository->save( $license_data );

		if (! $saved) {
			return Result::failure( __( 'Błąd podczas zapisywania licencji. Spróbuj ponownie.', 'ustawa-jawnosci-cen' ) );
		}

		return Result::success( __( 'Licencja została pomyślnie aktywowana!', 'ustawa-jawnosci-cen' ) );
	}
}
