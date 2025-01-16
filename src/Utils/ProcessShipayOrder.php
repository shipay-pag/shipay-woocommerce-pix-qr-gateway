<?php

namespace Shipay\WcShipayPayment\Utils;

use Shipay\WcShipayPayment\Gateway\Client\ConsultStatus;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class ProcessShipayOrder
{
    const SHIPAY_ORDER_PENDING = 'pending';

    const SHIPAY_ORDER_REFUNDED = 'refunded';

    const SHIPAY_ORDER_PARTIAL_REFUNDED = 'partial_refunded';

    const SHIPAY_ORDER_REFUND_PENDING = 'refund_pending';

    const SHIPAY_ORDER_APPROVED = 'approved';

    const SHIPAY_ORDER_PENDINGV = 'pendingv';

    const SHIPAY_ORDER_EXPIRED = 'expired';

    const SHIPAY_ORDER_CANCELLED = 'cancelled';

    protected $gateway;

    protected $order;

    protected $helper;

    public function __construct( $gateway )
    {
        $this->gateway = $gateway;
        $this->helper = new Helper();
    }
    
    public function set_order_additional_info( $order, $shipay_info)
    {
        if ( !$order->get_meta( '_wc_shipay_payment_order_id' )) {
            $order->update_meta_data( '_wc_shipay_payment_order_id', $shipay_info['order_id'] );

            $order->add_order_note( "Shipay: order_id - " . $shipay_info['order_id'] );

        }

        if ( !$order->get_meta( '_wc_shipay_payment_pix_dict_key' ) && isset( $shipay_info['pix_dict_key'] ) ) {
            $order->update_meta_data( '_wc_shipay_payment_pix_dict_key', $shipay_info['pix_dict_key'] );
        }

        if ( !$order->get_meta( '_wc_shipay_payment_qr_code_image' )) {
            $order->update_meta_data( '_wc_shipay_payment_qr_code_image', $shipay_info['qr_code'] );
        }

        if ( !$order->get_meta( '_wc_shipay_payment_qr_code' )) {
            $order->update_meta_data( '_wc_shipay_payment_qr_code', $shipay_info['qr_code_text'] );
        }

        if ( !$order->get_meta( '_wc_shipay_payment_wallet' )) {
            $order->update_meta_data( '_wc_shipay_payment_wallet', $shipay_info['wallet'] );
        }

        $order->update_meta_data( '_wc_shipay_payment_status', $shipay_info['status'] );
        return $order;
    }
    
    public function set_bolepix_additional_info( $order, $shipay_info )
    {
        $order = $this->set_order_additional_info( $order, $shipay_info );
        $order->update_meta_data( '_wc_shipay_payment_bank_slip_id', $shipay_info['bank_slip_id'] );
        $order->update_meta_data( '_wc_shipay_payment_barcode', $shipay_info['barcode'] );
        $order->update_meta_data( '_wc_shipay_payment_expedition_date', $shipay_info['expedition_date'] );
        $order->update_meta_data( '_wc_shipay_payment_reference', $shipay_info['reference'] );
        $order->update_meta_data( '_wc_shipay_payment_typeable_line', $shipay_info['typeable_line'] );
        $order->update_meta_data( '_wc_shipay_payment_pdf', $shipay_info['charge_url_pdf'] );
        $order->save();

        return $order;
    }

    public function set_pix_additional_info( $order, $shipay_info )
    {
        $order = $this->set_order_additional_info( $order, $shipay_info );

        if ( !$order->get_meta('_wc_shipay_payment_expiration_date') ) {
            $expiration_time = 3600;
            if ( isset( $shipay_info[ 'expiration_date' ] ) ) {
                $order->update_meta_data( '_wc_shipay_payment_pix_type', 'with_expiration' );
                $expiration_time = $this->gateway->pix_expiration_seconds;
            }

            $order->update_meta_data( '_wc_shipay_payment_expiration_date', $this->helper->get_date($expiration_time) );
        }

        $order->save();
        return $order;
    }

    public function process_order_status( $order, $shipay_status )
    {
        $this->gateway->log->add( $this->gateway->id, ' Status: ' . $shipay_status);
        $this->gateway->log->add( $this->gateway->id, ' new_order_status: ' . $this->gateway->new_order_status);
        $this->gateway->log->add( $this->gateway->id, ' get_status: ' . $order->get_status());

        switch ( $shipay_status ) {
            case self::SHIPAY_ORDER_PENDING:
            case self::SHIPAY_ORDER_PENDINGV:
                $order->update_status($this->gateway->new_order_status, __('Shipay: Aguardando Pagamento.', 'wc-shipay-payment'));
                break;
                
            case self::SHIPAY_ORDER_APPROVED:
                if ( 'wc-' . $order->get_status() == $this->gateway->new_order_status ) {
                    $order->update_status($this->gateway->after_paid_status, __('Shipay: Pagamento Aprovado.', 'wc-shipay-payment'));
                }

                break;
                
            case self::SHIPAY_ORDER_PARTIAL_REFUNDED:
                $order->add_order_note( __( 'Shipay: Pedido for estornado parcialmente', 'wc-shipay-payment' ) );
                break;
                
            case self::SHIPAY_ORDER_REFUND_PENDING:
                $order->add_order_note( __( 'Shipay: O estorno esta pendente', 'wc-shipay-payment' ) );
                break;
                
            case self::SHIPAY_ORDER_CANCELLED:
            case self::SHIPAY_ORDER_EXPIRED:
                $order->update_status($this->gateway->not_paid_status, __('Shipay: O pagamento não foi identificado e o pedido foi cancelado', 'wc-shipay-payment'));
                break;

            case self::SHIPAY_ORDER_REFUNDED:
                $order->update_status( 'wc-refunded', __('Shipay: O pedido foi reembolsado', 'wc-shipay-payment') );
                break;

            default:
                break;
        }

        $order->save();
    }


    public function webhook_handler() {
        @ob_clean();

        if ( $this->gateway->is_debug() ) {
            $this->gateway->log->add( $this->gateway->id, 'Retornou um POSTBACK' );
            $this->gateway->log->add( $this->gateway->id, 'Response' . print_r( file_get_contents( 'php://input'), true ) );
        }

        $posted = ! empty( file_get_contents( 'php://input') ) ? file_get_contents( 'php://input') : false;

        if ( $posted ) {
            header( 'HTTP/1.1 200 OK' );

            $this->process_webhook( json_decode( $posted, true ) );
            exit;
        } else {
            wp_die( esc_html__( 'PIX Request Failure', 'wc-shipay-payment' ), '', array( 'response' => 401 ) );
        }
    }

    public function process_webhook( $posted ) {
        $posted = wp_unslash( $posted );

        if ( $this->gateway->is_debug() ) {
            $this->gateway->log->add( $this->gateway->id, 'Sucesso: OrderID = ' . $posted['order_id'] );
        }

        $args = array(
            'limit' => 1,
            'meta_key' => '_wc_shipay_payment_order_id',
            'meta_value' => $posted['order_id'],
            'meta_compare' => '=',
        );

        $orders = wc_get_orders( $args );

        if ( empty( $orders ) ) {
            $this->gateway->log->add( $this->gateway->id, 'ERRO: Nenhum pedido encontrado para o order_id ' );
            return;
        }

        $order = reset( $orders );
        $status = new ConsultStatus($this->gateway);
        $this->gateway->log->add( $this->gateway->id, $order->get_meta('_wc_shipay_payment_expiration_date')  );

        $response = $status->check_status( $posted['order_id'], $order->get_meta('_wc_shipay_payment_pix_type') );

        if ( isset($response['order_id']) && isset($response['status'] ) ) {
            $order = $this->set_order_additional_info( $order, $response );
            $this->process_order_status( $order, sanitize_text_field($response['status'] ) );
            $order->save();
        }
    }
}