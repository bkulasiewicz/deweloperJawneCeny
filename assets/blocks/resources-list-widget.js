document.addEventListener('DOMContentLoaded', function() {
    
    // Price History Modal functionality
    document.querySelectorAll('.price-history-btn').forEach(function(button) {
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
            
            // Make AJAX request
            fetch(resourcesListAjax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_resource_price_history',
                    resource_id: resourceId,
                    nonce: resourcesListAjax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('history-loading').style.display = 'none';
                
                if (data.success) {
                    displayPriceHistory(data.data.history);
                } else {
                    document.getElementById('history-content').innerHTML = 
                        '<p class="error-message">' + resourcesListAjax.strings.error + '</p>';
                    document.getElementById('history-content').style.display = 'block';
                }
            })
            .catch(error => {
                document.getElementById('history-loading').style.display = 'none';
                document.getElementById('history-content').innerHTML = 
                    '<p class="error-message">' + resourcesListAjax.strings.error + '</p>';
                document.getElementById('history-content').style.display = 'block';
            });
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
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
    
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
                '<p>' + resourcesListAjax.strings.no_history + '</p>';
            document.getElementById('history-content').style.display = 'block';
            return;
        }
        
        let historyHtml = '';
        
        history.forEach(function(entry) {
            historyHtml += '<div class="history-entry">';
            historyHtml += '<div class="history-date">' + formatDate(entry.data_zmiany) + '</div>';
            
            // Price per m2
            if (entry.cena_m2_new) {
                historyHtml += '<div class="history-change">';
                historyHtml += '<span class="history-change-type">Cena za m²:</span>';
                historyHtml += '<span class="price-change">';
                
                if (entry.cena_m2_old) {
                    historyHtml += '<span class="price-old">' + formatPrice(entry.cena_m2_old) + ' zł</span> → ';
                }
                historyHtml += '<span class="price-new">' + formatPrice(entry.cena_m2_new) + ' zł</span>';
                historyHtml += '</span>';
                historyHtml += '</div>';
            }
            
            // Total price
            if (entry.cena_calkowita_new) {
                historyHtml += '<div class="history-change">';
                historyHtml += '<span class="history-change-type">Cena całkowita:</span>';
                historyHtml += '<span class="price-change">';
                
                if (entry.cena_calkowita_old) {
                    historyHtml += '<span class="price-old">' + formatPrice(entry.cena_calkowita_old) + ' zł</span> → ';
                }
                historyHtml += '<span class="price-new">' + formatPrice(entry.cena_calkowita_new) + ' zł</span>';
                historyHtml += '</span>';
                historyHtml += '</div>';
            }
            
            // Price with extras
            if (entry.cena_z_dodatkami_new) {
                historyHtml += '<div class="history-change">';
                historyHtml += '<span class="history-change-type">Cena z dodatkami:</span>';
                historyHtml += '<span class="price-change">';
                
                if (entry.cena_z_dodatkami_old) {
                    historyHtml += '<span class="price-old">' + formatPrice(entry.cena_z_dodatkami_old) + ' zł</span> → ';
                }
                historyHtml += '<span class="price-new">' + formatPrice(entry.cena_z_dodatkami_new) + ' zł</span>';
                historyHtml += '</span>';
                historyHtml += '</div>';
            }
            
            historyHtml += '</div>';
        });
        
        document.getElementById('history-content').innerHTML = historyHtml;
        document.getElementById('history-content').style.display = 'block';
    }
    
    function formatPrice(price) {
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