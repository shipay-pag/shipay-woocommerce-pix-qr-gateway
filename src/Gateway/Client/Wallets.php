<?php

namespace Shipay\WcShipayPayment\Gateway\Client;

use Shipay\WcShipayPayment\Utils\Endpoints;
use Shipay\WcShipayPayment\Utils\Sources;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class Wallets extends Api
{
    private $gateway;

    private $access_key;

    private $secret_key;

    private $client_id;

    private $environment;

    public function __construct(
        $environment = null,
        $access_key = null,
        $secret_key = null,
        $client_id = null,
        $gateway = null,
        $base_api_url = null
    ) {
        parent::__construct($base_api_url);
        $this->gateway = $gateway;
        $this->environment = $environment;
        $this->access_key = $access_key;
        $this->secret_key = $secret_key;
        $this->client_id = $client_id;
    }

    protected function get_access_key()
    {
        if (!$this->access_key) {
            $this->access_key = $this->gateway->access_key;
        }

        return $this->access_key;
    }

    protected function get_secret_key()
    {
        if (!$this->secret_key) {
            $this->secret_key = $this->gateway->secret_key;
        }

        return $this->secret_key;
    }

    protected function get_client_id()
    {
        if (!$this->client_id) {
            $this->client_id = $this->gateway->client_id;
        }

        return $this->client_id;
    }

    protected function get_environment()
    {
        if (!$this->environment) {
            $this->environment = $this->gateway->environment;
        }

        return $this->environment;
    }

    public function get_access_token()
    {
        $token_service = new Token(
            $this->get_environment(),
            $this->get_access_key(),
            $this->get_secret_key(),
            $this->get_client_id()
        );

        $response = $token_service->get_access_token();
        delete_transient('wc_shipay_api_access_token');
        return $response['access_token'];
    }


    public function get_available_wallets()
    {
        if ($this->environment == Sources::SANDBOX_ENVIRONMENT) {
            $this->set_base_url(Endpoints::SANDBOX_GATEWAY_URI);
        } else {
            $this->set_base_url(Endpoints::PRODUCTION_GATEWAY_URI);
        }


        $token = $this->get_access_token();
        $header = $this->getDefaultPaymentHeader($token);

        $response = $this->do_request(
            Endpoints::WALLETS_ENDPOINT,
            'GET',
            [],
            $header
        );

        if ( isset ($response['body']) ) {
            return json_decode( $response['body'], true);
        }

        return $response;
    }
}