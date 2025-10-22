/**
 * PublicationPage JavaScript functionality
 * Handles manual file generation with AJAX and copy functionality
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

function copyPublicUrls() {
    const content = document.getElementById('public-urls-content');

    // Extract URLs from the content
    const links = content.querySelectorAll('a');
    if (links.length < 2) {
        alert('❌ Nie znaleziono URLi do skopiowania');
        return;
    }

    const xmlUrl = links[0].textContent;
    const md5Url = links[1].textContent;

    const textToCopy = `Plik XML: ${xmlUrl}\nPlik MD5: ${md5Url}`;

    // Copy to clipboard using modern Clipboard API
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(textToCopy).then(function() {
            alert('✅ Skopiowano do schowka!');
        }).catch(function(err) {
            console.error('Clipboard API error:', err);
            fallbackCopy(textToCopy);
        });
    } else {
        fallbackCopy(textToCopy);
    }
}

// Fallback copy method for older browsers
function fallbackCopy(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();

    try {
        document.execCommand('copy');
        alert('✅ Skopiowano do schowka!');
    } catch (err) {
        console.error('Fallback copy error:', err);
        alert('❌ Nie udało się skopiować. Spróbuj ręcznie.');
    }

    document.body.removeChild(textarea);
}