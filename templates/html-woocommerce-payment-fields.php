<?php
defined('ABSPATH') || exit;

printf('<p class="mgn-description">%s</p>', nl2br(esc_html($checkout_instructions)));
?>

<fieldset id="wc-shipay-payment-form" class="wc-shipay-payment-form wc-payment-form"
          style="background:transparent; border: none;">

    <div class="form-row form-row-wide">
        <label for="shipay_pix_document">CPF/CNPJ <span class="required">*</span></label>
        <input id="shipay_pix_document" class="input-text" inputmode="numeric" name="shipay_pix_document" type="text"
               placeholder="___.___.___-__" autocomplete="off">
    </div>
</fieldset>
