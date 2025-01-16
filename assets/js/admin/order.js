jQuery(document).ready(function($) {
    $('#shipay-cancel-order-button').on('click', function (e) {

        cancel_order_on_shipay(
            $(e.target).attr('shipay-order-id'),
            $(e.target).attr('order-id')
        )
    });

    function cancel_order_on_shipay( shipay_order_id, order_id) {
        $.ajax({
            url: wc_shipay_payment_api.ajax_url,
            type: 'POST',
            data: {
                action: 'wc_shipay_cancel_order',
                security: wc_shipay_payment_api.security,
                shipay_order_id: shipay_order_id,
                order_id: order_id
            },
            success: function(response) {
                if (response.data.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('Failed to fetch data. Please check the API.');
            }
        });
    }
});
