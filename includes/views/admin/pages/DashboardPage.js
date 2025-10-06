jQuery(document).ready(function($) {
    window.manualGeneration = function() {
        const button = document.getElementById('quick-generation-btn');
        const originalText = button.textContent;
        button.textContent = '⏳ Generowanie...';
        button.disabled = true;

        $.post(dashboardPageData.ajaxurl, {
            action: 'jawneceny_manual_generation',
            nonce: dashboardPageData.nonce
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
            console.error('Manual generation AJAX Error:', xhr, status, error);
            alert('❌ Błąd połączenia: ' + error);
            button.textContent = originalText;
            button.disabled = false;
        });
    };

    window.downloadLogs = function() {
        $.post(dashboardPageData.ajaxurl, {
            action: 'jawneceny_download_logs',
            nonce: dashboardPageData.nonce
        }, function(response) {
            if (response.success) {
                // Utwórz i pobierz plik
                const blob = new Blob([response.data.logs], { type: 'text/plain' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = response.data.filename;
                a.click();
                URL.revokeObjectURL(url);
            }
        }).fail(function(xhr, status, error) {
            console.error('Log download error:', error);
        });
    };
});
