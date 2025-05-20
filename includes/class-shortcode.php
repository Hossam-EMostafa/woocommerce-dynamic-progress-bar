<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Progress_Bar_Shortcode {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_shortcode('wc_progress_bar', array($this, 'render_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'maybe_enqueue_scripts_late'));
    }
    
    public function render_shortcode($atts) {
        $progress_bar = WC_Progress_Bar::instance();
        
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'width' => '',
            'height' => '',
            'background_color' => '',
            'fill_color' => '',
            'border_color' => '',
            'border_width' => '',
            'border_radius' => '',
            'transition' => '',
            'text_color' => '',
            'font_size' => '',
            'font_weight' => '',
        ), $atts, 'wc_progress_bar');
        
        // Prepare attributes for the progress bar
        $processed_atts = array();
        foreach ($atts as $key => $value) {
            if (!empty($value)) {
                $processed_atts[$key] = $value;
            }
        }
        
        return $progress_bar->render_progress_bar($processed_atts);
    }
    
    public function enqueue_scripts() {
        wp_register_style(
            'wc-progress-bar-frontend',
            WC_PROGRESS_BAR_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            WC_PROGRESS_BAR_VERSION
        );
        
        wp_register_script(
            'wc-progress-bar-frontend',
            WC_PROGRESS_BAR_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery', 'wp-util'),
            WC_PROGRESS_BAR_VERSION,
            true
        );
    }
    
    public function maybe_enqueue_scripts_late() {
        // If the shortcode was used on the page but we haven't enqueued scripts yet
        if (wp_script_is('wc-progress-bar-frontend', 'registered') && !wp_script_is('wc-progress-bar-frontend', 'enqueued')) {
            wp_enqueue_style('wc-progress-bar-frontend');
            wp_enqueue_script('wc-progress-bar-frontend');
        }
    }
}