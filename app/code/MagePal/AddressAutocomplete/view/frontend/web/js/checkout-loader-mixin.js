define([
    'mage/utils/wrapper', 'magePalCheckoutAutocomplete', 'uiRegistry', 'rjsResolver'
], function (wrapper, checkoutAutocomplete, uiRegistry, resolver) {
    'use strict';

    function loadAutocomplete()
    {
        if (window.checkoutConfig.magepal_autocomplete.active) {
            var autocomplete = checkoutAutocomplete();
            autocomplete.init(window.checkoutConfig.magepal_autocomplete.field_mapping);
        }
    }

    return function (hideLoader) {
        return wrapper.wrap(hideLoader, function (originalHideLoader, config, element) {
            originalHideLoader(config, element);
            resolver(loadAutocomplete.bind(null, element));
        });
    };

});
