<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * License Repository
 *
 * Handles storage and retrieval of license data from wp_options.
 * Implements strict validation with auto-clear on domain mismatch.
 */
class LicenseRepository {

	/**
	 * Option key for license data storage
	 */
	const LICENSE_DATA = 'jawneceny_license_data';

	/**
	 * Saves license data to wp_options
	 *
	 * @param array $data License data (key, status, domain, activated_at, last_checked, last_api_status, expires_at).
	 * @return bool True if successfully saved.
	 */
	public function save( array $data ) {
		return update_option( self::LICENSE_DATA, $data );
	}

	/**
	 * Gets license data from wp_options
	 *
	 * @return array|null License data or null if not found.
	 */
	public function get() {
		$data = get_option( self::LICENSE_DATA, null );

		if (empty( $data ) || ! is_array( $data )) {
			return null;
		}

		return $data;
	}

	/**
	 * Clears license data from wp_options
	 *
	 * @return bool True if successfully cleared.
	 */
	public function clear() {
		return delete_option( self::LICENSE_DATA );
	}

	/**
	 * Gets license status as enum
	 *
	 * Returns status without modifying license data.
	 * Use this for displaying appropriate messages in UI.
	 *
	 * @return LicenseStatus Current license status.
	 */
	public function getStatus(): LicenseStatus {
		$license_data = $this->get();

		// No license data exists.
		if ( null === $license_data ) {
			return LicenseStatus::NONE;
		}

		// Check status field.
		if ( ! isset( $license_data['status'] ) || 'active' !== $license_data['status'] ) {
			return LicenseStatus::NONE;
		}

		// Check domain mismatch.
		$current_domain = $this->getDomain();
		$saved_domain   = $license_data['domain'] ?? '';

		if ( $current_domain !== $saved_domain ) {
			return LicenseStatus::DOMAIN_MISMATCH;
		}

		// Check expiration.
		if ( isset( $license_data['expires_at'] ) && null !== $license_data['expires_at'] ) {
			if ( time() > $license_data['expires_at'] ) {
				return LicenseStatus::EXPIRED;
			}
		}

		return LicenseStatus::ACTIVE;
	}

	/**
	 * Gets context data for status messages
	 *
	 * @return array Context with 'expires_at' and 'domain' keys.
	 */
	public function getStatusContext(): array {
		$license_data = $this->get();

		return array(
			'expires_at' => $license_data['expires_at'] ?? null,
			'domain'     => $license_data['domain'] ?? null,
		);
	}

	/**
	 * Checks if license is valid
	 *
	 * Validates:
	 * 1. License data exists
	 * 2. Status is 'active'
	 * 3. Domain matches current domain (strict check, auto-clear on mismatch)
	 * 4. License hasn't expired (if expires_at is set)
	 *
	 * Note: License is only validated during manual activation.
	 * No automatic periodic validation or expiration checks are performed.
	 *
	 * @return bool True if license is valid.
	 */
	public function isValid() {
		return $this->getStatus()->isValid();
	}

	/**
	 * Gets current domain from WordPress home_url
	 *
	 * @return string Current domain (e.g., 'example.com').
	 */
	public function getDomain() {
		$home_url = home_url();
		$parsed   = wp_parse_url( $home_url );

		return $parsed['host'] ?? '';
	}

	/**
	 * Updates last checked timestamp and API status
	 *
	 * @param string $api_status API validation result: 'success', 'failed', 'invalid'.
	 * @return bool True if successfully updated.
	 */
	public function updateLastCheck( $api_status ) {
		$license_data = $this->get();

		if (null === $license_data) {
			return false;
		}

		$license_data['last_checked']    = time();
		$license_data['last_api_status'] = $api_status;

		return $this->save( $license_data );
	}

	/**
	 * Updates license status
	 *
	 * @param string $status New status: 'active', 'invalid', 'expired'.
	 * @return bool True if successfully updated.
	 */
	public function updateStatus( $status ) {
		$license_data = $this->get();

		if (null === $license_data) {
			return false;
		}

		$license_data['status'] = $status;

		return $this->save( $license_data );
	}
}
