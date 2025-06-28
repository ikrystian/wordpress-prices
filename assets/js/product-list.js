/**
 * WordPress Prices - Product List JavaScript
 */

jQuery(document).ready(function($) {
    
    // Initialize quick edit functionality
    initQuickEdit();
    
    // Initialize bulk edit functionality
    initBulkEdit();
    
    /**
     * Initialize quick edit
     */
    function initQuickEdit() {
        // Populate quick edit fields when opened
        $('a.editinline').on('click', function() {
            var postId = $(this).closest('tr').attr('id').replace('post-', '');
            var marginInfo = $(this).closest('tr').find('.wp-prices-margin-column');
            
            if (marginInfo.length) {
                var category = marginInfo.find('.wp-prices-category').text().toLowerCase();
                
                setTimeout(function() {
                    var quickEditRow = $('#edit-' + postId);
                    var marginSelect = quickEditRow.find('select[name="margin_category"]');
                    
                    if (marginSelect.length && category) {
                        marginSelect.val(category);
                    }
                }, 100);
            }
        });
    }
    
    /**
     * Initialize bulk edit
     */
    function initBulkEdit() {
        // Handle bulk edit form submission
        $('#bulk_edit').on('click', function() {
            var bulkRow = $('#bulk-edit');
            var marginSelect = bulkRow.find('select[name="margin_category"]');
            var selectedValue = marginSelect.val();
            
            if (!selectedValue) {
                return;
            }
            
            var postIds = [];
            $('tbody th.check-column input[type="checkbox"]:checked').each(function() {
                postIds.push($(this).val());
            });
            
            if (postIds.length === 0) {
                alert('Nie wybrano żadnych produktów.');
                return;
            }
            
            // Show loading
            showLoading(bulkRow);
            
            // Send AJAX request
            $.ajax({
                url: wp_prices_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'save_bulk_edit_wp_prices',
                    post_ids: postIds,
                    margin_category: selectedValue,
                    _wpnonce: wp_prices_ajax.nonce
                },
                success: function(response) {
                    hideLoading(bulkRow);
                    
                    // Refresh the page to show updated data
                    window.location.reload();
                },
                error: function() {
                    hideLoading(bulkRow);
                    alert('Wystąpił błąd podczas zapisywania zmian.');
                }
            });
        });
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
     * Update margin info in real-time (for future enhancements)
     */
    function updateMarginInfo(productId, marginData) {
        var row = $('#post-' + productId);
        var marginColumn = row.find('.wp-prices-margin-column');
        
        if (marginColumn.length && marginData) {
            var html = '<div class="wp-prices-category">' +
                '<strong>' + marginData.category.charAt(0).toUpperCase() + marginData.category.slice(1) + '</strong>' +
                '</div>';
            
            if (marginData.margin_percentage > 0) {
                html += '<div class="wp-prices-percentage">Marża: ' + marginData.margin_percentage + '%</div>';
                html += '<div class="wp-prices-without-margin">Bez marży: ' + formatPrice(marginData.price_without_margin) + '</div>';
                html += '<div class="wp-prices-margin-amount">Kwota marży: ' + formatPrice(marginData.margin_amount) + '</div>';
            }
            
            marginColumn.html(html);
        }
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
     * Filter products by margin category (future enhancement)
     */
    function addMarginFilter() {
        var filterHtml = '<select name="margin_category_filter" id="margin-category-filter">' +
            '<option value="">Wszystkie kategorie marży</option>' +
            '<option value="no_margin">Bez marży</option>' +
            '</select>';
        
        $('.tablenav.top .alignleft.actions').first().append(filterHtml);
        
        $('#margin-category-filter').on('change', function() {
            var selectedCategory = $(this).val();
            filterProductsByMargin(selectedCategory);
        });
    }
    
    /**
     * Filter products by margin category
     */
    function filterProductsByMargin(category) {
        var rows = $('#the-list tr');
        
        if (!category) {
            rows.show();
            return;
        }
        
        rows.each(function() {
            var marginColumn = $(this).find('.wp-prices-margin-column');
            var shouldShow = false;
            
            if (category === 'no_margin') {
                shouldShow = marginColumn.find('.wp-prices-no-margin').length > 0;
            } else {
                var categoryElement = marginColumn.find('.wp-prices-category');
                if (categoryElement.length) {
                    var productCategory = categoryElement.text().toLowerCase();
                    shouldShow = productCategory === category;
                }
            }
            
            if (shouldShow) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }
    
    // Add margin filter on page load (optional feature)
    // addMarginFilter();
});
