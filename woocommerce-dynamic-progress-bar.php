<?php
/**
 * Plugin Name: WooCommerce Dynamic Progress Bar
 * Plugin URI: https://school-of-marketing.com/
 * Description: Adds a dynamic progress bar to WooCommerce that updates based on cart conditions.
 * Version: 1.1.0
 * Author: SoM (Hossam Essam)
 * Author URI: https://school-of-marketing.com/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wc-dynamic-progress-bar
 * Domain Path: /languages
 * WC requires at least: 7.0
 * WC tested up to: 8.0
 */

defined('ABSPATH') || exit;

// Define plugin constants
define('WC_PROGRESS_BAR_VERSION', '1.1.0');
define('WC_PROGRESS_BAR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_PROGRESS_BAR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_PROGRESS_BAR_BASENAME', plugin_basename(__FILE__));

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) {
    add_action('admin_notices', function() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('WooCommerce Dynamic Progress Bar requires WooCommerce to be installed and active.', 'wc-dynamic-progress-bar'); ?></p>
        </div>
        <?php
    });
    return;
}

// Load plugin classes
require_once WC_PROGRESS_BAR_PLUGIN_DIR . 'includes/class-progress-bar.php';
require_once WC_PROGRESS_BAR_PLUGIN_DIR . 'includes/class-settings.php';
require_once WC_PROGRESS_BAR_PLUGIN_DIR . 'includes/class-shortcode.php';

// Initialize plugin
function wc_progress_bar_init() {
    // Initialize settings
    WC_Progress_Bar_Settings::instance();
    
    // Initialize frontend functionality
    if (!is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
        WC_Progress_Bar_Shortcode::instance();
    }
}
add_action('plugins_loaded', 'wc_progress_bar_init');

// Load text domain
function wc_progress_bar_load_textdomain() {
    load_plugin_textdomain('wc-dynamic-progress-bar', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('init', 'wc_progress_bar_load_textdomain');

// Register activation hook
register_activation_hook(__FILE__, function() {
    // Set default options if they don't exist
    $defaults = array(
        'conditions' => array(),
        'default_text' => __('Add {remaining_amount} to get free shipping!', 'wc-dynamic-progress-bar'),
        'default_progress' => 0,
        'text_style' => array(
            'font_size' => '16px',
            'font_weight' => 'normal',
            'color' => '#333333',
        ),
        'bar_style' => array(
            'height' => '20px',
            'width' => '100%',
            'background_color' => '#f5f5f5',
            'fill_color' => '#4CAF50',
            'border_color' => '#dddddd',
            'border_width' => '1px',
            'border_radius' => '4px',
            'transition' => 'yes',
        ),
    );
    
    if (!get_option('wc_progress_bar_settings')) {
        update_option('wc_progress_bar_settings', $defaults);
    }
});

// AJAX handler for frontend updates
add_action('wp_ajax_get_progress_bar_data', 'wc_progress_bar_get_progress_data');
add_action('wp_ajax_nopriv_get_progress_bar_data', 'wc_progress_bar_get_progress_data');
function wc_progress_bar_get_progress_data() {
    check_ajax_referer('wc_progress_bar_nonce', 'nonce');
    
    $progress_bar = WC_Progress_Bar::instance();
    $progress_data = $progress_bar->get_current_progress();
    
    wp_send_json_success($progress_data);
}

// Add WooCommerce cart fragments for AJAX updates
add_filter('woocommerce_add_to_cart_fragments', 'wc_progress_bar_add_to_cart_fragments');
function wc_progress_bar_add_to_cart_fragments($fragments) {
    $progress_bar = WC_Progress_Bar::instance();
    $fragments['.wc-progress-bar-container'] = $progress_bar->render_progress_bar();
    
    return $fragments;
}