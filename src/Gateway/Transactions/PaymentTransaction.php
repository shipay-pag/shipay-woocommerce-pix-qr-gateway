<?php

namespace Shipay\Payment\Gateway\Transactions;

use Shipay\Payment\Utils\Sources;
use Shipay\Payment\Utils\Endpoints;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class PaymentTransaction
{
    protected $order;

    protected $gateway;

    public function __construct(
        $gateway,
        $order
    ) {
        $this->gateway = $gateway;
        $this->order = $order;
    }

    protected function get_transaction( $buyer_document )
    {
        return [
            'buyer' => $this->get_buyer( $buyer_document ),
            'callback_url' => WC()->api_request_url( $this->gateway->id ),
            'order_ref' => $this->order->get_order_key(),
            'total' => $this->order->get_total(),
            'items' => $this->get_order_items()
        ];
    }

    protected function get_buyer( $document )
    {
        return [
            'cpf_cnpj' => $document,
            'name' => trim( $this->order->billing_first_name . ' ' . $this->order->billing_last_name ),
            'email' => $this->order->billing_email,
            'phone' => $this->order->billing_phone
        ];
    }

    protected function get_order_items()
    {
        $items = [];
        foreach ($this->order->get_items() as $item) {
            $items[] = [
                'item_title' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'unit_price' => $item->get_total() / $item->get_quantity()
            ];
        }

        if ($this->order->get_shipping_total()) {
            $items[] = [
                'item_title' => 'Envio',
                'quantity' => 1,
                'unit_price' => $this->order->get_shipping_total()
            ];
        }

        return $items;
    }
}