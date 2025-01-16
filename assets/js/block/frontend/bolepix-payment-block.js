let bolepix_shipay_data = window.wc.wcSettings.getSetting( 'wc_shipay_bolepix_payment_geteway_data', {} );
let bolepix_shipay_label = window.wp.htmlEntities.decodeEntities( bolepix_shipay_data.title ) || window.wp.i18n.__( 'Shipay', 'shipay' );

let BolepixDocumentInputField = ({ id, label,value,onChange }) => {
    return React.createElement(
        'div',
        { className: 'wc-block-components-text-input is-active' },
        React.createElement(
            'label',
            {htmlFor: id},
            label,
            React.createElement(
                'span',
                { className: 'required' },
                '*'
            )
        ),
        React.createElement(
            'input',
            {
                type: 'text',
                id: id,
                name: id,
                required: true,
                placeholder: 'CPF/CNPJ',
                value: value,
                onChange: onChange,
                maxLength: 20
            }
        ),
    );
};

let BolepixContent = ( props ) => {
    let { eventRegistration, emitResponse } = props;
    let { onPaymentProcessing } = eventRegistration;
    let [documentNumber, setDocumentNumber] = React.useState('');

    let handleDocumentChange = (event) => {
        setDocumentNumber(event.target.value);
    };

    React.useEffect( () => {
        const unsubscribe = onPaymentProcessing( async () => {
            const myGatewayCustomData = jQuery('#shipay_bolepix_document').val();
            const customDataIsValid = !! myGatewayCustomData.length;

            if ( customDataIsValid ) {
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: {
                            'shipay_bolepix_document': myGatewayCustomData,
                        },
                    },
                };
            }

            return {
                type: emitResponse.responseTypes.ERROR,
                message: 'There was an error',
            };
        } );
        // Unsubscribes when this component is unmounted.
        return () => {
            unsubscribe();
        };
    }, [
        emitResponse.responseTypes.ERROR,
        emitResponse.responseTypes.SUCCESS,
        onPaymentProcessing,
    ] );

    return React.createElement('div', null,
        React.createElement('p', null, window.wp.htmlEntities.decodeEntities(bolepix_shipay_data.description ||  '')),
        React.createElement(
            BolepixDocumentInputField,
            {
                id: 'shipay_bolepix_document',
                label: 'CPF/CNPJ',
                value: documentNumber,
                class: 'required',
                onChange: handleDocumentChange
            }
        )
    );
};

let bolepix_shipay = {
    name: 'wc_shipay_bolepix_payment_geteway',
    label: bolepix_shipay_label,
    content: Object( window.wp.element.createElement )( BolepixContent, null ),
    edit: Object( window.wp.element.createElement )( BolepixContent, null ),
    canMakePayment: () => true,
    ariaLabel: bolepix_shipay_label,
    supports: {
        features: bolepix_shipay_data.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( bolepix_shipay );