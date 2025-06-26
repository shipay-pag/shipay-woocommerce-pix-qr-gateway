jQuery(document).ready(function($) {
    $('[shipay-credential-field-type="true"]').on('change', function() {
        if (
            $('[shipay-credential-access-key="true"]').val()
            &&  $('[shipay-credential-secret-key="true"]').val()
            && $('[shipay-credential-client-id="true"]').val()
        ) {
            get_available_wallets();
        }
    });

    function get_available_wallets() {
        $.ajax({
            url: wc_shipay_payment_api.ajax_url,
            type: 'POST',
            data: {
                action: 'wc_shipay_payment_wallets',
                security: wc_shipay_payment_api.security,
                environment: $('[shipay-environment="true"]').val(),
                access_key: $('[shipay-credential-access-key="true"]').val(),
                secret_key: $('[shipay-credential-secret-key="true"]').val(),
                client_id: $('[shipay-credential-client-id="true"]').val(),
                _ajax_nonce: wc_shipay_payment_api.shipay_consult_wallet_nonce
            },
            success: function(response) {
                $('[shipay-available-wallets="true"]').empty();

                if (response.data.available_wallets) {
                    $.each(response.data.available_wallets, function(index, value) {
                        $('[shipay-available-wallets="true"]').append($('<option>', {
                            value: value.wallet,
                            text: value.friendly_name + " (" + value.wallet + ")"
                        }));
                    });
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
