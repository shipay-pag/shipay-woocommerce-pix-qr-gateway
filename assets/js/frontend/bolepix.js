jQuery(document).ready(function($) {
    $('#shipay_bolepix_pix_copy_button').on('click', function () {
        copy_shipay_bolepix();
    });

    function copy_shipay_bolepix() {
        document.getElementById('shipay_bolepix_pix_copy_button').innerHTML = 'Copiado!';
        navigator.clipboard.writeText(jQuery('#shipay_bolepix_code').val());
        setTimeout(()=> {
            document.getElementById('shipay_bolepix_pix_copy_button').innerHTML = 'Copiar Pix Copia e Cola';
        },1000)
    }
});
