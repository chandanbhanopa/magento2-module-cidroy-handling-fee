console.log("Checkout Handling fees ...");
define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'mage/translate'
], function (Component, customerData, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Cidroy_HandlingFee/summary/handling-fee'
        },

        initialize: function () {
            this._super();
            this.cart = customerData.get('cart');
            return this;
        },

        isDisplayed: function () {
            return parseFloat(this.cart()['handling_fee'] || 0) > 0;
        },

        getTitle: function () {
            return this.cart()['handling_fee_label'] || this.config.title || $t('Handling Fee');
        },

        getValue: function () {
            return this.cart()['handling_fee_formatted'] || '';
        }
    });
});
