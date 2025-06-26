<?php
defined('ABSPATH') || exit;

printf('<p class="mgn-description">%s</p>', nl2br(esc_html($checkout_instructions)));
?>

<fieldset id="pix-e-bolepix-por-shipay-form" class="pix-e-bolepix-por-shipay-form wc-payment-form"
          style="background:transparent; border: none;">
    <?php echo wp_nonce_field('shipay_pix_nonce', 'shipay_pix_nonce') ?>

    <div class="form-row form-row-wide">
        <label for="shipay_pix_document">CPF/CNPJ <span class="required">*</span></label>
        <input id="shipay_pix_document" class="input-text" inputmode="numeric" name="shipay_pix_document" type="text"
               placeholder="___.___.___-__" autocomplete="off">
    </div>
</fieldset>
