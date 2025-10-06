jQuery(document).ready(function($) {
    console.log('DEV Console script loaded');
    console.log('WP_DEBUG status:', devConsoleTileData.wpDebugStatus);

    window.triggerFallback = function() {
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = '⏳ Uruchamianie fallback...';
        button.disabled = true;

        $.post(devConsoleTileData.ajaxurl, {
            action: 'jawneceny_dev_trigger_fallback',
            nonce: devConsoleTileData.nonce
        }, function(response) {
            if (response.success) {
                alert('✅ ' + response.data);
            } else {
                alert('❌ Błąd: ' + (response.data || 'Nieznany błąd'));
            }
        }).fail(function(xhr, status, error) {
            console.error('Trigger Fallback AJAX Error:', xhr, status, error);
            alert('❌ Błąd połączenia: ' + error);
        }).always(function() {
            button.textContent = originalText;
            button.disabled = false;
        });
    };

    window.clearLogs = function() {
        $.post(devConsoleTileData.ajaxurl, {
            action: 'jawneceny_dev_clear_logs',
            nonce: devConsoleTileData.nonce
        }, function(response) {
            if (response.success) {
                alert('✅ ' + response.data);
            } else {
                alert('❌ Błąd: ' + (response.data || 'Nieznany błąd'));
            }
        }).fail(function(xhr, status, error) {
            console.error('Clear Logs AJAX Error:', xhr, status, error);
            alert('❌ Błąd połączenia: ' + error);
        });
    };

    window.confirmClearTable = function(type, description) {
        const message = `Czy na pewno chcesz usunąć ${description}?\n\nTa operacja jest nieodwracalna!`;

        if (confirm(message)) {
            const secondConfirm = `OSTATNIE OSTRZEŻENIE!\n\nUsuwasz: ${description}\n\nKliknij OK aby kontynuować.`;
            if (confirm(secondConfirm)) {
                clearTableData(type);
            }
        }
    };

    function clearTableData(type) {
        $.post(devConsoleTileData.ajaxurl, {
            action: 'jawneceny_dev_clear_table',
            table_type: type,
            nonce: devConsoleTileData.nonce
        }, function(response) {
            if (response.success) {
                alert('✅ ' + response.data);
                location.reload();
            } else {
                alert('❌ Błąd: ' + (response.data || 'Nieznany błąd'));
            }
        }).fail(function(xhr, status, error) {
            console.error('DEV Console AJAX Error:', xhr, status, error);
            alert('❌ Błąd połączenia: ' + error);
        });
    }
});
