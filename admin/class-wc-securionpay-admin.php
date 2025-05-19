<?php
/**
 * This file contains the definition of the Wc_Securionpay_Admin class, which
 * is used to load the plugin's admin-specific functionality.
 *
 * @package       Wc_Securionpay
 * @subpackage    Wc_Securionpay/admin
 * @author        Sajjad Hossain Sagor <sagorh672@gmail.com>
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version and other methods.
 *
 * @since    2.0.0
 */
class Wc_Securionpay_Admin {
	/**
	 * The ID of this plugin.
	 *
	 * @since     2.0.0
	 * @access    private
	 * @var       string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since     2.0.0
	 * @access    private
	 * @var       string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @param     string $plugin_name The name of this plugin.
	 * @param     string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Adds a settings link to the plugin's action links on the plugin list table.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @param     array $links The existing array of plugin action links.
	 * @return    array $links The updated array of plugin action links, including the settings link.
	 */
	public function add_plugin_action_links( $links ) {
		$links[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_securionpay_gateway' ) ), __( 'Settings', 'wc-securionpay' ) );

		return $links;
	}

	/**
	 * Displays admin notices in the admin area.
	 *
	 * This function checks if the required plugin is active.
	 * If not, it displays a warning notice and deactivates the current plugin.
	 *
	 * @since     2.0.0
	 * @access    public
	 */
	public function admin_notices() {
		// Check if required plugin is active.
		if ( ! class_exists( 'WooCommerce', false ) ) {
			sprintf(
				'<div class="notice notice-warning is-dismissible"><p>%s <a href="%s">%s</a> %s</p></div>',
				__( 'Payment Gateway for WooCommerce - SecurionPay requires', 'wc-securionpay' ),
				esc_url( 'https://wordpress.org/plugins/woocommerce/' ),
				__( 'WooCommerce', 'wc-securionpay' ),
				__( 'plugin to be active!', 'wc-securionpay' ),
			);

			// Deactivate the plugin.
			deactivate_plugins( WC_SECURIONPAY_PLUGIN_BASENAME );
		}

		$wc_securionpay_gateway = new WC_Securionpay_Gateway();

		if ( 'no' === $wc_securionpay_gateway->enabled ) {
			return;
		}

		// Show message if gateway mode set to live but no SSL.
		if ( 'yes' === $wc_securionpay_gateway->sandbox && 'no' === get_option( 'woocommerce_force_ssl_checkout' ) && ! class_exists( 'WordPressHTTPS' ) ) {
			sprintf(
				'<div class="notice notice-error is-dismissible"><p>%s <a href="%s">%s</a> %s</p></div>',
				__( 'Securionpay is enabled, but the', 'wc-securionpay' ),
				esc_url( admin_url( 'admin.php?page=wc-settings&tab=advanced' ) ),
				__( 'force SSL option', 'wc-securionpay' ),
				__( 'disabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate.', 'wc-securionpay' ),
			);
		}
	}

	/**
	 * Declares compatibility with WooCommerce's custom order tables feature.
	 *
	 * This function is hooked into the `before_woocommerce_init` action and checks
	 * if the `FeaturesUtil` class exists in the `Automattic\WooCommerce\Utilities`
	 * namespace. If it does, it declares compatibility with the 'custom_order_tables'
	 * feature. This is important for ensuring the plugin works correctly with
	 * WooCommerce versions that support this feature.
	 *
	 * @since     2.0.0
	 * @access    public
	 */
	public function declare_compatibility_with_wc_custom_order_tables() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}

	/**
	 * Adds SecurionPay gateway to the list of available WooCommerce payment gateways.
	 *
	 * This filter allows you to add custom payment gateways to WooCommerce.
	 * This function adds the WC_Securionpay_Gateway to the list.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @param     array $gateways An array of WooCommerce payment gateway class names.
	 * @return    array           An array of WooCommerce payment gateway class names with 'WC_Securionpay_Gateway' added.
	 */
	public function add_payment_gateway( $gateways ) {
		$gateways[] = 'WC_Securionpay_Gateway';

		return $gateways;
	}

	/**
	 * Load the gateway class.
	 *
	 * @since     2.0.0
	 * @access    public
	 */
	public function gateway_init() {
		require_once WC_SECURIONPAY_PLUGIN_PATH . 'includes/class-wc-securionpay-gateway.php';
	}
}
