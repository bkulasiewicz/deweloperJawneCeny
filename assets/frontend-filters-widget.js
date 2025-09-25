/**
 * Frontend JavaScript for Resources Filters Widget
 * Handles form submission, URL parameter management, and user interactions
 */
(function() {
    'use strict';

    // Initialize when DOM is ready
    function initFiltersWidget() {
        const form = document.getElementById('resources-filters-form');
        const clearBtn = document.getElementById('clear-filters');
        const widget = document.querySelector('.resources-filters-widget');

        if (!form || !widget) {
            return; // No filters widget found
        }

        console.log('Initializing Resources Filters Widget');

        // Set initial state based on current URL
        setInitialState(form);

        // Handle form submission
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            handleFormSubmit(form, widget);
        });

        // Handle clear filters
        if (clearBtn) {
            clearBtn.addEventListener('click', function(e) {
                e.preventDefault();
                handleClearFilters(form, widget);
            });
        }

        // Handle individual checkbox changes for immediate visual feedback
        const checkboxes = form.querySelectorAll('.filter-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateFilterGroupState(checkbox);
            });
        });

        // Update visual states on load
        updateAllFilterStates(form);
    }

    /**
     * Set initial checkbox states based on current URL parameters
     */
    function setInitialState(form) {
        const urlParams = new URLSearchParams(window.location.search);
        const checkboxes = form.querySelectorAll('.filter-checkbox');

        checkboxes.forEach(checkbox => {
            const paramName = checkbox.name;
            if (urlParams.has(paramName)) {
                checkbox.checked = true;
            }
        });
    }

    /**
     * Handle form submission - update URL with selected filters
     */
    function handleFormSubmit(form, widget) {
        console.log('Applying filters...');

        // Show loading state
        widget.classList.add('loading');

        const formData = new FormData(form);
        const params = new URLSearchParams(window.location.search);

        // Clear existing filter parameters
        for (let [key] of params.entries()) {
            if (key.startsWith('filter_')) {
                params.delete(key);
            }
        }

        // Add new filter parameters
        for (let [key, value] of formData.entries()) {
            if (key.startsWith('filter_') && value === '1') {
                params.set(key, value);
            }
        }

        // Build new URL
        const newUrl = window.location.pathname +
                      (params.toString() ? '?' + params.toString() : '');

        // Navigate to new URL (this will reload the page with filters applied)
        console.log('Redirecting to:', newUrl);
        window.location.href = newUrl;
    }

    /**
     * Handle clear filters - remove all filter parameters from URL
     */
    function handleClearFilters(form, widget) {
        console.log('Clearing filters...');

        // Show loading state
        widget.classList.add('loading');

        const params = new URLSearchParams(window.location.search);

        // Remove all filter parameters
        for (let [key] of Array.from(params.entries())) {
            if (key.startsWith('filter_')) {
                params.delete(key);
            }
        }

        // Build new URL
        const newUrl = window.location.pathname +
                      (params.toString() ? '?' + params.toString() : '');

        // Clear form visually
        form.reset();
        updateAllFilterStates(form);

        // Navigate to new URL
        console.log('Redirecting to:', newUrl);
        window.location.href = newUrl;
    }

    /**
     * Update visual state of a single filter group based on checkbox state
     */
    function updateFilterGroupState(checkbox) {
        const filterGroup = checkbox.closest('.filter-group');
        if (!filterGroup) return;

        if (checkbox.checked) {
            filterGroup.classList.add('has-active-filter');
        } else {
            filterGroup.classList.remove('has-active-filter');
        }
    }

    /**
     * Update visual states of all filter groups
     */
    function updateAllFilterStates(form) {
        const checkboxes = form.querySelectorAll('.filter-checkbox');
        checkboxes.forEach(checkbox => {
            updateFilterGroupState(checkbox);
        });
    }

    /**
     * Check if any filters are currently active
     */
    function hasActiveFilters() {
        const urlParams = new URLSearchParams(window.location.search);
        for (let [key] of urlParams.entries()) {
            if (key.startsWith('filter_')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Add visual indicator if filters are active
     */
    function updateWidgetState() {
        const widget = document.querySelector('.resources-filters-widget');
        if (!widget) return;

        if (hasActiveFilters()) {
            widget.classList.add('has-active-filters');
        } else {
            widget.classList.remove('has-active-filters');
        }
    }

    /**
     * Enhanced form validation before submission
     */
    function validateForm(form) {
        const checkboxes = form.querySelectorAll('.filter-checkbox:checked');

        if (checkboxes.length === 0) {
            // No filters selected - ask user for confirmation
            return confirm('Nie wybrano żadnych filtrów. Czy chcesz wyczyścić wszystkie filtry?');
        }

        return true;
    }

    /**
     * Add keyboard navigation support
     */
    function addKeyboardSupport() {
        const form = document.getElementById('resources-filters-form');
        if (!form) return;

        form.addEventListener('keydown', function(e) {
            // Submit form on Ctrl+Enter or Cmd+Enter
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                form.dispatchEvent(new Event('submit'));
            }

            // Clear filters on Ctrl+Delete or Cmd+Delete
            if ((e.ctrlKey || e.metaKey) && e.key === 'Delete') {
                e.preventDefault();
                const clearBtn = document.getElementById('clear-filters');
                if (clearBtn) {
                    clearBtn.click();
                }
            }
        });
    }

    /**
     * Add ARIA labels for accessibility
     */
    function enhanceAccessibility() {
        const widget = document.querySelector('.resources-filters-widget');
        if (!widget) return;

        // Add ARIA role and label to the widget
        widget.setAttribute('role', 'search');
        widget.setAttribute('aria-label', 'Filtry zasobów');

        // Add ARIA labels to buttons
        const applyBtn = widget.querySelector('.apply-filters-btn');
        const clearBtn = widget.querySelector('.clear-filters-btn');

        if (applyBtn) {
            applyBtn.setAttribute('aria-label', 'Zastosuj wybrane filtry');
        }

        if (clearBtn) {
            clearBtn.setAttribute('aria-label', 'Wyczyść wszystkie filtry');
        }

        // Add descriptions for screen readers
        const checkboxes = widget.querySelectorAll('.filter-checkbox');
        checkboxes.forEach(checkbox => {
            const helpText = checkbox.closest('.filter-group').querySelector('.filter-help');
            if (helpText) {
                const helpId = 'help-' + checkbox.name.replace(/[^a-zA-Z0-9]/g, '_');
                helpText.id = helpId;
                checkbox.setAttribute('aria-describedby', helpId);
            }
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initFiltersWidget();
            addKeyboardSupport();
            enhanceAccessibility();
            updateWidgetState();
        });
    } else {
        // DOM already loaded
        initFiltersWidget();
        addKeyboardSupport();
        enhanceAccessibility();
        updateWidgetState();
    }

    // Also try to initialize on window load as backup
    window.addEventListener('load', function() {
        // Only run if not already initialized
        if (!document.querySelector('.resources-filters-widget.initialized')) {
            initFiltersWidget();
            addKeyboardSupport();
            enhanceAccessibility();
            updateWidgetState();

            // Mark as initialized
            const widget = document.querySelector('.resources-filters-widget');
            if (widget) {
                widget.classList.add('initialized');
            }
        }
    });

})();