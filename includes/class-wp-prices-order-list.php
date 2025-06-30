<?php

/**
 * Order list functionality - displays margin information in WooCommerce orders list
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_Prices_Order_List
{

    /**
     * Constructor
     */
    public function __construct()
    {
        // Add columns to order list (support both old and new WooCommerce order tables)
        add_filter('manage_shop_order_posts_columns', array($this, 'add_order_columns'));
        add_action('manage_shop_order_posts_custom_column', array($this, 'populate_order_columns'), 10, 2);

        // Support for new WooCommerce HPOS (High-Performance Order Storage)
        add_filter('manage_woocommerce_page_wc-orders_columns', array($this, 'add_order_columns'));
        add_action('manage_woocommerce_page_wc-orders_custom_column', array($this, 'populate_order_columns'), 10, 2);

        // Enqueue styles for admin
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    }

    /**
     * Add custom columns to order list
     */
    public function add_order_columns($columns)
    {
        // Check if order margin column is enabled
        $display_options = get_option('wp_prices_display_options', array(
            'show_order_margin_column' => 1
        ));

        if (!isset($display_options['show_order_margin_column']) || !$display_options['show_order_margin_column']) {
            return $columns;
        }

        // Insert margin column after order total column
        $new_columns = array();

        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;

            // Add margin column after order total
            if ($key === 'order_total') {
                $new_columns['order_margin'] = __('Marża Zamówienia', 'wordpress-prices');
            }
        }

        return $new_columns;
    }

    /**
     * Populate custom columns
     */
    public function populate_order_columns($column, $order_id)
    {
        if ($column === 'order_margin') {
            $this->display_margin_column($order_id);
        }
    }

    /**
     * Display margin information in column
     */
    private function display_margin_column($order_id)
    {
        $margin_info = $this->get_order_margin_info($order_id);

        if (!$margin_info || $margin_info['total_margin'] <= 0) {
            echo '<span class="wp-prices-no-margin">' . __('Brak marży', 'wordpress-prices') . '</span>';
            return;
        }

        $display_options = get_option('wp_prices_display_options', array(
            'show_order_average_percentage' => 1,
            'show_order_products_count' => 1,
            'decimal_places' => 2
        ));

        echo '<div class="wp-prices-order-margin">';

        // Show total margin amount (always shown)
        echo '<div class="wp-prices-margin-amount">';
        echo '<strong>' . wc_price($margin_info['total_margin']) . '</strong>';
        echo '</div>';

        // Show average margin percentage if enabled
        if (isset($display_options['show_order_average_percentage']) && $display_options['show_order_average_percentage'] && $margin_info['average_margin_percentage'] > 0) {
            echo '<div class="wp-prices-average-percentage">';
            echo sprintf(__('Śr. marża: %s%%', 'wordpress-prices'), number_format($margin_info['average_margin_percentage'], 1));
            echo '</div>';
        }

        // Show products with margin count if enabled
        if (isset($display_options['show_order_products_count']) && $display_options['show_order_products_count'] && $margin_info['products_with_margin'] > 0) {
            echo '<div class="wp-prices-products-count">';
            echo sprintf(
                _n('%d produkt z marżą', '%d produktów z marżą', $margin_info['products_with_margin'], 'wordpress-prices'),
                $margin_info['products_with_margin']
            );
            echo '</div>';
        }

        echo '</div>';
    }

    /**
     * Get detailed margin information for order
     */
    private function get_order_margin_info($order_id)
    {
        // Use the enhanced function from margin calculator
        return WP_Prices_Margin_Calculator::get_order_margin_details($order_id);
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles($hook)
    {
        // Load styles on order list pages
        if ($hook !== 'edit.php' && $hook !== 'woocommerce_page_wc-orders') {
            return;
        }

        // Check if we're on the orders page
        $screen = get_current_screen();
        if (!$screen || ($screen->post_type !== 'shop_order' && $screen->id !== 'woocommerce_page_wc-orders')) {
            return;
        }

        wp_enqueue_style(
            'wp-prices-order-list',
            WP_PRICES_PLUGIN_URL . 'assets/css/order-list.css',
            array(),
            WP_PRICES_VERSION
        );
    }

    /**
     * Get order margin summary for API/AJAX
     */
    public static function get_order_margin_summary($order_id)
    {
        $instance = new self();
        return $instance->get_order_margin_info($order_id);
    }
}
