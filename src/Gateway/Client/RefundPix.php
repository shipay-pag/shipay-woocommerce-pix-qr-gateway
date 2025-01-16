<?php

namespace Shipay\WcShipayPayment\Gateway\Client;

use Shipay\WcShipayPayment\Utils\Endpoints;
use Shipay\WcShipayPayment\Utils\Sources;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class RefundPix extends Api
{
    private $gateway;

    public function __construct(
        $gateway = null,
        $base_api_url = null
    ) {
        parent::__construct($base_api_url);
        $this->gateway = $gateway;
    }

    public function get_access_token()
    {
        $token_service = new Token(
            $this->gateway->environment,
            $this->gateway->access_key,
            $this->gateway->secret_key,
            $this->gateway->client_id
        );

        $response = $token_service->get_cached_access_token();
        return $response['access_token'];
    }

    public function refund_order ( $shipay_order_id, $amount )
    {
        if ( $this->gateway->environment == Sources::SANDBOX_ENVIRONMENT ) {
            $this->set_base_url(Endpoints::SANDBOX_GATEWAY_URI );
        } else {
            $this->set_base_url(Endpoints::PRODUCTION_GATEWAY_URI );
        }

        $token = $this->get_access_token();
        $header = $this->getDefaultPaymentHeader( $token );

        $endpoint = $this->endpoints->get_endpoint_path( Endpoints::PIX_REFUND_ENDPOINT, $shipay_order_id );
        $response = $this->do_request(
            $endpoint,
            'DELETE',
            [ 'amount' => $amount ],
            $header
        );

        if ( isset ($response['body']) ) {
            if ( $this->gateway->is_debug() ) {
                $this->gateway->log->add( $this->gateway->id, "Shipay - Payment Refund Response:" );
                $this->gateway->log->add( $this->gateway->id, sprintf( "Body: %s", print_r( $response['body'], true ) ) );
            }

            return json_decode( $response['body'], true );
        }

        return $response;
    }
}