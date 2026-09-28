/*
 * ZIP 31 - Force Business Currency V2
 * Path: public/js/zip31-force-business-currency.js
 *
 * Purpose:
 * Forces the browser-side display currency to the active Business Settings currency.
 * This repairs pages already rendered with the wrong $ symbol and also works after AJAX reloads.
 */
(function () {
    'use strict';

    function val(id, fallback) {
        var el = document.getElementById(id);
        if (!el || el.value === undefined || el.value === null || el.value === '') {
            return fallback;
        }
        return el.value;
    }

    function getSettings() {
        return {
            symbol: val('__symbol', val('zip31_business_currency_symbol', window.__currency_symbol || '$')),
            thousand: val('__thousand', window.__currency_thousand_separator || ','),
            decimal: val('__decimal', window.__currency_decimal_separator || '.'),
            placement: val('__symbol_placement', window.__currency_symbol_placement || 'before'),
            precision: parseInt(val('__precision', window.__currency_precision || 2), 10) || 2
        };
    }

    function setGlobalCurrency() {
        var s = getSettings();
        window.__currency_symbol = s.symbol;
        window.__currency_thousand_separator = s.thousand;
        window.__currency_decimal_separator = s.decimal;
        window.__currency_symbol_placement = s.placement;
        window.__currency_precision = s.precision;
        window.__quantity_precision = parseInt(val('__quantity_precision', window.__quantity_precision || 2), 10) || 2;

        if (window.accounting && window.accounting.settings) {
            window.accounting.settings.currency = window.accounting.settings.currency || {};
            window.accounting.settings.currency.symbol = s.symbol;
            window.accounting.settings.currency.precision = s.precision;
            window.accounting.settings.currency.thousand = s.thousand;
            window.accounting.settings.currency.decimal = s.decimal;
            window.accounting.settings.currency.format = s.placement === 'after' ? '%v %s' : '%s %v';
        }
    }

    function parseNumber(text, s) {
        if (text === null || text === undefined) return null;
        var cleaned = String(text);
        cleaned = cleaned.replace(/<[^>]*>/g, '');
        cleaned = cleaned.replace(/[A-Za-z₨රු₹$€£¥₽₺₩₪₫₱฿₦₴₲₵₡₭₮₸₼ƒ]+/g, '');
        cleaned = cleaned.replace(new RegExp('\\' + s.thousand, 'g'), '');
        if (s.decimal !== '.') {
            cleaned = cleaned.replace(new RegExp('\\' + s.decimal, 'g'), '.');
        }
        cleaned = cleaned.replace(/[^0-9.\-]/g, '');
        if (cleaned === '' || cleaned === '-' || cleaned === '.') return null;
        var number = parseFloat(cleaned);
        return isNaN(number) ? null : number;
    }

    function formatNumber(number, s) {
        var fixed = Number(number).toFixed(s.precision);
        var parts = fixed.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, s.thousand);
        var value = parts.join(s.decimal);
        return s.placement === 'after' ? value + ' ' + s.symbol : s.symbol + ' ' + value;
    }

    function fixDisplayedCurrency(root) {
        setGlobalCurrency();
        var s = getSettings();
        var scope = root || document;

        if (window.jQuery && typeof window.__currency_convert_recursively === 'function') {
            try {
                window.__currency_convert_recursively(window.jQuery(scope), false);
            } catch (e) {}
        }

        var nodes = scope.querySelectorAll ? scope.querySelectorAll('.display_currency, .display_currency_with_symbol, [data-currency_symbol="true"]') : [];
        Array.prototype.forEach.call(nodes, function (node) {
            if (!node || node.tagName === 'INPUT' || node.tagName === 'TEXTAREA') return;
            var original = node.getAttribute('data-zip31-original-number');
            var number = original !== null ? parseFloat(original) : parseNumber(node.textContent, s);
            if (number === null || isNaN(number)) return;
            node.setAttribute('data-zip31-original-number', number);
            node.textContent = formatNumber(number, s);
        });
    }

    function run() {
        fixDisplayedCurrency(document);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }

    if (window.jQuery) {
        window.jQuery(document).ajaxComplete(function () {
            setTimeout(run, 20);
        });
        window.jQuery(document).on('draw.dt shown.bs.modal', function () {
            setTimeout(run, 20);
        });
    }

    window.zip31FixBusinessCurrency = run;
})();
