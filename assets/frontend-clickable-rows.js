/**
 * Frontend JavaScript for clickable rows in resources_list shortcode
 * Handles row clicks with fallback for popup blockers
 */
(function() {
    'use strict';

    function initClickableRows() {
        // Find all clickable rows with improved selector
        const clickableRows = document.querySelectorAll('.clickable-row[data-detail-url]');

        clickableRows.forEach(function(row) {
            row.addEventListener('click', function(e) {
                // Don't trigger row click if clicking on buttons or links
                if (e.target.tagName === 'BUTTON' ||
                    e.target.closest('button') ||
                    e.target.tagName === 'A' ||
                    e.target.closest('a')) {
                    return;
                }

                // Get the detail URL from data attribute
                const detailUrl = this.getAttribute('data-detail-url');
                if (!detailUrl) {
                    return;
                }

                // Open in new tab (fallback temporarily disabled for testing)
                window.open(detailUrl, '_blank');

                // FALLBACK CODE (temporarily disabled):
                // try {
                //     const newWindow = window.open(detailUrl, '_blank');
                //     if (!newWindow || newWindow.closed || typeof newWindow.closed === 'undefined') {
                //         if (confirm('Otwórz stronę szczegółów w nowej karcie?\n\n' + detailUrl)) {
                //             window.location.href = detailUrl;
                //         }
                //     }
                // } catch (error) {
                //     if (confirm('Nie można otworzyć nowej karty. Przejść do strony?\n\n' + detailUrl)) {
                //         window.location.href = detailUrl;
                //     }
                // }
            });

            // Add visual feedback for clickable rows
            row.style.cursor = 'pointer';
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initClickableRows);
    } else {
        // DOM already loaded
        initClickableRows();
    }

    // Also try to initialize on window load as backup
    window.addEventListener('load', function() {
        // Only run if not already initialized
        if (document.querySelectorAll('.clickable-row[data-detail-url]').length > 0) {
            initClickableRows();
        }
    });

})();