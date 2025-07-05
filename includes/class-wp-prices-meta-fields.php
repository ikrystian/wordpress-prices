<?php

/**
 * Product meta fields functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_Prices_Meta_Fields
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_margin_category_field'));
        add_action('woocommerce_process_product_meta', array($this, 'save_margin_category_field'));
        add_action('woocommerce_product_after_variable_attributes', array($this, 'add_variation_margin_category_field'), 10, 3);
        add_action('woocommerce_save_product_variation', array($this, 'save_variation_margin_category_field'), 10, 2);
    }

    /**
     * Add margin category field to product general tab
     */
    public function add_margin_category_field()
    {
        global $post;

        $meta_key = get_option('wp_prices_meta_key', 'margin_category');
        $margins = get_option('wp_prices_margins', array());
        $current_value = get_post_meta($post->ID, $meta_key, true);

        // Prepare options for select field
        $options = array('' => __('-- Wybierz kategorię marży --', 'wordpress-prices'));
        foreach ($margins as $category => $margin) {
            $options[$category] = sprintf('%s (%s%%)', ucfirst($category), $margin);
        }

        echo '<div class="options_group">';

        woocommerce_wp_select(array(
            'id' => $meta_key,
            'label' => __('Kategoria Marży', 'wordpress-prices'),
            'description' => __('Wybierz kategorię marży dla tego produktu.', 'wordpress-prices'),
            'desc_tip' => true,
            'options' => $options,
            'value' => $current_value
        ));

        // Display current margin info if category is selected
        if (!empty($current_value) && isset($margins[$current_value])) {
            $margin_percentage = $margins[$current_value];
            $product = wc_get_product($post->ID);

            if ($product && $product->get_price()) {
                $price_with_margin = floatval($product->get_price());
                $price_without_margin = WP_Prices_Margin_Calculator::calculate_price_without_margin($price_with_margin, $margin_percentage);
                $profit_amount = $price_with_margin - $price_without_margin;

                echo '<p class="form-field wp-prices-info">';
                echo sprintf(__('Zysk: %s', 'wordpress-prices'), wc_price($profit_amount)) . '<br>';
                echo sprintf(__('Cena z marżą: %s', 'wordpress-prices'), wc_price($price_with_margin)) . '<br>';
                echo sprintf(__('Cena bez marży: %s', 'wordpress-prices'), wc_price($price_without_margin));
                echo '</p>';
            }
        }

        echo '</div>';
    }

    /**
     * Save margin category field
     */
    public function save_margin_category_field($post_id)
    {
        $meta_key = get_option('wp_prices_meta_key', 'margin_category');

        if (isset($_POST[$meta_key])) {
            $value = sanitize_text_field($_POST[$meta_key]);
            update_post_meta($post_id, $meta_key, $value);
        }
    }

    /**
     * Add margin category field to product variations
     */
    public function add_variation_margin_category_field($loop, $variation_data, $variation)
    {
        $meta_key = get_option('wp_prices_meta_key', 'margin_category');
        $margins = get_option('wp_prices_margins', array());
        $current_value = get_post_meta($variation->ID, $meta_key, true);

        // Prepare options for select field
        $options = array('' => __('-- Wybierz kategorię marży --', 'wordpress-prices'));
        foreach ($margins as $category => $margin) {
            $options[$category] = sprintf('%s (%s%%)', ucfirst($category), $margin);
        }

        woocommerce_wp_select(array(
            'id' => $meta_key . '[' . $loop . ']',
            'name' => $meta_key . '[' . $loop . ']',
            'label' => __('Kategoria Marży', 'wordpress-prices'),
            'description' => __('Wybierz kategorię marży dla tej wariacji.', 'wordpress-prices'),
            'desc_tip' => true,
            'options' => $options,
            'value' => $current_value,
            'wrapper_class' => 'form-row form-row-full'
        ));
    }

    /**
     * Save variation margin category field
     */
    public function save_variation_margin_category_field($variation_id, $loop)
    {
        $meta_key = get_option('wp_prices_meta_key', 'margin_category');

        if (isset($_POST[$meta_key][$loop])) {
            $value = sanitize_text_field($_POST[$meta_key][$loop]);
            update_post_meta($variation_id, $meta_key, $value);
        }
    }

    /**
     * Get margin category for product
     */
    public static function get_product_margin_category($product_id)
    {
        $meta_key = get_option('wp_prices_meta_key', 'margin_category');
        return get_post_meta($product_id, $meta_key, true);
    }

    /**
     * Get margin percentage for product
     */
    public static function get_product_margin_percentage($product_id)
    {
        $category = self::get_product_margin_category($product_id);

        if (empty($category)) {
            return 0;
        }

        $margins = get_option('wp_prices_margins', array());
        return isset($margins[$category]) ? floatval($margins[$category]) : 0;
    }

    /**
     * Check if product has margin category
     */
    public static function product_has_margin($product_id)
    {
        $category = self::get_product_margin_category($product_id);
        return !empty($category);
    }
}
