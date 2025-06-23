<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Progress_Bar {
    private static $instance = null;
    private $settings;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->settings = get_option('wc_progress_bar_settings', array());
        add_action('wp_ajax_get_progress_bar_data', array($this, 'get_progress_via_ajax'));
        add_action('wp_ajax_nopriv_get_progress_bar_data', array($this, 'get_progress_via_ajax'));
    }

    public function get_current_progress() {
        $cache_key = 'wc_progress_bar_' . md5(json_encode(WC()->cart ? WC()->cart->get_cart_contents() : 'empty'));
        $cached = wp_cache_get($cache_key, 'wc_progress_bar');
        
        if (false !== $cached) {
            return $cached;
        }

        if (!WC()->cart || WC()->cart->is_empty()) {
            $result = array(
                'progress' => 0,
                'text' => __('Cart is empty', 'wc-dynamic-progress-bar'),
            );
            wp_cache_set($cache_key, $result, 'wc_progress_bar', 3600);
            return $result;
        }

        $cart_total = WC()->cart->get_subtotal();
        $product_count = WC()->cart->get_cart_contents_count();
        $conditions = $this->get_sorted_conditions();

        foreach ($conditions as $condition) {
            if ($this->evaluate_condition($condition, $cart_total, $product_count)) {
                $target_value = floatval($condition['value']);
                $remaining = max(0, $target_value - $cart_total);
                $progress_percentage = $this->calculate_progress_percentage($cart_total, $target_value);
                
                $result = array(
                    'progress' => $condition['progress'],
                    'text' => $this->generate_dynamic_text($condition['text'], $cart_total, $product_count, $condition['progress'], $remaining, $progress_percentage),
                );
                
                wp_cache_set($cache_key, $result, 'wc_progress_bar', 3600);
                return $result;
            }
        }

        // Default state
        $target_value = $this->get_next_target_value($cart_total);
        $remaining = max(0, $target_value - $cart_total);
        $progress_percentage = $this->calculate_progress_percentage($cart_total, $target_value);
        
        $result = array(
            'progress' => $this->settings['default_progress'] ?? 0,
            'text' => $this->generate_dynamic_text(
                $this->settings['default_text'] ?? '',
                $cart_total,
                $product_count,
                $this->settings['default_progress'] ?? 0,
                $remaining,
                $progress_percentage
            ),
        );
        
        wp_cache_set($cache_key, $result, 'wc_progress_bar', 3600);
        return $result;
    }

    private function get_sorted_conditions() {
        $conditions = $this->settings['conditions'] ?? array();
        usort($conditions, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
        return $conditions;
    }

    private function evaluate_condition($condition, $cart_total, $product_count) {
        $value_to_compare = $condition['type'] === 'cart_total' ? $cart_total : $product_count;
        $main_result = $this->compare_values($value_to_compare, $condition['operator'], floatval($condition['value']));

        if (empty($condition['sub_conditions'])) {
            return $main_result;
        }

        $sub_results = array_map(function($sub_condition) use ($cart_total, $product_count) {
            $sub_value = $sub_condition['type'] === 'cart_total' ? $cart_total : $product_count;
            return $this->compare_values($sub_value, $sub_condition['operator'], floatval($sub_condition['value']));
        }, $condition['sub_conditions']);

        return $condition['logic'] === 'and' 
            ? $main_result && !in_array(false, $sub_results, true)
            : $main_result || in_array(true, $sub_results, true);
    }

    private function compare_values($actual, $operator, $expected) {
        switch ($operator) {
            case '>': return $actual > $expected;
            case '>=': return $actual >= $expected;
            case '<': return $actual < $expected;
            case '<=': return $actual <= $expected;
            case '==': return $actual == $expected;
            default: return false;
        }
    }

    private function generate_dynamic_text($text, $cart_total, $product_count, $progress, $remaining, $progress_percentage) {
        $placeholders = array(
            '{cart_total}' => wc_price($cart_total),
            '{product_count}' => $product_count,
            '{progress_percentage}' => $progress_percentage . '%',
            '{remaining_amount}' => wc_price($remaining),
        );
        return wp_kses_post(str_replace(array_keys($placeholders), array_values($placeholders), $text));
    }

    private function get_next_target_value($cart_total) {
        $conditions = $this->settings['conditions'] ?? array();
        $min_amount = null;

        foreach ($conditions as $condition) {
            if ($condition['type'] === 'cart_total' && $condition['operator'] === '>=') {
                $target = floatval($condition['value']);
                if ($cart_total < $target && ($min_amount === null || $target < $min_amount)) {
                    $min_amount = $target;
                }
            }
        }

        return $min_amount ?? 0;
    }

    private function calculate_progress_percentage($cart_total, $target_value) {
        if ($target_value <= 0) return 0;
        $percentage = ($cart_total / $target_value) * 100;
        return min(100, max(0, round($percentage, 2)));
    }

    public function render_progress_bar($atts = array()) {
        $progress_data = $this->get_current_progress();
        $settings = $this->settings;

        // Merge settings with shortcode attributes
        $bar_style = array_merge(
            $settings['bar_style'] ?? array(),
            $this->filter_atts_by_prefix($atts, 'bar_')
        );
        
        $text_style = array_merge(
            $settings['text_style'] ?? array(),
            $this->filter_atts_by_prefix($atts, 'text_')
        );

        // Enqueue assets
        $this->enqueue_frontend_assets();

        ob_start();
        ?>
        <div class="wc-progress-bar-container" data-transition="<?php echo esc_attr($bar_style['transition'] === 'yes' ? '1' : '0'); ?>">
            <div class="wc-progress-bar-text" style="
                font-size: <?php echo esc_attr($text_style['font_size'] ?? '16px'); ?>;
                font-weight: <?php echo esc_attr($text_style['font_weight'] ?? 'normal'); ?>;
                color: <?php echo esc_attr($text_style['color'] ?? '#333333'); ?>;
                margin-bottom: 10px;
            ">
                <?php echo $progress_data['text']; ?>
            </div>
            <div class="wc-progress-bar-background" style="
                height: <?php echo esc_attr($bar_style['height'] ?? '20px'); ?>;
                width: <?php echo esc_attr($bar_style['width'] ?? '100%'); ?>;
                background-color: <?php echo esc_attr($bar_style['background_color'] ?? '#f5f5f5'); ?>;
                border: <?php echo esc_attr($bar_style['border_width'] ?? '1px'); ?> solid <?php echo esc_attr($bar_style['border_color'] ?? '#dddddd'); ?>;
                border-radius: <?php echo esc_attr($bar_style['border_radius'] ?? '4px'); ?>;
                overflow: hidden;
            ">
                <div class="wc-progress-bar-fill" style="
                    height: 100%;
                    width: <?php echo esc_attr($progress_data['progress']); ?>%;
                    background-color: <?php echo esc_attr($bar_style['fill_color'] ?? '#4CAF50'); ?>;
                    transition: <?php echo ($bar_style['transition'] ?? 'yes') === 'yes' ? 'width 0.5s ease-in-out' : 'none'; ?>;
                " role="progressbar" aria-valuenow="<?php echo esc_attr($progress_data['progress']); ?>" aria-valuemin="0" aria-valuemax="100">
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function filter_atts_by_prefix($atts, $prefix) {
        $filtered = array();
        $prefix_length = strlen($prefix);
        
        foreach ($atts as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $filtered[substr($key, $prefix_length)] = $value;
            }
        }
        
        return $filtered;
    }

    private function enqueue_frontend_assets() {
        wp_enqueue_style('wc-progress-bar-frontend');
        wp_enqueue_script('wc-progress-bar-frontend');
        
        wp_localize_script('wc-progress-bar-frontend', 'wcProgressBar', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_progress_bar_nonce'),
        ));
    }

    public function get_progress_via_ajax() {
        check_ajax_referer('wc_progress_bar_nonce', 'nonce');
        wp_send_json_success($this->get_current_progress());
    }
}