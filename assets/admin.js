jQuery(document).ready(function($) {
    
    // AJAX dla formularza dewelopera
    $('#developer-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=jawneceny_save_developer';
        
        var $submit = $(this).find('input[type="submit"]');
        var originalText = $submit.val();
        $submit.val(jawneceny_ajax.strings.saving).prop('disabled', true);
        
        $.post(jawneceny_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                showNotice('success', jawneceny_ajax.strings.saved);
            } else {
                showNotice('error', response.data || jawneceny_ajax.strings.error);
            }
        }).fail(function() {
            showNotice('error', jawneceny_ajax.strings.error);
        }).always(function() {
            $submit.val(originalText).prop('disabled', false);
        });
    });
    
    // Checkbox "taki sam adres"
    $('#same_address').on('change', function() {
        if ($(this).is(':checked')) {
            // Kopiuj adres siedziby do sprzedaży
            $('input[name^="sprzed_"]').each(function() {
                var field = $(this).attr('name').replace('sprzed_', 'siedz_');
                var value = $('input[name="' + field + '"]').val();
                $(this).val(value);
            });
        }
    });
    
    // Helper functions
    function showNotice(type, message) {
        var $notice = $('<div class="notice ujc-notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').after($notice);
        
        setTimeout(function() {
            $notice.fadeOut();
        }, 3000);
    }
    
    
    
    
});