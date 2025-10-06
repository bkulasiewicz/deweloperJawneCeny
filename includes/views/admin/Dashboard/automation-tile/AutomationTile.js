/**
 * AutomationTile JavaScript functionality
 * Handles external cron toggle functionality
 */

function toggleExternalCron(enable) {
    const button = document.getElementById('external-cron-toggle-btn');
    const originalText = button.textContent;
    button.textContent = enable ? '⏳ Włączanie...' : '⏳ Wyłączanie...';
    button.disabled = true;

    const nonce = jawneceny_automation_ajax.nonce;
    const schedule = document.getElementById('external-cron-schedule-select') ?
        document.getElementById('external-cron-schedule-select').value : '24hour';

    jQuery.post(jawneceny_automation_ajax.ajaxurl, {
        action: 'jawneceny_toggle_external_cron',
        enable: enable,
        schedule: schedule,
        nonce: nonce
    }, function(response) {
        if (response.success) {
            alert('✅ ' + response.data);
            location.reload();
        } else {
            alert('❌ Błąd: ' + (response.data || 'Nieznany błąd'));
            button.textContent = originalText;
            button.disabled = false;
        }
    }).fail(function(xhr, status, error) {
        console.error('Toggle External Cron AJAX Error:', xhr, status, error);
        alert('❌ Błąd połączenia: ' + error);
        button.textContent = originalText;
        button.disabled = false;
    });
}