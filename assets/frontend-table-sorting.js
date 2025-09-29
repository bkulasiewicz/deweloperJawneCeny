// Frontend table sorting functionality for resources tables
// Pure JavaScript without WordPress Gutenberg dependencies

// Natural sort key generator for alphanumeric values like A1, A2, A17
function naturalSortKey(value) {
    return value.replace(/(\d+)/g, function(match) {
        return match.padStart(10, '0');
    }).toLowerCase();
}

jQuery(document).ready(function($) {
    // Initialize sorting for all resource tables with sorting enabled
    const sortableTables = document.querySelectorAll('.resources-table[data-sortable="true"]');

    sortableTables.forEach(function(table) {
        initTableSorting(table);
    });
});

function initTableSorting(table) {
    const headers = table.querySelectorAll('th[data-sort-key]');
    let currentSort = { column: null, direction: 'none' };

    headers.forEach(function(header) {
        header.style.cursor = 'pointer';
        header.classList.add('sortable-header');

        // Add sort indicator container
        const sortIndicator = document.createElement('span');
        sortIndicator.className = 'sort-indicator';
        sortIndicator.innerHTML = '▲▼';
        sortIndicator.style.cssText = 'position: absolute; bottom: 4px; right: 6px; font-size: 11px; font-weight: bold; line-height: 1; color: #666;';
        header.appendChild(sortIndicator);

        // Make header relative positioned for absolute indicator
        header.style.position = 'relative';

        header.addEventListener('click', function() {
            const sortKey = header.getAttribute('data-sort-key');

            // Determine sort direction
            let newDirection = 'asc';
            if (currentSort.column === sortKey) {
                if (currentSort.direction === 'asc') {
                    newDirection = 'desc';
                } else if (currentSort.direction === 'desc') {
                    newDirection = 'none';
                } else {
                    newDirection = 'asc';
                }
            }

            // Update sort indicators
            updateSortIndicators(table, sortKey, newDirection);

            // Sort table
            if (newDirection === 'none') {
                restoreOriginalOrder(table);
            } else {
                sortTable(table, sortKey, newDirection);
            }

            currentSort = { column: sortKey, direction: newDirection };
        });
    });

    // Store original row order
    storeOriginalOrder(table);
}

function updateSortIndicators(table, activeSortKey, direction) {
    const headers = table.querySelectorAll('th[data-sort-key]');

    headers.forEach(function(header) {
        const indicator = header.querySelector('.sort-indicator');
        const sortKey = header.getAttribute('data-sort-key');

        const isActive = (sortKey === activeSortKey && direction !== 'none');
        const arrows = { asc: '▲', desc: '▼', none: '▲▼' };

        indicator.innerHTML = arrows[isActive ? direction : 'none'];
        indicator.classList.toggle('active', isActive);

        // Update styling based on state
        if (isActive) {
            indicator.style.color = '#333';
            indicator.style.fontSize = '12px';
        } else {
            indicator.style.color = '#666';
            indicator.style.fontSize = '11px';
        }
    });
}

function storeOriginalOrder(table) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    rows.forEach(function(row, index) {
        row.setAttribute('data-original-index', index);
    });
}

function restoreOriginalOrder(table) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    rows.sort(function(a, b) {
        const aIndex = parseInt(a.getAttribute('data-original-index'));
        const bIndex = parseInt(b.getAttribute('data-original-index'));
        return aIndex - bIndex;
    });

    rows.forEach(function(row) {
        tbody.appendChild(row);
    });
}

function sortTable(table, sortKey, direction) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    rows.sort(function(a, b) {
        const cellA = a.querySelector('[data-sort-value][data-sort-key="' + sortKey + '"]');
        const cellB = b.querySelector('[data-sort-value][data-sort-key="' + sortKey + '"]');

        if (!cellA || !cellB) return 0;

        let valueA = cellA.getAttribute('data-sort-value');
        let valueB = cellB.getAttribute('data-sort-value');

        // Handle different data types
        const dataType = cellA.getAttribute('data-sort-type') || 'string';

        if (dataType === 'number') {
            valueA = parseFloat(valueA) || 0;
            valueB = parseFloat(valueB) || 0;
        } else if (dataType === 'date') {
            valueA = new Date(valueA).getTime() || 0;
            valueB = new Date(valueB).getTime() || 0;
        } else if (dataType === 'alphanumeric') {
            // Natural sort for unit numbers like A1, A2, A17
            valueA = naturalSortKey(valueA.toString());
            valueB = naturalSortKey(valueB.toString());
        } else {
            // String comparison
            valueA = valueA.toString().toLowerCase();
            valueB = valueB.toString().toLowerCase();
        }

        let comparison = 0;
        if (valueA > valueB) {
            comparison = 1;
        } else if (valueA < valueB) {
            comparison = -1;
        }

        return direction === 'desc' ? comparison * -1 : comparison;
    });

    rows.forEach(function(row) {
        tbody.appendChild(row);
    });
}