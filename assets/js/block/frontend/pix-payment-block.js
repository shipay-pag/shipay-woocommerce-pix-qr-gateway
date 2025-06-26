const shipay_data = window.wc.wcSettings.getSetting( 'wc_shipay_pix_payment_geteway_data', {} );

const shipay_label = window.wp.htmlEntities.decodeEntities( shipay_data.title ) || window.wp.i18n.__( 'Shipay', 'shipay' );

const CustomInputField = ({ id, label,value,onChange }) => {
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

const CustomHiddenInputField = ({ id, value }) => {
    return React.createElement(
        'input',
        {
            type: 'hidden',
            id: id,
            name: id,
            value: value
        }
    );
};

const Content = ( props ) => {
    const { eventRegistration, emitResponse } = props;
    const { onPaymentProcessing } = eventRegistration;
    const [documentNumber, setDocumentNumber] = React.useState('');

    const handleDocumentChange = (event) => {
        setDocumentNumber(event.target.value);
    };

    React.useEffect( () => {
        const unsubscribe = onPaymentProcessing( async () => {
            const myGatewayCustomData = jQuery('#shipay_pix_document').val();
            const nonceData = jQuery('#shipay_pix_nonce').val();
            const customDataIsValid = !! myGatewayCustomData.length;

            if ( customDataIsValid ) {
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: {
                            'shipay_pix_document': myGatewayCustomData,
                            'shipay_pix_nonce': nonceData,
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
        React.createElement('p', null, window.wp.htmlEntities.decodeEntities(shipay_data.description ||  '')),
        React.createElement(
            CustomInputField,
            {
                id: 'shipay_pix_document',
                label: 'CPF/CNPJ',
                value: documentNumber,
                class: 'required',
                onChange: handleDocumentChange
            }
        ),
        React.createElement(
            CustomHiddenInputField,
            {
                id: 'shipay_pix_nonce',
                value: shipay_data.shipay_pix_nonce
            }
        ),

    );
};

const shipay = {
        name: 'wc_shipay_pix_payment_geteway',
        label: shipay_label,
        content: Object( window.wp.element.createElement )( Content, null ),
        edit: Object( window.wp.element.createElement )( Content, null ),
        canMakePayment: () => true,
        ariaLabel: shipay_label,
        supports: {
            features: shipay_data.supports,
        },
    };
window.wc.wcBlocksRegistry.registerPaymentMethod( shipay );