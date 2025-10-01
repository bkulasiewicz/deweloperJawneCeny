jQuery(document).ready(function($) {
    // Toggle marketing fields based on investment configuration
    function toggleMarketingFields(investmentConfig) {
        // Get currently selected property type
        const selectedPropertyType = $('#modal-rodzaj_nieruchomosci').val();

        // Types for which we show additional information section
        const allowedTypes = ['residential_unit', 'service_premises', 'single_family_house'];
        const isAllowedType = allowedTypes.includes(selectedPropertyType);

        const hasAnyMarketingField = investmentConfig.show_floor_field ||
                                   investmentConfig.show_rooms_field ||
                                   investmentConfig.show_description_field ||
                                   investmentConfig.show_garden_field ||
                                   investmentConfig.show_floor_plan_field;

        // Show section only if type is allowed AND fields are enabled
        const showSection = isAllowedType && hasAnyMarketingField;
        $('#marketing-fields-section').toggle(showSection);

        // Show/hide individual fields
        $('#floor-field-row').toggle(investmentConfig.show_floor_field);
        $('#rooms-field-row').toggle(investmentConfig.show_rooms_field);
        $('#description-field-row').toggle(investmentConfig.show_description_field);
        $('#garden-field-row').toggle(investmentConfig.show_garden_field);
        $('#floor-plan-field-row').toggle(investmentConfig.show_floor_plan_field);

    }

    // Załaduj konfigurację inwestycji
    function loadInvestmentConfiguration() {
        const nonce = typeof ujc_ajax !== 'undefined' ? ujc_ajax.nonce : ($('#ujc-nonce').length ? $('#ujc-nonce').val() : '');

        return $.post(typeof ujc_ajax !== 'undefined' ? ujc_ajax.ajax_url : ajaxurl, {
            action: 'ujc_get_investment',
            nonce: nonce
        }).done(function(response) {
            if (response.success) {
                const config = response.data;

                // Show/hide component buttons based on investment config
                $('#add-property-part-btn').toggle(config.has_property_parts == '1');
                $('#add-belonging-room-btn').toggle(config.has_belonging_rooms == '1');
                $('#add-usage-rights-btn').toggle(config.has_usage_rights == '1');
                $('#add-other-services-btn').toggle(config.has_other_services == '1');

                // Show/hide marketing fields based on investment config
                toggleMarketingFields(config);

                // Final price section is always visible now
                $('#final-price-section').show();

                window.investmentConfig = config; // Store globally for later use
            } else {
                console.error('Error loading investment configuration:', response.data);
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX error loading investment config:', xhr, status, error);
        });
    }

    // Otwórz modal - dodawanie
    window.openResourceModal = function(mode = 'add', resourceId = null) {
        // Load investment configuration first
        loadInvestmentConfiguration().always(function() {
            if (mode === 'add') {
                $('#modal-title').text('Dodaj Zasób');
                $('#modal-submit-btn').text('Dodaj Zasób');
                $('#modal-action').val('add');
                $('#resource-id').val('');
                $('#resource-modal-form')[0].reset();
                $('#modal-delete-btn').hide(); // Ukryj przycisk usuń w trybie dodawania
                $('#current-floor-plan').hide(); // Hide existing file section
                $('#modal-floor_plan_pdf').show().prop('disabled', false); // Show and enable file input for new resource
            } else if (mode === 'edit' && resourceId) {
                $('#modal-title').text('Edytuj Zasób');
                $('#modal-submit-btn').text('Zapisz Zmiany');
                $('#modal-action').val('edit');
                $('#resource-id').val(resourceId);
                $('#modal-delete-btn').show(); // Pokaż przycisk usuń w trybie edycji

                // Załaduj dane zasobu
                loadResourceData(resourceId);
            }

            $('#ujc-resource-modal').show();
            setupAutoCalculations();

            // For add mode, apply property type logic with default value
            if (mode === 'add') {
                applyPropertyTypeLogic($('#modal-rodzaj_nieruchomosci').val());
            }
        });
    };

    // Załaduj dane zasobu do edycji
    function loadResourceData(resourceId) {
        const nonce = typeof ujc_ajax !== 'undefined' ? ujc_ajax.nonce : ($('#ujc-nonce').length ? $('#ujc-nonce').val() : '');


        $.post(typeof ujc_ajax !== 'undefined' ? ujc_ajax.ajax_url : ajaxurl, {
            action: 'ujc_get_resource',
            resource_id: resourceId,
            nonce: nonce
        }, function(response) {
            if (response.success) {
                const data = response.data;
                $('#modal-rodzaj_nieruchomosci').val(data.rodzaj_nieruchomosci);
                $('#modal-nr_lokalu').val(data.nr_lokalu);
                $('#modal-powierzchnia_uzytkowa').val(data.powierzchnia_uzytkowa || '');
                $('#modal-cena_m2').val(data.cena_m2);
                $('#modal-cena_calkowita').val(data.cena_calkowita || '');
                $('#modal-cena_z_dodatkami').val(data.cena_z_dodatkami || '');
                // Use window.resourceModalData.defaultStatus passed from PHP
                $('#modal-status').val(data.status || window.resourceModalData.defaultStatus);

                // Load component data and show sections/buttons based on data availability
                if (data.property_part_title) {
                    $('#property-part-title').val(data.property_part_title);
                    $('#property-part-designation').val(data.property_part_designation || '');
                    $('#property-part-price').val(data.property_part_price || '');
                    // Show section, hide button
                    $('#property-parts-section').show();
                    $('#add-property-part-btn').hide();
                } else {
                    $('#property-part-title, #property-part-designation, #property-part-price').val('');
                    // Hide section, show button (if enabled by investment config)
                    $('#property-parts-section').hide();
                    if (window.investmentConfig && window.investmentConfig.has_property_parts == '1') {
                        $('#add-property-part-btn').show();
                    }
                }

                if (data.belonging_room_title) {
                    $('#belonging-room-title').val(data.belonging_room_title);
                    $('#belonging-room-designation').val(data.belonging_room_designation || '');
                    $('#belonging-room-price').val(data.belonging_room_price || '');
                    // Show section, hide button
                    $('#belonging-rooms-section').show();
                    $('#add-belonging-room-btn').hide();
                } else {
                    $('#belonging-room-title, #belonging-room-designation, #belonging-room-price').val('');
                    // Hide section, show button (if enabled by investment config)
                    $('#belonging-rooms-section').hide();
                    if (window.investmentConfig && window.investmentConfig.has_belonging_rooms == '1') {
                        $('#add-belonging-room-btn').show();
                    }
                }

                if (data.usage_right_title) {
                    $('#usage-rights-title').val(data.usage_right_title || '');
                    $('#usage-rights-price').val(data.usage_right_price || '');
                    // Show section, hide button
                    $('#usage-rights-section').show();
                    $('#add-usage-rights-btn').hide();
                } else {
                    $('#usage-rights-title, #usage-rights-price').val('');
                    // Hide section, show button (if enabled by investment config)
                    $('#usage-rights-section').hide();
                    if (window.investmentConfig && window.investmentConfig.has_usage_rights == '1') {
                        $('#add-usage-rights-btn').show();
                    }
                }

                if (data.other_service_title) {
                    $('#other-services-title').val(data.other_service_title || '');
                    $('#other-services-price').val(data.other_service_price || '');
                    // Show section, hide button
                    $('#other-services-section').show();
                    $('#add-other-services-btn').hide();
                } else {
                    $('#other-services-title, #other-services-price').val('');
                    // Hide section, show button (if enabled by investment config)
                    $('#other-services-section').hide();
                    if (window.investmentConfig && window.investmentConfig.has_other_services == '1') {
                        $('#add-other-services-btn').show();
                    }
                }

                // Load marketing fields
                $('#modal-floor_number').val(data.floor_number ?? '');
                $('#modal-room_count').val(data.room_count ?? '');
                $('#modal-additional_description').val(data.additional_description ?? '');
                $('#modal-garden_area').val(data.garden_area ?? '');

                // Handle floor plan PDF if exists
                if (data.floor_plan_pdf) {
                    $('#current-floor-plan').show();
                    $('#current-floor-plan-name').text(data.floor_plan_pdf);
                    $('#modal-floor_plan_pdf').hide().prop('disabled', true); // Hide and disable file input when file exists
                } else {
                    $('#current-floor-plan').hide();
                    $('#modal-floor_plan_pdf').show().prop('disabled', false); // Show and enable file input when no file
                }

                // Przelicz cenę finalną
                calculateFinalPrice();

                // Apply property type logic after loading data
                applyPropertyTypeLogic(data.rodzaj_nieruchomosci);
            } else {
                console.error('Error loading resource data:', response.data);
                alert('❌ Błąd ładowania danych zasobu: ' + (response.data || 'Nieznany błąd'));
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX error loading resource:', xhr, status, error);
            alert('❌ Błąd połączenia: ' + error);
        });
    }

    // Zamknij modal
    function closeModal() {
        $('#ujc-resource-modal').hide();
        $('#resource-modal-form')[0].reset();
        // Reset component buttons and sections
        $('.component-add-btn').hide();
        $('.component-section').hide();
    }

    $('.ujc-modal-close, .ujc-modal-cancel').on('click', closeModal);

    // Generic handler for add buttons - with proper ID mapping
    const buttonToSectionMap = {
        'add-property-part-btn': 'property-parts-section',
        'add-belonging-room-btn': 'belonging-rooms-section',
        'add-usage-rights-btn': 'usage-rights-section',
        'add-other-services-btn': 'other-services-section'
    };

    $('[id^="add-"][id$="-btn"]').on('click', function() {
        const buttonId = $(this).attr('id');
        const sectionId = buttonToSectionMap[buttonId];

        if (sectionId) {
            $(this).hide();
            $('#' + sectionId).show();
            calculateFinalPrice();
        }
    });

    // Zamknij modal kliknięciem poza nim
    $('#ujc-resource-modal').on('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // Obsługa przycisku usuń
    $('#modal-delete-btn').on('click', function() {
        const resourceId = $('#resource-id').val();

        if (!resourceId) {
            alert('❌ Brak ID zasobu do usunięcia');
            return;
        }

        // Potwierdzenie usunięcia
        const confirmation = confirm('Czy na pewno chcesz usunąć ten zasób? Ta operacja jest nieodwracalna.');
        if (!confirmation) {
            return;
        }

        const nonce = typeof ujc_ajax !== 'undefined' ? ujc_ajax.nonce : ($('#ujc-nonce').length ? $('#ujc-nonce').val() : '');
        const $deleteBtn = $(this);
        const originalText = $deleteBtn.text();

        $deleteBtn.text('Usuwanie...').prop('disabled', true);

        $.post(typeof ujc_ajax !== 'undefined' ? ujc_ajax.ajax_url : ajaxurl, {
            action: 'ujc_delete_resource',
            resource_id: resourceId,
            nonce: nonce
        }, function(response) {
            if (response.success) {
                alert('✅ ' + response.data);
                closeModal();
                if (typeof loadResourcesList === 'function') {
                    loadResourcesList();
                }
            } else {
                alert('❌ ' + (response.data || 'Błąd podczas usuwania'));
            }
        }).fail(function() {
            alert('❌ Błąd połączenia');
        }).always(function() {
            $deleteBtn.text(originalText).prop('disabled', false);
        });
    });

    // Remove section button click handlers
    $('.remove-section-btn').on('click', function() {
        const sectionType = $(this).data('section');

        // Hide the section
        $(`#${sectionType}-section`).hide();

        // Clear form data for this section
        clearSectionData(sectionType);

        // Show the corresponding add button
        showAddButtonIfEnabled(sectionType);

        // Recalculate final price
        calculateFinalPrice();
    });

    // Section configuration for data-driven approach
    const sectionConfig = {
        'property-parts': {
            fields: ['#property-part-title', '#property-part-designation', '#property-part-price'],
            button: '#add-property-part-btn'
        },
        'belonging-rooms': {
            fields: ['#belonging-room-title', '#belonging-room-designation', '#belonging-room-price'],
            button: '#add-belonging-room-btn'
        },
        'usage-rights': {
            fields: ['#usage-rights-title', '#usage-rights-price'],
            button: '#add-usage-rights-btn'
        },
        'other-services': {
            fields: ['#other-services-title', '#other-services-price'],
            button: '#add-other-services-btn'
        }
    };

    // Clear form data for a specific section type
    function clearSectionData(sectionType) {
        const config = sectionConfig[sectionType];
        if (config) {
            $(config.fields.join(', ')).val('');
        }
    }

    // Show add button for the section type
    function showAddButtonIfEnabled(sectionType) {
        const config = sectionConfig[sectionType];
        if (config) {
            $(config.button).show();
        }
    }

    // Automatyczne przeliczanie ceny finalnej
    function calculateFinalPrice() {
        const cenaCalkowita = parseFloat($('#modal-cena_calkowita').val()) || 0;

        let totalExtra = 0;

        // Collect prices from visible component sections
        if ($('#property-parts-section').is(':visible')) {
            totalExtra += parseFloat($('#property-part-price').val()) || 0;
        }
        if ($('#belonging-rooms-section').is(':visible')) {
            totalExtra += parseFloat($('#belonging-room-price').val()) || 0;
        }
        if ($('#usage-rights-section').is(':visible')) {
            totalExtra += parseFloat($('#usage-rights-price').val()) || 0;
        }
        if ($('#other-services-section').is(':visible')) {
            totalExtra += parseFloat($('#other-services-price').val()) || 0;
        }

        // Always calculate final price if we have base price
        if (cenaCalkowita > 0) {
            const cenaFinalna = cenaCalkowita + totalExtra;
            $('#modal-cena_z_dodatkami').val(cenaFinalna.toFixed(2));
        } else {
            $('#modal-cena_z_dodatkami').val('');
        }
    }

    // Automatyczne przeliczanie przy zmianie cen
    $('#modal-cena_calkowita, #property-part-price, #belonging-room-price, #usage-rights-price, #other-services-price').on('input', calculateFinalPrice);

    // Submit formularza
    $('#resource-modal-form').on('submit', function(e) {
        e.preventDefault();

        const mode = $('#modal-action').val();
        const action = mode === 'edit' ? 'ujc_update_resource' : 'ujc_save_resource';

        // Create FormData to handle file uploads
        const formData = new FormData(this);
        formData.append('action', action);

        const $submitBtn = $('#modal-submit-btn');
        const originalText = $submitBtn.text();
        $submitBtn.text('Zapisywanie...').prop('disabled', true);

        $.ajax({
            url: typeof ujc_ajax !== 'undefined' ? ujc_ajax.ajax_url : ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('✅ ' + response.data);
                    closeModal();
                    if (typeof loadResourcesList === 'function') {
                        loadResourcesList();
                    }
                } else {
                    alert('❌ ' + (response.data || 'Błąd podczas zapisywania'));
                }
            },
            error: function() {
                alert('❌ Błąd połączenia');
            },
            complete: function() {
                $submitBtn.text(originalText).prop('disabled', false);
            }
        });
    });

    // Automatyczne obliczenia - powierzchnia stała, ceny przeliczane
    function setupAutoCalculations() {
        const $powierzchnia = $('#modal-powierzchnia_uzytkowa');
        const $cenaM2 = $('#modal-cena_m2');
        const $cenaCalkowita = $('#modal-cena_calkowita');

        let lastModified = 'cena_m2'; // Domyślnie przeliczamy od ceny m²

        // Przelicz cenę całkowitą na podstawie ceny m²
        function calculateFromM2() {
            const powierzchnia = parseFloat($powierzchnia.val()) || 0;
            const cenaM2 = parseFloat($cenaM2.val()) || 0;

            if (powierzchnia > 0 && cenaM2 > 0) {
                const calculated = (powierzchnia * cenaM2).toFixed(2);
                $cenaCalkowita.val(calculated);
            }
            calculateFinalPrice();
        }

        // Przelicz cenę m² na podstawie ceny całkowitej
        function calculateFromTotal() {
            const powierzchnia = parseFloat($powierzchnia.val()) || 0;
            const cenaCalkowita = parseFloat($cenaCalkowita.val()) || 0;

            if (powierzchnia > 0 && cenaCalkowita > 0) {
                const calculated = (cenaCalkowita / powierzchnia).toFixed(2);
                $cenaM2.val(calculated);
            }
            calculateFinalPrice();
        }

        // Event handlery
        $cenaM2.off('input change focus').on('input change focus', function() {
            lastModified = 'cena_m2';
            calculateFromM2();
        });

        $cenaCalkowita.off('input change focus').on('input change focus', function() {
            lastModified = 'cena_calkowita';
            calculateFromTotal();
        });

        // Przy zmianie powierzchni, przelicz na podstawie ostatnio modyfikowanej ceny
        $powierzchnia.off('input change').on('input change', function() {
            if (lastModified === 'cena_calkowita') {
                calculateFromTotal();
            } else {
                calculateFromM2();
            }
        });
    }

    // Property type field visibility and validation logic
    function applyPropertyTypeLogic(propertyType) {
        const $cenaM2Field = $('#modal-cena_m2');
        const $cenaM2Row = $cenaM2Field.closest('tr');
        const $servicePremisesInfo = $('#service-premises-info');
        const $powierzchniaField = $('#modal-powierzchnia_uzytkowa');
        const $powierzchniaRow = $powierzchniaField.closest('tr');

        // Hide service premises info by default
        $servicePremisesInfo.hide();

        // Use window.resourceModalData.propertyTypes passed from PHP
        const propertyTypes = window.resourceModalData.propertyTypes;

        switch(propertyType) {
            case propertyTypes.PARKING_SPACE:
                // Hide cena m² and powierzchnia for parking spaces
                $cenaM2Row.hide();
                $cenaM2Field.removeAttr('required');
                $cenaM2Field.val(''); // Clear value
                $powierzchniaRow.hide();
                $powierzchniaField.removeAttr('required');
                $powierzchniaField.val(''); // Clear value
                // Labels: Pola ukryte - no label updates needed
                break;

            case propertyTypes.SERVICE_PREMISES:
                // Show service premises info, show all other fields
                $servicePremisesInfo.show();
                $cenaM2Row.show();
                $cenaM2Field.attr('required', 'required');
                $powierzchniaRow.show();
                $powierzchniaField.attr('required', 'required');
                // Labels: default (required)
                $('label[for="modal-powierzchnia_uzytkowa"]').html('Powierzchnia użytkowa [m²] *');
                $('label[for="modal-cena_m2"]').html('Cena m² *');
                break;

            case propertyTypes.STORAGE_ROOM:
                // Dla komórki powierzchnia i cena m² są opcjonalne
                $cenaM2Row.show();
                $cenaM2Field.removeAttr('required');
                $powierzchniaRow.show();
                $powierzchniaField.removeAttr('required');
                // Labels: optional
                $('label[for="modal-powierzchnia_uzytkowa"]').html('Powierzchnia użytkowa [m²] (opcjonalne)');
                $('label[for="modal-cena_m2"]').html('Cena m² (opcjonalne)');
                break;

            case propertyTypes.RESIDENTIAL_UNIT:
            case propertyTypes.SINGLE_FAMILY_HOUSE:
                // Show and require all fields for residential properties
                $cenaM2Row.show();
                $cenaM2Field.attr('required', 'required');
                $powierzchniaRow.show();
                $powierzchniaField.attr('required', 'required');
                // Labels: default (required)
                $('label[for="modal-powierzchnia_uzytkowa"]').html('Powierzchnia użytkowa [m²] *');
                $('label[for="modal-cena_m2"]').html('Cena m² *');
                break;

            case propertyTypes.GARAGE:
                // Dla garażu powierzchnia i cena m² są opcjonalne
                $cenaM2Row.show();
                $cenaM2Field.removeAttr('required');
                $powierzchniaRow.show();
                $powierzchniaField.removeAttr('required');
                // Labels: optional
                $('label[for="modal-powierzchnia_uzytkowa"]').html('Powierzchnia użytkowa [m²] (opcjonalne)');
                $('label[for="modal-cena_m2"]').html('Cena m² (opcjonalne)');
                break;

            default:
                // Show and require all fields for residential properties
                $cenaM2Row.show();
                $cenaM2Field.attr('required', 'required');
                $powierzchniaRow.show();
                $powierzchniaField.attr('required', 'required');
                // Labels: default (required)
                $('label[for="modal-powierzchnia_uzytkowa"]').html('Powierzchnia użytkowa [m²] *');
                $('label[for="modal-cena_m2"]').html('Cena m² *');
                break;
        }
    }


    // Handle floor plan file removal
    $('#remove-floor-plan').on('click', function() {
        if (confirm('Czy na pewno chcesz usunąć aktualny plik planu mieszkania?')) {
            $('#current-floor-plan').hide();
            $('#current-floor-plan-name').text('');
            $('#modal-floor_plan_pdf').val('').show().prop('disabled', false); // Show and enable file input after removal
            // Set a hidden flag to indicate file should be removed
            if (!$('#shouldRemovePdfFloorPlan-flag').length) {
                $('#resource-modal-form').append('<input type="hidden" id="shouldRemovePdfFloorPlan-flag" name="shouldRemovePdfFloorPlan" value="true">');
            }
        }
    });

    // Clear removal flag when new file is selected
    $('#modal-floor_plan_pdf').on('change', function() {
        $('#shouldRemovePdfFloorPlan-flag').remove();
    });

    // Initialize all functionality
    setupAutoCalculations();

    // Setup property type change event handler
    $('#modal-rodzaj_nieruchomosci').on('change', function() {
        const selectedType = $(this).val();
        applyPropertyTypeLogic(selectedType);

        // Also toggle marketing fields based on property type
        if (window.investmentConfig) {
            toggleMarketingFields(window.investmentConfig);
        }
    });
});