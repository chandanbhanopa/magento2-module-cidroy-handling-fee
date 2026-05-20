define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'mage/translate'
], function (Component, customerData, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Cidroy_HandlingFee/checkout/summary/extra-label'
        },

        initialize: function () {
            this._super();
            this.cart = customerData.get('cart');
            return this;
        },

        isDisplayed: function () {
            console.log(this.cart()['handling_fee']);
            return parseFloat(this.cart()['handling_fee']) ? true : false;
        },

        getTitle: function () {
            console.log("Get Title");
            return this.cart()['handling_fee_label'] || this.config.title || $t('Handling Fee');
        },

        getValue: function () {
            return this.cart()['handling_fee_formatted'] || '';
        }
    });
});
