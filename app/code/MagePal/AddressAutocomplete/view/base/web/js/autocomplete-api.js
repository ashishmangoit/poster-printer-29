/**
 * Copyright © MagePal LLC. All rights reserved.
 * See COPYING.txt for license details.
 * https://www.magepal.com | support@magepal.com
 */

define(['uiComponent'], function (Component) {
    'use strict';

    var magentoFormFields = {
        'address_street0': '{{street_number}} {{route}}',
        'address_city': '{{locality}}',
        'address_postcode': '{{postal_code}}',
        'address_region_id': '{{administrative_area_level_1}}',
        'address_country_id': '{{country}}'
    };

    var componentForm = {
        subpremise: 'short_name',
        street_number: "short_name",
        route: "long_name",
        locality: "long_name",
        administrative_area_level_1: "long_name",
        country: "long_name",
        postal_code: "short_name",
        postal_code_suffix: 'short_name',
        postal_town: 'short_name',
        sublocality_level_1: 'short_name'
    }

    function fillInAddress(addressFormType, autocomplete, fullAddressData)
    {
        const place = autocomplete.getPlace();

        var addressInfo = {
            street_number: "",
            route: "",
            locality: "",
            administrative_area_level_1: "",
            country: "",
            postal_code: "",
        };

        for (const component of place.address_components) {
            const addressType = component.types[0];

            if (componentForm[addressType]) {
                addressInfo[addressType] = component[componentForm[addressType]];
            }
        }

        for (const component in magentoFormFields) {
            updateFormElement(
                addressFormType + component,
                template(magentoFormFields[component], addressInfo, fullAddressData)
            );
        }
    }


    function template(templateContent, data, fullAddressData)
    {
        var fieldData = templateContent.replace(
            /{{(\w*)}}/g,
            function (m, key) {
                return data.hasOwnProperty(key) ? data[ key ] : "";
            }
        );

        if (templateContent === '{{street_number}} {{route}}') {
            if ((fullAddressData.indexOf(fieldData) !== -1 && data.hasOwnProperty('subpremise'))
                || (!fullAddressData.match(/^\d/) && fullAddressData.indexOf(fieldData) === -1)
                || (fullAddressData.match(/^\d/) && !fieldData.match(/^\d/))
            ) {
                var addressArray = fullAddressData.split(",");
                if (addressArray.length) {
                    return addressArray[0]
                }
            }
        }

        if (templateContent === '{{locality}}' && data.hasOwnProperty('postal_town') && fieldData) {
            fieldData = data.postal_town
        }

        return fieldData;
    }

    function updateFormElement(elementId, value)
    {
        var element = document.getElementById(elementId);

        if (element) {
            if (element.type === 'text') {
                element.value = value;
            } else if (element.type === 'select-one') {
                for (var i = 0; i < element.length; i++) {
                    if (element.options[i].text === value) {
                        element.value = element.options[i].value;
                        break;
                    }
                }
            }
            element.dispatchEvent(new Event("change"));
        }
    }

    /** Bias the autocomplete object to the user's geographical location,
     * as supplied by the browser's 'navigator.geolocation' object.
     */
    function geolocate()
    {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition((position) => {
                const geolocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                };
                const circle = new google.maps.Circle({
                    center: geolocation,
                    radius: position.coords.accuracy,
                });
                autocomplete.setBounds(circle.getBounds());
            });
        }
    }

    function initAdminAutoComplete(elementId, formType)
    {
        let autocompleteAddress;

        var addressElement = document.getElementById(elementId);
        if (addressElement
            && (!addressElement.getAttribute("placeholder")
                || !addressElement.getAttribute("placeholder").trim())
        ) {
            autocompleteAddress = new google.maps.places.Autocomplete(
                addressElement,
                { types: ["geocode"] }
            );

            autocompleteAddress.setFields(["address_component"]);
            autocompleteAddress.addListener("place_changed", function () {
                var fullAddressData = '';
                if (addressElement) {
                    fullAddressData = addressElement.value;
                }

                fillInAddress(formType, autocompleteAddress, fullAddressData)
            });

            //autocompleteBillingAddress.setAttribute('autocomplete', 'mp');
        }
    }

    return Component.extend({
        shippingElementId: 'order-shipping_address_street0',
        billingElementId: 'order-billing_address_street0',
        init: function () {
            initAdminAutoComplete(this.billingElementId, 'order-billing_');
            initAdminAutoComplete(this.shippingElementId, 'order-shipping_');
        },
        initCheckout: function () {
            initAdminAutoComplete(this.shippingElementId, '');
            initAdminAutoComplete(this.billingElementId, '');
        },
        setFormTemplate: function (value) {
            magentoFormFields = value;
            return this;
        },
        setComponentForm: function (value) {
            if (this.isJson(value)) {
                componentForm  = value;
            }

            return this;
        },
        getFormTemplate: function () {
            return magentoFormFields;
        },
        setShippingElementId: function (id) {
            this.shippingElementId = id ? id : this.shippingElementId;
            return this;
        },
        setBillingElementId: function (id) {
            this.billingElementId = id ? id : this.billingElementId;
            return this;
        },
        isJson: function (str) {
            var result = true;

            try {
                JSON.parse(JSON.stringify(str));
            } catch (e) {
                result = false;
            }

            return result;
        }
    });
});
