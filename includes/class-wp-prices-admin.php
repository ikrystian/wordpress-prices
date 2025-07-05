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
        add_menu_page(
            __('WordPress Prices', 'wordpress-prices'),
            __('WordPress Prices', 'wordpress-prices'),
            'manage_woocommerce',
            'wp-prices-settings',
            array($this, 'admin_page'),
            'dashicons-tag',
            999 // Position at the bottom of the menu
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
        register_setting('wp_prices_settings', 'wp_prices_functionality_options');
    }

    /**
     * Functionality options callback
     */
    public function functionality_options_callback()
    {
        $options = get_option('wp_prices_functionality_options', array(
            'enable_margin_management' => 1,
            'enable_linked_products' => 1,
            'enable_product_list_columns' => 1,
            'enable_order_list_columns' => 1
        ));

        echo '<div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px;">';

        echo '<h4 style="margin-top: 0;">' . __('Główne Funkcjonalności:', 'wordpress-prices') . '</h4>';

        // Margin Management with child options
        echo '<div style="margin-bottom: 15px;">';
        echo '<label style="display: block; margin-bottom: 10px;"><input type="checkbox" id="enable_margin_management" name="wp_prices_functionality_options[enable_margin_management]" value="1" ' . checked(1, isset($options['enable_margin_management']) ? $options['enable_margin_management'] : 0, false) . ' /> ';
        echo '<strong>' . __('Zarządzanie Marżami', 'wordpress-prices') . '</strong> - ' . __('Obliczanie i wyświetlanie marż produktów', 'wordpress-prices') . '</label>';

        // Child options for Margin Management
        echo '<div id="margin_management_children" style="margin-left: 25px; padding-left: 15px; border-left: 3px solid #ddd;">';
        echo '<p style="margin: 5px 0; color: #666; font-size: 13px;">' . __('Dodatkowe opcje zarządzania marżami:', 'wordpress-prices') . '</p>';

        echo '<label style="display: block; margin-bottom: 8px; font-size: 13px;"><input type="checkbox" name="wp_prices_functionality_options[enable_product_list_columns]" value="1" ' . checked(1, isset($options['enable_product_list_columns']) ? $options['enable_product_list_columns'] : 0, false) . ' /> ';
        echo __('Kolumny na Liście Produktów', 'wordpress-prices') . ' - ' . __('Dodatkowe kolumny z informacjami o marżach', 'wordpress-prices') . '</label>';

        echo '<label style="display: block; margin-bottom: 8px; font-size: 13px;"><input type="checkbox" name="wp_prices_functionality_options[enable_order_list_columns]" value="1" ' . checked(1, isset($options['enable_order_list_columns']) ? $options['enable_order_list_columns'] : 0, false) . ' /> ';
        echo __('Kolumny na Liście Zamówień', 'wordpress-prices') . ' - ' . __('Dodatkowe kolumny z informacjami o marżach zamówień', 'wordpress-prices') . '</label>';

        echo '</div>'; // End margin management children
        echo '</div>'; // End margin management container

        // Linked Products (standalone)
        echo '<label style="display: block; margin-bottom: 10px;"><input type="checkbox" name="wp_prices_functionality_options[enable_linked_products]" value="1" ' . checked(1, isset($options['enable_linked_products']) ? $options['enable_linked_products'] : 0, false) . ' /> ';
        echo '<strong>' . __('Produkty Powiązane (Checkboxy)', 'wordpress-prices') . '</strong> - ' . __('Interfejs checkboxów dla cross-sell i up-sell', 'wordpress-prices') . '</label>';

        echo '</div>';

        echo '<p class="description" style="color: #d63638;"><strong>' . __('Uwaga:', 'wordpress-prices') . '</strong> ' . __('Po zmianie ustawień funkcjonalności, odśwież stronę aby zobaczyć efekty.', 'wordpress-prices') . '</p>';

        // Add JavaScript to handle parent-child relationship
?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                function toggleMarginChildren() {
                    var isChecked = $('#enable_margin_management').is(':checked');
                    var $children = $('#margin_management_children');
                    var $childInputs = $children.find('input[type="checkbox"]');

                    if (isChecked) {
                        $children.show();
                        $childInputs.prop('disabled', false);
                    } else {
                        $children.hide();
                        $childInputs.prop('disabled', true).prop('checked', false);
                    }
                }

                // Initial state
                toggleMarginChildren();

                // Handle changes
                $('#enable_margin_management').on('change', toggleMarginChildren);
            });
        </script>
    <?php
    }



    /**
     * Meta key field callback
     */
    public function meta_key_field_callback()
    {
        $meta_key = get_option('wp_prices_meta_key', 'margin_category');
        echo '<input type="text" disabled name="wp_prices_meta_key" value="' . esc_attr($meta_key) . '" class="regular-text" />';
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
            'decimal_places' => 2,
            'show_order_margin_column' => 1,
            'show_order_average_percentage' => 1,
            'show_order_products_count' => 1
        ));

        $functionality_options = get_option('wp_prices_functionality_options', array(
            'enable_margin_management' => 1,
            'enable_product_list_columns' => 1,
            'enable_order_list_columns' => 1
        ));

        // Only show product list options if margin management AND product list columns are enabled
        if (!empty($functionality_options['enable_margin_management']) && !empty($functionality_options['enable_product_list_columns'])) {
            echo '<h4>' . __('Opcje wyświetlania na liście produktów:', 'wordpress-prices') . '</h4>';

            echo '<label><input type="checkbox" name="wp_prices_display_options[show_margin_percentage]" value="1" ' . checked(1, isset($options['show_margin_percentage']) ? $options['show_margin_percentage'] : 0, false) . ' /> ';
            echo __('Pokaż procentową marżę', 'wordpress-prices') . '</label><br>';

            echo '<label><input type="checkbox" name="wp_prices_display_options[show_price_without_margin]" value="1" ' . checked(1, isset($options['show_price_without_margin']) ? $options['show_price_without_margin'] : 0, false) . ' /> ';
            echo __('Pokaż cenę bez marży', 'wordpress-prices') . '</label><br>';

            echo '<label>' . __('Miejsca dziesiętne:', 'wordpress-prices') . ' ';
            echo '<input type="number" name="wp_prices_display_options[decimal_places]" value="' . esc_attr(isset($options['decimal_places']) ? $options['decimal_places'] : 2) . '" min="0" max="4" class="small-text" /></label><br><br>';
        }

        // Only show order list options if margin management AND order list columns are enabled
        if (!empty($functionality_options['enable_margin_management']) && !empty($functionality_options['enable_order_list_columns'])) {
            echo '<h4>' . __('Opcje wyświetlania na liście zamówień:', 'wordpress-prices') . '</h4>';

            echo '<label><input type="checkbox" name="wp_prices_display_options[show_order_margin_column]" value="1" ' . checked(1, isset($options['show_order_margin_column']) ? $options['show_order_margin_column'] : 0, false) . ' /> ';
            echo __('Pokaż kolumnę marży zamówień', 'wordpress-prices') . '</label><br>';

            echo '<label><input type="checkbox" name="wp_prices_display_options[show_order_average_percentage]" value="1" ' . checked(1, isset($options['show_order_average_percentage']) ? $options['show_order_average_percentage'] : 0, false) . ' /> ';
            echo __('Pokaż średnią marżę procentową', 'wordpress-prices') . '</label><br>';

            echo '<label><input type="checkbox" name="wp_prices_display_options[show_order_products_count]" value="1" ' . checked(1, isset($options['show_order_products_count']) ? $options['show_order_products_count'] : 0, false) . ' /> ';
            echo __('Pokaż liczbę produktów z marżą', 'wordpress-prices') . '</label>';
        }

        // Show message if no display options are available
        if (
            empty($functionality_options['enable_margin_management']) ||
            (empty($functionality_options['enable_product_list_columns']) && empty($functionality_options['enable_order_list_columns']))
        ) {
            echo '<p style="color: #666; font-style: italic;">' . __('Brak dostępnych opcji wyświetlania. Włącz zarządzanie marżami i odpowiednie funkcjonalności powyżej.', 'wordpress-prices') . '</p>';
        }
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

        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'functionality';

    ?>
        <div class="wrap">
            <h1><?php echo __('WordPress Prices - Ustawienia', 'wordpress-prices'); ?></h1>

            <!-- Tab Navigation -->
            <nav class="nav-tab-wrapper">
                <a href="?page=wp-prices-settings&tab=functionality" class="nav-tab <?php echo $current_tab === 'functionality' ? 'nav-tab-active' : ''; ?>">
                    <?php echo __('Funkcjonalności', 'wordpress-prices'); ?>
                </a>
                <a href="?page=wp-prices-settings&tab=margins" class="nav-tab <?php echo $current_tab === 'margins' ? 'nav-tab-active' : ''; ?>">
                    <?php echo __('Zarządzanie Marżami', 'wordpress-prices'); ?>
                </a>
            </nav>

            <!-- Tab Content -->
            <form method="post" action="">
                <?php settings_fields('wp_prices_settings'); ?>

                <div class="tab-content" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-top: none;">
                    <?php
                    switch ($current_tab) {
                        case 'functionality':
                            $this->render_functionality_tab();
                            break;
                        case 'margins':
                            $this->render_margins_tab();
                            break;
                        default:
                            $this->render_functionality_tab();
                            break;
                    }
                    ?>
                </div>

                <?php submit_button(); ?>
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
     * Render functionality tab
     */
    private function render_functionality_tab()
    {
        echo '<h2>' . __('Włącz/Wyłącz Funkcjonalności', 'wordpress-prices') . '</h2>';
        echo '<p>' . __('Wybierz które funkcjonalności pluginu chcesz włączyć lub wyłączyć.', 'wordpress-prices') . '</p>';
        $this->functionality_options_callback();
    }

    /**
     * Render margins tab
     */
    private function render_margins_tab()
    {
        $functionality_options = get_option('wp_prices_functionality_options', array(
            'enable_margin_management' => 1,
            'enable_product_list_columns' => 1,
            'enable_order_list_columns' => 1
        ));

        if (empty($functionality_options['enable_margin_management'])) {
            echo '<div class="notice notice-warning inline"><p>';
            echo '<strong>' . __('Uwaga:', 'wordpress-prices') . '</strong> ';
            echo __('Zarządzanie marżami jest wyłączone. Włącz tę funkcjonalność w zakładce "Funkcjonalności" aby móc konfigurować ustawienia marży.', 'wordpress-prices');
            echo '</p></div>';
            return;
        }

        echo '<h2>' . __('Ustawienia Marży', 'wordpress-prices') . '</h2>';
        echo '<p>' . __('Skonfiguruj marże dla różnych kategorii produktów.', 'wordpress-prices') . '</p>';

        echo '<table class="form-table" role="presentation">';

        echo '<tr>';
        echo '<th scope="row">' . __('Klucz Meta Pola', 'wordpress-prices') . '</th>';
        echo '<td>';
        $this->meta_key_field_callback();
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row">' . __('Marże dla Kategorii', 'wordpress-prices') . '</th>';
        echo '<td>';
        $this->margins_field_callback();
        echo '</td>';
        echo '</tr>';

        echo '</table>';

        // Add display options section
        echo '<hr style="margin: 30px 0;">';
        echo '<h2>' . __('Opcje Wyświetlania', 'wordpress-prices') . '</h2>';

        if (empty($functionality_options['enable_product_list_columns']) && empty($functionality_options['enable_order_list_columns'])) {
            echo '<div class="notice notice-warning inline"><p>';
            echo '<strong>' . __('Uwaga:', 'wordpress-prices') . '</strong> ';
            echo __('Kolumny na listach produktów i zamówień są wyłączone. Włącz odpowiednie funkcjonalności w zakładce "Funkcjonalności" aby móc konfigurować opcje wyświetlania.', 'wordpress-prices');
            echo '</p></div>';
        } else {
            echo '<p>' . __('Skonfiguruj sposób wyświetlania informacji o marżach na listach produktów i zamówień.', 'wordpress-prices') . '</p>';

            echo '<table class="form-table" role="presentation">';
            echo '<tr>';
            echo '<th scope="row">' . __('Opcje Wyświetlania', 'wordpress-prices') . '</th>';
            echo '<td>';
            $this->display_options_callback();
            echo '</td>';
            echo '</tr>';
            echo '</table>';
        }
    }



    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook)
    {
        if ($hook !== 'toplevel_page_wp-prices-settings') {
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
