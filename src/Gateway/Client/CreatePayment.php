<?php

namespace Shipay\Payment\Gateway\Client;

use Shipay\Payment\Utils\Endpoints;
use Shipay\Payment\Utils\Sources;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class CreatePayment extends Api
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

    public function create_payment( $body )
    {
        if ( $this->gateway->environment == Sources::SANDBOX_ENVIRONMENT ) {
            $this->set_base_url(Endpoints::SANDBOX_GATEWAY_URI );
        } else {
            $this->set_base_url(Endpoints::PRODUCTION_GATEWAY_URI );
        }

        if ( $this->gateway->is_debug() ) {
            $this->gateway->log->add( $this->gateway->id, "Shipay - Payment Creation Body:" );
            $this->gateway->log->add( $this->gateway->id, sprintf( "Body: %s", wp_json_encode( $body ) ) );
        }

        $token = $this->get_access_token();
        $header = $this->getDefaultPaymentHeader( $token );

        $endpoint = $this->endpoints->get_order_creation_endpoint( $this->gateway->id, isset($body['expiration']) );

        $response = $this->do_request(
            $endpoint,
            'POST',
            $body,
            $header
        );

        if (
            $this->gateway->id == 'wc_shipay_bolepix_payment_geteway'
            && $response['response']['code'] > 203
        ) {
            $response =  $this->check_payment_creation($body);
        }

        if ( $this->gateway->is_debug() ) {
            $this->gateway->log->add( $this->gateway->id, "Shipay - Payment Creation Response:" );

            if ( isset($response['body']) ) {
                $this->gateway->log->add( $this->gateway->id, sprintf( "Body: %s",  $response['body'] ) );
            } else {
                $this->gateway->log->add( $this->gateway->id, sprintf( "Body: %s", wp_json_encode( $response ) ) );
            }
        }

        if ( isset ($response['body']) ) {
            return json_decode( $response['body'], true );
        }

        return $response;
    }

    protected function check_payment_creation ( $body )
    {
        $endpoint = $this->endpoints->get_order_creation_endpoint( $this->gateway->id, isset($body['expiration']));

        $token = $this->get_access_token();
        $header = $this->getDefaultPaymentHeader( $token );

        $query = [
            'order_ref' => $body['order_ref'],
            'limit' => 1
        ];

        $response =  $this->do_request(
            $endpoint . '?' . http_build_query( $query ),
            'GET',
            [],
            $header
        );

        if ( isset($response['body'] ) ) {
            $body = json_decode( $response['body'], true );


            return isset($body['data'][0]) ? ['body' => wp_json_encode( $body['data'][0]) ] : false;
        }

        return $response;
    }
}