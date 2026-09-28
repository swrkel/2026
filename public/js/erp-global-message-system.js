(function (window, document) {
    'use strict';

    if (window.ErpGlobalMessages && window.ErpGlobalMessages.version) {
        return;
    }

    var VERSION = '2026.08.01-1';
    var DEDUPE_WINDOW = 1400;
    var recent = Object.create(null);
    var recentResult = Object.create(null);

    function cleanText(value) {
        if (value === null || typeof value === 'undefined') return '';
        if (Array.isArray(value)) return cleanText(value[0]);
        if (typeof value === 'object') {
            var keys = Object.keys(value);
            return keys.length ? cleanText(value[keys[0]]) : '';
        }
        return String(value).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
    }

    function firstValidationMessage(errors) {
        if (!errors || typeof errors !== 'object') return '';
        var keys = Object.keys(errors);
        return keys.length ? cleanText(errors[keys[0]]) : '';
    }

    function noticeKey(type, message) {
        return type + '|' + cleanText(message).toLowerCase();
    }

    function isDuplicate(type, message) {
        var key = noticeKey(type, message);
        var now = Date.now();
        if (recent[key] && now - recent[key] < DEDUPE_WINDOW) return true;
        recent[key] = now;
        window.setTimeout(function () {
            if (recent[key] && Date.now() - recent[key] >= DEDUPE_WINDOW) {
                delete recent[key];
                delete recentResult[key];
            }
        }, DEDUPE_WINDOW + 100);
        return false;
    }

    function configureToastr() {
        if (!window.toastr) return false;
        window.toastr.options = Object.assign({}, window.toastr.options || {}, {
            closeButton: true,
            newestOnTop: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            preventDuplicates: true,
            timeOut: 4200,
            extendedTimeOut: 1500,
            tapToDismiss: true
        });
        return true;
    }

    function wrapToastrMethod(type) {
        if (!window.toastr || typeof window.toastr[type] !== 'function') return;
        var original = window.toastr[type];
        if (original.__erpGlobalMessageWrapped) return;

        var wrapped = function (message, title, optionsOverride) {
            message = cleanText(message) || (type === 'success' ? 'Operation completed successfully.' : 'The operation could not be completed.');
            var key = noticeKey(type, message);
            if (isDuplicate(type, message)) return recentResult[key] || null;
            var result = original.call(window.toastr, message, title, optionsOverride);
            recentResult[key] = result;
            return result;
        };
        wrapped.__erpGlobalMessageWrapped = true;
        wrapped.__erpOriginal = original;
        window.toastr[type] = wrapped;
    }

    function ensureFallbackContainer() {
        var container = document.getElementById('erp-global-message-container');
        if (container) return container;
        container = document.createElement('div');
        container.id = 'erp-global-message-container';
        container.setAttribute('aria-live', 'polite');
        document.body.appendChild(container);
        return container;
    }

    function fallbackNotice(type, message, title) {
        if (isDuplicate(type, message)) return null;
        var notice = document.createElement('div');
        notice.className = 'erp-global-message erp-global-message--' + type;
        notice.setAttribute('role', type === 'error' ? 'alert' : 'status');
        var heading = document.createElement('strong');
        heading.className = 'erp-global-message__title';
        heading.textContent = title || (type === 'success' ? 'Success' : 'Failed');
        var body = document.createElement('span');
        body.textContent = cleanText(message);
        notice.appendChild(heading);
        notice.appendChild(body);
        ensureFallbackContainer().appendChild(notice);
        window.setTimeout(function () { notice.remove(); }, 4500);
        return notice;
    }

    function notify(type, message, title) {
        message = cleanText(message) || (type === 'success' ? 'Operation completed successfully.' : 'The operation could not be completed.');
        if (configureToastr()) {
            wrapToastrMethod('success');
            wrapToastrMethod('error');
            return window.toastr[type](message, title || (type === 'success' ? 'Success' : 'Failed'));
        }
        return fallbackNotice(type, message, title);
    }

    function responseState(payload) {
        if (!payload || typeof payload !== 'object') return null;
        var value;
        if (Object.prototype.hasOwnProperty.call(payload, 'success')) value = payload.success;
        else if (Object.prototype.hasOwnProperty.call(payload, 'ok')) value = payload.ok;
        else if (Object.prototype.hasOwnProperty.call(payload, 'status')) value = payload.status;
        else return null;

        if (value === true || value === 1 || value === '1' || value === 'success' || value === 'ok') return 'success';
        if (value === false || value === 0 || value === '0' || value === 'error' || value === 'failed' || value === 'fail') return 'error';
        return null;
    }

    function responseMessage(payload, type) {
        if (!payload || typeof payload !== 'object') return '';
        return cleanText(payload.msg || payload.message || payload.error || firstValidationMessage(payload.errors)) ||
            (type === 'success' ? 'Operation completed successfully.' : 'The operation could not be completed.');
    }

    function notifyFromPayload(payload) {
        var type = responseState(payload);
        if (!type) return;
        notify(type, responseMessage(payload, type));
    }

    function parseJson(text) {
        if (!text || typeof text !== 'string') return null;
        var trimmed = text.trim();
        if (!trimmed || (trimmed.charAt(0) !== '{' && trimmed.charAt(0) !== '[')) return null;
        try { return JSON.parse(trimmed); } catch (error) { return null; }
    }

    function normalizeMessageNode(node) {
        if (!node || node.nodeType !== 1) return;
        if (node.matches('.toast-success, .alert-success, .erp-global-message--success')) {
            node.setAttribute('role', 'status');
            node.setAttribute('aria-live', 'polite');
        }
        if (node.matches('.toast-error, .toast-danger, .alert-danger, .alert-error, .erp-global-message--error')) {
            node.setAttribute('role', 'alert');
            node.setAttribute('aria-live', 'assertive');
        }
        var children = node.querySelectorAll('.toast-success, .toast-error, .toast-danger, .alert-success, .alert-danger, .alert-error');
        Array.prototype.forEach.call(children, normalizeMessageNode);
    }

    function installAjaxResponseBridge() {
        if (!window.jQuery) return;
        window.jQuery(document).on('ajaxSuccess.erpGlobalMessages', function (event, xhr) {
            var payload = xhr && xhr.responseJSON ? xhr.responseJSON : parseJson(xhr && xhr.responseText);
            notifyFromPayload(payload);
        });
        window.jQuery(document).on('ajaxError.erpGlobalMessages', function (event, xhr) {
            var payload = xhr && xhr.responseJSON ? xhr.responseJSON : parseJson(xhr && xhr.responseText);
            var message = responseMessage(payload, 'error');
            if (payload && message) notify('error', message);
        });
    }

    function installFetchResponseBridge() {
        if (!window.fetch || window.fetch.__erpGlobalMessageWrapped) return;
        var originalFetch = window.fetch;
        var wrappedFetch = function () {
            return originalFetch.apply(window, arguments).then(function (response) {
                var contentType = response && response.headers && response.headers.get ? (response.headers.get('content-type') || '') : '';
                if (contentType.indexOf('json') !== -1 && response.clone) {
                    response.clone().json().then(notifyFromPayload).catch(function () {});
                }
                return response;
            });
        };
        wrappedFetch.__erpGlobalMessageWrapped = true;
        window.fetch = wrappedFetch;
    }

    configureToastr();
    wrapToastrMethod('success');
    wrapToastrMethod('error');
    installAjaxResponseBridge();
    installFetchResponseBridge();

    if (window.MutationObserver) {
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                Array.prototype.forEach.call(mutation.addedNodes, normalizeMessageNode);
            });
        }).observe(document.documentElement, { childList: true, subtree: true });
    }

    window.ErpGlobalMessages = {
        version: VERSION,
        success: function (message, title) { return notify('success', message, title); },
        error: function (message, title) { return notify('error', message, title); },
        fromResponse: notifyFromPayload
    };
    window.erpNotifySuccess = window.ErpGlobalMessages.success;
    window.erpNotifyError = window.ErpGlobalMessages.error;
})(window, document);
