jQuery(document).ready(function($) {
    $('#shipay_pix_copy_button').on('click', function () {
        copy_shipay_pix();
    });

    function copy_shipay_pix() {
        let self = this;

        document.getElementById('shipay_pix_copy_button').innerHTML = 'Copiado!';
        navigator.clipboard.writeText($('#shipay_pix_code').val());
        setTimeout(()=> {
            document.getElementById('shipay_pix_copy_button').innerHTML = 'Copiar Pix Copia e Cola';
        },1000)
    }

    function start_payment_countdown( countdown_time ) {
        if ( countdown_time <= 0 ) {
            return check_payment_status();
        }

        $('#shipay-pix-countdown').css('display', 'block' );

        // Update the countdown every 1 second
        var countdownFunction = setInterval(function() {
            // Calculate hours, minutes, and seconds
            var minutes = Math.floor( countdown_time / 60);
            var seconds = countdown_time % 60;

            // Add leading zeros to minutes and seconds if needed
            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            // Output the result in the elements with respective IDs
            document.getElementById("minutes").innerHTML = minutes;
            document.getElementById("seconds").innerHTML = seconds;

            // If the countdown reaches zero, display a message
            if (countdown_time <= 0) {
                clearInterval(countdownFunction);
                cancel_order( true )
            }

            // Decrement the countdown time by 1 second
            countdown_time--;

        }, 1000);
    }

    function check_payment_status( ) {
        var check_payment = setInterval(function () {
            $.get( woocommerce_params.ajax_url, {
                'action': 'wc_shipay_payment_check',
                'order_id': $('#shipay-current-order-id').val()
            }).done( function(data) {
                if ( data.status == 'approved' ) {
                    clearInterval(check_payment);
                    display_order_paid();
                    return;
                } else if ( data.status == 'cancelled' || data.status == 'expired' ) {
                    clearInterval(check_payment);
                    display_order_expired();
                    return;
                }
            });
        }, 5000);
    }

    function display_order_paid() {
        $('#shipay-pix-payment-received').css( 'display', 'block' );
        $('#shipay-pix-countdown').css( 'display', 'none' );
    }

    function display_order_expired() {
        $('#shipay-pix-payment-expired').css( 'display', 'block' );
        $('#shipay-pix-countdown').css( 'display', 'none' );
    }

    function display_order_refunded() {
        $('#shipay-pix-payment-expired').css( 'display', 'block' );
        $('#shipay-pix-countdown').css( 'display', 'none' );
    }

    if ( $('#shipay-current-order-id').length ) {
        start_payment_countdown($('#shipay-pix-expiration-seconds').val());
        check_payment_status();
    }
});
