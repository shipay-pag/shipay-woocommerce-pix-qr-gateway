<?php
namespace Shipay\Payment\Gateway\Methods;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Shipay\Payment\Gateway\Methods\ShipayBolepixGateway;

final class ShipayBolepixGatewayBlocksSupport extends AbstractPaymentMethodType {

	/**
	 * The gateway instance.
	 *
	 * @var ShipayBolepixGateway
	 */
	private $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'wc_shipay_bolepix_payment_geteway';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_wc_shipay_bolepix_payment_geteway_settings', [] );
		$this->gateway = ShipayBolepixGateway::getInstance();
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_path = 'assets/js/block/frontend/bolepix-payment-block.js';
		$script_url = \SHIPAY_PAYMENT_PLUGIN_URL . $script_path;

		wp_register_script(
			'wc-shipay-bolepix-payment-blocks',
			$script_url,
            array(
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
            ),
            null,
            true
		);

		return [ 'wc-shipay-bolepix-payment-blocks' ];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return [ 
			'title' => $this->get_setting( 'title' ),
			'description' => esc_html( $this->get_setting( 'checkout_message' ) ),
            'shipay_bolepix_nonce' => wp_create_nonce( 'shipay_bolepix_nonce' ),
            'supports' => array_filter( $this->gateway->supports, [ $this->gateway, 'supports' ] )
		];
	}
}
