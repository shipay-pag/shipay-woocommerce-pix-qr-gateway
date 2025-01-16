<?php

namespace Shipay\WcShipayPayment\Utils;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class Endpoints
{
    const PRODUCTION_GATEWAY_URI = 'https://api.shipay.com.br';

    const SANDBOX_GATEWAY_URI = 'https://api-staging.shipay.com.br';

    const ORDER_ENDPOINT = '/order';

    const PIX_EXPIRATION_ORDER_ENDPOINT = '/orderv';

    const AUTH_ENDPOINT = '/pdvauth';

    const WALLETS_ENDPOINT = '/v1/wallets';

    const PIX_STATUS_ENDPOINT = '/order/{order_id}';

    const WITH_EXPIRATION_PIX_STATUS_ENDPOINT = '/orderv/{order_id}';

    const PIX_REFUND_ENDPOINT = '/order/{order_id}/refund';

    const BOLEPIX_ORDER_ENDPOINT = '/v2/order-due-date';

    const BOLEPIX_STATUS_ENDPOINT = '/v2/order-due-date/{order_id}';

    public function get_base_url($is_sandbox = false)
    {
        if ($is_sandbox == Sources::SANDBOX_ENVIRONMENT) {
            return self::SANDBOX_GATEWAY_URI;
        }

        return self::PRODUCTION_GATEWAY_URI;
    }

    public function get_endpoint_path ( $endpoint, $order_id )
    {
        return str_replace(
            ['{order_id}'],
            [$order_id],
            $endpoint
        );
    }

    public function get_order_creation_endpoint( $payment_method_id, $has_expiration = false )
    {
        switch ( $payment_method_id ) {
            case 'wc_shipay_bolepix_payment_geteway':
                return self::BOLEPIX_ORDER_ENDPOINT;

            case 'wc_shipay_pix_payment_geteway' && $has_expiration:
                return self::PIX_EXPIRATION_ORDER_ENDPOINT;

            default:
                return self::ORDER_ENDPOINT;
        }
    }

    public function get_order_status_endpoint( $payment_method_id, $has_expiration = false )
    {
        switch ( $payment_method_id ) {
            case 'wc_shipay_bolepix_payment_geteway':
                return self::BOLEPIX_STATUS_ENDPOINT;

            case 'wc_shipay_pix_payment_geteway' && $has_expiration:
                return self::WITH_EXPIRATION_PIX_STATUS_ENDPOINT;

            default:
                return self::PIX_STATUS_ENDPOINT;
        }
    }
}