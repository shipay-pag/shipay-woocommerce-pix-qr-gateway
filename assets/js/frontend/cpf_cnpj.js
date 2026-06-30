jQuery(document).ready(function($) {
    const field_selectors = '[name="shipay_bolepix_document"], [name="shipay_pix_document"]';
    function validarCPF(cpf) {
        cpf = cpf.replace(/[^\d]+/g, '');
        if (cpf == '') return false;
        // Elimina CPFs inválidos conhecidos
        if (cpf.length != 11 ||
            cpf == "00000000000" ||
            cpf == "11111111111" ||
            cpf == "22222222222" ||
            cpf == "33333333333" ||
            cpf == "44444444444" ||
            cpf == "55555555555" ||
            cpf == "66666666666" ||
            cpf == "77777777777" ||
            cpf == "88888888888" ||
            cpf == "99999999999")
            return false;

        var add = 0;
        for (var i = 0; i < 9; i++)
            add += parseInt(cpf.charAt(i)) * (10 - i);

        var rev = 11 - (add % 11);
        if (rev == 10 || rev == 11)
            rev = 0;
        if (rev != parseInt(cpf.charAt(9)))
            return false;

        add = 0;
        for (var i = 0; i < 10; i++)
            add += parseInt(cpf.charAt(i)) * (11 - i);
        rev = 11 - (add % 11);
        if (rev == 10 || rev == 11)
            rev = 0;
        if (rev != parseInt(cpf.charAt(10)))
            return false;
        return true;
    }

    function validarCNPJ(cnpj) {
        cnpj = cnpj.replace(/[.\/-]/g, '').toUpperCase();

        if (!/^[A-Z0-9]{12}\d{2}$/.test(cnpj))
            return false;

        if (/^(\d)\1{13}$/.test(cnpj))
            return false;

        function calcularDigito(base, pesos) {
            var soma = 0;
            for (var i = 0; i < base.length; i++) {
                soma += (base.charCodeAt(i) - 48) * pesos[i];
            }

            var resto = soma % 11;
            return resto < 2 ? 0 : 11 - resto;
        }

        var base = cnpj.substring(0, 12);
        var primeiroDigito = calcularDigito(base, [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        if (primeiroDigito != parseInt(cnpj.charAt(12)))
            return false;

        var segundoDigito = calcularDigito(base + primeiroDigito, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        return segundoDigito == parseInt(cnpj.charAt(13));
    }

    function validarCampoCPFouCNPJ( field ) {
        var campo = $(field);
        var valor = campo.val().replace(/[.\/-]/g, '').toUpperCase();

        if (!/[A-Z]/.test(valor) && valor.length <= 11) {
            // Valida CPF
            if (!validarCPF(valor)) {
                alert('CPF inválido.');
                return false;
            }
        } else {
            // Valida CNPJ
            if (!validarCNPJ(valor)) {
                alert('CNPJ inválido.');
                return false;
            }
        }
        return true;
    }

    function aplicarMascara(field, valor) {
        var documento = valor.replace(/[^A-Z0-9]/gi, '');
        var mascaraCNPJ = /[A-Z]/i.test(documento) || documento.length > 11;

        $(field).mask(
            mascaraCNPJ ? 'AA.AAA.AAA/AAAA-00' : '000.000.000-00',
            {
                translation: {
                    'A': {pattern: /[A-Z0-9]/i}
                }
            }
        );
    }

    $('form.checkout, form#order_review').on('submit', function(e) {
        let selectedMethod = jQuery('[name="payment_method"]:checked').val();

        if (
            selectedMethod == 'wc_shipay_bolepix_payment_geteway'
            || selectedMethod == 'wc_shipay_pix_payment_geteway'
        ) {
            let taxvatField = selectedMethod == 'wc_shipay_bolepix_payment_geteway'
                ? '[name="shipay_bolepix_document"]'
                : '[name="shipay_pix_document"]';

            if (!validarCampoCPFouCNPJ( taxvatField )) {
                e.preventDefault();
            }
        }
    });

    $(document).on('keydown', field_selectors, function (e) {
        var key = e.key.length === 1 ? e.key : '';
        aplicarMascara(this, $(this).val().concat(key));
    });

    $(document).on('paste', field_selectors, function (e) {
        var clipboard = e.originalEvent && e.originalEvent.clipboardData;
        var value = clipboard ? clipboard.getData('text') : '';
        aplicarMascara(this, $(this).val().concat(value));
    });

    $(document).on('input', field_selectors, function () {
        this.value = this.value.toUpperCase();
    });
});
