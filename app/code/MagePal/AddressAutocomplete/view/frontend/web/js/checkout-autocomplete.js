define([
    'jquery', 'ko', 'uiComponent', 'uiRegistry', 'rjsResolver', 'magePalAddressAutocompleteApi', 'magePalGoogleMapPlaceLibrary'
], function ($, ko, Component, uiRegistry, resolver, addressAutocompleteApi) {
    'use strict';

    return Component.extend({
        init: function (formConfig) {
            var streetFieldset = uiRegistry.get(
                'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.street'
            );

            var postcodeFieldId = uiRegistry.get(
                'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.postcode'
            ).uid;

            var cityFieldId = uiRegistry.get(
                'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.city'
            ).uid;

            var regionFieldId = uiRegistry.get(
                'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.region_id'
            ).uid;

            var countryFieldId = uiRegistry.get(
                'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.country_id'
            ).uid;

            if (streetFieldset && streetFieldset.elems() && streetFieldset.elems().length > 1) {
                var addressAutocomplete = addressAutocompleteApi();
                addressAutocomplete.setComponentForm(formConfig);

                var fields = {};
                var streetFieldId = streetFieldset.elems()[0].uid;
                var _defaulFields = addressAutocomplete.getFormTemplate();

                if ("address_street0" in _defaulFields) {
                    fields[streetFieldId] = _defaulFields.address_street0;
                    fields[countryFieldId] = _defaulFields.address_country_id;
                    fields[regionFieldId] = _defaulFields.address_region_id;
                    fields[postcodeFieldId] = _defaulFields.address_postcode;
                    fields[cityFieldId] = _defaulFields.address_city;
                    addressAutocomplete.setFormTemplate(fields);
                }

                addressAutocomplete.setShippingElementId(streetFieldId, '');

                this.process(streetFieldset, addressAutocomplete)
            }
        },
        process: function (streetFieldset, addressAutocomplete) {
            var id = streetFieldset.elems()[0].uid;
            var element = $("#" + id);
            var jQueryInstance = window["jQuery"];
            var _self = this;

            ko.utils.domNodeDisposal.cleanExternalData = function (node) {
                if (jQueryInstance && (typeof jQueryInstance['cleanData'] == "function")) {
                    jQueryInstance['cleanData']([node]);
                }

                if ($(node).attr('id') === id) {
                    setTimeout(function () {
                        element.attr("autocomplete", "stop-autocomplete");
                        _self.process(streetFieldset, addressAutocomplete);
                        element.attr("autocomplete", "stop-autocomplete");
                    }, 2000);
                }
            };

            addressAutocomplete.initCheckout();
        }
    });
});
