=== WooCommerce Dynamic Progress Bar ===
Contributors: SoM
Tags: woocommerce, progress bar, cart, shipping, discount
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
WC requires at least: 7.0
WC tested up to: 8.0
Stable tag: 1.1.1
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Adds a dynamic progress bar to WooCommerce that updates based on cart conditions.

== Description ==

The WooCommerce Dynamic Progress Bar plugin adds a customizable progress bar to your store that reacts to changes in the cart. You can set multiple conditions based on cart total or product count, each triggering different progress states and custom text messages.

Key features:
- Real-time updates without page reload
- Multiple customizable conditions with AND/OR logic
- Flexible styling options
- Shortcode integration
- GreenShift theme optimized
- Fully responsive
- Caching for better performance

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/woocommerce-dynamic-progress-bar` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure the plugin settings under WooCommerce > Progress Bar.
4. Add the progress bar to your pages using the [wc_progress_bar] shortcode.

== Frequently Asked Questions ==

= Where can I configure the progress bar? =
Go to WooCommerce > Progress Bar in your WordPress admin to configure all settings.

= How do I add the progress bar to my pages? =
Use the [wc_progress_bar] shortcode. You can add it to any page, post, or widget.

= Can I override the styling with shortcode attributes? =
Yes! You can use attributes like width, height, text_color, etc. to override the default styling.

== Screenshots ==
1. Progress bar on the cart page
2. Admin settings page
3. Condition configuration

== Changelog ==

= 1.1.0 =
* Added caching for better performance
* Improved security with nonce verification
* Added AND/OR logic for conditions
* Fixed duplicate settings implementation
* Optimized asset loading

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.1.0 =
This version includes significant improvements to performance and security. It's recommended for all users.