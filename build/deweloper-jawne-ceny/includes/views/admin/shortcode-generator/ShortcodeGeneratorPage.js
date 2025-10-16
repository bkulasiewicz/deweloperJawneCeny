jQuery(document).ready(function($) {

    // Update shortcode preview when settings change
    function updateShortcodePreview() {
        const selectedTypes = $('input[name="selected_types[]"]:checked').map(function() {
            return this.value;
        }).get();

        // Generate columns with names in format "field:name,field2:name2" - for compact layout
        const columnsWithNames = [];
        $('.column-checkbox:checked').each(function() {
            const fieldName = this.value;
            const columnItem = $(this).closest('.compact-column-item');
            const nameInput = columnItem.find('.column-name-input');
            const customName = nameInput.val();
            // Replace spaces with underscores to prevent WordPress from splitting arguments
            const finalName = customName ? customName.trim().replace(/\s+/g, '_') : '';
            const columnString = fieldName + ':' + finalName;
            // Always include column, even with empty name (field:)
            columnsWithNames.push(columnString);
        });

        // Get detail page URL
        const detailPageUrl = $('#detail-page-url').val();

        // Get styling options - support both compact and legacy selectors
        const headerBgColor = $('#header_bg_color').val() || $('#header-bg-color').val();
        const headerTextColor = $('#header_text_color').val() || $('#header-text-color').val();
        const hoverBgColor = $('#hover_bg_color').val() || $('#hover-bg-color').val();
        const textColor = $('#text_color').val() || $('#text-color').val();
        const headerFontFamily = $('#header-font-family').val();
        const contentFontFamily = $('#content-font-family').val();

        // Status styling options (only elements that exist in HTML)
        const statusAvailableBgColor = $('#status_available_bg_color').val();
        const statusAvailableTextColor = $('#status_available_text_color').val();
        const statusSoldBgColor = $('#status_sold_bg_color').val();
        const statusSoldTextColor = $('#status_sold_text_color').val();
        const statusReservedBgColor = $('#status_reserved_bg_color').val();
        const statusReservedTextColor = $('#status_reserved_text_color').val();

        // Button styling options - Historia
        const historiaBtnText = $('#historia_btn_text').val();
        const historiaBtnBgColor = $('#historia_btn_bg_color').val();
        const historiaBtnTextColor = $('#historia_btn_text_color').val();

        // Button styling options - Karta Lokalu
        const kartaBtnText = $('#karta_btn_text').val();
        const kartaBtnBgColor = $('#karta_btn_bg_color').val();
        const kartaBtnTextColor = $('#karta_btn_text_color').val();

        // Get sorting options - combine into single parameter
        const sort = $('#sort').val();
        const sortOrder = $('#sort_order').val();
        const sortingCombined = (sort && sort !== '') ? sort + ':' + sortOrder : '';

        // Get filtering options - combine into single parameter
        const filterBy = $('#filterBy').val();
        const filterValue = $('#filterValue').val();
        const filteringCombined = (filterBy && filterBy !== '' && filterValue && filterValue.trim() !== '') ? filterBy + ':' + filterValue.trim() : '';

        let shortcode = '[resources_list';

        if (selectedTypes.length > 0) {
            shortcode += ' types="' + selectedTypes.join(',') + '"';
        }

        if (columnsWithNames.length > 0) {
            shortcode += ' columns="' + columnsWithNames.join(',') + '"';
        }

        // Add detail_page_url if provided
        if (detailPageUrl && detailPageUrl.trim() !== '') {
            shortcode += ' detail_page_url="' + detailPageUrl.trim() + '"';
        }

        // Add styling parameters
        shortcode += ' header_bg_color="' + headerBgColor + '"';
        shortcode += ' header_text_color="' + headerTextColor + '"';
        shortcode += ' hover_bg_color="' + hoverBgColor + '"';
        shortcode += ' text_color="' + textColor + '"';
        shortcode += ' header_font_family="' + headerFontFamily + '"';
        shortcode += ' content_font_family="' + contentFontFamily + '"';

        // Add status styling parameters (only if set)
        if (statusAvailableBgColor) {
            shortcode += ' status_available_bg_color="' + statusAvailableBgColor + '"';
        }
        if (statusAvailableTextColor) {
            shortcode += ' status_available_text_color="' + statusAvailableTextColor + '"';
        }
        if (statusSoldBgColor) {
            shortcode += ' status_sold_bg_color="' + statusSoldBgColor + '"';
        }
        if (statusSoldTextColor) {
            shortcode += ' status_sold_text_color="' + statusSoldTextColor + '"';
        }
        if (statusReservedBgColor) {
            shortcode += ' status_reserved_bg_color="' + statusReservedBgColor + '"';
        }
        if (statusReservedTextColor) {
            shortcode += ' status_reserved_text_color="' + statusReservedTextColor + '"';
        }

        // Status advanced styling options
        const statusDisplayStyle = $('#status_display_style').val();
        const statusFontSize = $('#status_font_size').val();
        const statusPadding = $('#status_padding').val();
        const statusBorderRadius = $('#status_border_radius').val();
        const statusFontWeight = $('#status_font_weight').val();

        if (statusDisplayStyle && statusDisplayStyle !== 'badge') {
            shortcode += ' status_display_style="' + statusDisplayStyle + '"';
        }
        if (statusFontSize && statusFontSize !== '0.875em') {
            shortcode += ' status_font_size="' + statusFontSize + '"';
        }
        if (statusPadding && statusPadding !== '4px 8px') {
            shortcode += ' status_padding="' + statusPadding + '"';
        }
        if (statusBorderRadius && statusBorderRadius !== '4px') {
            shortcode += ' status_border_radius="' + statusBorderRadius + '"';
        }
        if (statusFontWeight && statusFontWeight !== '500') {
            shortcode += ' status_font_weight="' + statusFontWeight + '"';
        }

        // Add button styling parameters - Historia
        if (historiaBtnText) {
            shortcode += ' historia_btn_text="' + historiaBtnText + '"';
        }
        if (historiaBtnBgColor) {
            shortcode += ' historia_btn_bg_color="' + historiaBtnBgColor + '"';
        }
        if (historiaBtnTextColor) {
            shortcode += ' historia_btn_text_color="' + historiaBtnTextColor + '"';
        }

        // Add button styling parameters - Karta Lokalu
        if (kartaBtnText) {
            shortcode += ' karta_btn_text="' + kartaBtnText + '"';
        }
        if (kartaBtnBgColor) {
            shortcode += ' karta_btn_bg_color="' + kartaBtnBgColor + '"';
        }
        if (kartaBtnTextColor) {
            shortcode += ' karta_btn_text_color="' + kartaBtnTextColor + '"';
        }

        // Add combined sorting parameter
        if (sortingCombined) {
            shortcode += ' sorting="' + sortingCombined + '"';
        }

        // Add combined filtering parameter
        if (filteringCombined) {
            shortcode += ' filtering="' + filteringCombined + '"';
        }

        // Add navigation mode parameters
        const navigationMode = $('input[name="navigation_mode"]:checked').val();
        if (detailPageUrl && detailPageUrl.trim() !== '' && navigationMode === 'button') {
            shortcode += ' navigation_mode="button"';

            const zobaczBtnText = $('#zobacz-btn-text').val();
            const zobaczBtnBgColor = $('#zobacz-btn-bg-color').val();
            const zobaczBtnTextColor = $('#zobacz-btn-text-color').val();
            const zobaczBtnPadding = $('#zobacz-btn-padding').val();
            const zobaczBtnBorderRadius = $('#zobacz-btn-border-radius').val();
            const zobaczBtnFontSize = $('#zobacz-btn-font-size').val();

            if (zobaczBtnText) {
                shortcode += ' zobacz_btn_text="' + zobaczBtnText + '"';
            }
            if (zobaczBtnBgColor) {
                shortcode += ' zobacz_btn_bg_color="' + zobaczBtnBgColor + '"';
            }
            if (zobaczBtnTextColor) {
                shortcode += ' zobacz_btn_text_color="' + zobaczBtnTextColor + '"';
            }
            if (zobaczBtnPadding) {
                shortcode += ' zobacz_btn_padding="' + zobaczBtnPadding + '"';
            }
            if (zobaczBtnBorderRadius) {
                shortcode += ' zobacz_btn_border_radius="' + zobaczBtnBorderRadius + '"';
            }
            if (zobaczBtnFontSize) {
                shortcode += ' zobacz_btn_font_size="' + zobaczBtnFontSize + '"';
            }
        }

        shortcode += ']';

        $('#generated-shortcode').text(shortcode);
    }

    // Update live preview
    let previewTimeout;
    function updatePreview() {
        // Clear previous timeout
        clearTimeout(previewTimeout);

        // Show loading
        $('#preview-loading').show();
        $('#preview-content').hide();

        // Debounce AJAX call by 500ms
        previewTimeout = setTimeout(function() {
            const shortcodeText = $('#generated-shortcode').text();

            const formData = {
                action: 'jawneceny_preview_shortcode',
                nonce: $('[name="nonce"]').val(),
                shortcode: shortcodeText
            };

            $.post(jawnecenyShortcodeGeneratorPageData.ajaxurl, formData)
                .done(function(response) {
                    $('#preview-loading').hide();
                    if (response.success) {
                        $('#preview-content').html(response.data.html).show();
                    } else {
                        $('#preview-content').html('<p style="color: red;">Błąd: ' + (response.data || 'Nieznany błąd') + '</p>').show();
                    }
                })
                .fail(function() {
                    $('#preview-loading').hide();
                    $('#preview-content').html('<p style="color: red;">Błąd połączenia z serwerem</p>').show();
                });
        }, 500);
    }

    // Toggle column name inputs based on visible columns selection - for compact layout
    function toggleColumnNameInputs() {
        $('.column-checkbox').each(function() {
            const isChecked = $(this).is(':checked');
            const columnItem = $(this).closest('.compact-column-item');
            const nameSection = columnItem.find('.column-name-section');

            if (isChecked) {
                nameSection.show();
            } else {
                nameSection.hide();
            }
        });
    }

    // Update hex displays for compact colors
    function updateColorHexDisplays() {
        $('.compact-color-input').each(function() {
            const colorValue = $(this).val().toUpperCase();
            const hexDisplay = $(this).siblings('.color-hex-display');
            hexDisplay.text(colorValue);
        });
    }

    // Update preview and column inputs on changes - for compact layout
    $(document).on('change', '.column-checkbox, input[name="selected_types[]"]', function() {
        updateShortcodePreview();
        toggleColumnNameInputs();
        updatePreview();
    });

    // Update shortcode when compact color inputs change
    $(document).on('change input', '.compact-color-input', function() {
        updateColorHexDisplays();
        updateShortcodePreview();
        updatePreview();
    });

    // Update shortcode when font options change
    $('#header-font-family, #content-font-family').on('change', function() {
        updateShortcodePreview();
        updatePreview();
    });

    // Update shortcode when status styling options change
    $(document).on('change input', '.status-input', function() {
        updateShortcodePreview();
        updatePreview();
    });

    // Update shortcode when button styling options change
    $(document).on('change input', '.button-input', function() {
        updateShortcodePreview();
        updatePreview();
    });

    // Update shortcode when column names change - for compact layout
    $(document).on('input', '.column-name-input', function() {
        updateShortcodePreview();
        updatePreview();
    });

    // Update shortcode when detail page URL changes
    $('#detail-page-url').on('input', function() {
        const hasUrl = $(this).val().trim() !== '';
        $('#navigation-mode-group').toggle(hasUrl);
        if (!hasUrl) {
            $('#button-config-group').hide();
        }
        updateShortcodePreview();
        updatePreview();
    });

    // Update shortcode when navigation mode changes
    $('input[name="navigation_mode"]').on('change', function() {
        const isButton = $(this).val() === 'button';
        $('#button-config-group').toggle(isButton);
        updateShortcodePreview();
        updatePreview();
    });

    // Update shortcode when zobacz button options change
    $('#zobacz-btn-text, #zobacz-btn-padding, #zobacz-btn-border-radius, #zobacz-btn-font-size').on('input', function() {
        updateShortcodePreview();
        updatePreview();
    });

    $('#zobacz-btn-bg-color, #zobacz-btn-text-color').on('change input', function() {
        updateColorHexDisplays();
        updateShortcodePreview();
        updatePreview();
    });

    // Update shortcode when sorting/filtering options change
    $('#sort, #sort_order, #filterBy').on('change', function() {
        updateShortcodePreview();
        updatePreview();
    });

    $('#filterValue').on('input', function() {
        updateShortcodePreview();
        updatePreview();
    });

    // Initialize on page load
    toggleColumnNameInputs();
    updateColorHexDisplays();
    updateShortcodePreview(); // Generate shortcode first
    updatePreview(); // Load initial preview

    // Instructions Modal
    $('#show-instructions').on('click', function() {
        $('#instructions-modal').fadeIn(200);
        $('body').addClass('modal-open');
    });

    $('#close-instructions, #close-instructions-footer, .modal-backdrop').on('click', function() {
        $('#instructions-modal').fadeOut(200);
        $('body').removeClass('modal-open');
    });

    // Prevent modal close when clicking inside modal content
    $('.modal-content').on('click', function(e) {
        e.stopPropagation();
    });

    // Copy shortcode to clipboard
    $('#copy-shortcode').on('click', function() {
        const $button = $(this);  // Zapisz referencję do przycisku
        const shortcode = $('#generated-shortcode').text();
        navigator.clipboard.writeText(shortcode).then(function() {
            $button.text('Skopiowano!').addClass('button-primary');
            setTimeout(() => {
                $button.text('Kopiuj').removeClass('button-primary');
            }, 2000);
        });
    });

    // Removed save functionality - shortcode is generated dynamically only

    // Reset settings
    $('#reset-settings').on('click', function() {
        if (!confirm('Czy na pewno chcesz przywrócić domyślne ustawienia? Ta operacja jest nieodwracalna.')) {
            return;
        }

        const $resetBtn = $(this);
        const originalText = $resetBtn.text();
        $resetBtn.text('Resetowanie...').prop('disabled', true);

        $.post(jawnecenyShortcodeGeneratorPageData.ajaxurl, {
            action: 'jawneceny_reset_frontend_settings',
            nonce: $('[name="nonce"]').val()
        }, function(response) {
            if (response.success) {
                alert('✅ ' + response.data.message);
                if (response.data.reload) {
                    location.reload();
                }
            } else {
                alert('❌ ' + response.data);
            }
        }).fail(function() {
            alert('❌ Błąd połączenia');
        }).always(function() {
            $resetBtn.text(originalText).prop('disabled', false);
        });
    });

    // Button tab switching
    $('.button-tab').on('click', function() {
        const tabId = $(this).data('tab');

        // Update active tab
        $('.button-tab').removeClass('active');
        $(this).addClass('active');

        // Show/hide content
        $('.button-tab-content').hide();
        $('#' + tabId + '-tab').show();
    });
});