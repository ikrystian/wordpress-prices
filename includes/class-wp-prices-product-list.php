<?php
/**
 * Product list display functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_Prices_Product_List {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add columns to product list
        add_filter('manage_edit-product_columns', array($this, 'add_product_columns'));
        add_action('manage_product_posts_custom_column', array($this, 'populate_product_columns'), 10, 2);
        add_filter('manage_edit-product_sortable_columns', array($this, 'make_columns_sortable'));
        
        // Add quick edit support
        add_action('quick_edit_custom_box', array($this, 'add_quick_edit_fields'), 10, 2);
        add_action('save_post', array($this, 'save_quick_edit_fields'));
        
        // Add bulk edit support
        add_action('bulk_edit_custom_box', array($this, 'add_bulk_edit_fields'), 10, 2);
        add_action('wp_ajax_save_bulk_edit_wp_prices', array($this, 'save_bulk_edit_fields'));
        
        // Enqueue scripts for admin
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add custom columns to product list
     */
    public function add_product_columns($columns) {
        // Insert margin columns after price column
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'price') {
                $new_columns['margin_info'] = __('Informacje o Marży', 'wordpress-prices');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Populate custom columns
     */
    public function populate_product_columns($column, $post_id) {
        if ($column === 'margin_info') {
            $this->display_margin_column($post_id);
        }
    }
    
    /**
     * Display margin information in column
     */
    private function display_margin_column($product_id) {
        $margin_info = WP_Prices_Margin_Calculator::get_product_margin_info($product_id);
        
        if (!$margin_info) {
            echo '<span class="wp-prices-no-margin">' . __('Brak marży', 'wordpress-prices') . '</span>';
            return;
        }
        
        $display_options = get_option('wp_prices_display_options', array(
            'show_margin_percentage' => 1,
            'show_price_without_margin' => 1,
            'decimal_places' => 2
        ));
        
        echo '<div class="wp-prices-margin-column">';
        
        // Show margin category
        echo '<div class="wp-prices-category">';
        echo '<strong>' . esc_html(ucfirst($margin_info['category'])) . '</strong>';
        echo '</div>';
        
        // Show margin percentage
        if (isset($display_options['show_margin_percentage']) && $display_options['show_margin_percentage']) {
            echo '<div class="wp-prices-percentage">';
            echo sprintf(__('Marża: %s%%', 'wordpress-prices'), number_format($margin_info['margin_percentage'], 1));
            echo '</div>';
        }
        
        // Show price without margin
        if (isset($display_options['show_price_without_margin']) && $display_options['show_price_without_margin']) {
            echo '<div class="wp-prices-without-margin">';
            echo sprintf(__('Bez marży: %s', 'wordpress-prices'), wc_price($margin_info['price_without_margin']));
            echo '</div>';
        }
        
        // Show margin amount
        echo '<div class="wp-prices-margin-amount">';
        echo sprintf(__('Kwota marży: %s', 'wordpress-prices'), wc_price($margin_info['margin_amount']));
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Make columns sortable
     */
    public function make_columns_sortable($columns) {
        $columns['margin_info'] = 'margin_category';
        return $columns;
    }
    
    /**
     * Add quick edit fields
     */
    public function add_quick_edit_fields($column_name, $post_type) {
        if ($post_type !== 'product' || $column_name !== 'margin_info') {
            return;
        }
        
        $meta_key = get_option('wp_prices_meta_key', 'margin_category');
        $margins = get_option('wp_prices_margins', array());
        
        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <label>
                    <span class="title"><?php _e('Kategoria Marży', 'wordpress-prices'); ?></span>
                    <select name="<?php echo esc_attr($meta_key); ?>">
                        <option value=""><?php _e('-- Wybierz kategorię marży --', 'wordpress-prices'); ?></option>
                        <?php foreach ($margins as $category => $margin): ?>
                            <option value="<?php echo esc_attr($category); ?>">
                                <?php echo sprintf('%s (%s%%)', esc_html(ucfirst($category)), $margin); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        </fieldset>
        <?php
    }
    
    /**
     * Save quick edit fields
     */
    public function save_quick_edit_fields($post_id) {
        if (get_post_type($post_id) !== 'product') {
            return;
        }
        
        $meta_key = get_option('wp_prices_meta_key', 'margin_category');
        
        if (isset($_POST[$meta_key])) {
            $value = sanitize_text_field($_POST[$meta_key]);
            update_post_meta($post_id, $meta_key, $value);
        }
    }
    
    /**
     * Add bulk edit fields
     */
    public function add_bulk_edit_fields($column_name, $post_type) {
        if ($post_type !== 'product' || $column_name !== 'margin_info') {
            return;
        }
        
        $meta_key = get_option('wp_prices_meta_key', 'margin_category');
        $margins = get_option('wp_prices_margins', array());
        
        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <label>
                    <span class="title"><?php _e('Kategoria Marży', 'wordpress-prices'); ?></span>
                    <select name="<?php echo esc_attr($meta_key); ?>">
                        <option value=""><?php _e('-- Bez zmian --', 'wordpress-prices'); ?></option>
                        <option value="remove"><?php _e('-- Usuń marżę --', 'wordpress-prices'); ?></option>
                        <?php foreach ($margins as $category => $margin): ?>
                            <option value="<?php echo esc_attr($category); ?>">
                                <?php echo sprintf('%s (%s%%)', esc_html(ucfirst($category)), $margin); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        </fieldset>
        <?php
    }
    
    /**
     * Save bulk edit fields
     */
    public function save_bulk_edit_fields() {
        if (!current_user_can('edit_products')) {
            wp_die(__('Brak uprawnień', 'wordpress-prices'));
        }
        
        $post_ids = isset($_POST['post_ids']) ? $_POST['post_ids'] : array();
        $meta_key = get_option('wp_prices_meta_key', 'margin_category');
        $margin_category = isset($_POST[$meta_key]) ? sanitize_text_field($_POST[$meta_key]) : '';
        
        if (empty($post_ids) || empty($margin_category)) {
            wp_die(__('Nieprawidłowe dane', 'wordpress-prices'));
        }
        
        foreach ($post_ids as $post_id) {
            if (get_post_type($post_id) === 'product') {
                if ($margin_category === 'remove') {
                    delete_post_meta($post_id, $meta_key);
                } else {
                    update_post_meta($post_id, $meta_key, $margin_category);
                }
            }
        }
        
        wp_die(__('Zapisano zmiany', 'wordpress-prices'));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'edit.php' || get_current_screen()->post_type !== 'product') {
            return;
        }
        
        wp_enqueue_script(
            'wp-prices-product-list',
            WP_PRICES_PLUGIN_URL . 'assets/js/product-list.js',
            array('jquery'),
            WP_PRICES_VERSION,
            true
        );
        
        wp_localize_script('wp-prices-product-list', 'wp_prices_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_prices_nonce')
        ));
    }
}
