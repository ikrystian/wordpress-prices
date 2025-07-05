<?php

/**
 * Order details functionality - displays margin information in WooCommerce order details page
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_Prices_Order_Details
{

    /**
     * Constructor
     */
    public function __construct()
    {
        // Add meta box for margin information
        add_action('add_meta_boxes', array($this, 'add_margin_meta_box'));

        // Add margin information to each order item
        add_action('woocommerce_admin_order_item_headers', array($this, 'add_order_item_margin_header'));
        add_action('woocommerce_admin_order_item_values', array($this, 'add_order_item_margin_values'), 10, 3);

        // Enqueue styles for admin order details
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    }

    /**
     * Add margin meta box to order edit page
     */
    public function add_margin_meta_box()
    {
        // Check if order margin details are enabled
        $display_options = get_option('wp_prices_display_options', array(
            'show_order_details_margin' => 1
        ));

        if (!isset($display_options['show_order_details_margin']) || !$display_options['show_order_details_margin']) {
            return;
        }

        // Add meta box for traditional order post type
        add_meta_box(
            'wp-prices-order-margin',
            __('Informacje o marży zamówienia', 'wordpress-prices'),
            array($this, 'display_margin_meta_box'),
            'shop_order',
            'normal',
            'default'
        );

        // Add meta box for new WooCommerce HPOS
        $screen_id = function_exists('wc_get_page_screen_id') ? wc_get_page_screen_id('shop-order') : 'woocommerce_page_wc-orders';
        add_meta_box(
            'wp-prices-order-margin-hpos',
            __('Informacje o marży zamówienia', 'wordpress-prices'),
            array($this, 'display_margin_meta_box'),
            $screen_id,
            'normal',
            'default'
        );
    }

    /**
     * Display margin meta box content
     */
    public function display_margin_meta_box($post_or_order_object)
    {
        // Get order ID from post or order object
        if (is_a($post_or_order_object, 'WP_Post')) {
            $order_id = $post_or_order_object->ID;
            $order = wc_get_order($order_id);
        } else {
            $order = $post_or_order_object;
            $order_id = $order->get_id();
        }

        if (!$order) {
            echo '<p>' . __('Nie można załadować danych zamówienia.', 'wordpress-prices') . '</p>';
            return;
        }

        $margin_details = WP_Prices_Margin_Calculator::get_order_margin_details($order_id);

        if (!$margin_details || $margin_details['total_margin'] <= 0) {
            echo '<p>' . __('To zamówienie nie zawiera produktów z marżą.', 'wordpress-prices') . '</p>';
            return;
        }

        ?>
        <div class="wp-prices-order-details-margin wp-prices-meta-box-content">

            <div class="wp-prices-margin-summary">
                <div class="wp-prices-margin-row">
                    <div class="wp-prices-margin-label">
                        <strong><?php _e('Łączna marża:', 'wordpress-prices'); ?></strong>
                    </div>
                    <div class="wp-prices-margin-value">
                        <strong class="wp-prices-total-margin"><?php echo wc_price($margin_details['total_margin']); ?></strong>
                    </div>
                </div>

                <?php if ($margin_details['average_margin_percentage'] > 0): ?>
                <div class="wp-prices-margin-row">
                    <div class="wp-prices-margin-label">
                        <?php _e('Średnia marża:', 'wordpress-prices'); ?>
                    </div>
                    <div class="wp-prices-margin-value">
                        <?php echo number_format($margin_details['average_margin_percentage'], 1); ?>%
                    </div>
                </div>
                <?php endif; ?>

                <div class="wp-prices-margin-row">
                    <div class="wp-prices-margin-label">
                        <?php _e('Produkty z marżą:', 'wordpress-prices'); ?>
                    </div>
                    <div class="wp-prices-margin-value">
                        <?php echo sprintf(
                            _n('%d z %d produktu', '%d z %d produktów', $margin_details['total_products'], 'wordpress-prices'),
                            $margin_details['products_with_margin'],
                            $margin_details['total_products']
                        ); ?>
                    </div>
                </div>

                <?php if ($margin_details['margin_coverage'] < 100): ?>
                <div class="wp-prices-margin-row">
                    <div class="wp-prices-margin-label">
                        <?php _e('Pokrycie marży:', 'wordpress-prices'); ?>
                    </div>
                    <div class="wp-prices-margin-value">
                        <?php echo number_format($margin_details['margin_coverage'], 1); ?>%
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($margin_details['details'])): ?>
            <div class="wp-prices-margin-breakdown">
                <h4><?php _e('Szczegóły marży produktów:', 'wordpress-prices'); ?></h4>
                <table class="wp-prices-margin-table">
                    <thead>
                        <tr>
                            <th><?php _e('Produkt', 'wordpress-prices'); ?></th>
                            <th><?php _e('Ilość', 'wordpress-prices'); ?></th>
                            <th><?php _e('Marża %', 'wordpress-prices'); ?></th>
                            <th><?php _e('Cena bez marży', 'wordpress-prices'); ?></th>
                            <th><?php _e('Marża za sztukę', 'wordpress-prices'); ?></th>
                            <th><?php _e('Łączna marża', 'wordpress-prices'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($margin_details['details'] as $detail): ?>
                        <tr>
                            <td class="wp-prices-product-name" data-label="<?php echo esc_attr(__('Produkt', 'wordpress-prices')); ?>">
                                <?php echo esc_html($detail['product_name']); ?>
                            </td>
                            <td class="wp-prices-quantity" data-label="<?php echo esc_attr(__('Ilość', 'wordpress-prices')); ?>">
                                <?php echo $detail['quantity']; ?>
                            </td>
                            <td class="wp-prices-margin-percentage" data-label="<?php echo esc_attr(__('Marża %', 'wordpress-prices')); ?>">
                                <?php echo number_format($detail['margin_info']['margin_percentage'], 1); ?>%
                            </td>
                            <td class="wp-prices-price-without-margin" data-label="<?php echo esc_attr(__('Cena bez marży', 'wordpress-prices')); ?>">
                                <?php echo wc_price($detail['margin_info']['price_without_margin']); ?>
                            </td>
                            <td class="wp-prices-margin-per-item" data-label="<?php echo esc_attr(__('Marża za sztukę', 'wordpress-prices')); ?>">
                                <?php echo wc_price($detail['margin_info']['margin_amount']); ?>
                            </td>
                            <td class="wp-prices-total-item-margin" data-label="<?php echo esc_attr(__('Łączna marża', 'wordpress-prices')); ?>">
                                <strong><?php echo wc_price($detail['total_margin']); ?></strong>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Add margin header to order items table
     */
    public function add_order_item_margin_header()
    {
        // Check if item margin column is enabled
        $display_options = get_option('wp_prices_display_options', array(
            'show_order_item_margin_column' => 1
        ));

        if (!isset($display_options['show_order_item_margin_column']) || !$display_options['show_order_item_margin_column']) {
            return;
        }

        ?>
        <th class="wp-prices-item-margin-header"><?php _e('Marża', 'wordpress-prices'); ?></th>
        <?php
    }

    /**
     * Add margin values to order items table
     */
    public function add_order_item_margin_values($product, $item, $item_id)
    {
        // Check if item margin column is enabled
        $display_options = get_option('wp_prices_display_options', array(
            'show_order_item_margin_column' => 1
        ));

        if (!isset($display_options['show_order_item_margin_column']) || !$display_options['show_order_item_margin_column']) {
            return;
        }

        if (!$product) {
            echo '<td class="wp-prices-item-margin-value">-</td>';
            return;
        }

        $product_id = $item->get_product_id();
        $variation_id = $item->get_variation_id();
        $quantity = $item->get_quantity();

        // Use variation ID if available, otherwise use product ID
        $id_to_check = $variation_id > 0 ? $variation_id : $product_id;

        $margin_info = WP_Prices_Margin_Calculator::get_product_margin_info($id_to_check);

        echo '<td class="wp-prices-item-margin-value">';

        if ($margin_info) {
            $total_margin = $margin_info['margin_amount'] * $quantity;

            echo '<div class="wp-prices-item-margin-details">';
            echo '<div class="wp-prices-margin-percentage">' . number_format($margin_info['margin_percentage'], 1) . '%</div>';
            echo '<div class="wp-prices-margin-amount">' . wc_price($total_margin) . '</div>';
            echo '</div>';
        } else {
            echo '<span class="wp-prices-no-margin">' . __('Brak marży', 'wordpress-prices') . '</span>';
        }

        echo '</td>';
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles($hook)
    {
        // Load styles on order edit pages
        if ($hook !== 'post.php' && $hook !== 'woocommerce_page_wc-orders') {
            return;
        }

        // Check if we're on the order edit page
        $screen = get_current_screen();
        if (!$screen || ($screen->post_type !== 'shop_order' && $screen->id !== 'woocommerce_page_wc-orders')) {
            return;
        }

        wp_enqueue_style(
            'wp-prices-order-details',
            WP_PRICES_PLUGIN_URL . 'assets/css/order-details.css',
            array(),
            WP_PRICES_VERSION
        );
    }

    /**
     * Get order margin details for API/AJAX
     */
    public static function get_order_margin_details_formatted($order_id)
    {
        $margin_details = WP_Prices_Margin_Calculator::get_order_margin_details($order_id);

        if (!$margin_details) {
            return false;
        }

        // Format for API response
        return array(
            'total_margin' => wc_price($margin_details['total_margin']),
            'average_margin_percentage' => number_format($margin_details['average_margin_percentage'], 1) . '%',
            'products_with_margin' => $margin_details['products_with_margin'],
            'total_products' => $margin_details['total_products'],
            'margin_coverage' => number_format($margin_details['margin_coverage'], 1) . '%',
            'details' => $margin_details['details']
        );
    }
}
