<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * License Validator
 *
 * Handles API communication with LMFWC (License Manager for WooCommerce).
 * Provides activation and validation endpoints.
 */
class LicenseValidator {

	/**
	 * API request timeout in seconds
	 *
	 * @var int
	 */
	const API_TIMEOUT = 15;

	/**
	 * Activates license via LMFWC API
	 *
	 * Calls: GET /wp-json/lmfwc/v2/licenses/activate/{license_key}
	 *
	 * @param string $license_key License key to activate.
	 * @return array Response: ['success' => bool, 'message' => string, 'data' => array|null].
	 */
	public function activate( $license_key ) {
		$license_key = sanitize_text_field( $license_key );

		if (empty( $license_key )) {
			return array(
				'success' => false,
				'message' => __( 'License key cannot be empty.', 'ustawa-jawnosci-cen' ),
				'data'    => null,
			);
		}

		$url = LicenseConfig::getActivationUrl( $license_key );

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => self::API_TIMEOUT,
			)
		);

		return $this->parseApiResponse( $response );
	}

	/**
	 * Validates license via LMFWC API
	 *
	 * Calls: GET /wp-json/lmfwc/v2/licenses/validate/{license_key}
	 *
	 * @param string $license_key License key to validate.
	 * @return array Response: ['success' => bool, 'message' => string, 'data' => array|null].
	 */
	public function validate( $license_key ) {
		$license_key = sanitize_text_field( $license_key );

		if (empty( $license_key )) {
			return array(
				'success' => false,
				'message' => __( 'License key cannot be empty.', 'ustawa-jawnosci-cen' ),
				'data'    => null,
			);
		}

		$url = LicenseConfig::getValidationUrl( $license_key );

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => self::API_TIMEOUT,
			)
		);

		return $this->parseApiResponse( $response );
	}

	/**
	 * Parses LMFWC API response
	 *
	 * @param array|WP_Error $response WordPress HTTP API response.
	 * @return array Standardized response: ['success' => bool, 'message' => string, 'data' => array|null].
	 */
	private function parseApiResponse( $response ) {
		// Check for WP_Error.
		if (is_wp_error( $response )) {
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: Error message */
					__( 'Connection error: %s', 'ustawa-jawnosci-cen' ),
					$response->get_error_message()
				),
				'data'    => null,
			);
		}

		// Get response code and body.
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// Decode JSON response.
		$body_data = json_decode( $response_body, true );

		// Handle non-200 responses.
		if (200 !== $response_code) {
			$error_message = $body_data['message'] ?? __( 'Unknown API error.', 'ustawa-jawnosci-cen' );

			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: 1: HTTP code, 2: Error message */
					__( 'API error (%1$d): %2$s', 'ustawa-jawnosci-cen' ),
					$response_code,
					$error_message
				),
				'data'    => null,
			);
		}

		// Validate JSON structure.
		if (! is_array( $body_data ) || ! isset( $body_data['success'] )) {
			return array(
				'success' => false,
				'message' => __( 'Invalid API response format.', 'ustawa-jawnosci-cen' ),
				'data'    => null,
			);
		}

		// CRITICAL FIX: LMFWC API returns success=true even for errors!
		// Real errors are in data.errors field, not in success field.
		// Check for errors in data.errors or data.error_data.
		if (isset( $body_data['data']['errors'] ) && ! empty( $body_data['data']['errors'] )) {
			// Extract error message from errors array.
			$errors        = $body_data['data']['errors'];
			$error_message = '';

			foreach ( $errors as $error_code => $messages ) {
				if (is_array( $messages ) && ! empty( $messages )) {
					$error_message = $messages[0];
					break;
				}
			}

			if (empty( $error_message )) {
				$error_message = __( 'License validation failed.', 'ustawa-jawnosci-cen' );
			}

			return array(
				'success' => false,
				'message' => $error_message,
				'data'    => null,
			);
		}

		// Return parsed response.
		return array(
			'success' => (bool) $body_data['success'],
			'message' => $body_data['message'] ?? '',
			'data'    => $body_data['data'] ?? null,
		);
	}
}
