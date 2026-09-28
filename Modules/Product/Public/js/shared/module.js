(function (window, $) {
    'use strict';

    window.ProductModule = window.ProductModule || {};

    ProductModule.csrfToken = function () {
        return $('meta[name="csrf-token"]').attr('content') || '';
    };

    ProductModule.formatNumber = function (value, decimals) {
        decimals = typeof decimals === 'number' ? decimals : 2;
        var number = parseFloat(String(value || 0).replace(/,/g, ''));
        if (isNaN(number)) { number = 0; }
        return number.toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
    };

    ProductModule.parseNumber = function (value) {
        var number = parseFloat(String(value || 0).replace(/,/g, ''));
        return isNaN(number) ? 0 : number;
    };

    ProductModule.bindConfirmDelete = function (selector) {
        $(document).off('click.productDelete', selector).on('click.productDelete', selector, function (e) {
            if (!confirm($(this).data('confirm') || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    };
})(window, jQuery);
