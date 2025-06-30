<?php

/**
 * Plugin Name: WordPress Prices - Margin Manager
 * Plugin URI: https://example.com/wordpress-prices
 * Description: Plugin do zarządzania marżami produktów WooCommerce z możliwością ustawiania marży dla produktów z określonym meta.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: wordpress-prices
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_PRICES_VERSION', '1.0.0');
define('WP_PRICES_PLUGIN_FILE', __FILE__);
define('WP_PRICES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_PRICES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_PRICES_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main WordPress Prices class
 */
class WordPress_Prices
{

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get single instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Initialize plugin
     */
    public function init()
    {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Load text domain
        load_plugin_textdomain('wordpress-prices', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // Include required files
        $this->includes();

        // Initialize classes
        $this->init_classes();
    }

    /**
     * Include required files
     */
    private function includes()
    {
        require_once WP_PRICES_PLUGIN_DIR . 'includes/class-wp-prices-admin.php';
        require_once WP_PRICES_PLUGIN_DIR . 'includes/class-wp-prices-meta-fields.php';
        require_once WP_PRICES_PLUGIN_DIR . 'includes/class-wp-prices-margin-calculator.php';
        require_once WP_PRICES_PLUGIN_DIR . 'includes/class-wp-prices-product-list.php';
        require_once WP_PRICES_PLUGIN_DIR . 'includes/class-wp-prices-order-list.php';
    }

    /**
     * Initialize classes
     */
    private function init_classes()
    {
        new WP_Prices_Admin();
        new WP_Prices_Meta_Fields();
        new WP_Prices_Margin_Calculator();
        new WP_Prices_Product_List();
        new WP_Prices_Order_List();
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        // Set default options
        $default_margins = array(
            'premium' => 30,
            'standard' => 20,
            'basic' => 10
        );

        if (!get_option('wp_prices_margins')) {
            update_option('wp_prices_margins', $default_margins);
        }

        if (!get_option('wp_prices_meta_key')) {
            update_option('wp_prices_meta_key', 'margin_category');
        }
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        // Clean up if needed
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice()
    {
        echo '<div class="notice notice-error"><p>';
        echo __('WordPress Prices wymaga aktywnego pluginu WooCommerce.', 'wordpress-prices');
        echo '</p></div>';
    }
}

/**
 * Initialize the plugin
 */
function wp_prices_init()
{
    return WordPress_Prices::get_instance();
}

// Start the plugin
wp_prices_init();
