/**
 * SetupChecklistTile JavaScript functionality
 * Handles checkbox toggle and dismiss functionality
 */

function toggleChecklistItem(checkbox) {
    const itemKey = checkbox.dataset.key;
    const isChecked = checkbox.checked;
    const listItem = checkbox.closest('.checklist-item');

    // Optimistic UI update
    if (isChecked) {
        listItem.classList.add('completed');
    } else {
        listItem.classList.remove('completed');
    }

    jQuery.post(jawneceny_checklist_ajax.ajaxurl, {
        action: 'jawneceny_toggle_checklist_item',
        item_key: itemKey,
        checked: isChecked,
        nonce: jawneceny_checklist_ajax.nonce
    }, function(response) {
        if (response.success) {
            // Hide tile if all completed
            if (response.data.all_completed) {
                hideTileWithAnimation();
            }
        } else {
            // Revert on failure
            checkbox.checked = !isChecked;
            if (isChecked) {
                listItem.classList.remove('completed');
            } else {
                listItem.classList.add('completed');
            }
            console.error('Error toggling checklist item:', response.data);
        }
    }).fail(function(xhr, status, error) {
        // Revert on AJAX failure
        checkbox.checked = !isChecked;
        if (isChecked) {
            listItem.classList.remove('completed');
        } else {
            listItem.classList.add('completed');
        }
        console.error('AJAX Error:', error);
    });
}

function hideTileWithAnimation() {
    const tile = document.getElementById('setup-checklist-tile');
    if (tile) {
        tile.style.transition = 'opacity 0.5s, transform 0.5s';
        tile.style.opacity = '0';
        tile.style.transform = 'scale(0.95)';
        setTimeout(function() {
            tile.style.display = 'none';
        }, 500);
    }
}

function dismissChecklist() {
    if (!confirm('Czy na pewno chcesz ukryć listę kontrolną?')) {
        return;
    }

    jQuery.post(jawneceny_checklist_ajax.ajaxurl, {
        action: 'jawneceny_dismiss_checklist',
        nonce: jawneceny_checklist_ajax.nonce
    }, function(response) {
        if (response.success) {
            hideTileWithAnimation();
        } else {
            alert('Błąd: ' + (response.data || 'Nie udało się ukryć listy'));
        }
    }).fail(function(xhr, status, error) {
        console.error('Dismiss AJAX Error:', error);
        alert('Błąd połączenia');
    });
}
