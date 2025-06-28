<?php

/**
 * Admin panel functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_Prices_Admin
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'woocommerce',
            __('Ustawienia Marży', 'wordpress-prices'),
            __('Marże Produktów', 'wordpress-prices'),
            'manage_woocommerce',
            'wp-prices-settings',
            array($this, 'admin_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings()
    {
        register_setting('wp_prices_settings', 'wp_prices_margins');
        register_setting('wp_prices_settings', 'wp_prices_meta_key');
        register_setting('wp_prices_settings', 'wp_prices_display_options');

        add_settings_section(
            'wp_prices_main_section',
            __('Ustawienia Marży', 'wordpress-prices'),
            array($this, 'main_section_callback'),
            'wp_prices_settings'
        );

        add_settings_field(
            'wp_prices_meta_key',
            __('Klucz Meta Pola', 'wordpress-prices'),
            array($this, 'meta_key_field_callback'),
            'wp_prices_settings',
            'wp_prices_main_section'
        );

        add_settings_field(
            'wp_prices_margins',
            __('Marże dla Kategorii', 'wordpress-prices'),
            array($this, 'margins_field_callback'),
            'wp_prices_settings',
            'wp_prices_main_section'
        );

        add_settings_field(
            'wp_prices_display_options',
            __('Opcje Wyświetlania', 'wordpress-prices'),
            array($this, 'display_options_callback'),
            'wp_prices_settings',
            'wp_prices_main_section'
        );
    }

    /**
     * Main section callback
     */
    public function main_section_callback()
    {
        echo '<p>' . __('Skonfiguruj marże dla różnych kategorii produktów.', 'wordpress-prices') . '</p>';
    }

    /**
     * Meta key field callback
     */
    public function meta_key_field_callback()
    {
        $meta_key = get_option('wp_prices_meta_key', 'margin_category');
        echo '<input type="text" name="wp_prices_meta_key" value="' . esc_attr($meta_key) . '" class="regular-text" />';
        echo '<p class="description">' . __('Klucz meta pola używany do identyfikacji kategorii marży produktu.', 'wordpress-prices') . '</p>';
    }

    /**
     * Margins field callback
     */
    public function margins_field_callback()
    {
        $margins = get_option('wp_prices_margins', array());
        echo '<div id="wp-prices-margins-container">';

        if (!empty($margins)) {
            foreach ($margins as $category => $margin) {
                echo $this->get_margin_row_html($category, $margin);
            }
        }

        echo '</div>';
        echo '<button type="button" id="add-margin-row" class="button">' . __('Dodaj Kategorię', 'wordpress-prices') . '</button>';
        echo '<p class="description">' . __('Ustaw procentowe marże dla różnych kategorii produktów.', 'wordpress-prices') . '</p>';
    }

    /**
     * Display options callback
     */
    public function display_options_callback()
    {
        $options = get_option('wp_prices_display_options', array(
            'show_margin_percentage' => 1,
            'show_price_without_margin' => 1,
            'decimal_places' => 2
        ));

        echo '<label><input type="checkbox" name="wp_prices_display_options[show_margin_percentage]" value="1" ' . checked(1, isset($options['show_margin_percentage']) ? $options['show_margin_percentage'] : 0, false) . ' /> ';
        echo __('Pokaż procentową marżę', 'wordpress-prices') . '</label><br>';

        echo '<label><input type="checkbox" name="wp_prices_display_options[show_price_without_margin]" value="1" ' . checked(1, isset($options['show_price_without_margin']) ? $options['show_price_without_margin'] : 0, false) . ' /> ';
        echo __('Pokaż cenę bez marży', 'wordpress-prices') . '</label><br>';

        echo '<label>' . __('Miejsca dziesiętne:', 'wordpress-prices') . ' ';
        echo '<input type="number" name="wp_prices_display_options[decimal_places]" value="' . esc_attr(isset($options['decimal_places']) ? $options['decimal_places'] : 2) . '" min="0" max="4" class="small-text" /></label>';
    }

    /**
     * Get margin row HTML
     */
    private function get_margin_row_html($category = '', $margin = '')
    {
        $html = '<div class="wp-prices-margin-row" style="margin-bottom: 10px;">';
        $html .= '<input type="text" name="wp_prices_margins_categories[]" value="' . esc_attr($category) . '" placeholder="' . __('Kategoria', 'wordpress-prices') . '" class="regular-text" style="margin-right: 10px;" />';
        $html .= '<input type="number" name="wp_prices_margins_values[]" value="' . esc_attr($margin) . '" placeholder="' . __('Marża %', 'wordpress-prices') . '" min="0" max="100" step="0.1" class="small-text" style="margin-right: 10px;" />';
        $html .= '<button type="button" class="button remove-margin-row">' . __('Usuń', 'wordpress-prices') . '</button>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Admin page
     */
    public function admin_page()
    {
        // Handle form submission
        if (isset($_POST['submit'])) {
            check_admin_referer('wp_prices_settings-options');
            $this->save_margins();
            echo '<div class="notice notice-success"><p>' . __('Ustawienia zostały zapisane.', 'wordpress-prices') . '</p></div>';
        }

?>
        <div class="wrap">
            <h1><?php echo __('Ustawienia Marży Produktów', 'wordpress-prices'); ?></h1>
            <form method="post" action="">
                <?php
                settings_fields('wp_prices_settings');
                do_settings_sections('wp_prices_settings');
                submit_button();
                ?>
            </form>
        </div>
<?php
    }

    /**
     * Save margins
     */
    private function save_margins()
    {
        if (isset($_POST['wp_prices_margins_categories']) && isset($_POST['wp_prices_margins_values'])) {
            $categories = $_POST['wp_prices_margins_categories'];
            $values = $_POST['wp_prices_margins_values'];
            $margins = array();

            for ($i = 0; $i < count($categories); $i++) {
                if (!empty($categories[$i]) && !empty($values[$i])) {
                    $margins[sanitize_text_field($categories[$i])] = floatval($values[$i]);
                }
            }

            update_option('wp_prices_margins', $margins);
        }
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook)
    {
        if ($hook !== 'woocommerce_page_wp-prices-settings') {
            return;
        }

        wp_enqueue_script(
            'wp-prices-admin',
            WP_PRICES_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            WP_PRICES_VERSION,
            true
        );

        wp_enqueue_style(
            'wp-prices-admin',
            WP_PRICES_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WP_PRICES_VERSION
        );
    }
}
