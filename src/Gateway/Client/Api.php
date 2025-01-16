<?php

namespace Shipay\WcShipayPayment\Gateway\Client;

use Shipay\WcShipayPayment\Utils\Sources;
use Shipay\WcShipayPayment\Utils\Endpoints;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class Api
{
    protected $base_api_url;

    protected $endpoints;

    public function __construct(
        $base_api_url = null
    ) {
        $this->base_api_url = $base_api_url;
        $this->endpoints = new Endpoints();
    }

    public function set_base_url( $base_url )
    {
        $this->base_api_url = $base_url;
    }

    public function get_base_url()
    {
        if (!$this->base_api_url) {
            $this->base_api_url = $this->endpoints->get_base_url();
        }

        return $this->base_api_url;
    }

    protected function getDefaultPaymentHeader( $token )
    {
        return [
            'Content-Type' => 'application/json',
            'x-shipay-order-type' => 'e-order',
            'Authorization' => 'Bearer ' . $token,
        ];
    }

    protected function getDefaultHeader()
    {
        return [
            'Content-Type' => 'application/json'
        ];
    }


    /**
     * Do request to Shipay API
     *
     * @param  string $endpoint API Endpoint.
     * @param  string $method   Request method.
     * @param  array  $data     Request data.
     * @param  array  $headers  Request headers.
     *
     * @return array            Request response.
     */
    protected function do_request( $endpoint, $method = 'POST', $data = array(), $headers = array() ) {
        $params = [
            'timeout' => 60,
        ];

        if ( ! empty ( $data ) ) {
            $params['body'] = wp_json_encode($data);
        }

        $params['headers'] = $headers ?: $this->getDefaultHeader();

        $response = false;

        if ( $method == 'POST' ) {
            $response = wp_remote_post($this->get_base_url() . $endpoint, $params);
        }

        if ( $method ==  'GET' ) {
            $response = wp_remote_get($this->get_base_url() . $endpoint, $params);
        }

        if ( $method ==  'DELETE' ) {
            $params[ 'method' ] = $method;
            $response = wp_remote_request($this->get_base_url() . $endpoint, $params);
        }

        return $response;
    }
}