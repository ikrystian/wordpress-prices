/**
 * WordPress Prices - Admin JavaScript
 */

jQuery(document).ready(function($) {
    
    // Margin settings page functionality
    if ($('#wp-prices-margins-container').length) {
        initMarginSettings();
    }
    
    // Product edit page functionality
    if ($('body').hasClass('post-type-product')) {
        initProductEdit();
    }
    
    /**
     * Initialize margin settings page
     */
    function initMarginSettings() {
        var container = $('#wp-prices-margins-container');
        var addButton = $('#add-margin-row');
        
        // Add new margin row
        addButton.on('click', function(e) {
            e.preventDefault();
            addMarginRow();
        });
        
        // Remove margin row
        container.on('click', '.remove-margin-row', function(e) {
            e.preventDefault();
            $(this).closest('.wp-prices-margin-row').remove();
        });
        
        // Validate margin values
        container.on('input', 'input[name="wp_prices_margins_values[]"]', function() {
            var value = parseFloat($(this).val());
            if (value < 0) {
                $(this).val(0);
            } else if (value > 100) {
                $(this).val(100);
            }
        });
        
        // Auto-format category names
        container.on('blur', 'input[name="wp_prices_margins_categories[]"]', function() {
            var value = $(this).val().toLowerCase().replace(/[^a-z0-9_-]/g, '');
            $(this).val(value);
        });
    }
    
    /**
     * Add new margin row
     */
    function addMarginRow() {
        var container = $('#wp-prices-margins-container');
        var rowHtml = '<div class="wp-prices-margin-row" style="margin-bottom: 10px;">' +
            '<input type="text" name="wp_prices_margins_categories[]" value="" placeholder="Kategoria" class="regular-text" style="margin-right: 10px;" />' +
            '<input type="number" name="wp_prices_margins_values[]" value="" placeholder="Marża %" min="0" max="100" step="0.1" class="small-text" style="margin-right: 10px;" />' +
            '<button type="button" class="button remove-margin-row">Usuń</button>' +
            '</div>';
        
        container.append(rowHtml);
    }
    
    /**
     * Initialize product edit page
     */
    function initProductEdit() {
        var marginSelect = $('select[name="margin_category"]');
        
        if (marginSelect.length) {
            marginSelect.on('change', function() {
                updateMarginInfo();
            });
            
            // Update margin info when price changes
            $('input[name="_regular_price"], input[name="_sale_price"]').on('input', function() {
                setTimeout(updateMarginInfo, 100);
            });
        }
    }
    
    /**
     * Update margin information display
     */
    function updateMarginInfo() {
        var marginSelect = $('select[name="margin_category"]');
        var selectedCategory = marginSelect.val();
        var infoContainer = $('.wp-prices-info');
        
        if (!selectedCategory) {
            infoContainer.hide();
            return;
        }
        
        var price = parseFloat($('input[name="_regular_price"]').val()) || parseFloat($('input[name="_sale_price"]').val()) || 0;
        
        if (price <= 0) {
            infoContainer.hide();
            return;
        }
        
        // Get margin percentage from select option text
        var selectedOption = marginSelect.find('option:selected');
        var optionText = selectedOption.text();
        var marginMatch = optionText.match(/\((\d+(?:\.\d+)?)%\)/);
        
        if (!marginMatch) {
            infoContainer.hide();
            return;
        }
        
        var marginPercentage = parseFloat(marginMatch[1]);
        var priceWithoutMargin = price / (1 + (marginPercentage / 100));
        var marginAmount = price - priceWithoutMargin;
        
        // Update info display
        var infoHtml = '<strong>Informacje o marży:</strong><br>' +
            'Marża: ' + marginPercentage + '%<br>' +
            'Cena z marżą: ' + formatPrice(price) + '<br>' +
            'Cena bez marży: ' + formatPrice(priceWithoutMargin) + '<br>' +
            'Kwota marży: ' + formatPrice(marginAmount);
        
        infoContainer.html(infoHtml).show();
    }
    
    /**
     * Format price for display
     */
    function formatPrice(price) {
        return new Intl.NumberFormat('pl-PL', {
            style: 'currency',
            currency: 'PLN'
        }).format(price);
    }
    
    /**
     * Show loading state
     */
    function showLoading(element) {
        element.addClass('wp-prices-loading');
    }
    
    /**
     * Hide loading state
     */
    function hideLoading(element) {
        element.removeClass('wp-prices-loading');
    }
    
    /**
     * Show message
     */
    function showMessage(message, type) {
        var messageHtml = '<div class="wp-prices-message ' + type + '">' + message + '</div>';
        $('.wrap h1').after(messageHtml);
        
        setTimeout(function() {
            $('.wp-prices-message').fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Validate form before submission
     */
    $('form').on('submit', function(e) {
        var isValid = true;
        var errorMessages = [];
        
        // Validate margin categories
        $('input[name="wp_prices_margins_categories[]"]').each(function() {
            var category = $(this).val().trim();
            var value = $(this).siblings('input[name="wp_prices_margins_values[]"]').val();
            
            if (category && !value) {
                errorMessages.push('Kategoria "' + category + '" nie ma ustawionej marży.');
                isValid = false;
            } else if (!category && value) {
                errorMessages.push('Marża ' + value + '% nie ma przypisanej kategorii.');
                isValid = false;
            }
        });
        
        // Check for duplicate categories
        var categories = [];
        $('input[name="wp_prices_margins_categories[]"]').each(function() {
            var category = $(this).val().trim();
            if (category) {
                if (categories.indexOf(category) !== -1) {
                    errorMessages.push('Kategoria "' + category + '" jest zduplikowana.');
                    isValid = false;
                } else {
                    categories.push(category);
                }
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Błędy w formularzu:\n' + errorMessages.join('\n'));
        }
    });
});
