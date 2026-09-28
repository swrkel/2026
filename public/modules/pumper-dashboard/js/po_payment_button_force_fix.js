/**
 * PD-038 / Pumper Dashboard Payment Button Force Fix
 * Purpose: restore click response for Pumper Dashboard / Payments buttons even when
 * Bootstrap data-toggle handlers, stale cached po_payment.js, or dynamic partial reloads
 * fail to bind correctly.
 */
(function ($) {
    'use strict';

    if (!$) {
        return;
    }

    var MODAL_IDS = ['#cash_payments', '#card_payment', '#cheque_payments', '#direct_cr', '#other_sales'];

    function isBlocked($el) {
        return $el.is(':disabled') ||
            $el.attr('disabled') === 'disabled' ||
            $el.hasClass('disabled') ||
            $el.hasClass('locked') ||
            $el.closest('.disabled,.locked').length > 0;
    }

    function modalExists(selector) {
        return selector && selector.charAt(0) === '#' && $(selector).length > 0;
    }

    function showModal(selector) {
        if (!modalExists(selector)) {
            return false;
        }

        // Remove broken leftovers from previous modal attempts.
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');

        var $modal = $(selector).appendTo(document.body);

        if ($.fn.modal) {
            $modal.modal({backdrop: true, keyboard: true, show: true});
        } else {
            // Very defensive fallback if Bootstrap JS is not active.
            $modal.addClass('in show').show().attr('aria-hidden', 'false');
            $('body').addClass('modal-open');
        }

        return true;
    }

    function targetFromElement($el) {
        var target = $el.attr('data-target') ||
            $el.attr('data-bs-target') ||
            $el.data('target') ||
            $el.data('bs-target') ||
            $el.attr('href');

        if (modalExists(target)) {
            return target;
        }

        var text = $.trim(($el.text() || '').toLowerCase());
        var cls = ($el.attr('class') || '').toLowerCase();
        var id = ($el.attr('id') || '').toLowerCase();
        var name = ($el.attr('name') || '').toLowerCase();
        var all = text + ' ' + cls + ' ' + id + ' ' + name;

        if (all.indexOf('cash') !== -1) {
            return '#cash_payments';
        }
        if (all.indexOf('card') !== -1) {
            return '#card_payment';
        }
        if (all.indexOf('cheque') !== -1 || all.indexOf('check') !== -1) {
            return '#cheque_payments';
        }
        if (all.indexOf('credit') !== -1 || all.indexOf('direct') !== -1) {
            return '#direct_cr';
        }
        if (all.indexOf('other') !== -1 || all.indexOf('meter') !== -1) {
            return '#other_sales';
        }

        return null;
    }

    function bindPaymentButtons() {
        // Normalize old/new Bootstrap attributes for known modal triggers.
        MODAL_IDS.forEach(function (selector) {
            $('[data-target="' + selector + '"], [data-bs-target="' + selector + '"], a[href="' + selector + '"], button[href="' + selector + '"]').each(function () {
                $(this).attr('data-toggle', 'modal').attr('data-target', selector);
            });
        });
    }

    $(document).on('click.pumperPaymentForceFix',
        '[data-target="#cash_payments"], [data-bs-target="#cash_payments"], a[href="#cash_payments"], button[href="#cash_payments"],' +
        '[data-target="#card_payment"], [data-bs-target="#card_payment"], a[href="#card_payment"], button[href="#card_payment"],' +
        '[data-target="#cheque_payments"], [data-bs-target="#cheque_payments"], a[href="#cheque_payments"], button[href="#cheque_payments"],' +
        '[data-target="#direct_cr"], [data-bs-target="#direct_cr"], a[href="#direct_cr"], button[href="#direct_cr"],' +
        '[data-target="#other_sales"], [data-bs-target="#other_sales"], a[href="#other_sales"], button[href="#other_sales"],' +
        '.payment_type_btn, .payment-type-btn, .pumper-payment-btn, .po-payment-btn, .cash_payment_btn, .card_payment_btn, .cheque_payment_btn, .credit_sale_btn, .other_sales_btn',
        function (e) {
            var $btn = $(this);

            if (isBlocked($btn)) {
                return;
            }

            var target = targetFromElement($btn);
            if (!target) {
                return;
            }

            e.preventDefault();
            e.stopImmediatePropagation();
            showModal(target);
        }
    );

    // Close button fallback for both Bootstrap 3 and 4/5 markup combinations.
    $(document).on('click.pumperPaymentForceFixClose', '[data-dismiss="modal"], [data-bs-dismiss="modal"], .modal .close', function (e) {
        var $modal = $(this).closest('.modal');
        if (!$modal.length) {
            return;
        }

        e.preventDefault();
        if ($.fn.modal) {
            $modal.modal('hide');
        } else {
            $modal.removeClass('in show').hide().attr('aria-hidden', 'true');
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('padding-right', '');
        }
    });

    $(document).ready(bindPaymentButtons);
    $(document).ajaxComplete(bindPaymentButtons);
})(window.jQuery);
