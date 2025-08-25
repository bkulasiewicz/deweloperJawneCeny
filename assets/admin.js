jQuery(document).ready(function($) {
    
    // AJAX dla formularza dewelopera
    $('#developer-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=ujc_save_developer';
        
        var $submit = $(this).find('input[type="submit"]');
        var originalText = $submit.val();
        $submit.val(ujc_ajax.strings.saving).prop('disabled', true);
        
        $.post(ujc_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                showNotice('success', ujc_ajax.strings.saved);
            } else {
                showNotice('error', response.data || ujc_ajax.strings.error);
            }
        }).fail(function() {
            showNotice('error', ujc_ajax.strings.error);
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
    
    
    // Dynamiczne dodawanie dodatków
    var extraIndex = 1;
    $('#add-extra').on('click', function() {
        var newExtra = $('.extra-item:first').clone();
        newExtra.find('select, input').each(function() {
            var name = $(this).attr('name');
            if (name) {
                $(this).attr('name', name.replace('[0]', '[' + extraIndex + ']'));
                $(this).val('');
            }
        });
        $('#extras-container').append(newExtra);
        extraIndex++;
    });
    
    // Usuwanie dodatków
    $(document).on('click', '.remove-extra', function() {
        if ($('.extra-item').length > 1) {
            $(this).closest('.extra-item').remove();
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