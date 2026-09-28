/* FIN-006: Finance module common JS.
 * Safe standalone helper file. Does not alter existing global/main-system JS.
 */
(function (window, $) {
    'use strict';

    window.FinanceModule = window.FinanceModule || {};

    window.FinanceModule.formatMoney = function (value, precision) {
        precision = Number.isInteger(precision) ? precision : 2;
        var number = parseFloat(String(value || 0).replace(/,/g, ''));
        if (isNaN(number)) {
            number = 0;
        }
        return number.toLocaleString(undefined, {
            minimumFractionDigits: precision,
            maximumFractionDigits: precision
        });
    };

    window.FinanceModule.bindAjaxError = function () {
        $(document).off('ajaxError.finance').on('ajaxError.finance', function () {
            if (window.toastr) {
                toastr.error('Finance request failed. Please refresh and try again.');
            }
        });
    };
})(window, window.jQuery);
