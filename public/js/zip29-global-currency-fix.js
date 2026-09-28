/*
 * ZIP 29 - Global Currency Fix
 * Path: public/js/zip29-global-currency-fix.js
 *
 * This file safely re-reads the currency hidden inputs after page load and
 * reconverts display_currency values. It does not change calculations.
 */
(function () {
    function readValue(selector, fallback) {
        var el = document.querySelector(selector);
        if (!el || el.value === undefined || el.value === null || el.value === '') {
            return fallback;
        }
        return el.value;
    }

    function applyGlobalCurrencyFromHiddenInputs() {
        try {
            window.__currency_symbol = readValue('input#__symbol', window.__currency_symbol || '');
            window.__currency_thousand_separator = readValue('input#__thousand', window.__currency_thousand_separator || ',');
            window.__currency_decimal_separator = readValue('input#__decimal', window.__currency_decimal_separator || '.');
            window.__currency_symbol_placement = readValue('input#__symbol_placement', window.__currency_symbol_placement || 'before');
            window.__currency_precision = readValue('input#__precision', window.__currency_precision || 2);
            window.__quantity_precision = readValue('input#__quantity_precision', window.__quantity_precision || 2);

            if (window.jQuery && typeof window.__currency_convert_recursively === 'function') {
                window.__currency_convert_recursively(window.jQuery(document), window.jQuery('input#p_symbol').length > 0);
            }
        } catch (e) {
            if (window.console && console.warn) {
                console.warn('ZIP29 global currency fix skipped:', e);
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyGlobalCurrencyFromHiddenInputs);
    } else {
        applyGlobalCurrencyFromHiddenInputs();
    }

    if (window.jQuery) {
        window.jQuery(document).ajaxComplete(function () {
            applyGlobalCurrencyFromHiddenInputs();
        });
    }
})();
