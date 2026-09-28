(function (window, document) {
    'use strict';

    var config = window.ERP_MODULE_DATE_DEFAULTS || {};
    if (!config.enabled) return;

    var eligibleNames = {
        'date': true,
        'transaction_date': true,
        'transaction_datetime': true,
        'operation_date': true,
        'operation_datetime': true,
        'date_of_operation': true,
        'paid_on': true,
        'payment_date': true,
        'payment_datetime': true,
        'purchase_date': true,
        'sale_date': true,
        'sell_date': true,
        'expense_date': true,
        'journal_date': true,
        'transfer_date': true,
        'deposit_date': true,
        'entry_date': true,
        'settlement_date': true,
        'invoice_date': true,
        'collection_date': true,
        'order_date': true,
        'realize_transaction_date': true,
        'date_time': true,
        'date_and_time': true
    };

    var explicitExclusions = /(birth|dob|expiry|expire|due_date|cheque_date|check_date|valid_|validity|warranty|manufactur|mfg_|start_date|end_date|from_date|to_date|date_range|filter_date|report_date|period_date)/i;

    function pad(value) {
        return String(value).padStart(2, '0');
    }

    function computerDateIso() {
        var now = new Date();
        return now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate());
    }

    function effectiveDateIso() {
        if (String(config.source || '').toLowerCase() === 'global' && /^\d{4}-\d{2}-\d{2}$/.test(config.globalDate || '')) {
            return config.globalDate;
        }
        return computerDateIso();
    }

    function currentTimeParts() {
        var now = new Date();
        return { hour: pad(now.getHours()), minute: pad(now.getMinutes()), second: pad(now.getSeconds()) };
    }

    function dateObject(isoDate) {
        var parts = isoDate.split('-');
        var t = currentTimeParts();
        return new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]), Number(t.hour), Number(t.minute), Number(t.second));
    }

    function phpDateFormat(isoDate, format) {
        var parts = isoDate.split('-');
        var map = { Y: parts[0], y: parts[0].slice(-2), m: parts[1], n: String(Number(parts[1])), d: parts[2], j: String(Number(parts[2])) };
        return String(format || 'm/d/Y').replace(/[Yymndj]/g, function (token) { return map[token] || token; });
    }

    function isEditForm(form) {
        if (!form) return true;
        if (form.hasAttribute('data-erp-date-default-force')) return false;
        if (form.hasAttribute('data-erp-date-default-off')) return true;

        var methodOverride = form.querySelector('input[name="_method"]');
        if (methodOverride && /^(PUT|PATCH|DELETE)$/i.test(methodOverride.value || '')) return true;

        var method = String(form.getAttribute('method') || 'GET').toUpperCase();
        if (method === 'GET') return true;

        var pagePath = window.location.pathname.toLowerCase();
        var action = String(form.getAttribute('action') || '').toLowerCase();
        if (/\/(edit)(\/|$)/.test(pagePath) || /\/(edit|update)(\/|$)/.test(action)) return true;

        return false;
    }

    function fieldKey(field) {
        var name = String(field.getAttribute('name') || '').replace(/\[[^\]]*\]$/g, '');
        var pieces = name.split(/[\[\].]/).filter(Boolean);
        var key = pieces.length ? pieces[pieces.length - 1] : '';
        if (!key) key = String(field.id || '');
        return key.toLowerCase();
    }

    function eligibleField(field) {
        if (!field || field.disabled) return false;
        if (field.getAttribute('data-erp-date-default') === 'off') return false;
        if (field.type === 'hidden') return false;
        if (field.getAttribute('data-erp-date-default') === 'on') return true;

        var key = fieldKey(field);
        var combined = key + ' ' + String(field.name || '') + ' ' + String(field.id || '');
        if (explicitExclusions.test(combined)) return false;

        if (eligibleNames[key]) return true;
        if (/^(transaction|operation|payment|purchase|sale|sell|expense|journal|transfer|deposit|entry|settlement|invoice|collection)_(date|datetime|date_time)$/.test(key)) return true;

        return false;
    }

    function hasTime(field) {
        if (field.type === 'datetime-local') return true;
        var key = fieldKey(field);
        var cls = String(field.className || '');
        var current = String(field.value || '');
        return /time/.test(key) || /datetime/i.test(cls) || /\d{1,2}:\d{2}/.test(current);
    }

    function setPluginDate(field, dateObj, isoDate) {
        if (!window.jQuery) return;
        var $field = window.jQuery(field);

        try {
            var dtp = $field.data('DateTimePicker');
            if (dtp && window.moment) {
                dtp.date(window.moment(dateObj));
                return;
            }
        } catch (e) {}

        try {
            var drp = $field.data('daterangepicker');
            if (drp && window.moment) {
                var momentDate = window.moment(dateObj);
                drp.setStartDate(momentDate);
                drp.setEndDate(momentDate);
                if (drp.singleDatePicker) return;
            }
        } catch (e) {}

        try {
            if ($field.hasClass('hasDatepicker') && window.jQuery.fn.datepicker) {
                $field.datepicker('setDate', dateObj);
            }
        } catch (e) {}
    }

    function assignField(field) {
        if (field.getAttribute('data-erp-date-default-user-set') === '1') return;
        if (!eligibleField(field)) return;

        var isoDate = effectiveDateIso();
        var t = currentTimeParts();
        var value;
        var currentValue = String(field.value || '');
        var placeholder = String(field.getAttribute('placeholder') || '');
        var wantsIsoText = /^\d{4}-\d{2}-\d{2}/.test(currentValue) || /YYYY-MM-DD/i.test(placeholder);
        var wantsSeconds = /:\d{2}:\d{2}/.test(currentValue) || /HH:mm:ss/i.test(placeholder);

        if (field.type === 'date') {
            value = isoDate;
        } else if (field.type === 'datetime-local') {
            value = isoDate + 'T' + t.hour + ':' + t.minute;
        } else {
            value = wantsIsoText ? isoDate : phpDateFormat(isoDate, config.businessDateFormat || 'm/d/Y');
            if (hasTime(field)) {
                value += ' ' + t.hour + ':' + t.minute + (wantsSeconds ? ':' + t.second : '');
            }
        }

        field.setAttribute('data-erp-date-default-setting', '1');
        field.value = value;
        field.setAttribute('data-erp-date-default-applied', '1');
        field.setAttribute('data-erp-date-default-source', String(config.source || 'computer'));
        setPluginDate(field, dateObject(isoDate), isoDate);
        window.setTimeout(function () {
            field.removeAttribute('data-erp-date-default-setting');
        }, 0);
    }

    function processForm(form) {
        if (!form || isEditForm(form)) return;
        form.querySelectorAll('input, textarea').forEach(assignField);
    }

    function processRoot(root) {
        if (!root) return;
        if (root.matches && root.matches('form')) processForm(root);
        if (root.querySelectorAll) root.querySelectorAll('form').forEach(processForm);
    }

    document.addEventListener('input', function (event) {
        if (eligibleField(event.target)
            && event.target.getAttribute('data-erp-date-default-applied') === '1'
            && event.target.getAttribute('data-erp-date-default-setting') !== '1') {
            event.target.setAttribute('data-erp-date-default-user-set', '1');
        }
    }, true);

    document.addEventListener('change', function (event) {
        if (eligibleField(event.target)
            && event.target.getAttribute('data-erp-date-default-applied') === '1'
            && event.target.getAttribute('data-erp-date-default-setting') !== '1') {
            event.target.setAttribute('data-erp-date-default-user-set', '1');
        }
    }, true);

    function start() {
        processRoot(document);

        if (window.jQuery) {
            window.jQuery(document).on('shown.bs.modal', '.modal', function () {
                var modal = this;
                window.setTimeout(function () { processRoot(modal); }, 0);
            });
        }

        if ('MutationObserver' in window) {
            var observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    mutation.addedNodes.forEach(function (node) {
                        if (node && node.nodeType === 1) processRoot(node);
                    });
                });
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})(window, document);
