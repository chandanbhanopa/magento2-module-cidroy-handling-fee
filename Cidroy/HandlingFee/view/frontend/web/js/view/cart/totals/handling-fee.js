define([
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/totals',
    'mage/translate'
], function (Component, totals, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Cidroy_HandlingFee/cart/totals/handling-fee'
        },

        isDisplayed: function () {
            return this.getPureValue() !== 0;
        },

        getValue: function () {
            return this.getFormattedPrice(this.getPureValue());
        },

        getPureValue: function () {
            var segment = totals.getSegment('handling_fee');

            return segment ? parseFloat(segment['value']) : 0;
        },

        getTitle: function () {
            var segment = totals.getSegment('handling_fee');

            return segment ? segment['title'] : $t('Handling Fee');
        }
    });
});
