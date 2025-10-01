jQuery(document).ready(function($) {
    let currentInvestmentData = null;

    // Otwórz modal inwestycji
    window.openInvestmentModal = function(investmentId = null) {
        if (investmentId === null) {
            showCreateMode();
        } else {
            loadInvestmentData(investmentId);
        }
        $('#ujc-investment-modal').show();
    };

    // Pokaż tryb dodawania nowej inwestycji
    function showCreateMode() {
        currentInvestmentData = null;
        $('#investment-edit-form')[0].reset();
        $('#investment-view').hide();
        $('#investment-edit').show();
        $('#investment-modal-title').text('Dodaj Nową Inwestycję');
        $('#save-investment-btn').text('Dodaj Inwestycję');
        setEditMode();
    }

    // Załaduj dane inwestycji
    function loadInvestmentData(investmentId = null) {
        const nonce = typeof investmentModalData !== 'undefined' ? investmentModalData.nonce : '';

        console.log('Loading investment data, investmentId:', investmentId, 'nonce:', nonce);

        const requestData = {
            action: 'jawneceny_get_investment',
            nonce: nonce
        };

        if (investmentId !== null) {
            requestData.investment_id = investmentId;
        }

        $.post(investmentModalData.ajaxurl, requestData, function(response) {
            if (response.success) {
                currentInvestmentData = response.data;
                populateViewData(response.data);
                populateEditForm(response.data);
                // Show in VIEW mode
                $('#investment-view').show();
                $('#investment-edit').hide();
                $('#investment-modal-title').text('Dane Inwestycji');
                setViewMode();
            } else {
                console.error('Error loading investment data:', response.data);
                alert('Błąd podczas ładowania danych: ' + response.data);
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX error loading investment data:', xhr.responseText, status, error);
            alert('Błąd podczas komunikacji z serwerem');
        });
    }

    // Wypełnij widok danymi
    function populateViewData(data) {
        $('#view-investment-name').text(data.name || 'Brak nazwy');
        $('#view-proj-wojewodztwo').text(data.proj_wojewodztwo || 'Brak danych');
        $('#view-proj-powiat').text(data.proj_powiat || 'Brak danych');
        $('#view-proj-gmina').text(data.proj_gmina || 'Brak danych');
        $('#view-proj-miejscowosc').text(data.proj_miejscowosc || 'Brak danych');
        $('#view-proj-ulica').text(data.proj_ulica || 'Brak danych');
        $('#view-proj-nr').text(data.proj_nr || 'Brak danych');
        $('#view-proj-kod').text(data.proj_kod || 'Brak danych');

        // Configuration checkboxes view
        $('#view-has_property_parts').text(data.has_property_parts ? 'Tak' : 'Nie');
        $('#view-has_belonging_rooms').text(data.has_belonging_rooms ? 'Tak' : 'Nie');
        $('#view-has_usage_rights').text(data.has_usage_rights ? 'Tak' : 'Nie');
        $('#view-has_other_services').text(data.has_other_services ? 'Tak' : 'Nie');

        // Marketing fields view
        $('#view-show_floor_field').text(data.show_floor_field ? 'Tak' : 'Nie');
        $('#view-show_rooms_field').text(data.show_rooms_field ? 'Tak' : 'Nie');
        $('#view-show_description_field').text(data.show_description_field ? 'Tak' : 'Nie');
        $('#view-show_garden_field').text(data.show_garden_field ? 'Tak' : 'Nie');
        $('#view-show_floor_plan_field').text(data.show_floor_plan_field ? 'Tak' : 'Nie');
    }

    // Wypełnij formularz edycji danymi
    function populateEditForm(data) {
        $('#edit-investment-name').val(data.name || '');
        $('#edit-proj-wojewodztwo').val(data.proj_wojewodztwo || '');
        $('#edit-proj-powiat').val(data.proj_powiat || '');
        $('#edit-proj-gmina').val(data.proj_gmina || '');
        $('#edit-proj-miejscowosc').val(data.proj_miejscowosc || '');
        $('#edit-proj-ulica').val(data.proj_ulica || '');
        $('#edit-proj-nr').val(data.proj_nr || '');
        $('#edit-proj-kod').val(data.proj_kod || '');

        // Configuration checkboxes
        $('#edit-has_property_parts').prop('checked', Boolean(data.has_property_parts));
        $('#edit-has_belonging_rooms').prop('checked', Boolean(data.has_belonging_rooms));
        $('#edit-has_usage_rights').prop('checked', Boolean(data.has_usage_rights));
        $('#edit-has_other_services').prop('checked', Boolean(data.has_other_services));

        // Marketing fields checkboxes
        $('#edit-show_floor_field').prop('checked', Boolean(data.show_floor_field));
        $('#edit-show_rooms_field').prop('checked', Boolean(data.show_rooms_field));
        $('#edit-show_description_field').prop('checked', Boolean(data.show_description_field));
        $('#edit-show_garden_field').prop('checked', Boolean(data.show_garden_field));
        $('#edit-show_floor_plan_field').prop('checked', Boolean(data.show_floor_plan_field));
    }

    // Ustaw tryb widoku (tylko odczyt)
    function setViewMode() {
        $('#investment-view-buttons').show();
        $('#investment-edit-buttons').hide();
    }

    // Ustaw tryb edycji
    function setEditMode() {
        $('#investment-view-buttons').hide();
        $('#investment-edit-buttons').show();
    }

    // Włącz tryb edycji
    $(document).on('click', '#edit-investment-btn', function() {
        $('#investment-view').hide();
        $('#investment-edit').show();
        $('#investment-modal-title').text('Edytuj Inwestycję');
        setEditMode();
    });

    // Anuluj edycję
    $(document).on('click', '#cancel-investment-edit', function() {
        if (currentInvestmentData) {
            // Powrót do widoku
            populateViewData(currentInvestmentData);
            populateEditForm(currentInvestmentData);
            $('#investment-edit').hide();
            $('#investment-view').show();
            $('#investment-modal-title').text('Dane Inwestycji');
            setViewMode();
        } else {
            // Zamknij modal
            closeInvestmentModal();
        }
    });

    // Zapisz inwestycję
    $(document).on('click', '#save-investment-btn', function() {
        const nonce = investmentModalData.nonce;

        if (!nonce) {
            console.error('No nonce available for investment save');
            alert('Błąd bezpieczeństwa - odśwież stronę i spróbuj ponownie');
            return;
        }

        const requestData = {
            action: 'jawneceny_update_investment',
            nonce: nonce,
            name: $('#edit-investment-name').val(),
            proj_wojewodztwo: $('#edit-proj-wojewodztwo').val(),
            proj_powiat: $('#edit-proj-powiat').val(),
            proj_gmina: $('#edit-proj-gmina').val(),
            proj_miejscowosc: $('#edit-proj-miejscowosc').val(),
            proj_ulica: $('#edit-proj-ulica').val(),
            proj_nr: $('#edit-proj-nr').val(),
            proj_kod: $('#edit-proj-kod').val(),
            has_property_parts: $('#edit-has_property_parts').is(':checked') ? '1' : '',
            has_belonging_rooms: $('#edit-has_belonging_rooms').is(':checked') ? '1' : '',
            has_usage_rights: $('#edit-has_usage_rights').is(':checked') ? '1' : '',
            has_other_services: $('#edit-has_other_services').is(':checked') ? '1' : '',
            show_floor_field: $('#edit-show_floor_field').is(':checked') ? '1' : '',
            show_rooms_field: $('#edit-show_rooms_field').is(':checked') ? '1' : '',
            show_description_field: $('#edit-show_description_field').is(':checked') ? '1' : '',
            show_garden_field: $('#edit-show_garden_field').is(':checked') ? '1' : '',
            show_floor_plan_field: $('#edit-show_floor_plan_field').is(':checked') ? '1' : ''
        };

        console.log('Saving investment with data:', requestData);

        // Pokazuj stan ładowania
        $('#save-investment-btn').text('Zapisywanie...').prop('disabled', true);

        $.post(investmentModalData.ajaxurl, requestData, function(response) {
            if (response.success) {
                console.log('Investment saved successfully:', response.data);
                alert('Inwestycja została zapisana pomyślnie!');

                // Reload data and show in view mode
                loadInvestmentData();
            } else {
                console.error('Error saving investment:', response.data);
                alert('Błąd podczas zapisywania inwestycji: ' + response.data);
                $('#save-investment-btn').text('Zapisz').prop('disabled', false);
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX error saving investment:', xhr.responseText, status, error);
            alert('Błąd podczas komunikacji z serwerem');
            $('#save-investment-btn').text('Zapisz').prop('disabled', false);
        });
    });

    // Zamknij modal
    window.closeInvestmentModal = function() {
        $('#ujc-investment-modal').hide();
        $('#investment-edit-form')[0].reset();
        currentInvestmentData = null;
    };

    // Zamknij modal klikając na X
    $(document).on('click', '.ujc-modal-close', function() {
        closeInvestmentModal();
    });

    // Zamknij modal klikając na przycisk Zamknij
    $(document).on('click', '.ujc-modal-cancel', function() {
        closeInvestmentModal();
    });

    // Zamknij modal klikając na tło
    $(document).on('click', '#ujc-investment-modal', function(e) {
        if (e.target === this) {
            closeInvestmentModal();
        }
    });

    // Obsługa klawiatury (ESC do zamknięcia)
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#ujc-investment-modal').is(':visible')) {
            closeInvestmentModal();
        }
    });
});