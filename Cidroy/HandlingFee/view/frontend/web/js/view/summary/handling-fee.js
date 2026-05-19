console.log("Cart Summery......");
define([
    'Cidroy_HandlingFee/js/view/cart/totals/handling-fee'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Cidroy_HandlingFee/summary/handling-fee'
        },

        /** Show only on the review (full) step, not on the estimate panel. */
        isDisplayed: function () {
            return this.isFullMode() && this.getPureValue() !== 0;
        }
    });
});
