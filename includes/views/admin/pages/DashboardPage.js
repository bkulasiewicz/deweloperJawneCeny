jQuery(document).ready(function($) {

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
