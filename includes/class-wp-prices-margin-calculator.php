<?php

/**
 * Margin calculation functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_Prices_Margin_Calculator
{

    /**
     * Constructor
     */
    public function __construct()
    {
        // Hook into price display if needed
        add_filter('woocommerce_get_price_html', array($this, 'modify_price_display'), 10, 2);
    }

    /**
     * Calculate price without margin
     */
    public static function calculate_price_without_margin($price_with_margin, $margin_percentage)
    {
        if ($margin_percentage <= 0) {
            return $price_with_margin;
        }

        return $price_with_margin / (1 + ($margin_percentage / 100));
    }

    /**
     * Calculate price with margin
     */
    public static function calculate_price_with_margin($price_without_margin, $margin_percentage)
    {
        if ($margin_percentage <= 0) {
            return $price_without_margin;
        }

        return $price_without_margin * (1 + ($margin_percentage / 100));
    }

    /**
     * Get margin info for product
     */
    public static function get_product_margin_info($product_id)
    {
        $product = wc_get_product($product_id);

        if (!$product) {
            return false;
        }

        $margin_percentage = WP_Prices_Meta_Fields::get_product_margin_percentage($product_id);

        if ($margin_percentage <= 0) {
            return false;
        }

        $price_with_margin = floatval($product->get_price());
        $price_without_margin = self::calculate_price_without_margin($price_with_margin, $margin_percentage);

        return array(
            'margin_percentage' => $margin_percentage,
            'price_with_margin' => $price_with_margin,
            'price_without_margin' => $price_without_margin,
            'margin_amount' => $price_with_margin - $price_without_margin,
            'category' => WP_Prices_Meta_Fields::get_product_margin_category($product_id)
        );
    }

    /**
     * Format margin info for display
     */
    public static function format_margin_info($product_id, $format = 'full')
    {
        $margin_info = self::get_product_margin_info($product_id);

        if (!$margin_info) {
            return '';
        }

        $display_options = get_option('wp_prices_display_options', array(
            'show_margin_percentage' => 1,
            'show_price_without_margin' => 1,
            'decimal_places' => 2
        ));

        $decimal_places = isset($display_options['decimal_places']) ? intval($display_options['decimal_places']) : 2;
        $parts = array();

        // Add margin percentage
        if (isset($display_options['show_margin_percentage']) && $display_options['show_margin_percentage']) {
            $parts[] = sprintf(
                __('Marża: %s%%', 'wordpress-prices'),
                number_format($margin_info['margin_percentage'], 1)
            );
        }

        // Add price without margin
        if (isset($display_options['show_price_without_margin']) && $display_options['show_price_without_margin']) {
            $parts[] = sprintf(
                __('Cena bez marży: %s', 'wordpress-prices'),
                wc_price($margin_info['price_without_margin'])
            );
        }

        if (empty($parts)) {
            return '';
        }

        switch ($format) {
            case 'inline':
                return implode(' | ', $parts);
            case 'list':
                return '<ul><li>' . implode('</li><li>', $parts) . '</li></ul>';
            case 'full':
            default:
                return '<div class="wp-prices-margin-info">' . implode('<br>', $parts) . '</div>';
        }
    }

    /**
     * Get margin info as array for API/AJAX
     */
    public static function get_margin_info_array($product_id)
    {
        $margin_info = self::get_product_margin_info($product_id);

        if (!$margin_info) {
            return array(
                'has_margin' => false,
                'margin_percentage' => 0,
                'price_with_margin' => 0,
                'price_without_margin' => 0,
                'margin_amount' => 0,
                'category' => ''
            );
        }

        return array_merge($margin_info, array('has_margin' => true));
    }

    /**
     * Modify price display (optional feature)
     */
    public function modify_price_display($price_html, $product)
    {
        // This can be used to modify how prices are displayed on frontend
        // For now, we'll keep the original price display
        return $price_html;
    }

    /**
     * Calculate margin for multiple products
     */
    public static function calculate_bulk_margins($product_ids)
    {
        $results = array();

        foreach ($product_ids as $product_id) {
            $results[$product_id] = self::get_margin_info_array($product_id);
        }

        return $results;
    }

    /**
     * Get total margin amount for order
     */
    public static function get_order_total_margin($order_id)
    {
        $order = wc_get_order($order_id);

        if (!$order) {
            return 0;
        }

        $total_margin = 0;

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            $quantity = $item->get_quantity();

            // Use variation ID if available, otherwise use product ID
            $id_to_check = $variation_id > 0 ? $variation_id : $product_id;

            $margin_info = self::get_product_margin_info($id_to_check);

            if ($margin_info) {
                $total_margin += $margin_info['margin_amount'] * $quantity;
            }
        }

        return $total_margin;
    }

    /**
     * Get detailed margin information for order
     */
    public static function get_order_margin_details($order_id)
    {
        $order = wc_get_order($order_id);

        if (!$order) {
            return false;
        }

        $total_margin = 0;
        $total_margin_base = 0;
        $products_with_margin = 0;
        $total_products = 0;
        $margin_details = array();

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            $quantity = $item->get_quantity();

            // Use variation ID if available, otherwise use product ID
            $id_to_check = $variation_id > 0 ? $variation_id : $product_id;

            $margin_info = self::get_product_margin_info($id_to_check);

            if ($margin_info) {
                $item_margin = $margin_info['margin_amount'] * $quantity;
                $item_base = $margin_info['price_without_margin'] * $quantity;

                $total_margin += $item_margin;
                $total_margin_base += $item_base;
                $products_with_margin++;

                $margin_details[] = array(
                    'product_id' => $product_id,
                    'variation_id' => $variation_id,
                    'product_name' => $item->get_name(),
                    'quantity' => $quantity,
                    'margin_info' => $margin_info,
                    'total_margin' => $item_margin,
                    'total_base' => $item_base
                );
            }

            $total_products++;
        }

        // Calculate average margin percentage
        $average_margin_percentage = 0;
        if ($total_margin_base > 0) {
            $average_margin_percentage = ($total_margin / $total_margin_base) * 100;
        }

        return array(
            'total_margin' => $total_margin,
            'total_margin_base' => $total_margin_base,
            'average_margin_percentage' => $average_margin_percentage,
            'products_with_margin' => $products_with_margin,
            'total_products' => $total_products,
            'margin_coverage' => $total_products > 0 ? ($products_with_margin / $total_products) * 100 : 0,
            'details' => $margin_details
        );
    }
}
