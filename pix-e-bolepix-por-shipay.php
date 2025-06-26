<?php
/**
 * Plugin Name: Pix e Bolepix por Shipay
 * Description: Integração do WooCommerce com Shipay para pagamentos via Pix e BolePix.
 * Version: 1.0.1
 * Requires at least: 5.4
 * Requires PHP: 7.2
 * WC requires at least: 5.0
 * WC tested up to: 9.4
 * Author: Shipay
 * Text Domain: pix-e-bolepix-por-shipay
 * Author: Shipay
 * Author URI: https://www.shipay.com.br/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

if (!defined( 'ABSPATH' )) {
    exit;
}

//Define globals
define( 'SHIPAY_PAYMENT_PLUGIN_NAME', 'pix-e-bolepix-por-shipay' );
define( 'SHIPAY_PAYMENT_PLUGIN_VERSION', '1.0.1' );
define( 'SHIPAY_PAYMENT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SHIPAY_PAYMENT_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'SHIPAY_PAYMENT_BASE_NAME', plugin_basename( __FILE__ ) );
define( 'SHIPAY_PAYMENT_DIR_NAME', dirname( plugin_basename( __FILE__ ) ) );
define( 'SHIPAY_PAYMENT_FILE_NAME', __FILE__ );

require SHIPAY_PAYMENT_PLUGIN_PATH . 'vendor/autoload.php';

/**
 * @since 1.0.0
 *
 * @return Shipay\Payment\Core
 */
function shipay_payment() {
    /**
     * @var \Shipay\Payment\Core
     */
    static $core;

    if ( !isset( $core ) ) {
        $core = new \Shipay\Payment\Core();
    }

    return $core;
}

shipay_payment();