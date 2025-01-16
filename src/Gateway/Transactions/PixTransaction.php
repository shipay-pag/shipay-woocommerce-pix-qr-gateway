<?php

namespace Shipay\WcShipayPayment\Gateway\Transactions;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class PixTransaction extends PaymentTransaction
{

    protected $customer_document;

    public function __construct(
        $gateway,
        $order,
        $customer_document
    ) {
        parent::__construct($gateway, $order);
        $this->customer_document = $customer_document;
    }

    public function get_pix_transaction()
    {
        $transaction = $this->get_transaction( $this->customer_document );
        $transaction['allow_amount_change'] = false;
        $transaction['wallet'] = $this->gateway->wallet;

        if ( $this->gateway->pix_expiration_seconds && $this->gateway->pix_expiration_seconds >= 600 ) {
            $transaction['expiration'] = $this->gateway->pix_expiration_seconds;
        }

        return $transaction;
    }
}