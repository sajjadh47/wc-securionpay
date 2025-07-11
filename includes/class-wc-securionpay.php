<?php
/**
 * This file contains the definition of the Wc_Securionpay class, which
 * is used to begin the plugin's functionality.
 *
 * @package       Wc_Securionpay
 * @subpackage    Wc_Securionpay/includes
 * @author        Sajjad Hossain Sagor <sagorh672@gmail.com>
 */

/**
 * The core plugin class.
 *
 * This is used to define admin-specific hooks and public-facing hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since    2.0.0
 */
class Wc_Securionpay {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since     2.0.0
	 * @access    protected
	 * @var       Wc_Securionpay_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since     2.0.0
	 * @access    protected
	 * @var       string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since     2.0.0
	 * @access    protected
	 * @var       string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since     2.0.0
	 * @access    public
	 */
	public function __construct() {
		$this->version     = defined( 'WC_SECURIONPAY_PLUGIN_VERSION' ) ? WC_SECURIONPAY_PLUGIN_VERSION : '1.0.0';
		$this->plugin_name = 'wc-securionpay';

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wc_Securionpay_Loader. Orchestrates the hooks of the plugin.
	 * - Wc_Securionpay_Admin.  Defines all hooks for the admin area.
	 * - Wc_Securionpay_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since     2.0.0
	 * @access    private
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once WC_SECURIONPAY_PLUGIN_PATH . 'includes/class-wc-securionpay-loader.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once WC_SECURIONPAY_PLUGIN_PATH . 'admin/class-wc-securionpay-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once WC_SECURIONPAY_PLUGIN_PATH . 'public/class-wc-securionpay-public.php';

		$this->loader = new Wc_Securionpay_Loader();
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since     2.0.0
	 * @access    private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Wc_Securionpay_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'plugin_action_links_' . WC_SECURIONPAY_PLUGIN_BASENAME, $plugin_admin, 'add_plugin_action_links' );

		$this->loader->add_action( 'admin_notices', $plugin_admin, 'admin_notices' );

		$this->loader->add_action( 'before_woocommerce_init', $plugin_admin, 'declare_compatibility_with_wc_custom_order_tables' );

		$this->loader->add_filter( 'woocommerce_payment_gateways', $plugin_admin, 'add_payment_gateway' );

		$this->loader->add_filter( 'plugins_loaded', $plugin_admin, 'gateway_init', 11 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since     2.0.0
	 * @access    private
	 */
	private function define_public_hooks() {
		$plugin_public = new Wc_Securionpay_Public( $this->get_plugin_name(), $this->get_version() );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since     2.0.0
	 * @access    public
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of WordPress.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @return    string The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @return    Wc_Securionpay_Loader Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @return    string The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Determines the card type based on the card number.
	 *
	 * This function uses regular expressions to identify the card type
	 * (Visa, American Express, MasterCard, Discover, JCB, Diners Club)
	 * based on the provided card number.
	 *
	 * @since     2.0.0
	 * @static
	 * @access    public
	 * @param     string $number The card number to identify.
	 * @return    string|null    The card type, or null if the type cannot be determined.
	 */
	public static function get_card_type( $number ) {
		switch ( true ) {
			case preg_match( '/^4\d{12}(\d{3})?(\d{3})?$/', $number ):
				return 'Visa';
			case preg_match( '/^3[47]\d{13}$/', $number ):
				return 'American Express';
			case preg_match( '/^(5[1-5]\d{4}|677189|222[1-9]\d{2}|22[3-9]\d{3}|2[3-6]\d{4}|27[01]\d{3}|2720\d{2})\d{10}$/', $number ):
				return 'MasterCard';
			case preg_match( '/^(6011|65\d{2}|64[4-9]\d)\d{12}|(62\d{14})$/', $number ):
				return 'Discover';
			case preg_match( '/^35(28|29|[3-8]\d)\d{12}$/', $number ):
				return 'JCB';
			case preg_match( '/^3(0[0-5]|[68]\d)\d{11}$/', $number ):
				return 'Diners Club';
			default:
				return null; // Or you might want to return a default value like 'Unknown'.
		}
	}

	/**
	 * Helper function to convert amount to minor unit
	 *
	 * Charge amount in minor units of given currency.
	 * For example 10€ is represented as "1000" and 10¥ is represented as "10".
	 *
	 * @since     2.0.0
	 * @static
	 * @access    public
	 * @param     float|int $amount   The amount to convert.
	 * @param     string    $currency The currency of the amount.
	 * @return    float|int $amount   Modified converted amount.
	 */
	public static function get_amount( $amount, $currency ) {
		$currency = strtoupper( $currency );

		// Currencies that do not require conversion.
		// if it's Chinese yuan (¥) or japanese yen then no amount conversion.
		$no_conversion_currencies = array(
			'JPY',
			'BIF',
			'CLP',
			'DJF',
			'GNF',
			'ISK',
			'KMF',
			'KRW',
			'PYG',
			'RWF',
			'UGX',
			'UYI',
			'XAF',
		);

		if ( in_array( $currency, $no_conversion_currencies, true ) ) {
			return $amount;
		}

		return $amount * 100;
	}
}
