<?php

namespace Shipay\WcShipayPayment\Gateway;

use Exception;
use Shipay\WcShipayPayment\Gateway\Client\CancelPix;
use Shipay\WcShipayPayment\Gateway\Client\ConsultStatus;
use Shipay\WcShipayPayment\Utils\Helper as WP;
use Shipay\WcShipayPayment\Gateway\Client\Wallets;
use Shipay\WcShipayPayment\Utils\ProcessShipayOrder;
use Shipay\WcShipayPayment\Utils\Sources;
use Shipay\WcShipayPayment\Gateway\Methods\ShipayPixGatewayBlocksSupport;
use Shipay\WcShipayPayment\Gateway\Methods\ShipayBolepixGatewayBlocksSupport;

if ( !defined('ABSPATH') ) {
    exit;
}

class BaseGateway {

    public static function init() {
        $base = new self();
        $base->payment_status_scheduled();

        WP::add_filter( 'woocommerce_payment_gateways', $base, 'add_gateway' );
        WP::add_filter( 'woocommerce_blocks_loaded', $base, 'add_gateway_block_support' );
        WP::add_filter( 'plugin_action_links_' . \WC_SHIPAY_PAYMENT_BASE_NAME, $base, 'plugin_action_links' );
        WP::add_action( 'wp_ajax_wc_shipay_payment_check', $base, 'check_payment' );
        WP::add_action( 'wp_ajax_nopriv_wc_shipay_payment_check', $base, 'check_payment' );
        WP::add_action( 'wp_ajax_wc_shipay_payment_wallets', $base, 'get_available_wallets' );
        WP::add_action( 'wp_ajax_wc_shipay_cancel_order', $base, 'cancel_order_on_shipay' );
        WP::add_action( 'admin_enqueue_scripts', $base, 'admin_enqueue_scripts' );
        WP::add_action( 'admin_enqueue_scripts', $base, 'admin_enqueue_scripts_on_order' );
        WP::add_action( 'wp_loaded', $base, 'wp_loaded' );
        WP::add_action( 'woocommerce_admin_order_data_after_order_details', $base, 'admin_order_display_info', 60, 1 );
        WP::add_action( 'wp_enqueue_scripts', $base, 'plugin_scripts' );
        WP::add_action( 'before_woocommerce_init', $base, 'woocommerce_declare_compatibility' );

    }

    public function wp_loaded() {
        WP::add_action( 'wc_shipay_payment_schedule', $this, 'consult_payment_status_on_shipay' );
    }

    public function check_payment() {
        $order = wc_get_order(  $_GET[ 'order_id' ] );

        if ( $order ) {
            wp_send_json( [ 'status' => $order->get_meta( '_wc_shipay_payment_status' ) ] );
            die();
        }

        wp_die( esc_html__( 'Pedido não existe', 'wc-shipay-payment' ), '', [ 'response' => 401 ] );
    }

    public function payment_status_scheduled() {
        if ( ! wp_next_scheduled( 'wc_shipay_payment_schedule' ) ) {
            wp_schedule_event( time(), 'hourly', 'wc_shipay_payment_schedule' );
        }
    }

    public function consult_payment_status_on_shipay() {
        foreach ( $this->get_shipay_methods() as $method ) {
            $orders = $this->get_shipay_pending_order( $method->id );
            $shipay_order_status_service = new ConsultStatus($method);

            foreach ( $orders as $order ) {
                try {
                    $response = $shipay_order_status_service->check_status(
                        $order->get_meta( '_wc_shipay_payment_order_id' ),
                        $order->get_meta('_wc_shipay_payment_pix_type' )
                    );

                    if ( isset( $response['status'] ) ) {
                        $process_order = new ProcessShipayOrder( $method );

                        $order = $process_order->set_order_additional_info( $order, $response );
                        $process_order->process_order_status( $order, $response['status'] );
                    }
                } catch (\Exception $e ) {
                    $method->log->add( $method->id, "Shipay - Error consulting order status - " . $order->get_id() );
                    $method->log->add( $method->id, $e->getMessage() );
                }
            }
        }
    }

    protected function get_shipay_pending_order ( $payment_method )
    {
        $orders = wc_get_orders( [
                'limit' => -1,
                'payment_method' => $payment_method,
                'meta_key' => '_wc_shipay_payment_status',
                'meta_value' => [ ProcessShipayOrder::SHIPAY_ORDER_PENDING, ProcessShipayOrder::SHIPAY_ORDER_PENDINGV, ProcessShipayOrder::SHIPAY_ORDER_REFUND_PENDING],
                'meta_compare' => 'IN'
            ]
        );

        return count( $orders ) > 0 ? $orders : false;
    }

    public function add_gateway( array $gateways ) {
        $shipay_methods = $this->get_shipay_methods();
        return array_merge( $gateways, $shipay_methods );
    }

    public function add_gateway_block_support(  ) {

         if ( !class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
         	return;
         }

        require_once \WC_SHIPAY_PAYMENT_PLUGIN_PATH . '/src/Gateway/Methods/ShipayPixGatewayBlocksSupport.php';
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function( \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                $payment_method_registry->register( new ShipayPixGatewayBlocksSupport() );
            }
        );

        require_once \WC_SHIPAY_PAYMENT_PLUGIN_PATH . '/src/Gateway/Methods/ShipayBolepixGatewayBlocksSupport.php';
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function( \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                $payment_method_registry->register( new ShipayBolepixGatewayBlocksSupport() );
            }
        );
    }

    protected function get_shipay_methods ()
    {
        $methods[] = Methods\ShipayPixGateway::getInstance();
        $methods[] = Methods\ShipayBolepixGateway::getInstance();
        return $methods;
    }

    public function admin_enqueue_scripts( $hook )
    {
        if (
            $hook != 'woocommerce_page_wc-settings'
            || !( isset( $_GET['section'] )
                && str_contains($_GET['section'], 'wc_shipay_')
            )
        ) {
            return;
        }

        wp_enqueue_script(
            \WC_SHIPAY_PAYMENT_PLUGIN_NAME . '-settings',
            \WC_SHIPAY_PAYMENT_PLUGIN_URL . 'assets/js/admin/wallet_setting.js',
            [ 'jquery' ],
            \WC_SHIPAY_PAYMENT_PLUGIN_VERSION
        );

        wp_localize_script(
            \WC_SHIPAY_PAYMENT_PLUGIN_NAME . '-settings',
            'wc_shipay_payment_api',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'security' => wp_create_nonce('wc_shipay_payment')
            ]
        );
    }

    public function admin_enqueue_scripts_on_order( $hook )
    {
        $screen    = get_current_screen();
        $screen_id = $screen ? $screen->id : '';

        if ( $screen_id != 'shop_order' ) {
            return;
        }

        wp_enqueue_script(
            \WC_SHIPAY_PAYMENT_PLUGIN_NAME . '-order-view',
            \WC_SHIPAY_PAYMENT_PLUGIN_URL . 'assets/js/admin/order.js',
            [ 'jquery' ],
            \WC_SHIPAY_PAYMENT_PLUGIN_VERSION
        );

        wp_localize_script(
            \WC_SHIPAY_PAYMENT_PLUGIN_NAME . '-order-view',
            'wc_shipay_payment_api',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'security' => wp_create_nonce('wc_shipay_payment')
            ]
        );
    }

    public function get_available_wallets()
    {
        if (
            isset($_POST['access_key'])
            && isset($_POST['secret_key'])
            && isset($_POST['client_id'])
        ) {
            $walletService = new Wallets(
                sanitize_text_field($_POST['environment']),
                sanitize_text_field($_POST['access_key']),
                sanitize_text_field($_POST['secret_key']),
                sanitize_text_field($_POST['client_id'])
            );

            $wallets = $walletService->get_available_wallets();

            if ( $wallets && isset($wallets[0]) ) {
                wp_send_json_success(
                    ['available_wallets' =>  $wallets]
                );
            } else {
                wp_send_json_error(
                    ['message' => __('Erro ao tentar retornar carteiras disponíveis na API Shipay', 'wc-shipay-payment' )]);
            }
        } else {
            wp_send_json_error(
                ['message' => __('É necessário preencher os campos de credenciais', 'wc-shipay-payment' )]);
        }

        wp_die();
    }

    public function cancel_order_on_shipay()
    {
        if (
            isset( $_POST[ 'shipay_order_id' ] )
            && isset( $_POST[ 'order_id' ] )
        ) {
            $order = wc_get_order($_POST['order_id']);

            $payment_method = $order->get_payment_method() == 'wc_shipay_pix_payment_geteway'
                ? Methods\ShipayPixGateway::getInstance()
                : Methods\ShipayBolepixGateway::getInstance();

            $shipay_order_status_service = new ConsultStatus( $payment_method );

            $response = $shipay_order_status_service->check_status(
                $order->get_meta('_wc_shipay_payment_order_id'),
                $order->get_meta('_wc_shipay_payment_pix_type')
            );

            if (
                $response['status'] == ProcessShipayOrder::SHIPAY_ORDER_PENDING
                || $response['status'] == ProcessShipayOrder::SHIPAY_ORDER_PENDINGV
            ) {
                $shipay_cancel_service = new CancelPix( Methods\ShipayPixGateway::getInstance() );
                $response = $shipay_cancel_service->cancel_order($order->get_meta('_wc_shipay_payment_order_id'));

                if ( $response['status'] == ProcessShipayOrder::SHIPAY_ORDER_CANCELLED ) {
                    if ( isset( $response['status'] ) ) {
                        $process_order = new ProcessShipayOrder( Methods\ShipayPixGateway::getInstance()  );
                        $order->update_meta_data( '_wc_shipay_payment_status', $response['status']  );
                        $order->update_meta_data( '_wc_shipay_payment_manual_edit', 'Cancelamento realizado pelo painel da loja'  );
                        $process_order->process_order_status( $order, $response['status'] );
                    }

                    wp_send_json_success(
                        ['success' =>  'Pedido cancelado com sucesso!']
                    );
                } else {
                    wp_send_json_error(
                        ['message' => __('Houve um erro ao cancelar o pedido. Consulte os logs para mais informações', 'wc-shipay-payment' )]);
                }
            } else {
                wp_send_json_error(
                    ['message' => __('O pedido só pode ser cancelado caso ainda esteja com pagamento pendente.', 'wc-shipay-payment' )]);
            }
        }

        wp_die();
    }

    public function prevent_order_status_change( $order_id, $order ) {
        $shipay_pending_statuses = [
            ProcessShipayOrder::SHIPAY_ORDER_PENDING,
            ProcessShipayOrder::SHIPAY_ORDER_PENDINGV
        ];

        if (
            str_contains( $order->get_payment_method(), 'wc_shipay')
            && in_array( $order->get_meta('_wc_shipay_payment_status'), $shipay_pending_statuses )
        ) {
            $not_allowed_statuses = [
                'processing',
                'completed',
                'refunded'
            ];

            if (
                in_array( $order->get_meta('_wc_shipay_payment_status'), $shipay_pending_statuses )
                && in_array( $order->get_status(), $not_allowed_statuses )
            ) {
                throw new \Exception('Status não pode ser alterado. O status de pagamento deste pedido ainda é pedente.');
            }
        }
    }

    public function plugin_action_links( $links ) {
        $pluginLinks = array();

        $pix = esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_shipay_pix_payment_geteway' ) );
        $bolepix = esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_shipay_bolepix_payment_geteway' ) );
        $pluginLinks[] = sprintf( '<a href="%s">%s</a>', $pix, __( 'Configurações Pix', 'wc-shipay-payment'  ) );
        $pluginLinks[] = sprintf( '<a href="%s">%s</a>', $bolepix, __( 'Configurações Bolepix', 'wc-shipay-payment' ) );

        return array_merge( $pluginLinks, $links );
    }

    public function admin_order_display_info( $order ) {
        if ( str_contains( $order->get_payment_method(), 'wc_shipay') ) {
            $sources = new Sources();
            $infos = wc_get_template_html(
                'html-woocommerce-order-details.php',
                [
                    'order_id' => $order->get_id(),
                    'shipay_order_id' => $order->get_meta( '_wc_shipay_payment_order_id' ),
                    'shipay_current_status' => $sources->get_shipay_status_description( $order->get_meta( '_wc_shipay_payment_status' ) ),
                    'shipay_current_status_code' => $order->get_meta( '_wc_shipay_payment_status' ),
                    'shipay_payment_method' => $order->get_payment_method(),
                    'payment_manual_edit' => $order->get_meta( '_wc_shipay_payment_manual_edit' )
                ],
                WC()->template_path() . \WC_SHIPAY_PAYMENT_DIR_NAME . '/',
                WC_SHIPAY_PAYMENT_PLUGIN_PATH . 'templates/'
            );

            echo wp_kses_post( $infos );
        }
    }

    public function plugin_scripts()
    {
        wp_enqueue_script(
            \WC_SHIPAY_PAYMENT_PLUGIN_NAME . '-payment',
            \WC_SHIPAY_PAYMENT_PLUGIN_URL . 'assets/js/frontend/cpf_cnpj.js',
            ['jquery', 'jquery-mask'],
            \WC_SHIPAY_PAYMENT_PLUGIN_VERSION,
            true
        );
    }

    public function woocommerce_declare_compatibility() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                \WC_SHIPAY_PAYMENT_FILE_NAME,
                true
            );
        }
    }
}