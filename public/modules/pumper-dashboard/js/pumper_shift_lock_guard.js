(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    function hasJquery() {
        return typeof window.jQuery !== 'undefined';
    }

    function getLockState() {
        return !!window.ZIP30_PUMPER_SHIFT_LOCKED;
    }

    function getMessage() {
        return window.ZIP30_PUMPER_SHIFT_LOCK_MESSAGE || 'This shift is closed. Please open a new shift before entering pump payments, payments, or other sales.';
    }

    function showMessage() {
        var message = getMessage();
        if (window.toastr && typeof window.toastr.error === 'function') {
            window.toastr.error(message);
            return;
        }
        alert(message);
    }

    function disableElement(el) {
        if (!el || el.getAttribute('data-zip30-shift-guard') === 'done') {
            return;
        }

        el.setAttribute('data-zip30-shift-guard', 'done');
        el.setAttribute('aria-disabled', 'true');
        el.classList.add('disabled');

        if (el.tagName === 'BUTTON' || el.tagName === 'INPUT' || el.tagName === 'SELECT' || el.tagName === 'TEXTAREA') {
            el.disabled = true;
        }

        el.addEventListener('click', function (e) {
            if (!getLockState()) {
                return true;
            }
            e.preventDefault();
            e.stopPropagation();
            showMessage();
            return false;
        }, true);
    }

    function textMatches(el) {
        var text = (el.innerText || el.textContent || el.value || '').toLowerCase().replace(/\s+/g, ' ').trim();
        if (!text) {
            return false;
        }

        return text.indexOf('received pump payment') !== -1 ||
            text.indexOf('received pump payments') !== -1 ||
            text === 'payment' ||
            text.indexOf(' payment') !== -1 && text.indexOf('summary') === -1 ||
            text.indexOf('other sale') !== -1 ||
            text.indexOf('other sales') !== -1 ||
            text.indexOf('cash') !== -1 ||
            text.indexOf('card') !== -1 ||
            text.indexOf('cheque') !== -1 ||
            text.indexOf('credit sale') !== -1;
    }

    function selectorMatches(el) {
        var selectorParts = [
            '.po_cash_payment', '.po_card_payment', '.po_cheque_payment', '.po_credit_payment',
            '.other_sale', '.other_sales', '.po_other_sale', '.received_pump_payment',
            '.received_pump_payments', '.payment_section', '.pump-payment-action',
            '[data-target="#cash_payments"]', '[data-target="#card_payment"]',
            '[data-target="#cheque_payments"]', '[data-target="#direct_cr"]',
            '[data-target="#other_sales"]', '[href="#cash_payments"]', '[href="#card_payment"]',
            '[href="#cheque_payments"]', '[href="#direct_cr"]', '[href="#other_sales"]'
        ];

        for (var i = 0; i < selectorParts.length; i++) {
            try {
                if (el.matches && el.matches(selectorParts[i])) {
                    return true;
                }
            } catch (ignore) {}
        }
        return false;
    }

    function applyLock() {
        if (!getLockState()) {
            return;
        }

        var candidates = document.querySelectorAll('a, button, input[type="button"], input[type="submit"]');
        Array.prototype.forEach.call(candidates, function (el) {
            if (selectorMatches(el) || textMatches(el)) {
                disableElement(el);
            }
        });

        if (hasJquery()) {
            var $ = window.jQuery;
            $('#cash_payments, #card_payment, #cheque_payments, #direct_cr, #other_sales').on('show.bs.modal', function (e) {
                if (!getLockState()) {
                    return true;
                }
                e.preventDefault();
                e.stopImmediatePropagation();
                showMessage();
                return false;
            });
        }
    }

    ready(function () {
        applyLock();
        setTimeout(applyLock, 500);
        setTimeout(applyLock, 1500);
    });
})();
