<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * License status enum
 * Values represent different license states with modal messages
 */
enum LicenseStatus: string {
	case NONE = 'none';
	case ACTIVE = 'active';
	case EXPIRED = 'expired';
	case DOMAIN_MISMATCH = 'domain_mismatch';

	/**
	 * Get modal title for this status
	 */
	public function getModalTitle(): string {
		return match($this) {
			self::NONE => 'Aktywuj Licencję Premium',
			self::EXPIRED => 'Licencja Wygasła',
			self::DOMAIN_MISMATCH => 'Niezgodność Domeny',
			self::ACTIVE => '',
		};
	}

	/**
	 * Get modal message for this status
	 *
	 * @param int|null    $expires_at Unix timestamp of expiration (for EXPIRED status).
	 * @param string|null $domain     Saved domain name (for DOMAIN_MISMATCH status).
	 */
	public function getModalMessage( ?int $expires_at = null, ?string $domain = null ): string {
		return match($this) {
			self::NONE => 'Aby korzystać z funkcji premium, wprowadź klucz licencji otrzymany po zakupie.',
			self::EXPIRED => sprintf(
				'Twoja licencja wygasła %s. Odnów licencję, aby kontynuować korzystanie z funkcji premium.',
				wp_date( 'd.m.Y', $expires_at )
			),
			self::DOMAIN_MISMATCH => sprintf(
				'System bezpieczeństwa wykrył niezgodność domeny. Licencja została aktywowana na domenie %s. Wprowadź klucz ponownie lub kup nową licencję.',
				$domain
			),
			self::ACTIVE => '',
		};
	}

	/**
	 * Check if license status is valid
	 */
	public function isValid(): bool {
		return $this === self::ACTIVE;
	}
}
