<?php

namespace JawneCeny;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * License Tile Component
 * Displays license status, activation date and expiration date on dashboard
 */
class LicenseTile {

	private $licenseRepository;

	public function __construct( LicenseRepository $licenseRepository ) {
		$this->licenseRepository = $licenseRepository;
	}

	/**
	 * Render the license tile
	 */
	public function render() {
		// Check if this is FREE version (no premium folder)
		$is_free_version = ! file_exists( JAWNECENY_PLUGIN_DIR . 'includes/premium/' );

		if ( $is_free_version ) {
			$this->render_free_version();
			return;
		}

		// Premium version - check license
		$license_data = $this->licenseRepository->get();
		$is_valid     = $this->licenseRepository->isValid();
		?>
		<div class="automation-control">
			<h2>Licencja</h2>
			<?php $this->render_license_info( $license_data, $is_valid ); ?>
		</div>
		<?php
	}

	/**
	 * Render FREE version message
	 */
	private function render_free_version() {
		?>
		<div class="automation-control">
			<h2>Licencja</h2>
			<div style="text-align: center; padding: 15px 0;">
				<div style="font-size: 14px; color: #666; margin-bottom: 10px;">
					Wersja darmowa
				</div>
				<p style="color: #666; margin: 10px 0; font-size: 12px;">
					Przejdź na wersję Premium aby odblokować<br>automatyczne publikowanie danych.
				</p>
				<a href="https://www.deweloperjawneceny.pl/?utm_source=wordpress&utm_medium=license-tile&utm_campaign=upgrade"
				   target="_blank"
				   style="display: inline-block; background: #2271b1; color: #fff; padding: 8px 16px; text-decoration: none; border-radius: 3px; font-size: 13px;">
					Kup licencję Premium
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Render license information
	 *
	 * @param array|null $license_data License data from repository.
	 * @param bool       $is_valid     Whether license is currently valid.
	 */
	private function render_license_info( $license_data, $is_valid ) {
		if ( empty( $license_data ) || ! $is_valid ) {
			$this->render_no_license();
			return;
		}

		$activated_at = $license_data['activated_at'] ?? null;
		$expires_at   = $license_data['expires_at'] ?? null;
		?>
		<div class="ujc-license-info">
			<div class="ujc-license-item" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eee;">
				<span style="color: #666;">Status:</span>
				<span style="color: #00a32a; font-weight: 500;">Aktywna</span>
			</div>

			<div class="ujc-license-item" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eee;">
				<span style="color: #666;">Data aktywacji:</span>
				<span style="font-weight: 500;">
					<?php echo esc_html( $this->format_date( $activated_at ) ); ?>
				</span>
			</div>

			<div class="ujc-license-item" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0;">
				<span style="color: #666;">Data wygaśnięcia:</span>
				<span style="font-weight: 500;">
					<?php echo esc_html( $this->format_expiration( $expires_at ) ); ?>
				</span>
			</div>
		</div>
		<?php
	}

	/**
	 * Render message when premium version but no valid license
	 */
	private function render_no_license() {
		?>
		<p style="text-align: center; color: #666; margin: 20px 0;">
			Brak aktywnej licencji.<br>
			<small>Aktywuj licencję w ustawieniach wtyczki.</small>
		</p>
		<?php
	}

	/**
	 * Format Unix timestamp to user-friendly date
	 *
	 * @param int|null $timestamp Unix timestamp.
	 * @return string Formatted date or placeholder.
	 */
	private function format_date( $timestamp ) {
		if ( empty( $timestamp ) ) {
			return '-';
		}

		return wp_date( 'd.m.Y H:i', $timestamp );
	}

	/**
	 * Format expiration date or show "Lifetime" for null
	 *
	 * @param int|null $expires_at Unix timestamp or null for lifetime.
	 * @return string Formatted date or "Lifetime".
	 */
	private function format_expiration( $expires_at ) {
		if ( null === $expires_at ) {
			return 'Bezterminowa';
		}

		return wp_date( 'd.m.Y H:i', $expires_at );
	}
}
