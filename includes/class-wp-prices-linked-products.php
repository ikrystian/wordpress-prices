<?php

/**
 * WP Prices Linked Products Class
 * 
 * Handles WooCommerce linked products with checkboxes interface
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WP_Prices_Linked_Products Class
 */
class WP_Prices_Linked_Products
{

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        // Add custom tab to WooCommerce product data panel
        add_filter('woocommerce_product_data_tabs', array($this, 'add_linked_products_tab'));
        add_action('woocommerce_product_data_panels', array($this, 'linked_products_panel'));
        add_action('woocommerce_process_product_meta', array($this, 'save_linked_products_data'), 20, 1);
        add_action('save_post', array($this, 'save_linked_products_data_fallback'), 25, 1);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Add CSS to hide default linked products tab
        add_action('admin_head', array($this, 'hide_default_linked_products_tab'));

        // Add admin notices
        add_action('admin_notices', array($this, 'admin_notice'));
        add_action('wp_ajax_wc_linked_products_dismiss_notice', array($this, 'dismiss_notice'));
    }

    /**
     * Hide default linked products tab with CSS
     */
    public function hide_default_linked_products_tab()
    {
        global $post_type;
        if ($post_type === 'product') {
?>
            <style type="text/css">
                /* Hide default WooCommerce linked products tab */
                .wc-tabs li.linked_product_tab,
                .wc-tabs li a[href="#linked_product_data"],
                #linked_product_data.panel {
                    display: none !important;
                }
            </style>
        <?php
        }
    }

    /**
     * Add custom tab to product data panel and hide default linked products tab
     */
    public function add_linked_products_tab($tabs)
    {
        // Remove default linked products tab
        unset($tabs['linked_product']);

        $tabs['wc_linked_products_checkboxes'] = array(
            'label'    => __('Produkty powiązane (Checkboxy)', 'wordpress-prices'),
            'target'   => 'wc_linked_products_checkboxes_data',
            'class'    => array('show_if_simple', 'show_if_variable', 'show_if_grouped', 'show_if_external'),
            'priority' => 80,
        );
        return $tabs;
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook)
    {
        global $post_type;
        if (($hook === 'post.php' || $hook === 'post-new.php') && $post_type === 'product') {
            wp_enqueue_script('jquery');
        }
    }

    /**
     * Render the linked products panel
     */
    public function linked_products_panel()
    {
        global $post;

        echo '<div id="wc_linked_products_checkboxes_data" class="panel woocommerce_options_panel">';

        // Add nonce for security
        wp_nonce_field('wc_linked_products_nonce_action', 'wc_linked_products_nonce');

        // Get current cross-sell and up-sell products
        $crosssell_ids = get_post_meta($post->ID, '_crosssell_ids', true);
        $upsell_ids = get_post_meta($post->ID, '_upsell_ids', true);

        if (!is_array($crosssell_ids)) {
            $crosssell_ids = array();
        }
        if (!is_array($upsell_ids)) {
            $upsell_ids = array();
        }

        // Get all published products except current one
        $products = get_posts(array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'numberposts' => -1,
            'exclude' => array($post->ID),
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        echo '<div id="wc-combined-container" style="display: flex; gap: 20px; gap: 0.5rem;">';

        // Cross-sell section
        echo '<div id="wc-crosssell-section" style="flex: 1;">';
        echo '<h3 style="margin-top: 0; color: #23282d; border-bottom: 1px solid #ddd; padding-bottom: 10px;">' . __('Cross-sell produkty', 'wordpress-prices') . '</h3>';
        echo '<p style="margin-bottom: 15px; color: #666;">' . __('Produkty wyświetlane w koszyku na podstawie zawartości', 'wordpress-prices') . '</p>';

        // Add search/filter functionality for cross-sell
        echo '<div style="margin-bottom: 15px;">';
        echo '<input type="text" id="wc-crosssell-search" placeholder="' . __('Wyszukaj produkty cross-sell...', 'wordpress-prices') . '" style="width: 100%; padding: 8px; margin-bottom: 10px;">';
        echo '</div>';

        echo '<div id="wc-crosssell-list" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">';

        if (!empty($products)) {
            foreach ($products as $product) {
                $checked = in_array($product->ID, $crosssell_ids) ? 'checked="checked"' : '';
                $product_title = $product->post_title;
                $product_sku = get_post_meta($product->ID, '_sku', true);
                $display_title = $product_title . ($product_sku ? ' (SKU: ' . $product_sku . ')' : '');

                echo '<div class="wc-crosssell-item" style="margin-bottom: 8px;">';
                echo '<label style="display: block; float: none; width: 100%; margin: unset; cursor: pointer; padding: 5px; border-radius: 3px;" onmouseover="this.style.backgroundColor=\'#e8f4f8\'" onmouseout="this.style.backgroundColor=\'transparent\'">';
                echo '<input type="checkbox" name="wc_crosssell_ids[]" value="' . esc_attr($product->ID) . '" ' . $checked . ' style="margin-right: 8px;">';
                echo '<span class="product-title">' . esc_html($display_title) . '</span>';
                echo '</label>';
                echo '</div>';
            }
        } else {
            echo '<p>' . __('Brak dostępnych produktów.', 'wordpress-prices') . '</p>';
        }

        echo '</div>';
        echo '</div>'; // End cross-sell section

        // Up-sell section
        echo '<div id="wc-upsell-section" style="flex: 1;">';
        echo '<h3 style="margin-top: 0; color: #23282d; border-bottom: 1px solid #ddd; padding-bottom: 10px;">' . __('Up-sell produkty', 'wordpress-prices') . '</h3>';
        echo '<p style="margin-bottom: 15px; color: #666;">' . __('Produkty rekomendowane zamiast aktualnie przeglądanego', 'wordpress-prices') . '</p>';

        // Add search/filter functionality for up-sell
        echo '<div style="margin-bottom: 15px;">';
        echo '<input type="text" id="wc-upsell-search" placeholder="' . __('Wyszukaj produkty up-sell...', 'wordpress-prices') . '" style="width: 100%; padding: 8px; margin-bottom: 10px;">';
        echo '</div>';

        echo '<div id="wc-upsell-list" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">';

        if (!empty($products)) {
            foreach ($products as $product) {
                $checked = in_array($product->ID, $upsell_ids) ? 'checked="checked"' : '';
                $product_title = $product->post_title;
                $product_sku = get_post_meta($product->ID, '_sku', true);
                $display_title = $product_title . ($product_sku ? ' (SKU: ' . $product_sku . ')' : '');

                echo '<div class="wc-upsell-item" style="margin-bottom: 8px;">';
                echo '<label style="display: block; float: none; width: 100%; margin: unset; cursor: pointer; padding: 5px; border-radius: 3px;" onmouseover="this.style.backgroundColor=\'#e8f4f8\'" onmouseout="this.style.backgroundColor=\'transparent\'">';
                echo '<input type="checkbox" name="wc_upsell_ids[]" value="' . esc_attr($product->ID) . '" ' . $checked . ' style="margin-right: 8px;">';
                echo '<span class="product-title">' . esc_html($display_title) . '</span>';
                echo '</label>';
                echo '</div>';
            }
        } else {
            echo '<p>' . __('Brak dostępnych produktów.', 'wordpress-prices') . '</p>';
        }

        echo '</div>';
        echo '</div>'; // End up-sell section

        echo '</div>'; // End combined container

        // Add JavaScript and CSS for combined functionality
        $this->render_scripts_and_styles();

        echo '</div>'; // Close panel
    }

    /**
     * Render JavaScript and CSS
     */
    private function render_scripts_and_styles()
    {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Debug: Check form submission
                $('#post').on('submit', function() {
                    console.log('Form being submitted');
                    var crosssellIds = [];
                    var upsellIds = [];

                    $('input[name="wc_crosssell_ids[]"]:checked').each(function() {
                        crosssellIds.push($(this).val());
                    });

                    $('input[name="wc_upsell_ids[]"]:checked').each(function() {
                        upsellIds.push($(this).val());
                    });

                    console.log('Cross-sell IDs:', crosssellIds);
                    console.log('Up-sell IDs:', upsellIds);
                    console.log('Nonce:', $('input[name="wc_linked_products_nonce"]').val());
                });
                // Cross-sell search functionality
                $('#wc-crosssell-search').on('keyup', function() {
                    var searchTerm = $(this).val().toLowerCase();
                    $('.wc-crosssell-item').each(function() {
                        var productTitle = $(this).find('.product-title').text().toLowerCase();
                        if (productTitle.indexOf(searchTerm) > -1) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                });

                // Up-sell search functionality
                $('#wc-upsell-search').on('keyup', function() {
                    var searchTerm = $(this).val().toLowerCase();
                    $('.wc-upsell-item').each(function() {
                        var productTitle = $(this).find('.product-title').text().toLowerCase();
                        if (productTitle.indexOf(searchTerm) > -1) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                });

                // Cross-sell controls
                var crosssellControlsHtml = '<div style="margin-bottom: 10px; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 3px;">' +
                    '<button type="button" id="wc-crosssell-select-all" class="button button-small" style="margin-right: 10px;">Zaznacz wszystkie</button>' +
                    '<button type="button" id="wc-crosssell-deselect-all" class="button button-small">Odznacz wszystkie</button>' +
                    '<div style="margin-top: 10px;"><strong>Wybrane Cross-sell:</strong></div>' +
                    '<div id="wc-crosssell-selected-list" style="margin-top: 5px; padding: 8px; background: #f0f0f1; border-radius: 3px; min-height: 20px; font-size: 12px; color: #666;">Brak wybranych produktów</div>' +
                    '</div>';

                $('#wc-crosssell-list').before(crosssellControlsHtml);

                // Up-sell controls
                var upsellControlsHtml = '<div style="margin-bottom: 10px; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 3px;">' +
                    '<button type="button" id="wc-upsell-select-all" class="button button-small" style="margin-right: 10px;">Zaznacz wszystkie</button>' +
                    '<button type="button" id="wc-upsell-deselect-all" class="button button-small">Odznacz wszystkie</button>' +
                    '<div style="margin-top: 10px;"><strong>Wybrane Up-sell:</strong></div>' +
                    '<div id="wc-upsell-selected-list" style="margin-top: 5px; padding: 8px; background: #f0f0f1; border-radius: 3px; min-height: 20px; font-size: 12px; color: #666;">Brak wybranych produktów</div>' +
                    '</div>';

                $('#wc-upsell-list').before(upsellControlsHtml);

                // Update cross-sell selected products list
                function updateCrosssellSelectedList() {
                    var selectedProducts = [];
                    var selectedData = [];

                    $('#wc-crosssell-list input[type="checkbox"]:checked').each(function() {
                        var productName = $(this).closest('label').find('.product-title').text();
                        var productId = $(this).val();
                        selectedProducts.push(productName);
                        selectedData.push({
                            name: productName,
                            id: productId
                        });
                    });

                    if (selectedProducts.length > 0) {
                        var html = '<strong>(' + selectedProducts.length + ')</strong> ';
                        var productLinks = [];
                        selectedData.forEach(function(product) {
                            productLinks.push('<span class="selected-crosssell-item" data-product-id="' + product.id + '" style="cursor: pointer; color: #0073aa; text-decoration: underline; margin-right: 5px;" title="Kliknij aby odznaczyć">' + product.name + '</span>');
                        });
                        html += productLinks.join(', ');
                        $('#wc-crosssell-selected-list').html(html);
                    } else {
                        $('#wc-crosssell-selected-list').text('Brak wybranych produktów');
                    }
                }

                // Update up-sell selected products list
                function updateUpsellSelectedList() {
                    var selectedProducts = [];
                    var selectedData = [];

                    $('#wc-upsell-list input[type="checkbox"]:checked').each(function() {
                        var productName = $(this).closest('label').find('.product-title').text();
                        var productId = $(this).val();
                        selectedProducts.push(productName);
                        selectedData.push({
                            name: productName,
                            id: productId
                        });
                    });

                    if (selectedProducts.length > 0) {
                        var html = '<strong>(' + selectedProducts.length + ')</strong> ';
                        var productLinks = [];
                        selectedData.forEach(function(product) {
                            productLinks.push('<span class="selected-upsell-item" data-product-id="' + product.id + '" style="cursor: pointer; color: #0073aa; text-decoration: underline; margin-right: 5px;" title="Kliknij aby odznaczyć">' + product.name + '</span>');
                        });
                        html += productLinks.join(', ');
                        $('#wc-upsell-selected-list').html(html);
                    } else {
                        $('#wc-upsell-selected-list').text('Brak wybranych produktów');
                    }
                }

                // Initial updates
                updateCrosssellSelectedList();
                updateUpsellSelectedList();

                // Cross-sell controls
                $('#wc-crosssell-select-all').on('click', function() {
                    $('#wc-crosssell-list input[type="checkbox"]:visible').prop('checked', true);
                    updateCrosssellSelectedList();
                });

                $('#wc-crosssell-deselect-all').on('click', function() {
                    $('#wc-crosssell-list input[type="checkbox"]:visible').prop('checked', false);
                    updateCrosssellSelectedList();
                });

                // Up-sell controls
                $('#wc-upsell-select-all').on('click', function() {
                    $('#wc-upsell-list input[type="checkbox"]:visible').prop('checked', true);
                    updateUpsellSelectedList();
                });

                $('#wc-upsell-deselect-all').on('click', function() {
                    $('#wc-upsell-list input[type="checkbox"]:visible').prop('checked', false);
                    updateUpsellSelectedList();
                });

                // Update lists on checkbox changes
                $('#wc-crosssell-list').on('change', 'input[type="checkbox"]', function() {
                    updateCrosssellSelectedList();
                });

                $('#wc-upsell-list').on('change', 'input[type="checkbox"]', function() {
                    updateUpsellSelectedList();
                });

                // Handle clicking on selected product names to uncheck them
                $(document).on('click', '.selected-crosssell-item', function() {
                    var productId = $(this).data('product-id');
                    $('#wc-crosssell-list input[type="checkbox"][value="' + productId + '"]').prop('checked', false);
                    updateCrosssellSelectedList();
                });

                $(document).on('click', '.selected-upsell-item', function() {
                    var productId = $(this).data('product-id');
                    $('#wc-upsell-list input[type="checkbox"][value="' + productId + '"]').prop('checked', false);
                    updateUpsellSelectedList();
                });
            });
        </script>

        <style type="text/css">
            /* Combined container styles */
            #wc-combined-container {
                display: flex;
                gap: 20px;
            }

            @media (max-width: 768px) {
                #wc-combined-container {
                    flex-direction: column;
                    gap: 15px;
                }
            }

            /* Cross-sell and Up-sell section styles */
            #wc-crosssell-section,
            #wc-upsell-section {
                flex: 1;
                min-width: 0;
            }

            /* Item styles */
            .wc-crosssell-item:nth-child(even),
            .wc-upsell-item:nth-child(even) {
                background-color: #f5f5f5;
            }

            .wc-crosssell-item input[type="checkbox"],
            .wc-upsell-item input[type="checkbox"] {
                transform: scale(1.2);
                padding: 0.5rem;
            }

            /* Search field styles */
            #wc-crosssell-search:focus,
            #wc-upsell-search:focus {
                border-color: #0073aa;
                box-shadow: 0 0 0 1px #0073aa;
                outline: none;
            }

            /* Selected item hover styles */
            .selected-crosssell-item:hover,
            .selected-upsell-item:hover {
                background-color: #f0f0f1;
                border-radius: 3px;
                padding: 2px 4px;
            }

            /* Section headers */
            #wc-crosssell-section h3,
            #wc-upsell-section h3 {
                margin-top: 0;
                color: #23282d;
                border-bottom: 1px solid #ddd;
                padding-bottom: 10px;
            }
        </style>
        <?php
    }

    /**
     * Save linked products data when product is saved
     */
    public function save_linked_products_data($post_id)
    {
        // Check if this is a product post type
        if (get_post_type($post_id) !== 'product') {
            return;
        }

        // Check if user has permission to edit this post
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check if our nonce is set and verify it
        if (!isset($_POST['wc_linked_products_nonce']) || !wp_verify_nonce($_POST['wc_linked_products_nonce'], 'wc_linked_products_nonce_action')) {
            return;
        }

        // Save cross-sell products
        $crosssell_ids = array();
        if (isset($_POST['wc_crosssell_ids']) && is_array($_POST['wc_crosssell_ids'])) {
            $crosssell_ids = array_map('intval', $_POST['wc_crosssell_ids']);
            $crosssell_ids = array_filter($crosssell_ids, function ($id) use ($post_id) {
                return $id > 0 && $id !== $post_id && get_post_type($id) === 'product';
            });
        }

        // Save up-sell products
        $upsell_ids = array();
        if (isset($_POST['wc_upsell_ids']) && is_array($_POST['wc_upsell_ids'])) {
            $upsell_ids = array_map('intval', $_POST['wc_upsell_ids']);
            $upsell_ids = array_filter($upsell_ids, function ($id) use ($post_id) {
                return $id > 0 && $id !== $post_id && get_post_type($id) === 'product';
            });
        }

        // Update the product meta
        update_post_meta($post_id, '_crosssell_ids', $crosssell_ids);
        update_post_meta($post_id, '_upsell_ids', $upsell_ids);

        // Also update the WooCommerce product object to ensure consistency
        $product = wc_get_product($post_id);
        if ($product) {
            $product->set_cross_sell_ids($crosssell_ids);
            $product->set_upsell_ids($upsell_ids);
            $product->save();
        }
    }

    /**
     * Fallback save function using save_post hook
     */
    public function save_linked_products_data_fallback($post_id)
    {
        // Only process if this is a product and we have our nonce
        if (get_post_type($post_id) !== 'product' || !isset($_POST['wc_linked_products_nonce'])) {
            return;
        }

        // Call the main save function
        $this->save_linked_products_data($post_id);
    }

    /**
     * Admin notice for plugin activation
     */
    public function admin_notice()
    {
        global $post_type, $pagenow;

        // Only show on product edit pages
        if ($post_type === 'product' && ($pagenow === 'post.php' || $pagenow === 'post-new.php')) {
            // Check if user has seen this notice before
            $user_id = get_current_user_id();
            $notice_dismissed = get_user_meta($user_id, 'wc_linked_products_notice_dismissed', true);

            if (!$notice_dismissed) {
                echo '<div class="notice notice-info is-dismissible" id="wc-linked-products-notice">';
                echo '<p><strong>' . __('Nowa funkcjonalność!', 'wordpress-prices') . '</strong> ';
                echo __('Produkty Cross-sell i Up-sell można teraz wybierać za pomocą checkboxów w tabie "Produkty powiązane (Checkboxy)".', 'wordpress-prices');
                echo '</p>';
                echo '</div>';

                // Add JavaScript to handle notice dismissal
        ?>
                <script type="text/javascript">
                    jQuery(document).ready(function($) {
                        $('#wc-linked-products-notice').on('click', '.notice-dismiss', function() {
                            $.ajax({
                                url: ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'wc_linked_products_dismiss_notice',
                                    nonce: '<?php echo wp_create_nonce('wc_linked_products_notice_nonce'); ?>'
                                }
                            });
                        });
                    });
                </script>
<?php
            }
        }
    }

    /**
     * Handle notice dismissal
     */
    public function dismiss_notice()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wc_linked_products_notice_nonce')) {
            wp_die('Security check failed');
        }

        // Mark notice as dismissed for current user
        $user_id = get_current_user_id();
        update_user_meta($user_id, 'wc_linked_products_notice_dismissed', true);

        wp_die(); // This is required to terminate immediately and return a proper response
    }
}
