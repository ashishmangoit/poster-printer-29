var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/checkout-loader': {
                'MagePal_AddressAutocomplete/js/checkout-loader-mixin': true
            }
        }
    },
    map: {
        '*': {
            magePalCheckoutAutocomplete: 'MagePal_AddressAutocomplete/js/checkout-autocomplete'
        }
    }
};
