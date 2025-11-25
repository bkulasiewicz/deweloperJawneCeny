<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * License API Configuration
 *
 * Contains LMFWC API endpoints and credentials for license validation.
 */
class LicenseConfig {

	/**
	 * License API base URL (same domain as plugin distribution)
	 *
	 * Note: Uses /index.php/ path due to permalink configuration
	 *
	 * @var string
	 */
	const API_BASE_URL = 'https://buildwisely.eu/index.php/wp-json/lmfwc/v2';

	/**
	 * LMFWC Consumer Key
	 *
	 * Generated in: WooCommerce → Settings → Advanced → REST API
	 *
	 * @var string
	 */
	const CONSUMER_KEY = 'ck_f0ea89ab4496d0253e21728421d8bc3f2e463d2a';

	/**
	 * LMFWC Consumer Secret
	 *
	 * Generated in: WooCommerce → Settings → Advanced → REST API
	 *
	 * @var string
	 */
	const CONSUMER_SECRET = 'cs_7cbbc576204658f066029687257581333190e992';

	/**
	 * Shop URL for "Buy License" link
	 *
	 * @var string
	 */
	const SHOP_URL = 'https://buildwisely.eu/';

	/**
	 * Get full activation endpoint URL
	 *
	 * @param string $license_key License key to activate.
	 * @return string Full API URL with authentication params.
	 */
	public static function getActivationUrl( $license_key ) {
		return sprintf(
			'%s/licenses/activate/%s?consumer_key=%s&consumer_secret=%s',
			self::API_BASE_URL,
			rawurlencode( $license_key ),
			self::CONSUMER_KEY,
			self::CONSUMER_SECRET
		);
	}

	/**
	 * Get full validation endpoint URL
	 *
	 * @param string $license_key License key to validate.
	 * @return string Full API URL with authentication params.
	 */
	public static function getValidationUrl( $license_key ) {
		return sprintf(
			'%s/licenses/validate/%s?consumer_key=%s&consumer_secret=%s',
			self::API_BASE_URL,
			rawurlencode( $license_key ),
			self::CONSUMER_KEY,
			self::CONSUMER_SECRET
		);
	}
}
