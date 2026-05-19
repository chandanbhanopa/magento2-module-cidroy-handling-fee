define([
    'Cidroy_HandlingFee/js/view/cart/totals/handling-fee'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Cidroy_HandlingFee/checkout/summary/extra-label'
        },

        isDisplayed: function () {
            return this.getPureValue() !== 0;
        }
    });
});
