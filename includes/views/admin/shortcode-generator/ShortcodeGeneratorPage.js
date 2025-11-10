jQuery(document).ready(function($) {

    // Tab switching functionality
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();

        const targetTab = $(this).data('tab');
        console.log('Switching to tab:', targetTab);

        // Update active tab
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        // Show/hide tab content
        $('.tab-content').removeClass('active');
        const $targetContent = $('.tab-content[data-tab-content="' + targetTab + '"]');
        console.log('Found content elements:', $targetContent.length);
        $targetContent.addClass('active');

        // Update shortcode preview for the active tab
        if (targetTab === 'resources-list') {
            updateShortcodePreview();
        } else if (targetTab === 'resource-single') {
            updateSingleShortcodePreview();
        }
    });

    // Update shortcode preview when settings change
    function updateShortcodePreview() {
        const selectedTypes = $('input[name="selected_types[]"]:checked').map(function() {
            return this.value;
        }).get();

        // Generate columns with names in format "field:name,field2:name2" - for compact layout
        const columnsWithNames = [];
        // Iterate through selected columns in DOM order (respects drag & drop)
        $('#selected-columns-list .selected-column-item').each(function() {
            const fieldName = $(this).data('column');
            const nameInput = $(this).find('.column-name-input');
            const customName = nameInput.val();
            // Replace spaces with underscores to prevent WordPress from splitting arguments
            const finalName = customName ? customName.trim().replace(/\s+/g, '_') : '';
            const columnString = fieldName + ':' + finalName;
            columnsWithNames.push(columnString);
        });

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

        let shortcode = '[jawneceny_resources_list';

        if (selectedTypes.length > 0) {
            shortcode += ' types="' + selectedTypes.join(',') + '"';
        }

        if (columnsWithNames.length > 0) {
            shortcode += ' columns="' + columnsWithNames.join(',') + '"';
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
        if (statusFontSize) {
            shortcode += ' status_font_size="' + statusFontSize + '"';
        }
        if (statusPadding) {
            shortcode += ' status_padding="' + statusPadding + '"';
        }
        if (statusBorderRadius) {
            shortcode += ' status_border_radius="' + statusBorderRadius + '"';
        }
        if (statusFontWeight) {
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
        if (navigationMode && navigationMode !== '') {
            shortcode += ' navigation_mode="' + navigationMode + '"';
        }

        // Add Zobacz więcej button styling if button mode is selected
        if (navigationMode === 'button') {
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

        // Add display mode and card configuration
        const displayMode = $('input[name="display_mode"]:checked').val();
        if (displayMode && displayMode !== 'table') {
            shortcode += ' display_mode="' + displayMode + '"';
        }

        // Add mobile switch parameter
        const mobileSwitchToCards = $('#mobile-switch-to-cards').is(':checked');
        if (mobileSwitchToCards) {
            shortcode += ' mobile_switch_to_cards="true"';
        }

        // Add card configuration if display_mode is 'cards'
        if (displayMode === 'cards') {
            const cardsPerRow = $('#cards-per-row').val();
            const cardGap = $('#card-gap').val();
            const cardBgColor = $('#card-bg-color').val();
            const cardBorderColor = $('#card-border-color').val();
            const cardBorderRadius = $('#card-border-radius').val();
            const cardPadding = $('#card-padding').val();
            const cardShadow = $('#card-shadow').val();
            const cardHoverShadow = $('#card-hover-shadow').val();

            if (cardsPerRow) {
                shortcode += ' cards_per_row="' + cardsPerRow + '"';
            }
            if (cardGap) {
                shortcode += ' card_gap="' + cardGap + '"';
            }
            if (cardBgColor) {
                shortcode += ' card_bg_color="' + cardBgColor + '"';
            }
            if (cardBorderColor) {
                shortcode += ' card_border_color="' + cardBorderColor + '"';
            }
            if (cardBorderRadius) {
                shortcode += ' card_border_radius="' + cardBorderRadius + '"';
            }
            if (cardPadding) {
                shortcode += ' card_padding="' + cardPadding + '"';
            }
            if (cardShadow) {
                shortcode += ' card_shadow="' + cardShadow + '"';
            }
            if (cardHoverShadow) {
                shortcode += ' card_hover_shadow="' + cardHoverShadow + '"';
            }
        }

        shortcode += ']';

        $('#generated-shortcode').text(shortcode);

        // Automatically update preview when shortcode changes
        updatePreview();
    }

    // Update single resource shortcode preview
    function updateSingleShortcodePreview() {
        // Generate columns with names in format "field:name,field2:name2"
        const columnsWithNames = [];
        // Iterate through selected fields in DOM order (respects drag & drop)
        $('#selected-columns-list-single .selected-column-item').each(function() {
            const fieldName = $(this).data('column');
            const nameInput = $(this).find('.column-name-input-single');
            const customName = nameInput.val();
            // Replace spaces with underscores
            const finalName = customName ? customName.trim().replace(/\s+/g, '_') : '';
            const columnString = fieldName + ':' + finalName;
            columnsWithNames.push(columnString);
        });

        // Card styling options
        const cardBgColor = $('#card_bg_color').val();
        const cardBorderColor = $('#card_border_color').val();
        const cardBorderRadius = $('#card_border_radius').val();
        const cardPadding = $('#card_padding').val();
        const cardTextColor = $('#card_text_color').val();
        const cardLabelColor = $('#card_label_color').val();
        const fieldSpacing = $('#field_spacing').val();

        // Status styling options
        const statusDisplayStyle = $('#status_display_style-single').val();
        const statusAvailableBgColor = $('#status_available_bg_color-single').val();
        const statusAvailableTextColor = $('#status_available_text_color-single').val();
        const statusSoldBgColor = $('#status_sold_bg_color-single').val();
        const statusSoldTextColor = $('#status_sold_text_color-single').val();
        const statusReservedBgColor = $('#status_reserved_bg_color-single').val();
        const statusReservedTextColor = $('#status_reserved_text_color-single').val();
        const statusFontSize = $('#status_font_size-single').val();
        const statusPadding = $('#status_padding-single').val();
        const statusBorderRadius = $('#status_border_radius-single').val();
        const statusFontWeight = $('#status_font_weight-single').val();

        // Button styling options
        const historiaBtnText = $('#historia_btn_text-single').val();
        const historiaBtnBgColor = $('#historia_btn_bg_color-single').val();
        const historiaBtnTextColor = $('#historia_btn_text_color-single').val();
        const kartaBtnText = $('#karta_btn_text-single').val();
        const kartaBtnBgColor = $('#karta_btn_bg_color-single').val();
        const kartaBtnTextColor = $('#karta_btn_text_color-single').val();

        let shortcode = '[jawneceny_resource_single';

        if (columnsWithNames.length > 0) {
            shortcode += ' columns="' + columnsWithNames.join(',') + '"';
        }

        // Add card styling parameters
        if (cardBgColor) {
            shortcode += ' card_bg_color="' + cardBgColor + '"';
        }
        if (cardBorderColor) {
            shortcode += ' card_border_color="' + cardBorderColor + '"';
        }
        if (cardBorderRadius) {
            shortcode += ' card_border_radius="' + cardBorderRadius + '"';
        }
        if (cardPadding) {
            shortcode += ' card_padding="' + cardPadding + '"';
        }
        if (cardTextColor) {
            shortcode += ' card_text_color="' + cardTextColor + '"';
        }
        if (cardLabelColor) {
            shortcode += ' card_label_color="' + cardLabelColor + '"';
        }
        if (fieldSpacing) {
            shortcode += ' field_spacing="' + fieldSpacing + '"';
        }

        // Add status styling parameters
        if (statusDisplayStyle) {
            shortcode += ' status_display_style="' + statusDisplayStyle + '"';
        }
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
        if (statusFontSize) {
            shortcode += ' status_font_size="' + statusFontSize + '"';
        }
        if (statusPadding) {
            shortcode += ' status_padding="' + statusPadding + '"';
        }
        if (statusBorderRadius) {
            shortcode += ' status_border_radius="' + statusBorderRadius + '"';
        }
        if (statusFontWeight) {
            shortcode += ' status_font_weight="' + statusFontWeight + '"';
        }

        // Add button styling parameters
        if (historiaBtnText) {
            shortcode += ' historia_btn_text="' + historiaBtnText + '"';
        }
        if (historiaBtnBgColor) {
            shortcode += ' historia_btn_bg_color="' + historiaBtnBgColor + '"';
        }
        if (historiaBtnTextColor) {
            shortcode += ' historia_btn_text_color="' + historiaBtnTextColor + '"';
        }
        if (kartaBtnText) {
            shortcode += ' karta_btn_text="' + kartaBtnText + '"';
        }
        if (kartaBtnBgColor) {
            shortcode += ' karta_btn_bg_color="' + kartaBtnBgColor + '"';
        }
        if (kartaBtnTextColor) {
            shortcode += ' karta_btn_text_color="' + kartaBtnTextColor + '"';
        }

        shortcode += ']';

        $('#generated-shortcode-single').text(shortcode);

        // Update preview
        updatePreview();
    }

    // Update live preview (shared for both tabs)
    let previewTimeout;
    function updatePreview() {
        // Clear previous timeout
        clearTimeout(previewTimeout);

        // Get active tab context
        const $activeTab = $('.tab-content.active');
        const $previewLoading = $activeTab.find('#preview-loading');
        const $previewContent = $activeTab.find('#preview-content');

        // Show loading
        $previewLoading.show();
        $previewContent.hide();

        // Debounce AJAX call by 500ms
        previewTimeout = setTimeout(function() {
            // Detect active tab and get appropriate shortcode
            const activeTab = $activeTab.data('tab-content');
            const shortcodeId = activeTab === 'resource-single' ? '#generated-shortcode-single' : '#generated-shortcode';
            const shortcodeText = $(shortcodeId).text();

            console.log('Preview for tab:', activeTab, 'shortcode:', shortcodeText);

            const formData = {
                action: 'jawneceny_preview_shortcode',
                nonce: $('[name="nonce"]').val(),
                shortcode: shortcodeText
            };

            $.post(jawnecenyShortcodeGeneratorPageData.ajaxurl, formData)
                .done(function(response) {
                    $previewLoading.hide();
                    if (response.success) {
                        $previewContent.html(response.data.html).show();
                    } else {
                        $previewContent.html('<p style="color: red;">Błąd: ' + (response.data || 'Nieznany błąd') + '</p>').show();
                    }
                })
                .fail(function() {
                    $previewLoading.hide();
                    $previewContent.html('<p style="color: red;">Błąd połączenia z serwerem</p>').show();
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
        updateShortcodePreview(); // This now automatically calls updatePreview()
        toggleColumnNameInputs();
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

    // Update card config section visibility
    function updateCardConfigVisibility() {
        const displayMode = $('input[name="display_mode"]:checked').val();
        const mobileSwitchEnabled = $('#mobile-switch-to-cards').is(':checked');
        const shouldShow = (displayMode === 'cards' || mobileSwitchEnabled);

        $('#card-config-section').toggle(shouldShow);
    }

    // Update shortcode when display mode changes
    $('input[name="display_mode"]').on('change', function() {
        updateCardConfigVisibility();
        updateShortcodePreview();
        updatePreview();
    });

    // Listen to mobile switch checkbox
    $('#mobile-switch-to-cards').on('change', function() {
        updateCardConfigVisibility();
        updateShortcodePreview();
        updatePreview();
    });

    // Initialize visibility on page load
    updateCardConfigVisibility();

    // Update shortcode when card configuration changes
    $('#cards-per-row, #card-gap, #card-border-radius, #card-padding, #card-shadow, #card-hover-shadow').on('input change', function() {
        updateShortcodePreview();
        updatePreview();
    });

    $('#card-bg-color, #card-border-color').on('change input', function() {
        updateColorHexDisplays();
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

    // Single resource tab event listeners
    // Toggle column name inputs for single resource
    function toggleColumnNameInputsSingle() {
        $('.column-checkbox-single').each(function() {
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

    // Update shortcode when single resource checkboxes change
    $(document).on('change', '.column-checkbox-single', function() {
        updateSingleShortcodePreview();
        toggleColumnNameInputsSingle();
    });

    // Update shortcode when single resource field names change
    $(document).on('input', '.column-name-input-single', function() {
        updateSingleShortcodePreview();
    });

    // Update shortcode when card styling changes
    $('#card_bg_color, #card_border_color, #card_text_color, #card_label_color').on('change input', function() {
        updateColorHexDisplays();
        updateSingleShortcodePreview();
    });

    $('#card_border_radius, #card_padding, #field_spacing').on('input', function() {
        updateSingleShortcodePreview();
    });

    // Update shortcode when status styling changes
    $('#status_display_style, #status_font_weight, #status_display_style-single, #status_font_weight-single').on('change', function() {
        // Determine which tab is active and update accordingly
        const $activeTab = $('.tab-content.active');
        const activeTab = $activeTab.data('tab-content');
        if (activeTab === 'resource-single') {
            updateSingleShortcodePreview();
        } else {
            updateShortcodePreview();
        }
    });

    $('#status_available_bg_color, #status_available_text_color, #status_sold_bg_color, #status_sold_text_color, #status_reserved_bg_color, #status_reserved_text_color, #status_available_bg_color-single, #status_available_text_color-single, #status_sold_bg_color-single, #status_sold_text_color-single, #status_reserved_bg_color-single, #status_reserved_text_color-single').on('change input', function() {
        updateColorHexDisplays();
        // Determine which tab is active and update accordingly
        const $activeTab = $('.tab-content.active');
        const activeTab = $activeTab.data('tab-content');
        if (activeTab === 'resource-single') {
            updateSingleShortcodePreview();
        } else {
            updateShortcodePreview();
        }
    });

    $('#status_font_size, #status_padding, #status_border_radius, #status_font_size-single, #status_padding-single, #status_border_radius-single').on('input', function() {
        // Determine which tab is active and update accordingly
        const $activeTab = $('.tab-content.active');
        const activeTab = $activeTab.data('tab-content');
        if (activeTab === 'resource-single') {
            updateSingleShortcodePreview();
        } else {
            updateShortcodePreview();
        }
    });

    // Update shortcode when button styling changes
    $('#historia_btn_text, #karta_btn_text, #historia_btn_text-single, #karta_btn_text-single').on('input', function() {
        // Determine which tab is active and update accordingly
        const $activeTab = $('.tab-content.active');
        const activeTab = $activeTab.data('tab-content');
        if (activeTab === 'resource-single') {
            updateSingleShortcodePreview();
        } else {
            updateShortcodePreview();
        }
    });

    $('#historia_btn_bg_color, #historia_btn_text_color, #karta_btn_bg_color, #karta_btn_text_color, #historia_btn_bg_color-single, #historia_btn_text_color-single, #karta_btn_bg_color-single, #karta_btn_text_color-single').on('change input', function() {
        updateColorHexDisplays();
        // Determine which tab is active and update accordingly
        const $activeTab = $('.tab-content.active');
        const activeTab = $activeTab.data('tab-content');
        if (activeTab === 'resource-single') {
            updateSingleShortcodePreview();
        } else {
            updateShortcodePreview();
        }
    });

    // Copy single shortcode to clipboard
    $('#copy-shortcode-single').on('click', function() {
        const $button = $(this);
        const shortcode = $('#generated-shortcode-single').text();
        const originalHtml = '<span class="dashicons dashicons-admin-page"></span> Kopiuj shortcode';

        // Try modern Clipboard API first
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(shortcode).then(function() {
                $button.text('Skopiowano!').addClass('button-primary');
                setTimeout(() => {
                    $button.html(originalHtml).removeClass('button-primary');
                }, 2000);
            }).catch(function(err) {
                console.error('Clipboard API failed:', err);
                // Fallback to older method
                const $temp = $('<textarea>');
                $('body').append($temp);
                $temp.val(shortcode).select();
                try {
                    const successful = document.execCommand('copy');
                    if (successful) {
                        $button.text('Skopiowano!').addClass('button-primary');
                        setTimeout(() => {
                            $button.html(originalHtml).removeClass('button-primary');
                        }, 2000);
                    } else {
                        alert('Nie udało się skopiować shortcode. Skopiuj go ręcznie.');
                    }
                } catch (fallbackErr) {
                    console.error('Fallback copy failed:', fallbackErr);
                    alert('Nie udało się skopiować shortcode. Skopiuj go ręcznie.');
                }
                $temp.remove();
            });
        } else {
            // Browser doesn't support Clipboard API - use fallback
            const $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(shortcode).select();
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    $button.text('Skopiowano!').addClass('button-primary');
                    setTimeout(() => {
                        $button.html(originalHtml).removeClass('button-primary');
                    }, 2000);
                } else {
                    alert('Nie udało się skopiować shortcode. Skopiuj go ręcznie.');
                }
            } catch (err) {
                console.error('Fallback copy failed:', err);
                alert('Nie udało się skopiować shortcode. Skopiuj go ręcznie.');
            }
            $temp.remove();
        }
    });

    // ========================================
    // DRAG & DROP FOR COLUMNS REORDERING
    // ========================================

    // Initialize jQuery UI Sortable for Lista zasobów
    if ($('#selected-columns-list').length) {
        $('#selected-columns-list').sortable({
            handle: '.drag-handle',
            axis: 'y',
            opacity: 0.7,
            cursor: 'move',
            placeholder: 'column-placeholder',
            update: function() {
                updateShortcodePreview();
            }
        });
    }

    // Initialize jQuery UI Sortable for Szczegóły zasobu
    if ($('#selected-columns-list-single').length) {
        $('#selected-columns-list-single').sortable({
            handle: '.drag-handle',
            axis: 'y',
            opacity: 0.7,
            cursor: 'move',
            placeholder: 'column-placeholder',
            update: function() {
                updateSingleShortcodePreview();
            }
        });
    }

    // Add column button - Lista zasobów
    $(document).on('click', '.add-column-btn', function() {
        const column = $(this).data('column');
        const displayName = $(this).text().replace('+ ', '');
        const defaultNames = {
            'unit_number': 'Numer',
            'property_type': 'Typ',
            'usable_area': 'Powierzchnia',
            'status': 'Status',
            'price_per_m2': 'Cena za m²',
            'total_price': 'Cena lokalu',
            'price_with_extras': 'Cena pełna',
            'floor_number': 'Piętro',
            'room_count': 'Pokoje',
            'additional_description': 'Opis',
            'garden_area': 'Ogród',
            'floor_plan_pdf': 'Plan',
            'historia_cen': 'Historia cen'
        };
        const defaultName = defaultNames[column] || displayName;

        // Add to selected list
        const newItem = $('<div class="selected-column-item" data-column="' + column + '">' +
            '<span class="drag-handle">⋮⋮</span>' +
            '<input type="checkbox" name="visible_columns[]" value="' + column + '" checked class="column-checkbox" style="display:none;">' +
            '<span class="column-label">' + displayName + '</span>' +
            '<input type="text" name="column_names[' + column + ']" value="' + defaultName + '" placeholder="Nazwa" class="column-name-input" data-column="' + column + '">' +
            '<button type="button" class="remove-column-btn" data-column="' + column + '">×</button>' +
            '</div>');

        $('#selected-columns-list').append(newItem);

        // Remove from available list
        $(this).remove();

        // Update sortable
        $('#selected-columns-list').sortable('refresh');
        updateShortcodePreview();
    });

    // Remove column button - Lista zasobów
    $(document).on('click', '.remove-column-btn', function() {
        const column = $(this).data('column');
        const $item = $(this).closest('.selected-column-item');
        const displayName = $item.find('.column-label').text();

        // Check if this is for single or list
        const isSingle = $item.closest('#selected-columns-list-single').length > 0;

        // Remove from selected list
        $item.remove();

        // Add back to available list
        const btnClass = isSingle ? 'add-column-btn-single' : 'add-column-btn';
        const newBtn = $('<button type="button" class="' + btnClass + '" data-column="' + column + '">+ ' + displayName + '</button>');

        if (isSingle) {
            $('.available-columns-list').last().append(newBtn);
            updateSingleShortcodePreview();
        } else {
            $('.available-columns-list').first().append(newBtn);
            updateShortcodePreview();
        }
    });

    // Add column button - Szczegóły zasobu
    $(document).on('click', '.add-column-btn-single', function() {
        const field = $(this).data('column');
        const displayName = $(this).text().replace('+ ', '');
        const defaultNames = {
            'unit_number': 'Numer',
            'property_type': 'Typ',
            'usable_area': 'Powierzchnia',
            'status': 'Status',
            'floor_number': 'Piętro',
            'room_count': 'Pokoje',
            'additional_description': 'Opis',
            'garden_area': 'Ogród',
            'price_per_m2': 'Cena za m²',
            'total_price': 'Cena lokalu',
            'price_with_extras': 'Cena pełna',
            'historia_cen': 'Historia cen',
            'floor_plan_pdf': 'Plan'
        };
        const defaultName = defaultNames[field] || displayName;

        // Add to selected list
        const newItem = $('<div class="selected-column-item" data-column="' + field + '">' +
            '<span class="drag-handle">⋮⋮</span>' +
            '<input type="checkbox" name="visible_columns_single[]" value="' + field + '" checked class="column-checkbox-single" style="display:none;">' +
            '<span class="column-label">' + displayName + '</span>' +
            '<input type="text" name="column_names_single[' + field + ']" value="' + defaultName + '" placeholder="Nazwa" class="column-name-input-single" data-column="' + field + '">' +
            '<button type="button" class="remove-column-btn" data-column="' + field + '">×</button>' +
            '</div>');

        $('#selected-columns-list-single').append(newItem);

        // Remove from available list
        $(this).remove();

        // Update sortable
        $('#selected-columns-list-single').sortable('refresh');
        updateSingleShortcodePreview();
    });

    // Initialize on page load
    toggleColumnNameInputs();
    toggleColumnNameInputsSingle();
    updateColorHexDisplays();
    updateShortcodePreview(); // Generate shortcode first
    updateSingleShortcodePreview(); // Generate single shortcode
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

    // Helper function to copy text to clipboard with fallback
    function copyToClipboard(text, $button, successText, originalText) {
        // Try modern Clipboard API first
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                $button.text('Skopiowano!').addClass('button-primary');
                setTimeout(() => {
                    $button.text(originalText).removeClass('button-primary');
                }, 2000);
            }).catch(function(err) {
                console.error('Clipboard API failed:', err);
                // Fallback to older method
                fallbackCopy(text, $button, originalText);
            });
        } else {
            // Browser doesn't support Clipboard API - use fallback
            fallbackCopy(text, $button, originalText);
        }
    }

    // Fallback copy method using deprecated execCommand
    function fallbackCopy(text, $button, originalText) {
        const $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();
        try {
            const successful = document.execCommand('copy');
            if (successful) {
                $button.text('Skopiowano!').addClass('button-primary');
                setTimeout(() => {
                    $button.text(originalText).removeClass('button-primary');
                }, 2000);
            } else {
                alert('Nie udało się skopiować shortcode. Skopiuj go ręcznie.');
            }
        } catch (err) {
            console.error('Fallback copy failed:', err);
            alert('Nie udało się skopiować shortcode. Skopiuj go ręcznie.');
        }
        $temp.remove();
    }

    // Copy shortcode to clipboard
    $('#copy-shortcode').on('click', function() {
        const $button = $(this);
        const shortcode = $('#generated-shortcode').text();
        copyToClipboard(shortcode, $button, 'Skopiowano!', 'Kopiuj');
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