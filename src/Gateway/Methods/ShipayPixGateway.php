<?php

namespace Shipay\Payment\Gateway\Methods;

use Shipay\Payment\Gateway\Client\ConsultStatus;
use Shipay\Payment\Gateway\Client\CreatePayment;
use Shipay\Payment\Gateway\Client\RefundPix;
use Shipay\Payment\Gateway\Transactions\PixTransaction;
use Shipay\Payment\Utils\Helper;
use Shipay\Payment\Utils\Sources;
use Shipay\Payment\Utils\ProcessShipayOrder;
use WC_Payment_Gateway;
use WC_Logger;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Pix Gateway class
 */
class ShipayPixGateway extends WC_Payment_Gateway
{

    private static $instance;

    public $debug = 0;

    public $environment;

    public $access_key;

    public $secret_key;

    public $client_id;

    public $wallet;

    public $helper;

    public $checkout_instructions;

    public $order_received_intructions;

    public $pix_expiration_seconds;

    public $new_order_status;

    public $after_paid_status;

    public $not_paid_status;

    public $async;

    public $log;

    public $shipay_utils_sources;

    public $shipay_update_order;

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        self::$instance = $this;

        global $current_section;

        $this->id = 'wc_shipay_pix_payment_geteway';
        $this->icon = false;
        $this->has_fields = true;
        $this->method_title = 'Pix';
        $this->method_description = 'Pagamento via PIX - Shipay';
        $this->supports = ['products', 'refunds'];
        $this->shipay_utils_sources = new Sources();

        // Method with all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        //Main settings
        $this->setup_settings();


        // Active logs.
        if ('yes' === $this->is_debug() ) {
            $this->log = new WC_Logger();
        }

        $this->helper = new Helper();
        $this->shipay_update_order = new ProcessShipayOrder($this);

        if (
            isset($_GET['page'])
            && $_GET['page'] == 'wc-settings'
            && isset($_GET['section'])
            && $_GET['section'] == 'wc_shipay_pix_payment_geteway'
        ) {
            $update_settings = get_option($this->get_option_key(), []);
            update_option($this->get_option_key(),
                apply_filters('woocommerce_settings_api_sanitized_fields_' . $this->id, $update_settings),
                'yes'
            );
        }

        // Actions.
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action( 'woocommerce_thankyou_' . $this->id, [$this, 'payment_page']);
        add_action( 'woocommerce_order_details_before_order_table', [$this, 'order_view_page']);
        add_action( 'woocommerce_api_' . $this->id, [$this, 'webhook_notification']);
        add_action( 'woocommerce_init', [ $this, 'init' ] );
        add_action( 'wp_enqueue_scripts', [$this, 'pix_script']);
    }

    /**
     * Update admin options
     *
     * @return void|bool
     * @since 1.1.0
     */
    public function process_admin_options()
    {
        $update_settings = get_option($this->get_option_key(), []);

        if (!is_array($update_settings))
            $update_settings = [];

        $title = filter_input(INPUT_POST, $this->get_field_name('title'), FILTER_SANITIZE_STRING);
        $environment = filter_input(INPUT_POST, $this->get_field_name('environment'), FILTER_SANITIZE_STRING);
        $access_key = filter_input(INPUT_POST, $this->get_field_name('access_key'), FILTER_SANITIZE_STRING);
        $secret_key = filter_input(INPUT_POST, $this->get_field_name('secret_key'), FILTER_SANITIZE_STRING);
        $client_id = filter_input(INPUT_POST, $this->get_field_name('client_id'), FILTER_SANITIZE_STRING);
        $wallet = filter_input(INPUT_POST, $this->get_field_name('wallet'), FILTER_SANITIZE_STRING);
        $checkout_instructions = filter_input(INPUT_POST, $this->get_field_name('checkout_instructions'), FILTER_SANITIZE_STRING);
        $order_received_instructions = filter_input(INPUT_POST, $this->get_field_name('order_received_instruction'), FILTER_SANITIZE_STRING);
        $pix_expiration_seconds = filter_input(INPUT_POST, $this->get_field_name('pix_expiration_seconds'), FILTER_SANITIZE_STRING);
        $new_order_status = filter_input(INPUT_POST, $this->get_field_name('new_order_status'), FILTER_SANITIZE_STRING);
        $after_paid_status = filter_input(INPUT_POST, $this->get_field_name('after_paid_status'), FILTER_SANITIZE_STRING);
        $not_paid_status = filter_input(INPUT_POST, $this->get_field_name('not_paid_status'), FILTER_SANITIZE_STRING);
        $debug = filter_input(INPUT_POST, $this->get_field_name('debug'), FILTER_SANITIZE_STRING);

        $update_settings['title'] = $title;
        $update_settings['environment'] = $environment;
        $update_settings['access_key'] = $access_key;
        $update_settings['secret_key'] = $secret_key;
        $update_settings['client_id'] = $client_id;
        $update_settings['wallet'] = $wallet;
        $update_settings['checkout_instructions'] = $checkout_instructions;
        $update_settings['order_received_instruction'] = $order_received_instructions;
        $update_settings['pix_expiration_seconds'] = $pix_expiration_seconds;
        $update_settings['new_order_status'] = $new_order_status;
        $update_settings['after_paid_status'] = $after_paid_status;
        $update_settings['not_paid_status'] = $not_paid_status;
        $update_settings['debug'] = isset($debug) ? 'yes' : 'no';

        return update_option(
            $this->get_option_key(),
            apply_filters('woocommerce_settings_api_sanitized_fields_' . $this->id, $update_settings),
            'yes'
        );
    }

    /**
     * Setup settings form fields.
     *
     * @return void
     * @since 1.1.0
     */
    public function init_form_fields()
    {
        $_current_wallet_option = [];
        if ($this->get_option('wallet')) {
            $_current_wallet_option = [
                $this->get_option('wallet') => $this->get_option('wallet')
            ];
        }

        $this->form_fields = [
            'title' => [
                'title' => __('Título', 'pix-e-bolepix-por-shipay'),
                'type' => 'text',
                'description' => __('Title that must be displayed for customer', 'pix-e-bolepix-por-shipay'),
                'default' => '',
                'custom_attributes' => [
                    'required' => 'required',
                ],
            ],
            'environment' => [
                'title' => __('Ambiente', 'pix-e-bolepix-por-shipay'),
                'type' => 'select',
                'default' => '',
                'options' => $this->shipay_utils_sources->environment_options(),
                'custom_attributes' => [
                    'required' => 'required',
                    'shipay-environment' => 'true'
                ],
            ],
            'access_key' => [
                'title' => __('Access Key', 'pix-e-bolepix-por-shipay'),
                'type' => 'text',
                'description' => sprintf(
                    'As etapas para obter as credenciais podem ser encontradas em %s.',
                    '<a href="https://shipay.freshdesk.com/support/solutions/articles/154000126973-painel-shipay-chaves-de-integrac%C3%A3o-onde-extrair-">' . __('Obter credenciais de API', 'pix-e-bolepix-por-shipay') . '</a>'
                ),
                'default' => '',
                'custom_attributes' => [
                    'required' => 'required',
                    'shipay-credential-field-type' => 'true',
                    'shipay-credential-access-key' => 'true'
                ],
            ],
            'secret_key' => [
                'title' => __('Secret Key', 'pix-e-bolepix-por-shipay'),
                'type' => 'text',
                'default' => '',
                'custom_attributes' => [
                    'required' => 'required',
                    'shipay-credential-field-type' => 'true',
                    'shipay-credential-secret-key' => 'true'
                ],
            ],
            'client_id' => [
                'title' => __('Client ID', 'pix-e-bolepix-por-shipay'),
                'type' => 'text',
                'default' => '',
                'custom_attributes' => [
                    'required' => 'required',
                    'shipay-credential-field-type' => 'true',
                    'shipay-credential-client-id' => 'true'
                ],
            ],
            'wallet' => [
                'title' => __('Wallet', 'pix-e-bolepix-por-shipay'),
                'type' => 'select',
                'default' => '',
                'description' => __('Preencha as credenciais para poder selecionar a carteira', 'pix-e-bolepix-por-shipay'),
                'options' => $_current_wallet_option,
                'custom_attributes' => [
                    'required' => 'required',
                    'shipay-available-wallets' => 'true'
                ],
            ],
            'checkout_instructions' => [
                'title' => __('Instruções de check-out', 'pix-e-bolepix-por-shipay'),
                'type' => 'text',
                'default' => '',
                'custom_attributes' => [
                    'required' => 'required',
                ],
            ],
            'order_received_instructions' => [
                'title' => __('Instruções para Pedido Recebido', 'pix-e-bolepix-por-shipay'),
                'type' => 'text',
                'default' => '',
                'custom_attributes' => [
                    'required' => 'required',
                ],
            ],
            'pix_expiration_seconds' => [
                'title' => __('Segundos de Expiração do Pix', 'pix-e-bolepix-por-shipay'),
                'type' => 'number',
                'description' => __('Para pagamentos com tempo de expiração menor que 60 minutos será utilizado o pix instantâneo.', 'pix-e-bolepix-por-shipay'),
                'default' => '',
                'custom_attributes' => [
                    'min' => 3600,
                ]
            ],
            'new_order_status' => [
                'title' => __('Status do Novo Pedido', 'pix-e-bolepix-por-shipay'),
                'type' => 'select',
                'description' => __('Defina o status quando o pedido for criado.', 'pix-e-bolepix-por-shipay'),
                'default' => '',
                'options' => wc_get_order_statuses(),
                'custom_attributes' => [
                    'required' => 'required',
                ],
            ],
            'after_paid_status' => [
                'title' => __('Status do Pedido Após o Pagamento', 'pix-e-bolepix-por-shipay'),
                'type' => 'select',
                'description' => __('Defina o status do pedido após o pagamento.', 'pix-e-bolepix-por-shipay'),
                'default' => '',
                'options' => wc_get_order_statuses(),
                'custom_attributes' => [
                    'required' => 'required',
                ],
            ],
            'not_paid_status' => [
                'title' => __('Status para Pedido não Pago', 'pix-e-bolepix-por-shipay'),
                'type' => 'select',
                'description' => __('Status para pedidos que não foram pagos e o pagamento expirou.', 'pix-e-bolepix-por-shipay'),
                'default' => '',
                'options' => wc_get_order_statuses(),
                'custom_attributes' => [
                    'required' => 'required',
                ],
            ],
//            'debug' => [
//                'title' => __('Debug Log', 'pix-e-bolepix-por-shipay'),
//                'type' => 'checkbox',
//                'label' => __('Ativar logs', 'pix-e-bolepix-por-shipay'),
//                'default' => 'no',
//                'description' => sprintf(
//                    __('Verifique os logs em %s', 'pix-e-bolepix-por-shipay'),
//                    '<a href="' . esc_url(admin_url('admin.php?page=wc-status&tab=logs&log_file=' . esc_attr($this->id) . '-' . sanitize_file_name(wp_hash($this->id)) . '.log')) . '">' . __('System Status &gt; Logs', 'pix-e-bolepix-por-shipay') . '</a>'
//                ),
//            ]
        ];
    }

    /**
     * Payment fields.
     */
    public function payment_fields()
    {
        if ($description = $this->get_description()) {
            echo wp_kses_post(wpautop(wptexturize($description)));
        }

        wc_get_template(
            'html-woocommerce-payment-fields.php',
            [
                'description' => $this->get_description(),
                'checkout_instructions' => $this->checkout_instructions
            ],
            WC()->template_path() . \SHIPAY_PAYMENT_DIR_NAME . '/',
            SHIPAY_PAYMENT_PLUGIN_PATH . 'templates/'
        );
    }

    /**
     * Set settings function
     *
     * @return void
     * @since 1.0.0
     */
    public function setup_settings()
    {
        // Define user set variables.
        $this->title = $this->get_option('title', 'Pix Shipay');
        $this->environment = $this->get_option('environment');
        $this->secret_key = $this->get_option('secret_key');
        $this->access_key = $this->get_option('access_key');
        $this->client_id = $this->get_option('client_id');
        $this->wallet = $this->get_option('wallet');
        $this->checkout_instructions = $this->get_option('checkout_instructions',
            __('O QRCode será exibido após você finalizar o pedido.', 'pix-e-bolepix-por-shipay'));
        $this->order_received_intructions = $this->get_option('order_received_instructions',
            __('Pague usando o QRCode ou a opção Copiar e Colar.', 'pix-e-bolepix-por-shipay'));
        $this->pix_expiration_seconds = $this->get_option('pix_expiration_seconds');
        $this->new_order_status = $this->get_option('new_order_status', 'wc-pending');
        $this->after_paid_status = $this->get_option('after_paid_status', 'wc-processing');
        $this->not_paid_status = $this->get_option('not_paid_status', 'wc-cancelled');
        $this->debug = $this->get_option('debug');
        $this->async = $this->get_option('async');
    }

    /**
     * Get name of fields
     *
     * @return string
     * @since 1.1.0
     */
    protected function get_field_name(string $field = '')
    {
        return 'woocommerce_' . $this->id . '_' . $field;
    }

    /**
     * Process the payment.
     *
     * @param int $order_id Order ID.
     *
     * @return array Redirect data.
     */
    public function process_payment( $order_id )
    {
        try {
            if (
                !isset( $_POST['shipay_pix_nonce'] )
                || !wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['shipay_pix_nonce'] ) ), 'shipay_pix_nonce')
            ) {
                wp_die( esc_html__('Erro de validação ao tentar fazer o pedido.', 'pix-e-bolepix-por-shipay'), '', ['response' => 403]);
            }

            $shipay_pix_document = isset($_POST['shipay_pix_document']) ? sanitize_text_field($_POST['shipay_pix_document']) : '';

            if (!$shipay_pix_document) {
                throw new \Exception(__('It is necessary to fill the document field', 'pix-e-bolepix-por-shipay'));
            }

            $order = wc_get_order( $order_id );
            $pix_transaction = new PixTransaction(
                $this,
                $order,
                $shipay_pix_document
            );

            $pix_service = new CreatePayment($this);

            $transaction_body = $pix_transaction->get_pix_transaction();
            $response = $pix_service->create_payment($transaction_body);

            if (isset($response['code']) && $response['code'] > 203) {
                throw new \Exception(__('Erro ao processar pagamento. Confira os dados inseridos.', 'pix-e-bolepix-por-shipay'));
            }

            $this->update_order_pix_info($order, $response);

            // Empty the cart.
            WC()->cart->empty_cart();

            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url( $order ),
            );
        } catch (\Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            return;
        }
    }

    public function process_refund($order_id, $amount = null, $reason = '')
    {
        try {
            $order = wc_get_order($order_id);

            if ($order && $order->get_meta('_wc_shipay_payment_order_id')) {
                $refund_service = new RefundPix($this);
                $response = $refund_service->refund_order($order->get_meta('_wc_shipay_payment_order_id'), $amount);
                if ($response && isset($response['status'])) {
                    $order->update_meta_data( '_wc_shipay_payment_manual_edit', 'Estorno Shipay: Pedido estornado na Shipay'  );
                    $this->update_order_pix_info($order, $response);
                    $order->save();
                } else {
                    throw new \Exception(__('Erro ao tentar estornar o pedido na Shipay', 'pix-e-bolepix-por-shipay'), 1);
                }
            } else {
                throw new \Exception(__('Pedido não encontrado', 'pix-e-bolepix-por-shipay'), 1);
            }

            return [
                'result' => 'success',
                'message' => __('Pedido extornado com sucesso.', 'pix-e-bolepix-por-shipay')
            ];
        } catch (\Error $e) {
            throw $e;
        }
    }

    protected function update_order_pix_info($order, $pix_info)
    {
        $this->shipay_update_order->set_pix_additional_info($order, $pix_info);
        $this->shipay_update_order->process_order_status($order, $pix_info['status']);
        $order->save();
    }

    /**
     * Thank You page message.
     *
     * @param int $order_id Order ID.
     */
    public function payment_page($order_id)
    {
        $order = wc_get_order($order_id);
        $qr_code = $order->get_meta('_wc_shipay_payment_qr_code');
        $expiration_date = $order->get_meta('_wc_shipay_payment_expiration_date');
        $qr_code_image = $order->get_meta('_wc_shipay_payment_qr_code_image');
        $date = $this->helper->get_date();

        $expiration_seconds = $this->helper->get_diff_betwen_dates($date, $expiration_date);
        $payment_received_visibility = $order->get_meta('_wc_shipay_payment_status') == ProcessShipayOrder::SHIPAY_ORDER_APPROVED
            ? 'block' : 'none';

        wc_get_template(
            'html-woocommerce-pix-payment-page.php',
            [
                'qr_code' => $qr_code,
                'order_received_instructions' => $this->order_received_intructions,
                'order' => $order,
                'qr_code_image' => $qr_code_image,
                'order_key' => $order->get_order_key(),
                'expiration_date' => $expiration_date,
                'expiration_seconds' => $expiration_seconds,
                'payment_received_visibility' => $payment_received_visibility,
                'status' => $order->get_meta('_wc_shipay_payment_status')
            ],
            WC()->template_path() . \SHIPAY_PAYMENT_DIR_NAME . '/',
            SHIPAY_PAYMENT_PLUGIN_PATH . 'templates/'
        );
    }

    /**
     * Pix QR Code in Order View
     *
     * @return void
     * @since 1.1.0
     */
    public function order_view_page($order)
    {
        if (
            $order->get_status() == $this->new_order_status
            && $order->get_payment_method() == $this->id
            && is_wc_endpoint_url('view-order')
        ) {
            do_action('woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id());
        }
    }


    public function pix_script()
    {
        wp_enqueue_script(
            \SHIPAY_PAYMENT_PLUGIN_NAME . 'pix-payment',
            \SHIPAY_PAYMENT_PLUGIN_URL . 'assets/js/frontend/pix.js',
            ['jquery'],
            \SHIPAY_PAYMENT_PLUGIN_VERSION,
            true
        );

        wp_localize_script(\SHIPAY_PAYMENT_PLUGIN_NAME . 'pix-payment',
            'shipay_payment', array(
                'rest_url' => esc_url(rest_url('shipay/v1/order_status?order_id=')),
                'shipay_check_payment_nonce' => wp_create_nonce('shipay_check_payment_nonce')
            ));

        wp_enqueue_style(
            \SHIPAY_PAYMENT_PLUGIN_NAME . 'pix-payment-style',
            \SHIPAY_PAYMENT_PLUGIN_URL . 'assets/css/pix.css',
            [],
            \SHIPAY_PAYMENT_PLUGIN_VERSION
        );
    }

    /**
     * Is Debug function
     */
    public function is_debug()
    {
//        return 'yes' === $this->debug ? true : false;
        return 'yes';
    }


    public function webhook_notification()
    {
        $this->shipay_update_order->webhook_handler();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
