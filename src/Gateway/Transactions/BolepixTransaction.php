<?php

namespace Shipay\WcShipayPayment\Gateway\Transactions;

use Shipay\WcShipayPayment\Utils\Sources;
use Shipay\WcShipayPayment\Utils\Helper;
use Shipay\WcShipayPayment\Utils\Endpoints;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class BolepixTransaction extends PaymentTransaction
{
    protected  $helper;

    protected $customer_document;

    public function __construct(
        $gateway,
        $order,
        $customer_document
    ) {
        parent::__construct($gateway, $order);
        $this->customer_document = $customer_document;
        $this->helper = new Helper();
    }

    public function get_bolepix_transaction()
    {
        $transaction = $this->get_transaction( $this->customer_document );
        $transaction['buyer'] = $this->get_buyer_with_address();

        $transaction['calendar'] = [
            'due_date' => $this->helper->get_date(
                $this->gateway->bolepix_expiration, 'days', 'Y-m-d'
            )
        ];

        $transaction['bank_slip_type'] = $this->gateway->bank_slip_type;
        $transaction['enable_bank_slip'] = true;
        $transaction['charge_enabled'] = true;
        $transaction['wallet'] = $this->gateway->wallet;
        return $transaction;
    }

    public function get_buyer_with_address()
    {
        $buyer = $this->get_buyer( $this->customer_document );
        $buyer['address_complement'] = $this->order->get_billing_address_2();;
        $buyer['address_number'] = $this->order->get_meta( '_billing_number' ) ?: '0';
        $buyer['city'] = $this->order->get_billing_city();
        $buyer['neighborhood'] = $this->order->get_meta( '_billing_neighborhood' ) ?: 'N/A';
        $buyer['phone'] = $this->helper->only_numbers($this->order->get_billing_phone());
        $buyer['postal_code'] = $this->order->get_billing_postcode();
        $buyer['state'] = $this->order->get_billing_state();
        $buyer['street_name'] = $this->order->get_billing_address_1();
        return $buyer;
    }
}