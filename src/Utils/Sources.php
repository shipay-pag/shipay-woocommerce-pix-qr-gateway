<?php

namespace Shipay\WcShipayPayment\Utils;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

use Shipay\WcShipayPayment\Utils\ProcessShipayOrder;

class Sources
{
    const SANDBOX_ENVIRONMENT = 'sandbox';

    const PRODUCTION_ENVIRONMENT = 'production';

    public function environment_options()
    {
        return [
            self::SANDBOX_ENVIRONMENT => 'Sandbox',
            self::PRODUCTION_ENVIRONMENT => __('Production', 'wc-shipay-payment')
        ];
    }

    public function get_shipay_status_description( $status )
    {
        switch ($status) {
            case ProcessShipayOrder::SHIPAY_ORDER_PENDING:
            case ProcessShipayOrder::SHIPAY_ORDER_PENDINGV:
                return __('Pendente (pending) - Pedido aberto e ainda não pago', 'wc-shipay-payment');
            case ProcessShipayOrder::SHIPAY_ORDER_APPROVED:
                return __('Aprovado (approved) - Pedido aprovado na carteira digital', 'wc-shipay-payment');
            case ProcessShipayOrder::SHIPAY_ORDER_CANCELLED:
                return __('Cancelado (cancelled) - Pedido (ainda não pago) cancelado na carteira digital', 'wc-shipay-payment');
            case ProcessShipayOrder::SHIPAY_ORDER_EXPIRED:
                return __('Expirado (expired) - Pedido expirado após o esgotamento do prazo de expiração', 'wc-shipay-payment');
            case ProcessShipayOrder::SHIPAY_ORDER_REFUNDED:
                return __('Estornado (refunded) - Pagamento devolvido ao comprador', 'wc-shipay-payment');
            case ProcessShipayOrder::SHIPAY_ORDER_PARTIAL_REFUNDED:
                return __('Estornado (partial_refunded) - Pagamento parcialmente devolvido ao comprador', 'wc-shipay-payment');
            case ProcessShipayOrder::SHIPAY_ORDER_REFUND_PENDING:
                return __('Estorno Pendente (refund_pending) - Pagamento aguardando devolução ao comprador', 'wc-shipay-payment');

            default:
                return $status;
        }
    }
}