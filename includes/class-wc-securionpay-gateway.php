<?php
/**
 * This file contains the definition of the WC_Securionpay_Gateway class, which
 * is used to register the securionpay gateway to wc gateways.
 *
 * @package       Wc_Securionpay
 * @subpackage    Wc_Securionpay/includes
 * @author        Sajjad Hossain Sagor <sagorh672@gmail.com>
 */

use SecurionPay\SecurionPayGateway;
use SecurionPay\Exception\SecurionPayException;

/**
 * Securionpay Payment Gateway
 *
 * Integrates Securionpay Payment Gateway;
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class       WC_Securionpay_Gateway
 * @extends     WC_Payment_Gateway_CC
 * @since       2.0.0
 * @package     WooCommerce/Classes/Payment
 */
class WC_Securionpay_Gateway extends WC_Payment_Gateway_CC {
	/**
	 * The id of the gateway.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @var       string $id The id of the gateway.
	 */
	public $id;

	/**
	 * The icon of the gateway.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @var       string $icon The icon of the gateway.
	 */
	public $icon;

	/**
	 * Enable custom credit card form.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @var       bool $has_fields Enable custom credit card form.
	 */
	public $has_fields;

	/**
	 * Gateway title.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @var       string $method_title Gateway title.
	 */
	public $method_title;

	/**
	 * Gateway description.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @var       string $method_description Gateway description.
	 */
	public $method_description;

	/**
	 * Gateway supported card types.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @var       array $cardtypes Gateway supported card types.
	 */
	public $cardtypes;

	/**
	 * Gateway sandbox mode.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @var       string $sandbox Gateway sandbox mode.
	 */
	public $sandbox;

	/**
	 * Gateway secret key.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @var       string $secret_key Gateway secret key.
	 */
	public $secret_key;

	/**
	 * Class constructor for the SecurionPay WooCommerce gateway.
	 *
	 * Initializes the gateway, setting up its ID, title, description,
	 * supported features, and admin form fields.  Also loads settings
	 * and handles test mode warnings.
	 *
	 * @since     2.0.0
	 * @access    public
	 */
	public function __construct() {
		// payment gateway plugin ID.
		$this->id = WC_SECURIONPAY_GATEWAY_NAME;

		// URL of the icon that will be displayed on checkout page near your gateway name.
		$this->icon               = WC_SECURIONPAY_PLUGIN_URL . 'public/images/logo.png';
		$this->has_fields         = true; // in case you need a custom credit card form.
		$this->method_title       = 'SecurionPay';
		$this->method_description = __( 'Integrate SecurionPay payment gateway to your Woocommerce Powered store.', 'wc-securionpay' ); // will be displayed on the options page.
		$this->cardtypes          = $this->get_option( 'cardtypes' );

		// gateways supports simple payments, refunds & saved payment methods.
		$this->supports = array(
			'products',
			'refunds',
			'tokenization',
			'add_payment_method',
			'default_credit_card_form',
		);

		// Method with all the options fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->enabled     = $this->get_option( 'enabled' );
		$this->sandbox     = 'yes' === (string) $this->get_option( 'sandbox' );
		$this->secret_key  = $this->sandbox ? $this->get_option( 'sandbox_secret_key' ) : $this->get_option( 'secret_key' );

		// Add test mode warning if sandbox.
		if ( $this->sandbox ) {
			$this->description  = trim( $this->description );
			$this->description .= __( 'TEST MODE ENABLED. Use test card number 4242424242424242 with any 3-digit CVC and a future expiration date.', 'wc-securionpay' );
		}

		// This action hook saves the settings.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Initialize Gateway Settings Form Fields.
	 *
	 * @since     2.0.0
	 * @access    public
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'            => array(
				'title'   => __( 'Enable/Disable', 'wc-securionpay' ),
				'label'   => __( 'Enable SecurionPay Gateway', 'wc-securionpay' ),
				'type'    => 'checkbox',
				'default' => 'no',
			),
			'title'              => array(
				'title'       => 'Title',
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wc-securionpay' ),
				'default'     => __( 'SecurionPay Payment Gateway', 'wc-securionpay' ),
			),
			'description'        => array(
				'title'       => __( 'Description', 'wc-securionpay' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'wc-securionpay' ),
				'default'     => __( 'Pay with your credit card via SecurionPay payment gateway.', 'wc-securionpay' ),
			),
			'sandbox'            => array(
				'title'       => __( 'Sandbox Mode', 'wc-securionpay' ),
				'label'       => __( 'Enable Sandbox Mode', 'wc-securionpay' ),
				'type'        => 'checkbox',
				'description' => __( 'Securionpay sandbox can be used to test payments.', 'wc-securionpay' ),
				'default'     => 'yes',
				'desc_tip'    => true,
			),
			'sandbox_secret_key' => array(
				'title' => __( 'Sandbox Secret Key', 'wc-securionpay' ),
				'type'  => 'text',
			),
			'secret_key'         => array(
				'title' => __( 'Live Secret Key', 'wc-securionpay' ),
				'type'  => 'text',
			),
			'cardtypes'          => array(
				'title'    => __( 'Accepted Cards', 'wc-securionpay' ),
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'css'      => 'width: 350px;',
				'desc_tip' => __( 'Select the card types to accept.', 'wc-securionpay' ),
				'options'  => array(
					'visa'       => 'Visa',
					'mastercard' => 'MasterCard',
					'amex'       => 'American Express',
					'discover'   => 'Discover',
					'jcb'        => 'JCB',
					'diners'     => 'Diners Club',
				),
				'default'  => array( 'visa', 'mastercard', 'amex' ),
			),
		);
	}

	/**
	 * Get card icon image.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @return    string
	 */
	public function get_icon() {
		$icon = '';

		if ( is_array( $this->cardtypes ) ) {
			$card_types = $this->cardtypes;

			foreach ( $card_types as $card_type ) {
				$icon .= '<img style="margin-left: 2px;width: 40px;" src="' . WC_HTTPS::force_https_url( WC()->plugin_url() . '/assets/images/icons/credit-cards/' . $card_type . '.svg' ) . '" alt="' . $card_type . '" />';
			}
		}

		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}

	/**
	 * Builds our payment fields area - including tokenization fields for logged
	 * in users, and the actual payment fields.
	 *
	 * @since     2.0.0
	 * @access    public
	 */
	public function payment_fields() {
		if ( $this->description ) {
			echo wpautop( wptexturize( $this->description ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( $this->supports( 'tokenization' ) && is_checkout() ) {
			$this->tokenization_script();
			$this->saved_payment_methods();
			$this->form();
			$this->save_payment_method_checkbox();
		} else {
			$this->form();
		}
	}

	/**
	 * Save the card to the current user.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @param     Object $gateway        The gateway object.
	 * @param     array  $exp_date_array The card expiry date.
	 * @param     string $card_number    The card number.
	 * @param     int    $card_cvc       The card cvc.
	 */
	public function save_card( $gateway, $exp_date_array, $card_number, $card_cvc ) {
		$current_user = wp_get_current_user();
		$email        = (string) $current_user->user_email; // get currrent user email.
		$card_number  = intval( str_replace( ' ', '', $card_number ) );
		$card_type    = Wc_Securionpay::get_card_type( $card_number );
		$exp_month    = trim( $exp_date_array[0] );
		$exp_year     = trim( $exp_date_array[1] );
		$exp_date     = $exp_month . substr( $exp_year, -2 );
		$user_card    = array(
			'number'   => $card_number,
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			'cvc'      => intval( $card_cvc ),
			'expMonth' => trim( $exp_month ),
			'expYear'  => trim( $exp_year ),
		);

		// make up the data to send to Gateway API.
		$request = array(
			'email' => $email,
			'card'  => $user_card,
		);

		// check if customer is already exists.
		$customer_id = get_user_meta( $current_user->ID, '_cust_id', true );

		if ( ! empty( $customer_id ) ) {
			try {
				$customer            = $gateway->retrieveCustomer( $customer_id );
				$gateway_customer_id = $customer->getId();

				// make up the data to send to Gateway API.
				$update_customer = array(
					'customerId' => $gateway_customer_id,
					'card'       => $user_card,
				);

				$customer = $gateway->updateCustomer( $update_customer );
				$token    = new WC_Payment_Token_CC();

				$last_inserted_card = array_pop( $customer->getCards() );

				// charge id will be used as TransactionID for reference.
				$card_token = $last_inserted_card->getId();

				$token->set_token( $card_token );
				$token->set_gateway_id( WC_SECURIONPAY_GATEWAY_NAME );
				$token->set_card_type( strtolower( $card_type ) );
				$token->set_last4( substr( $card_number, -4 ) );
				$token->set_expiry_month( substr( $exp_date, 0, 2 ) );
				$token->set_expiry_year( '20' . substr( $exp_date, -2 ) );
				$token->set_user_id( $current_user->ID );
				$token->save();

				return array( 'result' => 'success' );
			} catch ( SecurionPayException $e ) {
				// something went wrong buddy.
				// handle error response - see https://securionpay.com/docs/api#error-object.
				$error_message = $e->getMessage();

				if ( ! empty( $error_message ) ) {
					$error_msg = __( 'Error adding card : ', 'wc-securionpay' ) . $error_message;
				} else {
					$error_msg = __( 'Error adding card. Please try again.', 'wc-securionpay' );
				}

				return array(
					'result'  => 'error',
					'message' => $error_msg,
				);
			}
		} else {
			// request for new customer creation.
			try {
				// do something with customer object - see https://securionpay.com/docs/api#customer-object.
				$customer           = $gateway->createCustomer( $request );
				$customer_id        = update_user_meta( $current_user->ID, '_cust_id', $customer->getId() );
				$token              = new WC_Payment_Token_CC();
				$last_inserted_card = array_pop( $customer->getCards() );

				// charge id will be used as TransactionID for reference.
				$card_token = $last_inserted_card->getId();

				$token->set_token( $card_token );
				$token->set_gateway_id( WC_SECURIONPAY_GATEWAY_NAME );
				$token->set_card_type( strtolower( $card_type ) );
				$token->set_last4( substr( $card_number, -4 ) );
				$token->set_expiry_month( substr( $exp_date, 0, 2 ) );
				$token->set_expiry_year( '20' . substr( $exp_date, -2 ) );
				$token->set_user_id( $current_user->ID );
				$token->save();

				return array( 'result' => 'success' );
			} catch ( SecurionPayException $e ) {
				// something went wrong buddy.
				// handle error response - see https://securionpay.com/docs/api#error-object.
				$error_message = $e->getMessage();

				if ( ! empty( $error_message ) ) {
					$error_msg = __( 'Error adding card : ', 'wc-securionpay' ) . $error_message;
				} else {
					$error_msg = __( 'Error adding card. Please try again.', 'wc-securionpay' );
				}

				return array(
					'result'  => 'error',
					'message' => $error_msg,
				);
			}
		}

		return array(
			'result'  => 'error',
			'message' => __( 'Error adding card. Please try again.', 'wc-securionpay' ),
		);
	}

	/**
	 * Adds a new payment method (card) for the current user.
	 *
	 * This function processes the addition of a new payment method,
	 * handling the submission of card details, interaction with the
	 * SecurionPay API, and storing the card information.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @return    array {
	 * @type string 'result'   The result of the operation ('success' or 'error').
	 * @type string 'redirect' The URL to redirect to after successful addition.
	 * @type string 'message'  An error message in case of failure.
	 * }
	 */
	public function add_payment_method() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST['wc_securionpay_gateway-card-expiry'] ) && ! empty( $_POST['wc_securionpay_gateway-card-number'] ) && ! empty( $_POST['wc_securionpay_gateway-card-cvc'] ) ) {
			// SecurionPay API key.
			$api_key = $this->secret_key;

			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$exp_date_array = explode( '/', sanitize_text_field( wp_unslash( $_POST['wc_securionpay_gateway-card-expiry'] ) ) );

			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$card_number = sanitize_text_field( wp_unslash( $_POST['wc_securionpay_gateway-card-number'] ) );

			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$card_cvc = intval( wp_unslash( $_POST['wc_securionpay_gateway-card-cvc'] ) );

			// load the securionpay library [https://github.com/securionpay/securionpay-php].
			require WC_SECURIONPAY_PLUGIN_PATH . '/vendor/autoload.php';

			// initiate the SecurionPay gateway library class.
			$gateway = new SecurionPayGateway( $api_key );
			$result  = $this->save_card( $gateway, $exp_date_array, $card_number, $card_cvc );

			if ( 'success' === $result['result'] ) {
				return array(
					'result'   => 'success',
					'redirect' => wc_get_endpoint_url( 'payment-methods' ),
				);
			} elseif ( 'error' === $result['result'] ) {
				wc_add_notice( $result['message'], 'error' );
			}
		}

		wc_add_notice( __( 'Something went wrong! Please try again.', 'wc-securionpay' ), 'error' );
	}

	/**
	 * Process the payment for a WooCommerce order using SecurionPay.
	 *
	 * This function handles the payment processing for a WooCommerce order. It
	 * retrieves the order details, processes the payment through SecurionPay,
	 * and updates the order status accordingly. It supports both new card payments
	 * and payments using saved card tokens.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @param     int $order_id The ID of the order to process the payment for.
	 * @return    array         An array containing the result of the payment processing
	 *                          and a redirect URL if the payment was successful.  Possible
	 *                          values for 'result' are 'success' and 'fail'.
	 */
	public function process_payment( $order_id ) {
		global $woocommerce;

		$order  = wc_get_order( $order_id );
		$amount = Wc_Securionpay::get_amount( $order->get_total(), get_option( 'woocommerce_currency' ) );
		$card   = '';

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST['wc_securionpay_gateway-card-expiry'] ) && ! empty( $_POST['wc_securionpay_gateway-card-number'] ) && ! empty( $_POST['wc_securionpay_gateway-card-cvc'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$exp_date_array = explode( '/', sanitize_text_field( wp_unslash( $_POST['wc_securionpay_gateway-card-expiry'] ) ) );
			$exp_month      = trim( $exp_date_array[0] );
			$exp_year       = trim( $exp_date_array[1] );
			$exp_date       = $exp_month . substr( $exp_year, -2 );
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$card_number = sanitize_text_field( wp_unslash( $_POST['wc_securionpay_gateway-card-number'] ) );

			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$card_cvc = intval( wp_unslash( $_POST['wc_securionpay_gateway-card-cvc'] ) );

			// make up the data to send to Gateway API.
			$request = array(
				'amount'   => $amount,
				'currency' => get_option( 'woocommerce_currency' ),
				'card'     => array(
					// phpcs:ignore WordPress.Security.NonceVerification.Missing
					'number'   => str_replace( ' ', '', sanitize_text_field( wp_unslash( $_POST['wc_securionpay_gateway-card-number'] ) ) ),
					// phpcs:ignore WordPress.Security.NonceVerification.Missing
					'cvc'      => intval( $_POST['wc_securionpay_gateway-card-cvc'] ),
					'expMonth' => $exp_month,
					'expYear'  => $exp_year,
				),
			);
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST['wc-wc_securionpay_gateway-payment-token'] ) && 'new' !== $_POST['wc-wc_securionpay_gateway-payment-token'] ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$token_id = wc_clean( sanitize_text_field( wp_unslash( $_POST['wc-wc_securionpay_gateway-payment-token'] ) ) );
			$card     = WC_Payment_Tokens::get( $token_id );

			// Return if card does not belong to current user.
			if ( $card->get_user_id() !== get_current_user_id() ) {
				return;
			}

			$token                 = $card->get_token();
			$request['card']       = $token;
			$request['customerId'] = get_user_meta( get_current_user_id(), '_cust_id', true );
		}

		// SecurionPay API key.
		$api_key = $this->secret_key;

		// load the securionpay library [https://github.com/securionpay/securionpay-php].
		require WC_SECURIONPAY_PLUGIN_PATH . '/vendor/autoload.php';

		// initiate the SecurionPay gateway library class.
		$gateway = new SecurionPayGateway( $api_key );

		// go for it... charge the amount and see if it has any chance.
		try {
			// the charge object after successfully charging a card.
			// do something with charge object - see https://securionpay.com/docs/api#charge-object.
			$charge = $gateway->createCharge( $request );

			// charge id will be used as TransactionID for reference.
			$charge_id = $charge->getId();
			$card_obj  = $charge->getCard();

			$order->payment_complete( $trans_id );

			$order->reduce_order_stock();

			$woocommerce->cart->empty_cart();

			if ( ! empty( $card ) ) {
				$exp_date = $card->get_expiry_month() . substr( $card->get_expiry_year(), -2 );
			}

			$amount_approved = number_format( $amount, '2', '.', '' );

			$order->add_order_note(
				sprintf(
					/* translators: 1: Payment amount, 2: Transaction ID */
					__( 'Securionpay payment completed for %1$s. Transaction ID: %2$s', 'wc-securionpay' ),
					$amount_approved,
					$charge_id,
				)
			);

			$tran_meta = array(
				'transaction_id' => $charge_id,
				'cc_last4'       => $card_obj->getLast4(),
				'cc_expiry'      => $exp_date,
			);

			add_post_meta( $order_id, '_securionpay_transaction', $tran_meta );

			// Save the card if possible.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( 'new' === sanitize_text_field( wp_unslash( $_POST['wc-wc_securionpay_gateway-payment-token'] ) ) && is_user_logged_in() ) {
				$this->save_card( $gateway, $exp_date_array, $card_number, $card_cvc );
			}

			// Return thankyou redirect.
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		} catch ( Exception $e ) {
			// handle error response - see https://securionpay.com/docs/api#error-object.
			wc_add_notice( $e->getMessage(), 'error' );

			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
		}

		return array(
			'result'   => 'fail',
			'redirect' => '',
		);
	}

	/**
	 * Processes a refund for a WooCommerce order using the SecurionPay gateway.
	 *
	 * This function handles the refund process for a given order. It retrieves the
	 * necessary information, such as the SecurionPay transaction ID and API key,
	 * and then uses the SecurionPay PHP library to initiate the refund.  It also
	 * adds a note to the order with the refund details or any error messages.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @param     int    $order_id The ID of the order to be refunded.
	 * @param     float  $amount   The amount to refund. If null, the entire order amount is refunded.
	 * @param     string $reason   An optional reason for the refund.
	 * @return    bool|WP_Error    True on success, a WP_Error object on failure.
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );

		// SecurionPay API key.
		$api_key        = $this->secret_key;
		$tran_meta      = get_post_meta( $order_id, '_securionpay_transaction', true );
		$transaction_id = $tran_meta['transaction_id'];

		// if transaction id is not set then no refund can happen buddy.
		if ( empty( $transaction_id ) ) {
			return;
		}

		if ( $amount > 0 ) {
			try {
				// load the securionpay library [https://github.com/securionpay/securionpay-php].
				require WC_SECURIONPAY_PLUGIN_PATH . '/vendor/autoload.php';

				// initiate the SecurionPay gateway library class.
				$gateway = new SecurionPayGateway( $api_key );

				// make up the data to send to Gateway API.
				$request = array(
					'chargeId' => $transaction_id,
					'amount'   => intval( $amount ),
				);

				$refund = $gateway->refundCharge( $request );

				// do something with charge object - see https://securionpay.com/docs/api#charge-object.
				$refund_id       = $refund->getId();
				$refunded_amount = number_format( $amount, '2', '.', '' );

				$order->add_order_note(
					sprintf(
						/* translators: 1: Refunded amount, 2: Refund ID */
						__( 'Securionpay refund completed for %1$s. Refund ID: %2$s', 'wc-securionpay' ),
						$refunded_amount,
						$refund_id,
					)
				);

				return true;
			} catch ( Exception $e ) {
				$order->add_order_note( $e->getMessage() );

				return new WP_Error( 'securionpay_error', $e->getMessage() );
			}
		} else {
			return false;
		}
	}

	/**
	 * Output field name HTML
	 *
	 * Gateways which support tokenization do not require names - we don't want the data to post to the server.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @param     string $name Name of the field.
	 * @return    string
	 */
	public function field_name( $name ) {
		return ' name="' . esc_attr( $this->id . '-' . $name ) . '" ';
	}
}
