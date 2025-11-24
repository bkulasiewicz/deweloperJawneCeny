/**
 * License Modal JavaScript
 *
 * Handles license activation form submission via AJAX.
 * Blocks ESC key and clicks outside modal (cannot close).
 */

jQuery(document).ready(function($) {
	'use strict';

	var $modal = $('#jawneceny-license-modal');
	var $form = $('#jawneceny-license-activation-form');
	var $input = $('#license_key');
	var $message = $('#jawneceny-license-message');
	var $submitBtn = $form.find('button[type="submit"]');
	var originalBtnText = $submitBtn.text();

	// Block ESC key - prevent modal closing
	$(document).on('keydown', function(e) {
		if (e.key === 'Escape' && $modal.is(':visible')) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
	});

	// Block clicks outside modal content
	$modal.on('click', function(e) {
		if ($(e.target).is($modal)) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}
	});

	// Auto-format license key input (XXXX-XXXX-XXXX-XXXX)
	$input.on('input', function() {
		var value = $(this).val().toUpperCase().replace(/[^A-Z0-9]/g, '');
		var formatted = '';

		for (var i = 0; i < value.length && i < 16; i++) {
			if (i > 0 && i % 4 === 0) {
				formatted += '-';
			}
			formatted += value[i];
		}

		$(this).val(formatted);
	});

	// Handle form submission
	$form.on('submit', function(e) {
		e.preventDefault();

		var licenseKey = $input.val().trim();

		// Clear previous messages
		$message.html('').removeClass('error success');

		// Validate license key format
		if (licenseKey.length === 0) {
			showError('Klucz licencji nie może być pusty.');
			return;
		}

		if (!/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/.test(licenseKey)) {
			showError('Nieprawidłowy format klucza. Poprawny format: XXXX-XXXX-XXXX-XXXX');
			return;
		}

		// Disable form during submission
		$submitBtn.prop('disabled', true).text(jawnecenyLicenseModal.strings.activating);
		$input.prop('disabled', true);

		// Submit AJAX request
		$.post(
			jawnecenyLicenseModal.ajaxurl,
			{
				action: 'jawneceny_activate_license',
				nonce: jawnecenyLicenseModal.nonce,
				license_key: licenseKey
			},
			function(response) {
				if (response.success) {
					showSuccess(response.data);
					// Reload page after 1 second to apply license changes
					setTimeout(function() {
						location.reload();
					}, 1000);
				} else {
					showError(response.data || jawnecenyLicenseModal.strings.error);
					// Re-enable form
					$submitBtn.prop('disabled', false).text(originalBtnText);
					$input.prop('disabled', false).focus();
				}
			}
		).fail(function() {
			showError(jawnecenyLicenseModal.strings.error);
			// Re-enable form
			$submitBtn.prop('disabled', false).text(originalBtnText);
			$input.prop('disabled', false).focus();
		});
	});

	/**
	 * Shows error message
	 */
	function showError(message) {
		$message.html('<p class="error"><strong>Błąd:</strong> ' + escapeHtml(message) + '</p>').addClass('error');
	}

	/**
	 * Shows success message
	 */
	function showSuccess(message) {
		$message.html('<p class="success"><strong>Sukces:</strong> ' + escapeHtml(message) + '</p>').addClass('success');
	}

	/**
	 * Escapes HTML to prevent XSS
	 */
	function escapeHtml(text) {
		var map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return text.replace(/[&<>"']/g, function(m) { return map[m]; });
	}

	// Focus input field on page load
	if ($modal.is(':visible')) {
		$input.focus();
	}
});
