(function (window, document) {
    'use strict';

    if (window.ErpOperationGuard && window.ErpOperationGuard.version) {
        return;
    }

    var VERSION = '2026.08.02-4';
    var MUTATION_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];
    var ACTION_WORDS = /\b(save|update|submit|create|add|post|finali[sz]e|complete|close|approve|reject|confirm|process|reconcile|delete|remove|deactivate|activate|send|upload|pay|transfer|adjust|issue|receive)\b/i;
    var EXCLUDED_WORDS = /\b(cancel|back|search|filter|preview|print|export|download|expand|collapse|view)\b/i;
    var busyElements = new Set();
    var busyForms = new Set();
    var activeRequests = 0;
    var statusHideTimer = null;
    var afterCurrentEvent = window.queueMicrotask || function (callback) { window.setTimeout(callback, 0); };

    function closest(element, selector) {
        return element && element.closest ? element.closest(selector) : null;
    }

    function isOptedOut(element) {
        return !!closest(element, '[data-operation-guard="off"], [data-erp-operation-guard="off"], [data-allow-multiple-submit]');
    }

    /*
     * Tabs are navigation controls, never mutation controls. Many legacy ERP
     * pages place their complete tab strip inside the same <form> as Save or
     * Finalize. A form-level busy flag must therefore never cancel a tab click.
     */
    function isTabControl(element) {
        if (!element || !element.matches) return false;

        if (element.matches([
            '[role="tab"]',
            '[data-toggle="tab"]',
            '[data-bs-toggle="tab"]',
            '[data-toggle="pill"]',
            '[data-bs-toggle="pill"]',
            '[data-tab-target]',
            '[data-mpcs-tab-target]',
            '.erp-tab',
            '.erp-tab-btn',
            '.customer-tab-btn',
            '.module-tab-btn',
            '.tabs',
            '.tablinks',
            '.nav-tabs a[href*="#"]',
            '.nav-pills a[href*="#"]'
        ].join(','))) {
            return true;
        }

        var target = element.getAttribute('data-target')
            || element.getAttribute('data-bs-target')
            || element.getAttribute('href')
            || '';
        target = String(target || '').trim();
        if (target.charAt(0) !== '#') {
            try {
                var parsed = new window.URL(target, window.location.href);
                var current = new window.URL(window.location.href);
                var parsedPath = parsed.pathname.replace(/\/+$/, '') || '/';
                var currentPath = current.pathname.replace(/\/+$/, '') || '/';
                target = parsed.origin === current.origin && parsedPath === currentPath ? parsed.hash : '';
            } catch (ignoreUrl) {
                target = '';
            }
        }
        if (target.charAt(0) !== '#' || target.length < 2) return false;

        try {
            var pane = document.querySelector(target);
            return !!(pane && (pane.classList.contains('tab-pane') ||
                pane.getAttribute('role') === 'tabpanel' ||
                (pane.parentElement && pane.parentElement.classList.contains('tab-content'))));
        } catch (ignore) {
            return false;
        }
    }

    function isVisible(element) {
        if (!element || !document.documentElement.contains(element)) {
            return false;
        }
        var style = window.getComputedStyle(element);
        return style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0' && (element.offsetWidth > 0 || element.offsetHeight > 0);
    }

    function hasExistingProgressUi() {
        var selectors = [
            '.loading', '.loader', '.loading-overlay', '.page-loader', '.preloader',
            '.spinner-border', '.spinner-grow', '.fa-spinner', '.pace-active',
            '[aria-busy="true"]'
        ];
        var nodes = document.querySelectorAll(selectors.join(','));
        for (var i = 0; i < nodes.length; i += 1) {
            if (!nodes[i].hasAttribute('data-erp-operation-guard-owned') && isVisible(nodes[i])) {
                return true;
            }
        }
        return false;
    }

    function ensureStatusNode() {
        var node = document.getElementById('erp-global-operation-status');
        if (node) {
            return node;
        }

        node = document.createElement('div');
        node.id = 'erp-global-operation-status';
        node.setAttribute('role', 'status');
        node.setAttribute('aria-live', 'polite');
        node.setAttribute('aria-atomic', 'true');
        node.setAttribute('data-erp-operation-guard-owned', 'true');
        node.innerHTML = '<span class="erp-operation-status__spinner" aria-hidden="true"></span>' +
            '<span class="erp-operation-status__content">' +
            '<strong class="erp-operation-status__title">Processing&hellip;</strong>' +
            '<span class="erp-operation-status__message">Please wait. Do not click again.</span>' +
            '</span>';
        document.body.appendChild(node);
        return node;
    }

    function actionTitle(element) {
        var text = element ? (element.getAttribute('data-operation-label') || element.textContent || element.value || '') : '';
        text = text.trim().toLowerCase();
        if (/delete|remove/.test(text)) return 'Deleting&hellip;';
        if (/update|edit/.test(text)) return 'Updating&hellip;';
        if (/finali[sz]e|complete|close/.test(text)) return 'Finalizing&hellip;';
        if (/send|email|whatsapp/.test(text)) return 'Sending&hellip;';
        if (/save|create|add|submit|post/.test(text)) return 'Saving&hellip;';
        return 'Processing&hellip;';
    }

    function showStatus(title, message, force) {
        document.body.classList.add('erp-operation-in-progress');
        if (!force && hasExistingProgressUi()) {
            return;
        }
        window.clearTimeout(statusHideTimer);
        var node = ensureStatusNode();
        node.classList.remove('erp-operation-status--error');
        node.querySelector('.erp-operation-status__title').innerHTML = title || 'Processing&hellip;';
        node.querySelector('.erp-operation-status__message').textContent = message || 'Please wait. Do not click again.';
        node.classList.add('erp-operation-status--visible');
    }

    function hideStatus() {
        var node = document.getElementById('erp-global-operation-status');
        document.body.classList.remove('erp-operation-in-progress');
        if (node) {
            node.classList.remove('erp-operation-status--visible', 'erp-operation-status--error');
        }
    }

    function showAlreadyProcessing() {
        showStatus('Already processing&hellip;', 'Your first action is still in progress.', true);
    }

    function showRequestError() {
        if (window.ErpGlobalMessages && typeof window.ErpGlobalMessages.error === 'function') {
            hideStatus();
            // Existing AJAX error handlers normally publish the useful server
            // message first. Give them priority and add only a generic fallback.
            window.setTimeout(function () {
                var existing = document.querySelector('#toast-container .toast-error, #toast-container .toast-danger, .alert-danger, .alert-error, .erp-global-message--error');
                if (!existing || !isVisible(existing)) {
                    window.ErpGlobalMessages.error('The action could not be completed. Please check the form and try again.');
                }
            }, 80);
            return;
        }
        var node = ensureStatusNode();
        document.body.classList.remove('erp-operation-in-progress');
        node.classList.add('erp-operation-status--error', 'erp-operation-status--visible');
        node.querySelector('.erp-operation-status__title').textContent = 'Action not completed';
        node.querySelector('.erp-operation-status__message').textContent = 'Please check the message on this page and try again.';
        window.clearTimeout(statusHideTimer);
        statusHideTimer = window.setTimeout(hideStatus, 3000);
    }

    function containsBusyVisual(element) {
        return !!(element && element.querySelector && element.querySelector('.spinner-border, .spinner-grow, .fa-spinner, .loading, [aria-busy="true"]'));
    }

    function rememberAndDecorate(element) {
        if (!element || element.hasAttribute('data-erp-operation-original-html')) {
            return;
        }
        element.setAttribute('data-erp-operation-original-html', element.innerHTML);
        if (!containsBusyVisual(element) && element.tagName !== 'INPUT') {
            element.innerHTML = '<span class="erp-operation-button__content"><span class="erp-operation-button__spinner" aria-hidden="true"></span><span>' + actionTitle(element) + '</span></span>';
        }
    }

    function markElementBusy(element, timeout) {
        if (!element || isOptedOut(element) || element.disabled || element.getAttribute('aria-disabled') === 'true' || element.getAttribute('aria-busy') === 'true') {
            return false;
        }
        if (element.getAttribute('data-erp-operation-busy') === 'true') {
            showAlreadyProcessing();
            return false;
        }

        element.setAttribute('data-erp-operation-busy', 'true');
        element.setAttribute('aria-busy', 'true');
        element.setAttribute('data-erp-operation-guard-owned', 'true');
        rememberAndDecorate(element);
        busyElements.add(element);
        showStatus(actionTitle(element));

        if (timeout) {
            window.setTimeout(function () {
                if (activeRequests === 0) releaseElement(element);
            }, timeout);
        }
        return true;
    }

    function releaseElement(element) {
        if (!element) return;
        var original = element.getAttribute('data-erp-operation-original-html');
        if (original !== null && element.tagName !== 'INPUT') {
            element.innerHTML = original;
        }
        if (element.getAttribute('data-erp-operation-disabled') === 'true') {
            element.disabled = false;
        }
        element.removeAttribute('data-erp-operation-original-html');
        element.removeAttribute('data-erp-operation-busy');
        element.removeAttribute('data-erp-operation-disabled');
        element.removeAttribute('data-erp-operation-guard-owned');
        element.removeAttribute('aria-busy');
        busyElements.delete(element);
    }

    function releaseForm(form) {
        if (!form) return;
        form.removeAttribute('data-erp-operation-busy');
        form.removeAttribute('data-erp-operation-guard-owned');
        form.removeAttribute('aria-busy');
        var mirrors = form.querySelectorAll('input[data-erp-operation-submitter-mirror="true"]');
        Array.prototype.forEach.call(mirrors, function (mirror) { mirror.remove(); });
        var controls = form.querySelectorAll('[data-erp-operation-disabled="true"]');
        Array.prototype.forEach.call(controls, function (control) {
            control.disabled = false;
            control.removeAttribute('data-erp-operation-disabled');
        });
        busyForms.delete(form);
    }

    function releaseAll() {
        Array.from(busyElements).forEach(releaseElement);
        Array.from(busyForms).forEach(releaseForm);
        activeRequests = 0;
        hideStatus();
    }

    function formMethod(form) {
        var override = form.querySelector('input[name="_method"]');
        return ((override && override.value) || form.getAttribute('method') || 'GET').toUpperCase();
    }

    function isMutation(method) {
        return MUTATION_METHODS.indexOf(String(method || 'GET').toUpperCase()) !== -1;
    }

    function preserveSubmitterValue(form, submitter) {
        if (!submitter || !submitter.name) return;
        var mirror = document.createElement('input');
        mirror.type = 'hidden';
        mirror.name = submitter.name;
        mirror.value = submitter.value;
        mirror.setAttribute('data-erp-operation-submitter-mirror', 'true');
        form.appendChild(mirror);
    }

    function lockForm(form, submitter) {
        if (form.getAttribute('data-erp-operation-busy') === 'true') {
            showAlreadyProcessing();
            return false;
        }
        form.setAttribute('data-erp-operation-busy', 'true');
        form.setAttribute('data-erp-operation-guard-owned', 'true');
        form.setAttribute('aria-busy', 'true');
        busyForms.add(form);
        preserveSubmitterValue(form, submitter);

        var controls = form.querySelectorAll('button[type="submit"], input[type="submit"], input[type="image"]');
        Array.prototype.forEach.call(controls, function (control) {
            if (!control.disabled) {
                control.disabled = true;
                control.setAttribute('data-erp-operation-disabled', 'true');
            }
        });
        if (submitter && !submitter.disabled && submitter.getAttribute('aria-busy') !== 'true') {
            rememberAndDecorate(submitter);
            submitter.setAttribute('data-erp-operation-busy', 'true');
            submitter.setAttribute('data-erp-operation-guard-owned', 'true');
            submitter.setAttribute('aria-busy', 'true');
            busyElements.add(submitter);
        }
        showStatus(actionTitle(submitter));

        // Safety recovery for client-side validation/AJAX code that keeps the page open.
        window.setTimeout(function () {
            if (document.documentElement.contains(form) && activeRequests === 0) {
                releaseElement(submitter);
                releaseForm(form);
                hideStatus();
            }
        }, 120000);
        return true;
    }

    function controlText(control) {
        return ((control && (control.getAttribute('data-operation-label') || control.textContent || control.value)) || '').replace(/\s+/g, ' ').trim();
    }

    function isActionControl(control) {
        if (!control || isOptedOut(control)) return false;
        if (isTabControl(control)) return false;
        if (closest(control, '[data-dismiss], [data-bs-dismiss], [data-toggle="modal"], [data-bs-toggle="modal"], .close, .modal-close')) return false;
        if (control.matches('button[type="submit"], input[type="submit"], input[type="image"]')) return false;
        if (control.tagName === 'A') {
            var href = control.getAttribute('href') || '';
            var ajaxLike = control.hasAttribute('onclick') || control.hasAttribute('data-url') || control.classList.contains('ajax-modal') || control.classList.contains('delete_item');
            if (href && href !== '#' && href.indexOf('javascript:') !== 0 && !ajaxLike) return false;
        }
        var text = controlText(control);
        return ACTION_WORDS.test(text) && !EXCLUDED_WORDS.test(text);
    }

    function requestStarted() {
        activeRequests += 1;
        var activeElement = document.activeElement;
        var activeControl = closest(activeElement, 'button, input[type="submit"], input[type="button"], input[type="image"], a');
        var activeForm = activeElement && (activeElement.form || closest(activeElement, 'form'));

        // This also covers AJAX forms submitted with the Enter key and legacy
        // handlers that stop the submit event before it reaches document.
        if (activeForm && !isOptedOut(activeForm) && isMutation(formMethod(activeForm))) {
            if (!lockForm(activeForm, activeControl)) {
                showStatus('Processing&hellip;');
            }
            return;
        }

        if (activeControl && !isOptedOut(activeControl) &&
            !isTabControl(activeControl) &&
            (activeControl.matches('button[type="submit"], button:not([type]), input[type="submit"], input[type="image"]') || isActionControl(activeControl))) {
            // AJAX form handlers commonly prevent the native submit event. Lock the
            // initiating control here so those forms still receive duplicate protection.
            if (!markElementBusy(activeControl, 120000)) {
                showStatus('Processing&hellip;');
            }
        } else {
            showStatus('Processing&hellip;');
        }
    }

    function requestFinished(success) {
        activeRequests = Math.max(0, activeRequests - 1);
        if (activeRequests > 0) return;
        Array.from(busyElements).forEach(releaseElement);
        Array.from(busyForms).forEach(releaseForm);
        if (success === false) {
            showRequestError();
        } else {
            hideStatus();
        }
    }

    document.addEventListener('click', function (event) {
        var control = closest(event.target, 'button, input[type="submit"], input[type="button"], input[type="image"], a');
        if (!control || isOptedOut(control)) return;

        // Tabs must remain available even when their surrounding form is busy.
        if (isTabControl(control)) return;

        var form = control.form || closest(control, 'form');
        var duplicateSensitive = control.matches('button[type="submit"], button:not([type]), input[type="submit"], input[type="button"], input[type="image"]')
            || isActionControl(control);
        if (control.getAttribute('data-erp-operation-busy') === 'true'
            || (duplicateSensitive && form && form.getAttribute('data-erp-operation-busy') === 'true')) {
            event.preventDefault();
            event.stopImmediatePropagation();
            showAlreadyProcessing();
            return;
        }

        if (isActionControl(control)) {
            // Run after existing click handlers. If they already supplied a loader/disabled
            // state, the guard leaves their presentation untouched.
            afterCurrentEvent(function () {
                if (!event.defaultPrevented && activeRequests > 0 && document.documentElement.contains(control)) {
                    markElementBusy(control, 120000);
                }
            });
        }
    }, true);

    // Capture repeat keyboard/programmatic submits before page handlers can run.
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || isOptedOut(form) || !isMutation(formMethod(form))) return;
        if (form.getAttribute('data-erp-operation-busy') === 'true') {
            event.preventDefault();
            event.stopImmediatePropagation();
            showAlreadyProcessing();
        }
    }, true);

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || isOptedOut(form) || !isMutation(formMethod(form))) return;
        if (event.defaultPrevented) return;
        if (form.getAttribute('data-erp-operation-busy') === 'true') {
            event.preventDefault();
            event.stopImmediatePropagation();
            showAlreadyProcessing();
            return;
        }
        lockForm(form, event.submitter || document.activeElement);
    }, false);

    // Mutation XHRs include jQuery AJAX and legacy module requests.
    if (window.XMLHttpRequest && !window.XMLHttpRequest.prototype.__erpOperationGuardPatched) {
        var originalOpen = window.XMLHttpRequest.prototype.open;
        var originalSend = window.XMLHttpRequest.prototype.send;
        window.XMLHttpRequest.prototype.open = function (method) {
            this.__erpOperationMethod = String(method || 'GET').toUpperCase();
            return originalOpen.apply(this, arguments);
        };
        window.XMLHttpRequest.prototype.send = function () {
            var xhr = this;
            var guarded = isMutation(xhr.__erpOperationMethod);
            if (guarded) {
                requestStarted();
                xhr.addEventListener('loadend', function () {
                    requestFinished(xhr.status >= 200 && xhr.status < 400);
                }, { once: true });
            }
            try {
                return originalSend.apply(xhr, arguments);
            } catch (error) {
                if (guarded) requestFinished(false);
                throw error;
            }
        };
        window.XMLHttpRequest.prototype.__erpOperationGuardPatched = true;
    }

    // Modern modules may use fetch instead of jQuery/XHR.
    if (window.fetch && !window.fetch.__erpOperationGuardPatched) {
        var originalFetch = window.fetch;
        var guardedFetch = function (input, init) {
            var method = (init && init.method) || (input && input.method) || 'GET';
            if (!isMutation(method)) return originalFetch.apply(window, arguments);
            requestStarted();
            return originalFetch.apply(window, arguments).then(function (response) {
                requestFinished(response.ok);
                return response;
            }, function (error) {
                requestFinished(false);
                throw error;
            });
        };
        guardedFetch.__erpOperationGuardPatched = true;
        window.fetch = guardedFetch;
    }

    window.addEventListener('pageshow', function (event) {
        if (event.persisted) releaseAll();
    });

    window.ErpOperationGuard = {
        version: VERSION,
        releaseAll: releaseAll,
        isBusy: function () { return activeRequests > 0 || busyElements.size > 0 || busyForms.size > 0; },
        start: function (element, label) {
            if (element && label) element.setAttribute('data-operation-label', label);
            return markElementBusy(element, 120000);
        },
        finish: function (element) {
            if (element) releaseElement(element);
            if (activeRequests === 0) hideStatus();
        }
    };
})(window, document);
