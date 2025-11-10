document.addEventListener('DOMContentLoaded', function() {
    
    // Price History Modal functionality
    document.querySelectorAll('.ujc-historia-btn, .historia-btn').forEach(function(button) {
        // Skip if already has event listener
        if (button.hasAttribute('data-ujc-history-initialized')) {
            return;
        }

        // Mark as initialized
        button.setAttribute('data-ujc-history-initialized', 'true');

        button.addEventListener('click', function() {
            const resourceId = this.dataset.resourceId;
            const resourceName = this.dataset.resourceName;
            
            if (!resourceId) return;
            
            // Set modal title
            document.getElementById('modal-resource-name').textContent = resourceName;
            
            // Show modal and loading state
            document.getElementById('price-history-modal').style.display = 'flex';
            document.getElementById('history-loading').style.display = 'block';
            document.getElementById('history-content').style.display = 'none';
            
            // Make AJAX request (no nonce - price history is public)
            fetch(jawnecenyResourcesListAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'jawneceny_get_price_history',
                    resource_id: resourceId
                })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('history-loading').style.display = 'none';
                
                if (data.success) {
                    displayPriceHistory(data.data.history);
                } else {
                    document.getElementById('history-content').innerHTML = 
                        '<p class="error-message">' + jawnecenyResourcesListAjax.strings.error + '</p>';
                    document.getElementById('history-content').style.display = 'block';
                }
            })
            .catch(error => {
                document.getElementById('history-loading').style.display = 'none';
                document.getElementById('history-content').innerHTML = 
                    '<p class="error-message">' + jawnecenyResourcesListAjax.strings.error + '</p>';
                document.getElementById('history-content').style.display = 'block';
            });
        });
    });
    
    // Floor Plan Download functionality
    document.querySelectorAll('.download-floorplan-btn, .floor-plan-btn').forEach(function(button) {
        // Skip if already has event listener
        if (button.hasAttribute('data-ujc-download-initialized')) {
            return;
        }

        // Mark as initialized
        button.setAttribute('data-ujc-download-initialized', 'true');

        button.addEventListener('click', function() {
            const filename = this.dataset.filename;

            if (!filename) return;

            // Create download URL
            const downloadUrl = window.location.origin + window.location.pathname + '?file=' + encodeURIComponent(filename);

            // Trigger download
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    });
    
    // Close modal functionality
    const modal = document.getElementById('price-history-modal');
    const closeBtn = document.querySelector('.modal-close');
    
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }
    
    // Close modal when clicking outside
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    }
    
    // Prevent closing when clicking modal content
    document.querySelector('.modal-content').addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            modal.style.display = 'none';
        }
    });
    
    function displayPriceHistory(history) {
        if (!history || history.length === 0) {
            document.getElementById('history-content').innerHTML = 
                '<p>' + jawnecenyResourcesListAjax.strings.no_history + '</p>';
            document.getElementById('history-content').style.display = 'block';
            return;
        }
        
        let historyHtml = '';
        
        history.forEach(function(entry) {
            historyHtml += '<div class="history-entry">';
            historyHtml += '<div class="history-date">' + entry.data_zmiany + '</div>';
            
            // Price per m2
            historyHtml += '<div class="history-change">';
            historyHtml += '<span class="history-change-type">Cena za m²:</span>';
            historyHtml += '<span class="price-current">' + formatPrice(entry.cena_m2) + ' zł</span>';
            historyHtml += '</div>';
            
            // Unit price (cena lokalu)
            historyHtml += '<div class="history-change">';
            historyHtml += '<span class="history-change-type">Cena lokalu:</span>';
            historyHtml += '<span class="price-current">' + formatPrice(entry.cena_calkowita) + ' zł</span>';
            historyHtml += '<div class="history-date-small">Data zmiany: ' + entry.data_zmiany + '</div>';
            historyHtml += '</div>';
            
            // Full price (cena pełna)
            historyHtml += '<div class="history-change">';
            historyHtml += '<span class="history-change-type">Cena pełna:</span>';
            historyHtml += '<span class="price-current">' + formatPrice(entry.cena_z_dodatkami) + ' zł</span>';
            historyHtml += '<div class="history-date-small">Data zmiany: ' + entry.data_cena_z_dodatkami + '</div>';
            historyHtml += '</div>';
            
            historyHtml += '</div>';
        });
        
        document.getElementById('history-content').innerHTML = historyHtml;
        document.getElementById('history-content').style.display = 'block';
    }
    
    function formatPrice(price) {
        // Handle NULL, undefined, NaN, or empty values
        if (price === null || price === undefined || price === '' || isNaN(parseFloat(price))) {
            return '—';
        }

        return parseFloat(price).toLocaleString('pl-PL', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('pl-PL', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
});