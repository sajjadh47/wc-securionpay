<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @package           Wc_Securionpay
 * @author            Sajjad Hossain Sagor <sagorh672@gmail.com>
 *
 * Plugin Name:       Payment Gateway for WooCommerce - SecurionPay
 * Plugin URI:        https://wordpress.org/plugins/wc-securionpay/
 * Description:       Integrate SecurionPay payment gateway to your WooCommerce Powered store.
 * Version:           2.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            Sajjad Hossain Sagor
 * Author URI:        https://sajjadhsagor.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wc-securionpay
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'WC_SECURIONPAY_PLUGIN_VERSION', '2.0.0' );

/**
 * Define Plugin Folders Path
 */
define( 'WC_SECURIONPAY_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

define( 'WC_SECURIONPAY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define( 'WC_SECURIONPAY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Define Gateway Name (Used as ID).
define( 'WC_SECURIONPAY_GATEWAY_NAME', 'wc_securionpay_gateway' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wc-securionpay-activator.php
 *
 * @since    2.0.0
 */
function on_activate_wc_securionpay() {
	require_once WC_SECURIONPAY_PLUGIN_PATH . 'includes/class-wc-securionpay-activator.php';

	Wc_Securionpay_Activator::on_activate();
}

register_activation_hook( __FILE__, 'on_activate_wc_securionpay' );

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wc-securionpay-deactivator.php
 *
 * @since    2.0.0
 */
function on_deactivate_wc_securionpay() {
	require_once WC_SECURIONPAY_PLUGIN_PATH . 'includes/class-wc-securionpay-deactivator.php';

	Wc_Securionpay_Deactivator::on_deactivate();
}

register_deactivation_hook( __FILE__, 'on_deactivate_wc_securionpay' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 *
 * @since    2.0.0
 */
require WC_SECURIONPAY_PLUGIN_PATH . 'includes/class-wc-securionpay.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    2.0.0
 */
function run_wc_securionpay() {
	$plugin = new Wc_Securionpay();

	$plugin->run();
}

run_wc_securionpay();
