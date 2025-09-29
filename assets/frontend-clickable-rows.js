/**
 * Frontend JavaScript for clickable rows and cards in resources list
 * Handles row/card clicks with fallback for popup blockers
 */
(function() {
    'use strict';

    function initClickableElements() {
        // Find all clickable rows and cards with improved selector
        const clickableElements = document.querySelectorAll('.clickable-row[data-detail-url], .clickable-card[data-detail-url]');

        clickableElements.forEach(function(element) {
            element.addEventListener('click', function(e) {
                // Don't trigger click if clicking on buttons or links
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

            // Add visual feedback for clickable elements
            element.style.cursor = 'pointer';
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initClickableElements);
    } else {
        // DOM already loaded
        initClickableElements();
    }

    // Also try to initialize on window load as backup
    window.addEventListener('load', function() {
        // Only run if not already initialized
        if (document.querySelectorAll('.clickable-row[data-detail-url], .clickable-card[data-detail-url]').length > 0) {
            initClickableElements();
        }
    });

})();