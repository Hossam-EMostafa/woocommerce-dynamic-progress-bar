<?php
/**
 * Admin Settings Page for WooCommerce Dynamic Progress Bar
 *
 * This file handles the display and functionality of the plugin's settings page in the WordPress admin.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Progress_Bar_Admin_Page {

    /**
     * Constructor
     */
    public function __construct() {
        // Hook into admin menu to add our settings page
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Register our settings
        add_action('admin_init', array($this, 'setup_sections'));
        add_action('admin_init', array($this, 'setup_fields'));

        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Dynamic Progress Bar Settings', 'wc-dynamic-progress-bar'),
            __('Progress Bar', 'wc-dynamic-progress-bar'),
            'manage_options',
            'wc-progress-bar-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render the settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('wc_progress_bar_settings');
                do_settings_sections('wc-progress-bar-settings');
                submit_button();
                ?>
            </form>

            <div class="wc-progress-bar-preview-section">
                <h2><?php esc_html_e('Preview', 'wc-dynamic-progress-bar'); ?></h2>
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

    /**
     * Setup settings sections
     */
    public function setup_sections() {
        add_settings_section(
            'wc_progress_bar_general_section',
            __('General Settings', 'wc-dynamic-progress-bar'),
            array($this, 'section_callback'),
            'wc-progress-bar-settings'
        );

        add_settings_section(
            'wc_progress_bar_conditions_section',
            __('Progress Conditions', 'wc-dynamic-progress-bar'),
            array($this, 'conditions_section_callback'),
            'wc-progress-bar-settings'
        );

        add_settings_section(
            'wc_progress_bar_text_style_section',
            __('Text Styling', 'wc-dynamic-progress-bar'),
            array($this, 'section_callback'),
            'wc-progress-bar-settings'
        );

        add_settings_section(
            'wc_progress_bar_style_section',
            __('Progress Bar Styling', 'wc-dynamic-progress-bar'),
            array($this, 'section_callback'),
            'wc-progress-bar-settings'
        );
    }

    /**
     * Section callback
     */
    public function section_callback($arguments) {
        switch ($arguments['id']) {
            case 'wc_progress_bar_general_section':
                echo '<p>' . esc_html__('Configure the default behavior of the progress bar.', 'wc-dynamic-progress-bar') . '</p>';
                break;
            case 'wc_progress_bar_text_style_section':
                echo '<p>' . esc_html__('Customize the appearance of the text above the progress bar.', 'wc-dynamic-progress-bar') . '</p>';
                break;
            case 'wc_progress_bar_style_section':
                echo '<p>' . esc_html__('Customize the appearance of the progress bar itself.', 'wc-dynamic-progress-bar') . '</p>';
                break;
        }
    }

    /**
     * Conditions section callback
     */
    public function conditions_section_callback() {
        echo '<p>' . esc_html__('Set conditions that determine the progress bar state based on cart total or product count.', 'wc-dynamic-progress-bar') . '</p>';
        echo '<p>' . esc_html__('Conditions are evaluated in order from top to bottom. The first matching condition will be used.', 'wc-dynamic-progress-bar') . '</p>';
    }

    /**
     * Setup settings fields
     */
    public function setup_fields() {
        $fields = array(
            // General Settings
            array(
                'uid' => 'wc_progress_bar_default_text',
                'label' => __('Default Text', 'wc-dynamic-progress-bar'),
                'section' => 'wc_progress_bar_general_section',
                'type' => 'textarea',
                'options' => false,
                'placeholder' => __('Add {remaining_amount} to get free shipping!', 'wc-dynamic-progress-bar'),
                'helper' => __('Default text to display when no conditions are met. Available placeholders: {cart_total}, {product_count}, {progress_percentage}, {remaining_amount}', 'wc-dynamic-progress-bar'),
                'supplemental' => '',
                'default' => __('Add {remaining_amount} to get free shipping!', 'wc-dynamic-progress-bar')
            ),
            array(
                'uid' => 'wc_progress_bar_default_progress',
                'label' => __('Default Progress (%)', 'wc-dynamic-progress-bar'),
                'section' => 'wc_progress_bar_general_section',
                'type' => 'number',
                'options' => false,
                'placeholder' => '0',
                'helper' => '',
                'supplemental' => __('Default progress percentage (0-100) when no conditions are met.', 'wc-dynamic-progress-bar'),
                'default' => '0',
                'attributes' => array(
                    'min' => '0',
                    'max' => '100',
                    'step' => '1'
                )
            ),

            // Conditions (handled separately)

            // Text Styling
            array(
                'uid' => 'wc_progress_bar_text_font_size',
                'label' => __('Font Size', 'wc-dynamic-progress-bar'),
                'section' => 'wc_progress_bar_text_style_section',
                'type' => 'text',
                'options' => false,
                'placeholder' => '16px',
                'helper' => '',
                'supplemental' => __('Font size (e.g., 16px, 1em)', 'wc-dynamic-progress-bar'),
                'default' => '16px'
            ),
            array(
                'uid' => 'wc_progress_bar_text_font_weight',
                'label' => __('Font Weight', 'wc-dynamic-progress-bar'),
                'section' => 'wc_progress_bar_text_style_section',
                'type' => 'select',
                'options' => array(
                    'normal' => __('Normal', 'wc-dynamic-progress-bar'),
                    'bold' => __('Bold', 'wc-dynamic-progress-bar'),
                    'bolder' => __('Bolder', 'wc-dynamic-progress-bar'),
                    'lighter' => __('Lighter', 'wc-dynamic-progress-bar'),
                    '100' => '100',
                    '200' => '200',
                    '300' => '300',
                    '400' => '400',
                    '500' => '500',
                    '600' => '600',
                    '700' => '700',
                    '800' => '800',
                    '900' => '900'
                ),
                'default' => array('normal'),
                'helper' => '',
                'supplemental' => ''
            ),
            array(
                'uid' => 'wc_progress_bar_text_color',
                'label' => __('Text Color', 'wc-dynamic-progress-bar'),
                'section' => 'wc_progress_bar_text_style_section',
                'type' => 'color',
                'options' => false,
                'placeholder' => '',
                'helper' => '',
                'supplemental' => '',
                'default' => '#333333'
            ),

            // Progress Bar Styling
            array(
                'uid' => 'wc_progress_bar_height',
                'label' => __('Height', 'wc-dynamic-progress-bar'),
                'section' => 'wc_progress_bar_style_section',
                'type' => 'text',
                'options' => false,
                'placeholder' => '20px',
                'helper' => '',
                'supplemental' => __('Height of the progress bar (e.g., 20px, 1em)', 'wc-dynamic-progress-bar'),
                'default' => '20px'
            ),
            array(
                'uid' => 'wc_progress_bar_width',
                'label' => __('Width', 'wc-dynamic-progress-bar'),
                'section' => 'wc_progress_bar_style_section',
                'type' => 'text',
                'options' => false,
                'placeholder' => '100%',
                'helper' => '',
                'supplemental' => __('Width of the progress bar (e.g., 100%, 300px)', 'wc-dynamic-progress-bar'),
                'default' => '100%'
            ),
            array(
                'uid' => 'wc_progress_bar_background_color',
                'label' => __('Background Color', 'wc-dynamic-progress-bar'),
                'section' => 'wc_progress_bar_style_section',
                'type' => 'color',
                'options' => false,
                'placeholder' => '',
                'helper' => '',
                'supplemental' => '',
                'default' => '#f5f5f5'
            ),
            array(
                'uid' => 'wc_progress_bar_fill_color',
                'label' => __('Fill Color', 'wc-dynamic-progress-bar'),
                'section' => 'wc_progress_bar_style_section',
                'type' => 'color',
                'options' => false,
                'placeholder' => '',
                'helper' => '',
                'supplemental' => '',
                'default' => '#4CAF50'
            ),
            array(
                'uid' => 'wc_progress_bar_border_color',
                'label' => __('Border Color', 'wc-dynamic-progress-bar'),
                'section' => 'wc_progress_bar_style_section',
                'type' => 'color',
                'options' => false,
                'placeholder' => '',
                'helper' => '',
                'supplemental' => '',
                'default' => '#dddddd'
            ),
            array(
                'uid' => 'wc_progress_bar_border_width',
                'label' => __('Border Width', 'wc-dynamic-progress-bar'),
                'section' => 'wc_progress_bar_style_section',
                'type' => 'text',
                'options' => false,
                'placeholder' => '1px',
                'helper' => '',
                'supplemental' => __('Border width (e.g., 1px, 0.2em)', 'wc-dynamic-progress-bar'),
                'default' => '1px'
            ),
            array(
                'uid' => 'wc_progress_bar_border_radius',
                'label' => __('Border Radius', 'wc-dynamic-progress-bar'),
                'section' => 'wc_progress_bar_style_section',
                'type' => 'text',
                'options' => false,
                'placeholder' => '4px',
                'helper' => '',
                'supplemental' => __('Border radius for rounded corners (e.g., 4px, 50%)', 'wc-dynamic-progress-bar'),
                'default' => '4px'
            ),
            array(
                'uid' => 'wc_progress_bar_transition',
                'label' => __('Smooth Transition', 'wc-dynamic-progress-bar'),
                'section' => 'wc_progress_bar_style_section',
                'type' => 'checkbox',
                'options' => false,
                'placeholder' => '',
                'helper' => '',
                'supplemental' => __('Enable smooth transition animation when progress changes', 'wc-dynamic-progress-bar'),
                'default' => '1'
            )
        );

        foreach ($fields as $field) {
            add_settings_field(
                $field['uid'],
                $field['label'],
                array($this, 'field_callback'),
                'wc-progress-bar-settings',
                $field['section'],
                $field
            );

            register_setting('wc_progress_bar_settings', $field['uid']);
        }

        // Special handling for conditions
        add_settings_field(
            'wc_progress_bar_conditions',
            __('Conditions', 'wc-dynamic-progress-bar'),
            array($this, 'conditions_field_callback'),
            'wc-progress-bar-settings',
            'wc_progress_bar_conditions_section'
        );

        register_setting('wc_progress_bar_settings', 'wc_progress_bar_conditions', array(
            'sanitize_callback' => array($this, 'sanitize_conditions')
        ));
    }

    /**
     * Field callback
     */
    public function field_callback($arguments) {
        $value = get_option($arguments['uid']);

        if (!$value && isset($arguments['default'])) {
            $value = $arguments['default'];
        }

        switch ($arguments['type']) {
            case 'text':
            case 'password':
            case 'number':
                printf(
                    '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" class="regular-text" %5$s />',
                    esc_attr($arguments['uid']),
                    esc_attr($arguments['type']),
                    esc_attr($arguments['placeholder']),
                    esc_attr($value),
                    isset($arguments['attributes']) ? $this->get_attributes($arguments['attributes']) : ''
                );
                break;
            case 'textarea':
                printf(
                    '<textarea name="%1$s" id="%1$s" placeholder="%2$s" rows="5" cols="50">%3$s</textarea>',
                    esc_attr($arguments['uid']),
                    esc_attr($arguments['placeholder']),
                    esc_textarea($value)
                );
                break;
            case 'select':
            case 'multiselect':
                if (!empty($arguments['options']) && is_array($arguments['options'])) {
                    $attributes = '';
                    $options_markup = '';

                    foreach ($arguments['options'] as $key => $label) {
                        $options_markup .= sprintf(
                            '<option value="%s" %s>%s</option>',
                            esc_attr($key),
                            selected($value, $key, false),
                            esc_html($label)
                        );
                    }

                    printf(
                        '<select name="%1$s" id="%1$s" %2$s>%3$s</select>',
                        esc_attr($arguments['uid']),
                        $attributes,
                        $options_markup
                    );
                }
                break;
            case 'radio':
            case 'checkbox':
                if (!empty($arguments['options']) && is_array($arguments['options'])) {
                    $options_markup = '';
                    $iterator = 0;

                    foreach ($arguments['options'] as $key => $label) {
                        $iterator++;
                        $options_markup .= sprintf(
                            '<label for="%1$s_%6$s"><input id="%1$s_%6$s" name="%1$s[]" type="%2$s" value="%3$s" %4$s /> %5$s</label><br/>',
                            esc_attr($arguments['uid']),
                            esc_attr($arguments['type']),
                            esc_attr($key),
                            checked($value[array_search($key, $value, true)], $key, false),
                            esc_html($label),
                            esc_attr($iterator)
                        );
                    }

                    printf('<fieldset>%s</fieldset>', $options_markup);
                }
                break;
            case 'color':
                printf(
                    '<input name="%1$s" id="%1$s" type="text" value="%2$s" class="color-picker" />',
                    esc_attr($arguments['uid']),
                    esc_attr($value)
                );
                break;
        }

        if ($helper = $arguments['helper']) {
            printf('<span class="helper"> %s</span>', esc_html($helper));
        }

        if ($supplemental = $arguments['supplemental']) {
            printf('<p class="description">%s</p>', esc_html($supplemental));
        }
    }

    /**
     * Conditions field callback
     */
    public function conditions_field_callback() {
        $conditions = get_option('wc_progress_bar_conditions', array());
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
            <button type="button" id="wc-progress-bar-add-condition" class="button"><?php esc_html_e('Add Condition', 'wc-dynamic-progress-bar'); ?></button>
        </div>
        <?php
    }

    /**
     * Render condition fields
     */
    private function render_condition_fields($index, $condition = array()) {
    $condition = wp_parse_args($condition, array(
        'type' => 'cart_total',
        'operator' => '>=',
        'value' => '',
        'progress' => 50,
        'text' => '',
        'priority' => $index,
        'logic' => 'and', // New field for logical operator
        'sub_conditions' => array() // New field for sub-conditions
    ));
    ?>
    <div class="wc-progress-bar-condition-fields">
        <div class="wc-progress-bar-main-condition">
            <select name="wc_progress_bar_conditions[<?php echo esc_attr($index); ?>][type]" class="wc-progress-bar-condition-type">
                <option value="cart_total" <?php selected($condition['type'], 'cart_total'); ?>><?php esc_html_e('Cart Total', 'wc-dynamic-progress-bar'); ?></option>
                <option value="product_count" <?php selected($condition['type'], 'product_count'); ?>><?php esc_html_e('Product Count', 'wc-dynamic-progress-bar'); ?></option>
            </select>

            <select name="wc_progress_bar_conditions[<?php echo esc_attr($index); ?>][operator]" class="wc-progress-bar-condition-operator">
                <option value=">" <?php selected($condition['operator'], '>'); ?>><?php esc_html_e('Greater than', 'wc-dynamic-progress-bar'); ?></option>
                <option value=">=" <?php selected($condition['operator'], '>='); ?>><?php esc_html_e('Greater than or equal', 'wc-dynamic-progress-bar'); ?></option>
                <option value="<" <?php selected($condition['operator'], '<'); ?>><?php esc_html_e('Less than', 'wc-dynamic-progress-bar'); ?></option>
                <option value="<=" <?php selected($condition['operator'], '<='); ?>><?php esc_html_e('Less than or equal', 'wc-dynamic-progress-bar'); ?></option>
                <option value="==" <?php selected($condition['operator'], '=='); ?>><?php esc_html_e('Equal to', 'wc-dynamic-progress-bar'); ?></option>
            </select>

            <input type="number" name="wc_progress_bar_conditions[<?php echo esc_attr($index); ?>][value]" min="0" step="0.01" value="<?php echo esc_attr($condition['value']); ?>" placeholder="<?php echo $condition['type'] === 'cart_total' ? esc_attr(wc_get_price_decimal_separator() . '00') : '0'; ?>">

            <span><?php esc_html_e('then set progress to', 'wc-dynamic-progress-bar'); ?></span>
            <input type="number" name="wc_progress_bar_conditions[<?php echo esc_attr($index); ?>][progress]" min="0" max="100" value="<?php echo esc_attr($condition['progress']); ?>">%

            <input type="hidden" name="wc_progress_bar_conditions[<?php echo esc_attr($index); ?>][priority]" value="<?php echo esc_attr($index); ?>">

            <button type="button" class="wc-progress-bar-remove-condition button-link"><?php esc_html_e('Remove', 'wc-dynamic-progress-bar'); ?></button>
        </div>

        <!-- Add logical operator selector -->
        <div class="wc-progress-bar-logic-operator">
            <select name="wc_progress_bar_conditions[<?php echo esc_attr($index); ?>][logic]" class="wc-progress-bar-condition-logic">
                <option value="and" <?php selected($condition['logic'], 'and'); ?>><?php esc_html_e('AND', 'wc-dynamic-progress-bar'); ?></option>
                <option value="or" <?php selected($condition['logic'], 'or'); ?>><?php esc_html_e('OR', 'wc-dynamic-progress-bar'); ?></option>
            </select>
            <button type="button" class="wc-progress-bar-add-sub-condition button"><?php esc_html_e('Add Sub-Condition', 'wc-dynamic-progress-bar'); ?></button>
        </div>

        <!-- Sub-conditions container -->
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
            <label><?php esc_html_e('Display Text:', 'wc-dynamic-progress-bar'); ?></label>
            <input type="text" name="wc_progress_bar_conditions[<?php echo esc_attr($index); ?>][text]" value="<?php echo esc_attr($condition['text']); ?>" class="regular-text">
            <p class="description">
                <?php esc_html_e('Available placeholders:', 'wc-dynamic-progress-bar'); ?>
                <code>{cart_total}</code>, <code>{product_count}</code>, <code>{progress_percentage}</code>, <code>{remaining_amount}</code>
            </p>
        </div>
    </div>
    <?php
}

// New method to render sub-condition fields    private function render_sub_condition_fields($parent_index, $sub_index, $sub_condition = array()) {
    $sub_condition = wp_parse_args($sub_condition, array(
        'type' => 'cart_total',
        'operator' => '>=',
        'value' => ''
    ));
    ?>
    <div class="wc-progress-bar-sub-condition-fields">
        <select name="wc_progress_bar_conditions[<?php echo esc_attr($parent_index); ?>][sub_conditions][<?php echo esc_attr($sub_index); ?>][type]" class="wc-progress-bar-condition-type">
            <option value="cart_total" <?php selected($sub_condition['type'], 'cart_total'); ?>><?php esc_html_e('Cart Total', 'wc-dynamic-progress-bar'); ?></option>
            <option value="product_count" <?php selected($sub_condition['type'], 'product_count'); ?>><?php esc_html_e('Product Count', 'wc-dynamic-progress-bar'); ?></option>
        </select>

        <select name="wc_progress_bar_conditions[<?php echo esc_attr($parent_index); ?>][sub_conditions][<?php echo esc_attr($sub_index); ?>][operator]" class="wc-progress-bar-condition-operator">
            <option value=">" <?php selected($sub_condition['operator'], '>'); ?>><?php esc_html_e('Greater than', 'wc-dynamic-progress-bar'); ?></option>
            <option value=">=" <?php selected($sub_condition['operator'], '>='); ?>><?php esc_html_e('Greater than or equal', 'wc-dynamic-progress-bar'); ?></option>
            <option value="<" <?php selected($sub_condition['operator'], '<'); ?>><?php esc_html_e('Less than', 'wc-dynamic-progress-bar'); ?></option>
            <option value="<=" <?php selected($sub_condition['operator'], '<='); ?>><?php esc_html_e('Less than or equal', 'wc-dynamic-progress-bar'); ?></option>
            <option value="==" <?php selected($sub_condition['operator'], '=='); ?>><?php esc_html_e('Equal to', 'wc-dynamic-progress-bar'); ?></option>
        </select>

        <input type="number" name="wc_progress_bar_conditions[<?php echo esc_attr($parent_index); ?>][sub_conditions][<?php echo esc_attr($sub_index); ?>][value]" min="0" step="0.01" value="<?php echo esc_attr($sub_condition['value']); ?>" placeholder="<?php echo $sub_condition['type'] === 'cart_total' ? esc_attr(wc_get_price_decimal_separator() . '00') : '0'; ?>">

        <button type="button" class="wc-progress-bar-remove-sub-condition button-link"><?php esc_html_e('Remove', 'wc-dynamic-progress-bar'); ?></button>
    </div>
    <?php
}

    /**
     * Sanitize conditions
     */
    public function sanitize_conditions($input) {
    if (!is_array($input)) {
        return array();
    }

    $output = array();

    foreach ($input as $index => $condition) {
        $sanitized_condition = array();

        // Sanitize main condition
        $sanitized_condition['type'] = in_array($condition['type'], array('cart_total', 'product_count')) ? $condition['type'] : 'cart_total';
        $sanitized_condition['operator'] = in_array($condition['operator'], array('>', '>=', '<', '<=', '==')) ? $condition['operator'] : '>=';
        $sanitized_condition['value'] = is_numeric($condition['value']) ? floatval($condition['value']) : 0;
        $sanitized_condition['progress'] = min(absint($condition['progress']), 100);
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

        $output[] = $sanitized_condition;
    }

    // Sort conditions by priority
    usort($output, function($a, $b) {
        return $a['priority'] <=> $b['priority'];
    });

    return $output;
}

    /**
     * Get attributes string
     */
    private function get_attributes($attributes) {
        $attrs = array();

        foreach ($attributes as $attr => $value) {
            $attrs[] = esc_attr($attr) . '="' . esc_attr($value) . '"';
        }

        return implode(' ', $attrs);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'woocommerce_page_wc-progress-bar-settings') {
            return;
        }

        // Color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        // jQuery UI for sortable
        wp_enqueue_script('jquery-ui-sortable');

        // Admin CSS
        wp_enqueue_style(
            'wc-progress-bar-admin',
            WC_PROGRESS_BAR_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WC_PROGRESS_BAR_VERSION
        );

        // Admin JS
        wp_enqueue_script(
            'wc-progress-bar-admin',
            WC_PROGRESS_BAR_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker', 'jquery-ui-sortable'),
            WC_PROGRESS_BAR_VERSION,
            true
        );

        // Localize script
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
            )
        ));
    }
}

// Initialize the admin page
new WC_Progress_Bar_Admin_Page();