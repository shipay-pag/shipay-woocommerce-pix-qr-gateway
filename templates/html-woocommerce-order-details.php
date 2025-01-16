<?php
defined('ABSPATH') || exit;
?>
<div class="form-field form-field-wide">
    <h3>Shipay</h3>

    <div>
        <strong>Código do Pedido:</strong>
        <span><?php echo esc_html( $shipay_order_id ) ?></span>
    </div>

    <div>
        <strong>Status do Pedido:</strong>
        <span><?php echo esc_html( $shipay_current_status ) ?></span>
    </div>

    <?php if ( $shipay_current_status_code == 'pending' || $shipay_current_status_code == 'pendingv' ) : ?>
        <button type="button" class="button" id="shipay-cancel-order-button"
                order-id="<?php echo esc_html( $order_id ) ?>" shipay-order-id="<?php echo esc_html( $shipay_order_id ) ?>">Cancelar na Shipay</button>
    <?php endif; ?>

    <?php if ( $payment_manual_edit ): ?>
        <div>
            <strong>Observação do Pedido:</strong>
            <span><?php echo esc_html( $payment_manual_edit ) ?></span>
        </div>
    <?php endif; ?>
</div>
