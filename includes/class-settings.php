<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Progress_Bar_Settings {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function add_settings_page() {
        add_submenu_page(
            'woocommerce',
            __('Dynamic Progress Bar', 'wc-dynamic-progress-bar'),
            __('Progress Bar', 'wc-dynamic-progress-bar'),
            'manage_options',
            'wc-progress-bar-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting(
            'wc_progress_bar_settings_group',
            'wc_progress_bar_settings',
            array($this, 'sanitize_settings')
        );

        // Rendered bar results are cached for up to an hour; drop the cache
        // whenever settings change so the frontend reflects them immediately.
        add_action('update_option_wc_progress_bar_settings', array($this, 'flush_render_cache'), 10, 0);

        // General Settings Section
        add_settings_section(
            'wc_progress_bar_general_section',
            __('General Settings', 'wc-dynamic-progress-bar'),
            array($this, 'render_general_section'),
            'wc-progress-bar-settings'
        );

        add_settings_field(
            'default_text',
            __('Default Text', 'wc-dynamic-progress-bar'),
            array($this, 'render_default_text_field'),
            'wc-progress-bar-settings',
            'wc_progress_bar_general_section'
        );

        add_settings_field(
            'default_progress',
            __('Default Progress (%)', 'wc-dynamic-progress-bar'),
            array($this, 'render_default_progress_field'),
            'wc-progress-bar-settings',
            'wc_progress_bar_general_section'
        );

        // Conditions Section
        add_settings_section(
            'wc_progress_bar_conditions_section',
            __('Progress Conditions', 'wc-dynamic-progress-bar'),
            array($this, 'render_conditions_section'),
            'wc-progress-bar-settings'
        );

        add_settings_field(
            'conditions',
            __('Conditions', 'wc-dynamic-progress-bar'),
            array($this, 'render_conditions_field'),
            'wc-progress-bar-settings',
            'wc_progress_bar_conditions_section'
        );

        // Text Styling Section
        add_settings_section(
            'wc_progress_bar_text_style_section',
            __('Text Styling', 'wc-dynamic-progress-bar'),
            array($this, 'render_text_style_section'),
            'wc-progress-bar-settings'
        );

        add_settings_field(
            'text_font_size',
            __('Font Size', 'wc-dynamic-progress-bar'),
            array($this, 'render_text_font_size_field'),
            'wc-progress-bar-settings',
            'wc_progress_bar_text_style_section'
        );

        add_settings_field(
            'text_font_weight',
            __('Font Weight', 'wc-dynamic-progress-bar'),
            array($this, 'render_text_font_weight_field'),
            'wc-progress-bar-settings',
            'wc_progress_bar_text_style_section'
        );

        add_settings_field(
            'text_color',
            __('Text Color', 'wc-dynamic-progress-bar'),
            array($this, 'render_text_color_field'),
            'wc-progress-bar-settings',
            'wc_progress_bar_text_style_section'
        );

        // Progress Bar Styling Section
        add_settings_section(
            'wc_progress_bar_style_section',
            __('Progress Bar Styling', 'wc-dynamic-progress-bar'),
            array($this, 'render_bar_style_section'),
            'wc-progress-bar-settings'
        );

        add_settings_field(
            'bar_height',
            __('Height', 'wc-dynamic-progress-bar'),
            array($this, 'render_bar_height_field'),
            'wc-progress-bar-settings',
            'wc_progress_bar_style_section'
        );

        add_settings_field(
            'bar_width',
            __('Width', 'wc-dynamic-progress-bar'),
            array($this, 'render_bar_width_field'),
            'wc-progress-bar-settings',
            'wc_progress_bar_style_section'
        );

        add_settings_field(
            'bar_background_color',
            __('Background Color', 'wc-dynamic-progress-bar'),
            array($this, 'render_bar_background_color_field'),
            'wc-progress-bar-settings',
            'wc_progress_bar_style_section'
        );

        add_settings_field(
            'bar_fill_color',
            __('Fill Color', 'wc-dynamic-progress-bar'),
            array($this, 'render_bar_fill_color_field'),
            'wc-progress-bar-settings',
            'wc_progress_bar_style_section'
        );

        add_settings_field(
            'bar_border_color',
            __('Border Color', 'wc-dynamic-progress-bar'),
            array($this, 'render_bar_border_color_field'),
            'wc-progress-bar-settings',
            'wc_progress_bar_style_section'
        );

        add_settings_field(
            'bar_border_width',
            __('Border Width', 'wc-dynamic-progress-bar'),
            array($this, 'render_bar_border_width_field'),
            'wc-progress-bar-settings',
            'wc_progress_bar_style_section'
        );

        add_settings_field(
            'bar_border_radius',
            __('Border Radius', 'wc-dynamic-progress-bar'),
            array($this, 'render_bar_border_radius_field'),
            'wc-progress-bar-settings',
            'wc_progress_bar_style_section'
        );

        add_settings_field(
            'bar_transition',
            __('Smooth Transition', 'wc-dynamic-progress-bar'),
            array($this, 'render_bar_transition_field'),
            'wc-progress-bar-settings',
            'wc_progress_bar_style_section'
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <form action="options.php" method="post">
                <?php
                settings_fields('wc_progress_bar_settings_group');
                do_settings_sections('wc-progress-bar-settings');
                submit_button(__('Save Settings', 'wc-dynamic-progress-bar'));
                ?>
            </form>

            <div class="wc-progress-bar-preview">
                <h2><?php _e('Preview', 'wc-dynamic-progress-bar'); ?></h2>
                <div id="wc-progress-bar-admin-preview">
                    <?php
                    $progress_bar = WC_Progress_Bar::instance();
                    echo $progress_bar->render_progress_bar();
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_general_section() {
        echo '<p>' . __('Configure the default behavior of the progress bar.', 'wc-dynamic-progress-bar') . '</p>';
    }

    public function render_default_text_field() {
        $settings = get_option('wc_progress_bar_settings');
        $value = isset($settings['default_text']) ? $settings['default_text'] : '';
        ?>
        <textarea id="default_text" name="wc_progress_bar_settings[default_text]" rows="3" cols="50" class="regular-text"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">
            <?php _e('Default text to display when no conditions are met. Available placeholders:', 'wc-dynamic-progress-bar'); ?>
            <code>{cart_total}</code>, <code>{product_count}</code>, <code>{progress_percentage}</code>, <code>{remaining_amount}</code>
        </p>
        <?php
    }

    public function render_default_progress_field() {
        $settings = get_option('wc_progress_bar_settings');
        $value = isset($settings['default_progress']) ? $settings['default_progress'] : 0;
        ?>
        <input type="number" id="default_progress" name="wc_progress_bar_settings[default_progress]" min="0" max="100" value="<?php echo esc_attr($value); ?>" class="small-text">
        <p class="description"><?php _e('Default progress percentage (0-100) when no conditions are met.', 'wc-dynamic-progress-bar'); ?></p>
        <?php
    }

    public function render_conditions_section() {
        echo '<p>' . __('Set conditions that determine the progress bar state based on cart total or product count.', 'wc-dynamic-progress-bar') . '</p>';
    }

    public function render_conditions_field() {
        $settings = get_option('wc_progress_bar_settings');
        $conditions = isset($settings['conditions']) ? $settings['conditions'] : array();
        ?>
        <div id="wc-progress-bar-conditions">
            <?php if (empty($conditions)) : ?>
                <div class="wc-progress-bar-condition" data-index="0">
                    <?php $this->render_condition_fields(0); ?>
                </div>
            <?php else : ?>
                <?php foreach ($conditions as $index => $condition) : ?>
                    <div class="wc-progress-bar-condition" data-index="<?php echo esc_attr($index); ?>">
                        <?php $this->render_condition_fields($index, $condition); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <button type="button" id="wc-progress-bar-add-condition" class="button"><?php _e('Add Condition', 'wc-dynamic-progress-bar'); ?></button>
        </div>
        <?php
    }

    private function render_condition_fields($index, $condition = array()) {
        $condition = wp_parse_args($condition, array(
            'type' => 'cart_total',
            'operator' => '>=',
            'value' => '',
            'progress' => 50,
            'text' => '',
            'priority' => $index,
            'logic' => 'and',
            'sub_conditions' => array()
        ));
        ?>
        <div class="wc-progress-bar-condition-fields">
            <div class="wc-progress-bar-main-condition">
                <select name="wc_progress_bar_settings[conditions][<?php echo esc_attr($index); ?>][type]" class="wc-progress-bar-condition-type">
                    <option value="cart_total" <?php selected($condition['type'], 'cart_total'); ?>><?php _e('Cart Total', 'wc-dynamic-progress-bar'); ?></option>
                    <option value="product_count" <?php selected($condition['type'], 'product_count'); ?>><?php _e('Product Count', 'wc-dynamic-progress-bar'); ?></option>
                </select>

                <select name="wc_progress_bar_settings[conditions][<?php echo esc_attr($index); ?>][operator]" class="wc-progress-bar-condition-operator">
                    <option value=">" <?php selected($condition['operator'], '>'); ?>><?php _e('Greater than', 'wc-dynamic-progress-bar'); ?></option>
                    <option value=">=" <?php selected($condition['operator'], '>='); ?>><?php _e('Greater than or equal', 'wc-dynamic-progress-bar'); ?></option>
                    <option value="<" <?php selected($condition['operator'], '<'); ?>><?php _e('Less than', 'wc-dynamic-progress-bar'); ?></option>
                    <option value="<=" <?php selected($condition['operator'], '<='); ?>><?php _e('Less than or equal', 'wc-dynamic-progress-bar'); ?></option>
                    <option value="==" <?php selected($condition['operator'], '=='); ?>><?php _e('Equal to', 'wc-dynamic-progress-bar'); ?></option>
                </select>

                <input type="number" name="wc_progress_bar_settings[conditions][<?php echo esc_attr($index); ?>][value]" min="0" step="0.01" value="<?php echo esc_attr($condition['value']); ?>" placeholder="<?php echo $condition['type'] === 'cart_total' ? esc_attr(wc_get_price_decimal_separator() . '00') : '0'; ?>">

                <span><?php _e('then set progress to', 'wc-dynamic-progress-bar'); ?></span>
                <input type="number" name="wc_progress_bar_settings[conditions][<?php echo esc_attr($index); ?>][progress]" min="0" max="100" value="<?php echo esc_attr($condition['progress']); ?>">%

                <input type="hidden" name="wc_progress_bar_settings[conditions][<?php echo esc_attr($index); ?>][priority]" value="<?php echo esc_attr($index); ?>">

                <button type="button" class="wc-progress-bar-remove-condition button-link"><?php _e('Remove', 'wc-dynamic-progress-bar'); ?></button>
            </div>

            <div class="wc-progress-bar-logic-operator">
                <select name="wc_progress_bar_settings[conditions][<?php echo esc_attr($index); ?>][logic]" class="wc-progress-bar-condition-logic">
                    <option value="and" <?php selected($condition['logic'], 'and'); ?>><?php _e('AND', 'wc-dynamic-progress-bar'); ?></option>
                    <option value="or" <?php selected($condition['logic'], 'or'); ?>><?php _e('OR', 'wc-dynamic-progress-bar'); ?></option>
                </select>
                <button type="button" class="wc-progress-bar-add-sub-condition button"><?php _e('Add Sub-Condition', 'wc-dynamic-progress-bar'); ?></button>
            </div>

            <div class="wc-progress-bar-sub-conditions">
                <?php if (!empty($condition['sub_conditions'])) : ?>
                    <?php foreach ($condition['sub_conditions'] as $sub_index => $sub_condition) : ?>
                        <div class="wc-progress-bar-sub-condition">
                            <?php $this->render_sub_condition_fields($index, $sub_index, $sub_condition); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="wc-progress-bar-condition-text">
                <label><?php _e('Display Text:', 'wc-dynamic-progress-bar'); ?></label>
                <input type="text" name="wc_progress_bar_settings[conditions][<?php echo esc_attr($index); ?>][text]" value="<?php echo esc_attr($condition['text']); ?>" class="regular-text">
                <p class="description">
                    <?php _e('Available placeholders:', 'wc-dynamic-progress-bar'); ?>
                    <code>{cart_total}</code>, <code>{product_count}</code>, <code>{progress_percentage}</code>, <code>{remaining_amount}</code>
                </p>
            </div>
        </div>
        <?php
    }

    private function render_sub_condition_fields($parent_index, $sub_index, $sub_condition = array()) {
        $sub_condition = wp_parse_args($sub_condition, array(
            'type' => 'cart_total',
            'operator' => '>=',
            'value' => ''
        ));
        ?>
        <div class="wc-progress-bar-sub-condition-fields">
            <select name="wc_progress_bar_settings[conditions][<?php echo esc_attr($parent_index); ?>][sub_conditions][<?php echo esc_attr($sub_index); ?>][type]" class="wc-progress-bar-condition-type">
                <option value="cart_total" <?php selected($sub_condition['type'], 'cart_total'); ?>><?php _e('Cart Total', 'wc-dynamic-progress-bar'); ?></option>
                <option value="product_count" <?php selected($sub_condition['type'], 'product_count'); ?>><?php _e('Product Count', 'wc-dynamic-progress-bar'); ?></option>
            </select>

            <select name="wc_progress_bar_settings[conditions][<?php echo esc_attr($parent_index); ?>][sub_conditions][<?php echo esc_attr($sub_index); ?>][operator]" class="wc-progress-bar-condition-operator">
                <option value=">" <?php selected($sub_condition['operator'], '>'); ?>><?php _e('Greater than', 'wc-dynamic-progress-bar'); ?></option>
                <option value=">=" <?php selected($sub_condition['operator'], '>='); ?>><?php _e('Greater than or equal', 'wc-dynamic-progress-bar'); ?></option>
                <option value="<" <?php selected($sub_condition['operator'], '<'); ?>><?php _e('Less than', 'wc-dynamic-progress-bar'); ?></option>
                <option value="<=" <?php selected($sub_condition['operator'], '<='); ?>><?php _e('Less than or equal', 'wc-dynamic-progress-bar'); ?></option>
                <option value="==" <?php selected($sub_condition['operator'], '=='); ?>><?php _e('Equal to', 'wc-dynamic-progress-bar'); ?></option>
            </select>

            <input type="number" name="wc_progress_bar_settings[conditions][<?php echo esc_attr($parent_index); ?>][sub_conditions][<?php echo esc_attr($sub_index); ?>][value]" min="0" step="0.01" value="<?php echo esc_attr($sub_condition['value']); ?>" placeholder="<?php echo $sub_condition['type'] === 'cart_total' ? esc_attr(wc_get_price_decimal_separator() . '00') : '0'; ?>">

            <button type="button" class="wc-progress-bar-remove-sub-condition button-link"><?php _e('Remove', 'wc-dynamic-progress-bar'); ?></button>
        </div>
        <?php
    }

    public function render_text_style_section() {
        echo '<p>' . __('Customize the appearance of the text above the progress bar.', 'wc-dynamic-progress-bar') . '</p>';
    }

    public function render_text_font_size_field() {
        $settings = get_option('wc_progress_bar_settings');
        $value = isset($settings['text_style']['font_size']) ? $settings['text_style']['font_size'] : '16px';
        ?>
        <input type="text" id="text_font_size" name="wc_progress_bar_settings[text_style][font_size]" value="<?php echo esc_attr($value); ?>" class="small-text">
        <p class="description"><?php _e('Font size (e.g., 16px, 1em)', 'wc-dynamic-progress-bar'); ?></p>
        <?php
    }

    public function render_text_font_weight_field() {
        $settings = get_option('wc_progress_bar_settings');
        $value = isset($settings['text_style']['font_weight']) ? $settings['text_style']['font_weight'] : 'normal';
        ?>
        <select id="text_font_weight" name="wc_progress_bar_settings[text_style][font_weight]">
            <option value="normal" <?php selected($value, 'normal'); ?>><?php _e('Normal', 'wc-dynamic-progress-bar'); ?></option>
            <option value="bold" <?php selected($value, 'bold'); ?>><?php _e('Bold', 'wc-dynamic-progress-bar'); ?></option>
            <option value="bolder" <?php selected($value, 'bolder'); ?>><?php _e('Bolder', 'wc-dynamic-progress-bar'); ?></option>
            <option value="lighter" <?php selected($value, 'lighter'); ?>><?php _e('Lighter', 'wc-dynamic-progress-bar'); ?></option>
            <option value="100" <?php selected($value, '100'); ?>>100</option>
            <option value="200" <?php selected($value, '200'); ?>>200</option>
            <option value="300" <?php selected($value, '300'); ?>>300</option>
            <option value="400" <?php selected($value, '400'); ?>>400</option>
            <option value="500" <?php selected($value, '500'); ?>>500</option>
            <option value="600" <?php selected($value, '600'); ?>>600</option>
            <option value="700" <?php selected($value, '700'); ?>>700</option>
            <option value="800" <?php selected($value, '800'); ?>>800</option>
            <option value="900" <?php selected($value, '900'); ?>>900</option>
        </select>
        <?php
    }

    public function render_text_color_field() {
        $settings = get_option('wc_progress_bar_settings');
        $value = isset($settings['text_style']['color']) ? $settings['text_style']['color'] : '#333333';
        ?>
        <input type="text" id="text_color" name="wc_progress_bar_settings[text_style][color]" value="<?php echo esc_attr($value); ?>" class="color-picker">
        <?php
    }

    public function render_bar_style_section() {
        echo '<p>' . __('Customize the appearance of the progress bar itself.', 'wc-dynamic-progress-bar') . '</p>';
    }

    public function render_bar_height_field() {
        $settings = get_option('wc_progress_bar_settings');
        $value = isset($settings['bar_style']['height']) ? $settings['bar_style']['height'] : '20px';
        ?>
        <input type="text" id="bar_height" name="wc_progress_bar_settings[bar_style][height]" value="<?php echo esc_attr($value); ?>" class="small-text">
        <p class="description"><?php _e('Height of the progress bar (e.g., 20px, 1em)', 'wc-dynamic-progress-bar'); ?></p>
        <?php
    }

    public function render_bar_width_field() {
        $settings = get_option('wc_progress_bar_settings');
        $value = isset($settings['bar_style']['width']) ? $settings['bar_style']['width'] : '100%';
        ?>
        <input type="text" id="bar_width" name="wc_progress_bar_settings[bar_style][width]" value="<?php echo esc_attr($value); ?>" class="small-text">
        <p class="description"><?php _e('Width of the progress bar (e.g., 100%, 300px)', 'wc-dynamic-progress-bar'); ?></p>
        <?php
    }

    public function render_bar_background_color_field() {
        $settings = get_option('wc_progress_bar_settings');
        $value = isset($settings['bar_style']['background_color']) ? $settings['bar_style']['background_color'] : '#f5f5f5';
        ?>
        <input type="text" id="bar_background_color" name="wc_progress_bar_settings[bar_style][background_color]" value="<?php echo esc_attr($value); ?>" class="color-picker">
        <?php
    }

    public function render_bar_fill_color_field() {
        $settings = get_option('wc_progress_bar_settings');
        $value = isset($settings['bar_style']['fill_color']) ? $settings['bar_style']['fill_color'] : '#4CAF50';
        ?>
        <input type="text" id="bar_fill_color" name="wc_progress_bar_settings[bar_style][fill_color]" value="<?php echo esc_attr($value); ?>" class="color-picker">
        <?php
    }

    public function render_bar_border_color_field() {
        $settings = get_option('wc_progress_bar_settings');
        $value = isset($settings['bar_style']['border_color']) ? $settings['bar_style']['border_color'] : '#dddddd';
        ?>
        <input type="text" id="bar_border_color" name="wc_progress_bar_settings[bar_style][border_color]" value="<?php echo esc_attr($value); ?>" class="color-picker">
        <?php
    }

    public function render_bar_border_width_field() {
        $settings = get_option('wc_progress_bar_settings');
        $value = isset($settings['bar_style']['border_width']) ? $settings['bar_style']['border_width'] : '1px';
        ?>
        <input type="text" id="bar_border_width" name="wc_progress_bar_settings[bar_style][border_width]" value="<?php echo esc_attr($value); ?>" class="small-text">
        <p class="description"><?php _e('Border width (e.g., 1px, 0.2em)', 'wc-dynamic-progress-bar'); ?></p>
        <?php
    }

    public function render_bar_border_radius_field() {
        $settings = get_option('wc_progress_bar_settings');
        $value = isset($settings['bar_style']['border_radius']) ? $settings['bar_style']['border_radius'] : '4px';
        ?>
        <input type="text" id="bar_border_radius" name="wc_progress_bar_settings[bar_style][border_radius]" value="<?php echo esc_attr($value); ?>" class="small-text">
        <p class="description"><?php _e('Border radius for rounded corners (e.g., 4px, 50%)', 'wc-dynamic-progress-bar'); ?></p>
        <?php
    }

    public function render_bar_transition_field() {
        $settings = get_option('wc_progress_bar_settings');
        $value = isset($settings['bar_style']['transition']) ? $settings['bar_style']['transition'] : 'yes';
        ?>
        <label>
            <input type="checkbox" id="bar_transition" name="wc_progress_bar_settings[bar_style][transition]" value="yes" <?php checked($value, 'yes'); ?>>
            <?php _e('Enable smooth transition animation', 'wc-dynamic-progress-bar'); ?>
        </label>
        <?php
    }

    /**
     * Clears cached progress-bar render results.
     */
    public function flush_render_cache() {
        if (function_exists('wp_cache_flush_group')) {
            wp_cache_flush_group('wc_progress_bar');
        }
    }

    public function sanitize_settings($input) {
        $output = array();

        // Sanitize default text
        if (isset($input['default_text'])) {
            $output['default_text'] = wp_kses_post($input['default_text']);
        }

        // Sanitize default progress
        if (isset($input['default_progress'])) {
            $output['default_progress'] = absint($input['default_progress']);
            if ($output['default_progress'] > 100) {
                $output['default_progress'] = 100;
            }
        }

        // Sanitize conditions
        if (isset($input['conditions']) && is_array($input['conditions'])) {
            foreach ($input['conditions'] as $index => $condition) {
                $sanitized_condition = array();

                $sanitized_condition['type'] = in_array($condition['type'], array('cart_total', 'product_count')) ? $condition['type'] : 'cart_total';
                $sanitized_condition['operator'] = in_array($condition['operator'], array('>', '>=', '<', '<=', '==')) ? $condition['operator'] : '>=';
                $sanitized_condition['value'] = is_numeric($condition['value']) ? floatval($condition['value']) : 0;
                $sanitized_condition['progress'] = absint($condition['progress']);
                if ($sanitized_condition['progress'] > 100) {
                    $sanitized_condition['progress'] = 100;
                }
                $sanitized_condition['text'] = wp_kses_post($condition['text']);
                $sanitized_condition['priority'] = absint($condition['priority']);
                $sanitized_condition['logic'] = in_array($condition['logic'], array('and', 'or')) ? $condition['logic'] : 'and';

                // Sanitize sub-conditions
                $sanitized_condition['sub_conditions'] = array();
                if (!empty($condition['sub_conditions']) && is_array($condition['sub_conditions'])) {
                    foreach ($condition['sub_conditions'] as $sub_index => $sub_condition) {
                        $sanitized_sub_condition = array(
                            'type' => in_array($sub_condition['type'], array('cart_total', 'product_count')) ? $sub_condition['type'] : 'cart_total',
                            'operator' => in_array($sub_condition['operator'], array('>', '>=', '<', '<=', '==')) ? $sub_condition['operator'] : '>=',
                            'value' => is_numeric($sub_condition['value']) ? floatval($sub_condition['value']) : 0
                        );
                        $sanitized_condition['sub_conditions'][] = $sanitized_sub_condition;
                    }
                }

                $output['conditions'][] = $sanitized_condition;
            }

            // Sort conditions by priority
            if (!empty($output['conditions'])) {
                usort($output['conditions'], function($a, $b) {
                    return $a['priority'] <=> $b['priority'];
                });
            }
        }

        // Sanitize text style
        $output['text_style'] = array();
        if (isset($input['text_style']['font_size'])) {
            $output['text_style']['font_size'] = sanitize_text_field($input['text_style']['font_size']);
        }
        if (isset($input['text_style']['font_weight'])) {
            $output['text_style']['font_weight'] = sanitize_text_field($input['text_style']['font_weight']);
        }
        if (isset($input['text_style']['color'])) {
            $output['text_style']['color'] = sanitize_hex_color($input['text_style']['color']);
        }

        // Sanitize bar style
        $output['bar_style'] = array();
        if (isset($input['bar_style']['height'])) {
            $output['bar_style']['height'] = sanitize_text_field($input['bar_style']['height']);
        }
        if (isset($input['bar_style']['width'])) {
            $output['bar_style']['width'] = sanitize_text_field($input['bar_style']['width']);
        }
        if (isset($input['bar_style']['background_color'])) {
            $output['bar_style']['background_color'] = sanitize_hex_color($input['bar_style']['background_color']);
        }
        if (isset($input['bar_style']['fill_color'])) {
            $output['bar_style']['fill_color'] = sanitize_hex_color($input['bar_style']['fill_color']);
        }
        if (isset($input['bar_style']['border_color'])) {
            $output['bar_style']['border_color'] = sanitize_hex_color($input['bar_style']['border_color']);
        }
        if (isset($input['bar_style']['border_width'])) {
            $output['bar_style']['border_width'] = sanitize_text_field($input['bar_style']['border_width']);
        }
        if (isset($input['bar_style']['border_radius'])) {
            $output['bar_style']['border_radius'] = sanitize_text_field($input['bar_style']['border_radius']);
        }
        if (isset($input['bar_style']['transition'])) {
            $output['bar_style']['transition'] = $input['bar_style']['transition'] === 'yes' ? 'yes' : 'no';
        }

        return $output;
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'woocommerce_page_wc-progress-bar-settings') {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style(
            'wc-progress-bar-admin',
            WC_PROGRESS_BAR_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WC_PROGRESS_BAR_VERSION
        );

        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script(
            'wc-progress-bar-admin',
            WC_PROGRESS_BAR_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker', 'jquery-ui-sortable'),
            WC_PROGRESS_BAR_VERSION,
            true
        );

        wp_localize_script('wc-progress-bar-admin', 'wcProgressBarAdmin', array(
            'i18n' => array(
                'cartTotal' => __('Cart Total', 'wc-dynamic-progress-bar'),
                'productCount' => __('Product Count', 'wc-dynamic-progress-bar'),
                'greaterThan' => __('Greater than', 'wc-dynamic-progress-bar'),
                'greaterThanOrEqual' => __('Greater than or equal', 'wc-dynamic-progress-bar'),
                'lessThan' => __('Less than', 'wc-dynamic-progress-bar'),
                'lessThanOrEqual' => __('Less than or equal', 'wc-dynamic-progress-bar'),
                'equalTo' => __('Equal to', 'wc-dynamic-progress-bar'),
                'thenSetProgressTo' => __('then set progress to', 'wc-dynamic-progress-bar'),
                'remove' => __('Remove', 'wc-dynamic-progress-bar'),
                'displayText' => __('Display Text:', 'wc-dynamic-progress-bar'),
                'conditionPlaceholders' => __('Available placeholders: {cart_total}, {product_count}, {progress_percentage}, {remaining_amount}', 'wc-dynamic-progress-bar'),
                'and' => __('AND', 'wc-dynamic-progress-bar'),
                'or' => __('OR', 'wc-dynamic-progress-bar'),
                'addSubCondition' => __('Add Sub-Condition', 'wc-dynamic-progress-bar')
            ),
        ));
    }
}