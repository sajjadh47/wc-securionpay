<?php
/**
 * This file contains the definition of the Wc_Securionpay_I18n class, which
 * is used to load the plugin's internationalization.
 *
 * @package       Wc_Securionpay
 * @subpackage    Wc_Securionpay/includes
 * @author        Sajjad Hossain Sagor <sagorh672@gmail.com>
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since    2.0.0
 */
class Wc_Securionpay_I18n {
	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since     2.0.0
	 * @access    public
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'wc-securionpay',
			false,
			dirname( WC_SECURIONPAY_PLUGIN_BASENAME ) . '/languages/'
		);
	}
}
