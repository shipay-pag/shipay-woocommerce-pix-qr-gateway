<?php
namespace Shipay\WcShipayPayment\Gateway\Methods;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Shipay\WcShipayPayment\Gateway\Methods\ShipayPixGateway;

final class ShipayPixGatewayBlocksSupport extends AbstractPaymentMethodType {

	/**
	 * The gateway instance.
	 *
	 * @var ShipayPixGateway
	 */
	private $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'wc_shipay_pix_payment_geteway';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_wc_shipay_pix_payment_geteway_settings', [] );
		$this->gateway = ShipayPixGateway::getInstance();
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
		$script_path = 'assets/js/block/frontend/pix-payment-block.js';
		$script_url = \WC_SHIPAY_PAYMENT_PLUGIN_URL . $script_path;

		wp_register_script(
			'wc-shipay-pix-payment-blocks',
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

		return [ 'wc-shipay-pix-payment-blocks' ];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return [ 
			'title' => $this->get_setting( 'title' ),
			'description' => esc_html( $this->get_setting( 'checkout_instructions' ) ),
			'supports' => array_filter( $this->gateway->supports, [ $this->gateway, 'supports' ] )
		];
	}
}