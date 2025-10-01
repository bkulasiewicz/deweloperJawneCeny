// Funkcja historii cen - modal z inline styles i jQuery wrapper
window.showResourceHistory = function(resourceId) {
    // Użyj jQuery wrapper aby uniknąć konfliktów z $
    jQuery(document).ready(function($) {
        // Usuń poprzedni modal jeśli istnieje
        $('#history-modal').remove();

        // Twórz modal dla historii - kompletne inline style
        var modalHtml = '<div id="history-modal" class="history-modal-overlay">' +
            '<div class="history-modal-content">' +
            '<div class="history-modal-header">' +
            '<h2 class="history-modal-title">Historia Cen - Zasób #' + resourceId + '</h2>' +
            '<span class="ujc-modal-close">&times;</span>' +
            '</div>' +
            '<div class="ujc-modal-body history-modal-body">' +
            '<p class="loading-text">Ładowanie historii...</p>' +
            '</div>' +
            '</div>' +
            '</div>';

        $('body').append(modalHtml);

        // Załaduj dane
        $.post(typeof jawneceny_ajax !== 'undefined' ? jawneceny_ajax.ajax_url : ajaxurl, {
            action: 'jawneceny_get_resource_history',
            resource_id: resourceId,
            nonce: historyModalData.nonce
        }, function(response) {
            if (response.success) {
                $('#history-modal .ujc-modal-body').html(response.data);
            } else {
                $('#history-modal .ujc-modal-body').html('<p>Błąd ładowania historii: ' + (response.data || 'Nieznany błąd') + '</p>');
            }
        }).fail(function(xhr, status, error) {
            $('#history-modal .ujc-modal-body').html('<p>Błąd połączenia z serwerem: ' + error + '</p>');
        });

        // Obsługa zamykania
        $(document).off('click', '#history-modal .ujc-modal-close, #history-modal').on('click', '#history-modal .ujc-modal-close, #history-modal', function(e) {
            if (e.target === this) {
                $('#history-modal').remove();
            }
        });
    });
};