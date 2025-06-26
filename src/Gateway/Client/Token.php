<?php

namespace Shipay\Payment\Gateway\Client;

use Shipay\Payment\Utils\Endpoints;
use Shipay\Payment\Utils\Sources;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class Token extends Api
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

    function get_cached_access_token()
    {
        $cache_key = 'wc_shipay_api_access_token';
        $cached_response = get_transient($cache_key);

        if ($cached_response === false) {
            // Cache miss, make the API request
            $response = $this->get_access_token();

            if ($response !== false) {
                set_transient($cache_key, $response, $response['access_token_expires_in'] - 300);
            }
            return $response;
        }

        // Return the cached response
        return $cached_response;
    }

    public function get_access_token()
    {
        if ($this->environment == Sources::SANDBOX_ENVIRONMENT) {
            $this->set_base_url(Endpoints::SANDBOX_GATEWAY_URI);
        }  else {
            $this->set_base_url(Endpoints::PRODUCTION_GATEWAY_URI);
        }

        $body = [
            'access_key' => $this->get_access_key(),
            'secret_key' => $this->get_secret_key(),
            'client_id' => $this->get_client_id()
        ];

        $response = $this->do_request(
            Endpoints::AUTH_ENDPOINT,
            'POST',
            $body
        );

        if ( isset($response['body']) && $response['response']['code'] <= 203 ) {
            return json_decode($response['body'], true);
        }

        $logs = new \WC_Logger();
        $logs->add( 'shipay-token', 'ENVIRONMENT GET TOKEN: ' . $this->environment );
        $logs->add( 'shipay-token', 'RESPONSE GET TOKEN: ' . json_encode($response) );

        return false;
    }
}