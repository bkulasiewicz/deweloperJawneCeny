/**
 * PublicationPage JavaScript functionality
 * Handles manual file generation with AJAX
 */

function generateFiles() {
    const button = document.getElementById('generate-files-btn');
    const originalText = button.textContent;
    button.textContent = '⏳ Generowanie...';
    button.disabled = true;

    const nonce = jawnecenyPublicationPageData.nonce;

    jQuery.post(jawnecenyPublicationPageData.ajaxurl, {
        action: 'jawneceny_publication_generate',
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
        console.error('Publication generate AJAX Error:', xhr, status, error);
        alert('❌ Błąd połączenia: ' + error);
        button.textContent = originalText;
        button.disabled = false;
    });
}