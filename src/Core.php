<?php

namespace Shipay\WcShipayPayment;

use Shipay\WcShipayPayment\Gateway\BaseGateway;
use Shipay\WcShipayPayment\Utils\Helper as WP;

if (!defined('ABSPATH')) {
    exit;
}

class Core
{
    /**
     * Initialize the plugin public actions.
     */
    public function __construct()
    {
        WP::add_action( 'plugins_loaded', $this, 'init' );
    }

    public function init()
    {
        if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
            // Cannot start plugin
            return;
        }

        // Startup gateway
        BaseGateway::init();
    }
}