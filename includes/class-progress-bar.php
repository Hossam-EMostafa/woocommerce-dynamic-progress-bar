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
    }

    public function get_current_progress() {
        if (!WC()->cart) {
            return array(
                'progress' => 0,
                'text' => __('Cart is empty', 'wc-dynamic-progress-bar'),
            );
        }

        $cart_total = WC()->cart->get_subtotal();
        $product_count = WC()->cart->get_cart_contents_count();
        $conditions = isset($this->settings['conditions']) ? $this->settings['conditions'] : array();

        // Sort conditions by priority if needed
        usort($conditions, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        foreach ($conditions as $condition) {
            // Evaluate the condition with its sub-conditions
            $condition_met = $this->evaluate_condition($condition, $cart_total, $product_count);

            if ($condition_met) {
                $text = $this->replace_placeholders($condition['text'], $cart_total, $product_count, $condition['progress']);
                return array(
                    'progress' => $condition['progress'],
                    'text' => $text,
                );
            }
        }

        // Default state if no conditions are met
        $default_progress = isset($this->settings['default_progress']) ? $this->settings['default_progress'] : 0;
        $default_text = isset($this->settings['default_text']) ? $this->settings['default_text'] : '';

        $text = $this->replace_placeholders($default_text, $cart_total, $product_count, $default_progress);

        return array(
            'progress' => $default_progress,
            'text' => $text,
        );
    }

    /**
     * Evaluate a condition with its sub-conditions
     *
     * @param array $condition The condition to evaluate
     * @param float $cart_total The current cart total
     * @param int $product_count The current product count
     * @return bool Whether the condition is met
     */
    private function evaluate_condition($condition, $cart_total, $product_count) {
        // Evaluate main condition
        $main_result = $this->compare_values(
            $condition['type'] === 'cart_total' ? $cart_total : $product_count,
            $condition['operator'],
            floatval($condition['value'])
        );

        // If there are no sub-conditions, return the main result
        if (empty($condition['sub_conditions'])) {
            return $main_result;
        }

        // Evaluate sub-conditions
        $sub_results = array();
        foreach ($condition['sub_conditions'] as $sub_condition) {
            $sub_results[] = $this->compare_values(
                $sub_condition['type'] === 'cart_total' ? $cart_total : $product_count,
                $sub_condition['operator'],
                floatval($sub_condition['value'])
            );
        }

        // Apply logical operator (AND/OR)
        if (isset($condition['logic']) && $condition['logic'] === 'and') {
            // For AND logic, main condition and ALL sub-conditions must be true
            return $main_result && !in_array(false, $sub_results, true);
        } else {
            // For OR logic, main condition OR ANY sub-condition must be true
            return $main_result || in_array(true, $sub_results, true);
        }
    }

    /**
     * Compare values based on operator
     *
     * @param mixed $actual The actual value
     * @param string $operator The comparison operator
     * @param mixed $expected The expected value
     * @return bool The result of the comparison
     */
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

    private function replace_placeholders($text, $cart_total, $product_count, $progress) {
        $placeholders = array(
            '{cart_total}' => wc_price($cart_total),
            '{product_count}' => $product_count,
            '{progress_percentage}' => $progress . '%',
            '{remaining_amount}' => wc_price($this->get_remaining_amount($cart_total)),
        );

        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }

    private function get_remaining_amount($cart_total) {
        $conditions = isset($this->settings['conditions']) ? $this->settings['conditions'] : array();
        $min_amount = null;

        foreach ($conditions as $condition) {
            if ($condition['type'] === 'cart_total' && $condition['operator'] === '>=') {
                $target = floatval($condition['value']);
                if ($cart_total < $target && ($min_amount === null || $target < $min_amount)) {
                    $min_amount = $target;
                }
            }
        }

        return $min_amount !== null ? max(0, $min_amount - $cart_total) : 0;
    }

    public function render_progress_bar($atts = array()) {
        $progress_data = $this->get_current_progress();
        $settings = $this->settings;

        // Merge shortcode attributes with settings
        $bar_style = isset($settings['bar_style']) ? $settings['bar_style'] : array();
        $text_style = isset($settings['text_style']) ? $settings['text_style'] : array();

        // Override with shortcode attributes
        if (!empty($atts)) {
            foreach ($atts as $key => $value) {
                if (strpos($key, 'bar_') === 0) {
                    $bar_key = substr($key, 4);
                    $bar_style[$bar_key] = $value;
                } elseif (strpos($key, 'text_') === 0) {
                    $text_key = substr($key, 5);
                    $text_style[$text_key] = $value;
                }
            }
        }

        // Enqueue scripts and styles
        wp_enqueue_style('wc-progress-bar-frontend');
        wp_enqueue_script('wc-progress-bar-frontend');

        // Localize script with AJAX URL and cart data
        wp_localize_script('wc-progress-bar-frontend', 'wcProgressBar', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_progress_bar_nonce'),
        ));

        ob_start();
        ?>
        <div class="wc-progress-bar-container" data-transition="<?php echo esc_attr($bar_style['transition'] === 'yes' ? '1' : '0'); ?>">
            <div class="wc-progress-bar-text" style="
                font-size: <?php echo esc_attr($text_style['font_size']); ?>;
                font-weight: <?php echo esc_attr($text_style['font_weight']); ?>;
                color: <?php echo esc_attr($text_style['color']); ?>;
                margin-bottom: 10px;
            ">
                <?php echo wp_kses_post($progress_data['text']); ?>
            </div>
            <div class="wc-progress-bar-background" style="
                height: <?php echo esc_attr($bar_style['height']); ?>;
                width: <?php echo esc_attr($bar_style['width']); ?>;
                background-color: <?php echo esc_attr($bar_style['background_color']); ?>;
                border: <?php echo esc_attr($bar_style['border_width']); ?> solid <?php echo esc_attr($bar_style['border_color']); ?>;
                border-radius: <?php echo esc_attr($bar_style['border_radius']); ?>;
                overflow: hidden;
            ">
                <div class="wc-progress-bar-fill" style="
                    height: 100%;
                    width: <?php echo esc_attr($progress_data['progress']); ?>%;
                    background-color: <?php echo esc_attr($bar_style['fill_color']); ?>;
                    transition: <?php echo $bar_style['transition'] === 'yes' ? 'width 0.5s ease-in-out' : 'none'; ?>;
                " role="progressbar" aria-valuenow="<?php echo esc_attr($progress_data['progress']); ?>" aria-valuemin="0" aria-valuemax="100">
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function get_progress_via_ajax() {
        check_ajax_referer('wc_progress_bar_nonce', 'nonce');

        $progress_data = $this->get_current_progress();

        wp_send_json_success(array(
            'progress' => $progress_data['progress'],
            'text' => $progress_data['text'],
        ));
    }
}