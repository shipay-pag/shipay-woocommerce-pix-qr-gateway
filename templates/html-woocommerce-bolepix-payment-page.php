<?php
/**
 * @var \WC_Order $order
 */

defined('ABSPATH') || exit;

if ($order) {
    $paid = $order->get_meta('_wc_shipay_payment_paid') === 'yes' ? true : false;
}

ob_start();
?>

<div class="shipay-bolepix-container">
    <div class="shipay-bolepix-pdf">
        <a href="<?php echo esc_html( $pdf_url ); ?>" class="button" target="_blank" >
            <span>Clique aqui para baixar o boleto.</span>
        </a>
    </div>

    <div class="pix-img-code" style="width: 200px;">
        <span>Ou pague usando Pix</span>
        <img src="<?php echo esc_html( $qr_code_image ); ?>" class="shipay-pix-qrcode-img" />
    </div>

    <div class="shipay-bolepix-qr-code">
        <input type="text" id="shipay_bolepix_code" value="<?php echo esc_html($qr_code); ?>" disabled readonly/>
        <button class="button copy-qr-code" onclick="copy_shipay_bolepix()" id="shipay_bolepix_pix_copy_button">Clique aqui para copiar o código pix</button>
    </div>
</div>

