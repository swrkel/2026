/**
 * Distribution-owned payment UI behaviour.
 * Does not load /js/payment.js from the main ERP.
 */
(function (window, document, $) {
    'use strict';
    if (!$) return;

    function togglePaymentFields($select) {
        var method = $select.val();
        var $row = $select.closest('.distribution-payment-row, .row');
        $row.find('.payment-type-field').addClass('hide');
        if (method === 'card') {
            $row.find('.payment-card-details').removeClass('hide');
        } else if (method === 'cheque') {
            $row.find('.payment-cheque-details').removeClass('hide');
        } else if (method === 'bank_transfer' || method === 'direct_bank_deposit') {
            $row.find('.payment-bank-details').removeClass('hide');
        }
    }

    $(document).on('change', '.payment_types_dropdown', function () {
        togglePaymentFields($(this));
    });

    $(function () {
        $('.payment_types_dropdown').each(function () {
            togglePaymentFields($(this));
        });
    });
})(window, document, window.jQuery);
