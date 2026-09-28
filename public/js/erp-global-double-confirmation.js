(function (window, document) {
    'use strict';

    if (window.ErpGlobalDoubleConfirmation) return;

    var VERSION = '2026.08.03-1';
    var ACTION_WORDS = /\b(add|create|edit|update|save|submit|finali[sz]e|close|approve|post|process)\b/i;
    var EXCLUDED_WORDS = /\b(login|logout|cancel|back|search|filter|preview|print|export|download|view|delete|remove)\b/i;
    var pending = false;

    function closest(node, selector) {
        return node && node.closest ? node.closest(selector) : null;
    }

    function clean(value) {
        return String(value == null ? '' : value).replace(/\s+/g, ' ').trim();
    }

    function escapeHtml(value) {
        return clean(value).replace(/[&<>'"]/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'}[character];
        });
    }

    function optedOut(node) {
        return !!closest(node, '[data-global-confirm="off"], [data-erp-double-confirm="off"], [data-no-double-confirm]');
    }

    function controlLabel(control) {
        return clean(control && (control.getAttribute('data-confirm-action') || control.getAttribute('data-operation-label') || control.textContent || control.value)) || 'Save';
    }

    function requiresConfirmation(control, form) {
        if (optedOut(control || form)) return false;
        var methodOverride = form && form.querySelector('input[name="_method"]');
        var method = clean((methodOverride && methodOverride.value) || (form && form.method) || 'GET').toUpperCase();
        if (['POST', 'PUT', 'PATCH'].indexOf(method) === -1) return false;
        var label = controlLabel(control);
        return ACTION_WORDS.test(label) && !EXCLUDED_WORDS.test(label);
    }

    function fieldLabel(field) {
        var id = field.id;
        var label = id ? document.querySelector('label[for="' + (window.CSS && CSS.escape ? CSS.escape(id) : id) + '"]') : null;
        if (!label) label = closest(field, '.form-group, .mb-3, .field, .input-group') && closest(field, '.form-group, .mb-3, .field, .input-group').querySelector('label');
        return clean((label && label.textContent) || field.getAttribute('data-confirm-label') || field.getAttribute('aria-label') || field.placeholder || field.name)
            .replace(/[:*]+$/, '') || 'Field';
    }

    function fieldValue(field) {
        var type = clean(field.type).toLowerCase();
        if (type === 'checkbox') return field.checked ? 'Yes' : 'No';
        if (type === 'radio') return field.checked ? clean(field.value) : '';
        if (field.tagName === 'SELECT') {
            return Array.prototype.filter.call(field.options, function (option) { return option.selected; })
                .map(function (option) { return clean(option.textContent || option.value); }).join(', ');
        }
        if (type === 'file') return field.files && field.files.length ? Array.prototype.map.call(field.files, function (file) { return file.name; }).join(', ') : '';
        return clean(field.value);
    }

    function formDetails(form) {
        if (!form) return [];
        var ignoredTypes = ['hidden', 'password', 'submit', 'button', 'image', 'reset'];
        var ignoredNames = ['_token', '_method', 'password', 'password_confirmation'];
        var details = [];
        var seen = {};
        var fields = form.querySelectorAll('input, select, textarea');

        Array.prototype.forEach.call(fields, function (field) {
            if (field.disabled || ignoredTypes.indexOf(clean(field.type).toLowerCase()) !== -1 || ignoredNames.indexOf(field.name) !== -1 || field.hasAttribute('data-confirm-exclude')) return;
            if (field.offsetParent === null && !field.hasAttribute('data-confirm-include')) return;
            var value = fieldValue(field);
            if (value === '' || (field.type === 'radio' && !field.checked)) return;
            var label = fieldLabel(field);
            var key = label + '|' + value;
            if (seen[key]) return;
            seen[key] = true;
            details.push({label: label, value: value});
        });

        return details;
    }

    function summaryHtml(action, form) {
        var title = clean((form && (form.getAttribute('data-confirm-title') || form.getAttribute('aria-label'))) || document.querySelector('h1, h2, h3, .page-title') && document.querySelector('h1, h2, h3, .page-title').textContent || document.title);
        var details = formDetails(form);
        var html = '<div class="erp-confirm-summary">' +
            '<div class="erp-confirm-summary__action"><strong>Action</strong><span>' + escapeHtml(action) + '</span></div>' +
            (title ? '<div class="erp-confirm-summary__context"><strong>Form</strong><span>' + escapeHtml(title) + '</span></div>' : '');
        if (details.length) {
            html += '<div class="erp-confirm-summary__fields">';
            details.forEach(function (item) {
                html += '<div class="erp-confirm-summary__row"><strong>' + escapeHtml(item.label) + '</strong><span>' + escapeHtml(item.value) + '</span></div>';
            });
            html += '</div>';
        } else {
            html += '<p class="erp-confirm-summary__empty">No additional visible field details apply to this action.</p>';
        }
        return html + '</div>';
    }

    function ask(title, html, finalStep) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            return window.Swal.fire({
                title: title,
                html: html,
                icon: finalStep ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                allowOutsideClick: false,
                allowEscapeKey: true,
                focusCancel: true,
                customClass: {popup: 'erp-double-confirm-popup'}
            }).then(function (result) { return !!result.isConfirmed; });
        }

        if (typeof window.swal === 'function') {
            var holder = document.createElement('div');
            holder.innerHTML = html;
            return Promise.resolve(window.swal({
                title: title,
                content: holder,
                icon: finalStep ? 'warning' : 'info',
                buttons: {cancel: {text: 'No', visible: true}, confirm: {text: 'Yes', value: true, visible: true}},
                closeOnClickOutside: false,
                dangerMode: finalStep
            })).then(Boolean);
        }

        var fallback = document.createElement('div');
        fallback.innerHTML = html;
        return Promise.resolve(window.confirm(
            (finalStep ? 'Final verification: ' : '') + title + '\n\n' + clean(fallback.textContent)
        ));
    }

    function verify(action, form) {
        var summary = summaryHtml(action, form);
        return ask('Review before continuing', summary, false).then(function (firstYes) {
            if (!firstYes) return false;
            return ask('Final confirmation', '<div class="erp-confirm-final">You reviewed the details for <strong>' + escapeHtml(action) + '</strong>.<br>Do you definitely want to continue?</div>', true);
        });
    }

    function allowNext(node) {
        if (!node) return;
        node.setAttribute('data-erp-double-confirm-bypass', '1');
    }

    function beginProgress(control, action) {
        if (window.ErpOperationGuard && typeof window.ErpOperationGuard.start === 'function') {
            window.ErpOperationGuard.start(control, action);
            return;
        }
        if (control) {
            control.setAttribute('aria-busy', 'true');
            control.classList.add('erp-confirm-action-started');
        }
    }

    function consumeBypass(node) {
        if (!node || node.getAttribute('data-erp-double-confirm-bypass') !== '1') return false;
        node.removeAttribute('data-erp-double-confirm-bypass');
        return true;
    }

    function isConfirmationOptions(options) {
        if (!options || typeof options !== 'object') return false;
        if (options.showCancelButton === true || options.showDenyButton === true || options.dangerMode === true || options.buttons === true) return true;
        if (options.buttons && typeof options.buttons === 'object') {
            return !!(options.buttons.cancel || options.buttons.deny);
        }
        return false;
    }

    // A few legacy modules show their own single confirmation after the click.
    // The global two approvals replace that prompt for this one replay only.
    // Informational/error alerts (without a cancel/deny action) are never bypassed.
    function runApprovedAction(callback) {
        var originalConfirm = window.confirm;
        var originalSwal = window.swal;
        var originalSwalFire = window.Swal && window.Swal.fire;
        var restored = false;

        function restore() {
            if (restored) return;
            restored = true;
            try { window.confirm = originalConfirm; } catch (ignore) {}
            try { if (originalSwal) window.swal = originalSwal; } catch (ignore) {}
            try { if (window.Swal && originalSwalFire) window.Swal.fire = originalSwalFire; } catch (ignore) {}
        }

        try { window.confirm = function () { return true; }; } catch (ignore) {}
        if (typeof originalSwal === 'function') {
            try {
                window.swal = function () {
                    var args = Array.prototype.slice.call(arguments);
                    if (!args.some(isConfirmationOptions)) return originalSwal.apply(window, args);
                    var legacyCallback = args.filter(function (arg) { return typeof arg === 'function'; })[0];
                    if (legacyCallback) Promise.resolve().then(function () { legacyCallback(true); });
                    return Promise.resolve(true);
                };
            } catch (ignore) {}
        }
        if (window.Swal && typeof originalSwalFire === 'function') {
            try {
                window.Swal.fire = function () {
                    var options = arguments[0];
                    if (!isConfirmationOptions(options)) return originalSwalFire.apply(window.Swal, arguments);
                    return Promise.resolve({isConfirmed: true, value: true});
                };
            } catch (ignore) {}
        }

        try {
            callback();
        } finally {
            // Promise continuations from a legacy confirmation run before this timer,
            // so chained confirmation prompts are also replaced without leaving a
            // persistent global override behind.
            window.setTimeout(restore, 0);
        }
    }

    document.addEventListener('click', function (event) {
        var control = closest(event.target, 'button, input[type="submit"], input[type="button"], input[type="image"], a');
        if (!control || consumeBypass(control) || pending) return;
        var form = control.form || closest(control, 'form');
        if (!form || !requiresConfirmation(control, form)) return;

        event.preventDefault();
        event.stopImmediatePropagation();
        pending = true;
        verify(controlLabel(control), form).then(function (approved) {
            pending = false;
            if (!approved) return;
            beginProgress(control, controlLabel(control));
            if (control.matches('button[type="submit"], button:not([type]), input[type="submit"], input[type="image"]')) {
                allowNext(form);
                runApprovedAction(function () {
                    if (typeof form.requestSubmit === 'function') form.requestSubmit(control);
                    else form.submit();
                });
            } else {
                // The operation guard has already marked this approved action busy so
                // the spinner is visible immediately. Let exactly this replayed click
                // pass through; all later clicks remain blocked as duplicates.
                control.setAttribute('data-erp-double-confirm-approved-replay', '1');
                allowNext(control);
                runApprovedAction(function () { control.click(); });
            }
        }, function () { pending = false; });
    }, true);

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || consumeBypass(form) || pending) return;
        var control = event.submitter || document.activeElement;
        if (!requiresConfirmation(control, form)) return;

        event.preventDefault();
        event.stopImmediatePropagation();
        pending = true;
        verify(controlLabel(control), form).then(function (approved) {
            pending = false;
            if (!approved) return;
            beginProgress(control, controlLabel(control));
            allowNext(form);
            runApprovedAction(function () {
                if (typeof form.requestSubmit === 'function') form.requestSubmit(control && control.form === form ? control : undefined);
                else form.submit();
            });
        }, function () { pending = false; });
    }, true);

    window.ErpGlobalDoubleConfirmation = {version: VERSION, verify: verify};
})(window, document);
