/**
 * Pumper Dashboard payment-type selector.
 *
 * Loaded after po_payment.js on the pumper Add Payments page. It replaces the
 * broad document-level payment-type handlers with one scoped handler so a
 * single click updates the selected payment immediately without duplicate UI
 * work. Cash-denomination behaviour and the existing Card/Cheque modal
 * handlers remain unchanged.
 *
 * Build: 2026-07-21-s510
 */
(function ($) {
    'use strict';

    if (!$) {
        return;
    }

    function showError(message) {
        if (typeof toastr !== 'undefined') {
            toastr.error(message);
        }
    }

    function openCashModal($modal) {
        if (!$modal.length) {
            return;
        }

        $modal.appendTo(document.body).css('z-index', 20000);

        if ($.fn.modal) {
            $modal.modal({
                backdrop: 'static',
                keyboard: false,
                show: true
            });
            $('.modal-backdrop').css('z-index', 19990);
            return;
        }

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance($modal[0], {
                backdrop: 'static',
                keyboard: false
            }).show();
            $('.modal-backdrop').css('z-index', 19990);
        }
    }

    function selectPaymentType($list, $button) {
        var $buttons = $list.children('.payment_type_btn');
        var $checkbox = $button.find('.payment_type_checkbox').first();
        var paymentType = $checkbox.val() || '';

        // Apply a fixed selected/unselected state without changing button size.
        $buttons
            .addClass('active')
            .attr('aria-pressed', 'false')
            .find('.payment_type_checkbox')
            .prop('checked', false);

        $button
            .removeClass('active')
            .attr('aria-pressed', 'true');

        $checkbox.prop('checked', true);
        $('#payment_type').val(paymentType);

        // Retain globals used by older custom scripts without relying on them.
        window.clicked_btn = $button;
        window.siblings = $button.siblings();

        return {
            checkbox: $checkbox,
            paymentType: paymentType
        };
    }

    $(function () {
        // Remove only handlers registered for this exact selector by the older
        // payment scripts. Card and Cheque handlers use their own selectors and
        // therefore continue to run normally after this scoped handler bubbles.
        $(document).off('click', '.payment_type_btn');

        $('.payment-type-list').each(function () {
            var $list = $(this);

            $list
                .off('click.pumperPaymentType', '.payment_type_btn')
                .on('click.pumperPaymentType', '.payment_type_btn', function (event) {
                    var meterSalesCompulsory = $('#meter_sales_compulsory').val();
                    if (meterSalesCompulsory === 'yes') {
                        event.preventDefault();
                        showError('First Enter Meters');
                        return false;
                    }

                    if (document.querySelector('.realtime-entries-payment-ui') ||
                        window.location.pathname.indexOf('/real-time-entries') !== -1) {
                        return;
                    }

                    var $button = $(this);
                    var selected = selectPaymentType($list, $button);

                    if (selected.paymentType === 'cash' &&
                        selected.checkbox.hasClass('cash_denoms_enter')) {
                        openCashModal($('#cash_payments'));
                    }
                });
        });
    });
})(window.jQuery);
