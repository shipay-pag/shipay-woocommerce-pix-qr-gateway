<?php
/**
 * @var \WC_Order $order
 */

defined('ABSPATH') || exit;
ob_start();
?>

<div class="shipay-pix-container">
    <input type="hidden" id="shipay-current-order-id" value="<?php echo esc_attr( $order->get_id() ) ?>">
    <input type="hidden" id="shipay-current-order-status" value="<?php echo esc_attr( $status ) ?>">
    <div class="pix-img-code" style="width: 200px;">
        <img src="<?php echo esc_attr( $qr_code_image ); ?>" class="shipay-pix-qrcode-img" />
    </div>

    <div class="shipay-pix-qr-code">
        <input type="text" id="shipay_pix_code" value="<?php echo esc_attr( $qr_code ); ?>" disabled readonly/>
        <button class="button copy-qr-code" id="shipay_pix_copy_button">Clique aqui para copiar o código</button>
    </div>

    <div id="shipay-pix-countdown" style="display: none">
        <input type="hidden" id="shipay-pix-expiration-seconds" value="<?php echo esc_attr( $expiration_seconds ) ?>">
        <span>QRCode válido por: <span id="minutes"></span>:<span id="seconds"></span></span>
    </div>

    <div id="shipay-pix-payment-received" style="display: <?php echo esc_attr( $payment_received_visibility ) ?>">
        <h2>Pagamento recebido com sucesso!</h2>
    </div>

    <div id="shipay-pix-payment-expired" style="display: none">
        <h2>Pagamento expirado.</h2>
    </div>
</div>


<style>
    .shipay-pix-container {
        text-align: -webkit-center;
    }

    .shipay-pix-qr-code > input {
        width: -webkit-fill-available;
    }
</style>
