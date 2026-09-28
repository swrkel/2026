/*
 * ERP Tenant URL Helper V3 Final
 * Keeps internal ERP navigation, modal/form actions, Ajax/Fetch/XHR calls and
 * new-tab links on the host that rendered the current page.
 * Central-only paths and external sites remain unchanged.
 */
(function (window, document) {
    'use strict';

    var CENTRAL_HOSTS = ['nivasa.shop', 'www.nivasa.shop'];
    var CENTRAL_ONLY_PREFIXES = [
        '/superadmin', '/tenant-management', '/install', '/register',
        '/password/reset', '/central', '/landlord', '/subscription', '/billing'
    ];
    var URL_ATTRIBUTES = [
        'href', 'data-href', 'data-url', 'data-action', 'data-route',
        'data-link', 'data-redirect', 'data-download', 'data-remote',
        'formaction'
    ];

    function hostname(value) {
        return String(value || '').toLowerCase().replace(/^www\./, '');
    }

    function isNivasaHost(value) {
        var host = hostname(value);
        return host === 'nivasa.shop' || host.slice(-12) === '.nivasa.shop';
    }

    function isTenantHost(value) {
        var host = hostname(value);
        return isNivasaHost(host) && host !== 'nivasa.shop';
    }

    function isCentralOnlyPath(pathname) {
        var path = String(pathname || '/').toLowerCase();
        return CENTRAL_ONLY_PREFIXES.some(function (prefix) {
            return path === prefix || path.indexOf(prefix + '/') === 0;
        });
    }

    function shouldIgnore(rawUrl) {
        var value = String(rawUrl || '').trim().toLowerCase();
        return !value || value === '#' || value.indexOf('javascript:') === 0 ||
            value.indexOf('mailto:') === 0 || value.indexOf('tel:') === 0 ||
            value.indexOf('data:') === 0 || value.indexOf('blob:') === 0 ||
            value.indexOf('about:') === 0 || value.indexOf('chrome-extension:') === 0;
    }

    function tenantSafeUrl(rawUrl) {
        if (rawUrl instanceof window.Request) {
            return tenantSafeUrl(rawUrl.url);
        }
        if (shouldIgnore(rawUrl)) {
            return rawUrl;
        }

        try {
            var current = new URL(window.location.href);
            var parsed = new URL(String(rawUrl), current.href);

            if (!isTenantHost(current.hostname)) {
                return rawUrl;
            }
            if (!isNivasaHost(parsed.hostname) || isCentralOnlyPath(parsed.pathname)) {
                return rawUrl;
            }

            if (hostname(parsed.hostname) !== hostname(current.hostname)) {
                parsed.protocol = current.protocol;
                parsed.host = current.host;
            }

            if (parsed.origin === current.origin) {
                return parsed.pathname + parsed.search + parsed.hash;
            }
            return parsed.href;
        } catch (ignore) {
            return rawUrl;
        }
    }

    function normalizeElement(element) {
        if (!element || !element.getAttribute) { return; }

        rewriteInlineHandler(element);

        URL_ATTRIBUTES.forEach(function (attribute) {
            if (!element.hasAttribute(attribute)) { return; }
            var original = element.getAttribute(attribute);
            var corrected = tenantSafeUrl(original);
            if (corrected && corrected !== original) {
                element.setAttribute(attribute, corrected);
            }
        });

        if (element.tagName === 'FORM' && element.hasAttribute('action')) {
            var action = element.getAttribute('action');
            var safeAction = tenantSafeUrl(action);
            if (safeAction && safeAction !== action) {
                element.setAttribute('action', safeAction);
            }
        }
    }

    function normalizeTree(root) {
        if (!root) { return; }
        if (root.nodeType === 1) { normalizeElement(root); }
        if (!root.querySelectorAll) { return; }
        root.querySelectorAll(
            'a[href], form[action], [data-href], [data-url], [data-action], ' +
            '[data-route], [data-link], [data-redirect], [data-download], ' +
            '[data-remote], [formaction]'
        ).forEach(normalizeElement);
    }



    function rewriteInlineHandler(element) {
        if (!element || !element.getAttribute || !isTenantHost(window.location.hostname)) { return; }
        ['onclick', 'onchange', 'onsubmit'].forEach(function (attribute) {
            if (!element.hasAttribute(attribute)) { return; }
            var value = element.getAttribute(attribute) || '';
            CENTRAL_HOSTS.forEach(function (centralHost) {
                value = value.replace(
                    new RegExp('https?:\\/\\/(?:www\\.)?' + centralHost.replace('.', '\\.') + '(?=\\/)', 'gi'),
                    window.location.origin
                );
            });
            element.setAttribute(attribute, value);
        });
    }

    function auditDocument() {
        var findings = [];
        if (!isTenantHost(window.location.hostname)) { return findings; }
        normalizeTree(document);
        document.querySelectorAll('*').forEach(function (element) {
            URL_ATTRIBUTES.concat(['action']).forEach(function (attribute) {
                if (!element.hasAttribute || !element.hasAttribute(attribute)) { return; }
                var value = element.getAttribute(attribute) || '';
                try {
                    var parsed = new URL(value, window.location.href);
                    if (hostname(parsed.hostname) === 'nivasa.shop' && !isCentralOnlyPath(parsed.pathname)) {
                        findings.push({ element: element, attribute: attribute, value: value });
                    }
                } catch (ignore) {}
            });
        });
        return findings;
    }

    function installProgrammaticNavigationProtection() {
        if (window.HTMLAnchorElement && window.HTMLAnchorElement.prototype.click) {
            var originalAnchorClick = window.HTMLAnchorElement.prototype.click;
            window.HTMLAnchorElement.prototype.click = function () {
                normalizeElement(this);
                return originalAnchorClick.apply(this, arguments);
            };
        }

        if (window.HTMLFormElement) {
            if (window.HTMLFormElement.prototype.submit) {
                var originalSubmit = window.HTMLFormElement.prototype.submit;
                window.HTMLFormElement.prototype.submit = function () {
                    normalizeElement(this);
                    return originalSubmit.apply(this, arguments);
                };
            }
            if (window.HTMLFormElement.prototype.requestSubmit) {
                var originalRequestSubmit = window.HTMLFormElement.prototype.requestSubmit;
                window.HTMLFormElement.prototype.requestSubmit = function (submitter) {
                    normalizeElement(this);
                    normalizeElement(submitter);
                    return originalRequestSubmit.apply(this, arguments);
                };
            }
        }

        if (window.history && window.history.pushState) {
            var originalPushState = window.history.pushState;
            window.history.pushState = function (state, title, url) {
                return originalPushState.call(this, state, title, tenantSafeUrl(url));
            };
        }
        if (window.history && window.history.replaceState) {
            var originalReplaceState = window.history.replaceState;
            window.history.replaceState = function (state, title, url) {
                return originalReplaceState.call(this, state, title, tenantSafeUrl(url));
            };
        }
    }

    function installJQueryProtection() {
        if (!window.jQuery) { return; }
        var $ = window.jQuery;

        if ($.ajaxPrefilter) {
            $.ajaxPrefilter(function (options, originalOptions) {
                var candidate = options.url || (originalOptions && originalOptions.url);
                var corrected = tenantSafeUrl(candidate);
                if (corrected) { options.url = corrected; }
            });
        }

        // Bootstrap 3 remote modal compatibility.
        $(document).on('show.bs.modal', '.modal', function (event) {
            var trigger = event && event.relatedTarget ? event.relatedTarget : null;
            normalizeElement(trigger);
        });
    }

    function installFetchProtection() {
        if (typeof window.fetch !== 'function') { return; }
        var originalFetch = window.fetch;
        window.fetch = function (input, init) {
            if (input instanceof window.Request) {
                var safeRequestUrl = tenantSafeUrl(input.url);
                if (safeRequestUrl !== input.url) {
                    input = new window.Request(safeRequestUrl, input);
                }
            } else {
                input = tenantSafeUrl(input);
            }
            return originalFetch.call(this, input, init);
        };
    }

    function installXhrProtection() {
        if (!window.XMLHttpRequest || !window.XMLHttpRequest.prototype.open) { return; }
        var originalOpen = window.XMLHttpRequest.prototype.open;
        window.XMLHttpRequest.prototype.open = function (method, url) {
            var args = Array.prototype.slice.call(arguments);
            args[1] = tenantSafeUrl(url);
            return originalOpen.apply(this, args);
        };
    }

    function installWindowOpenProtection() {
        var originalOpen = window.open;
        if (typeof originalOpen !== 'function') { return; }
        window.open = function (url) {
            var args = Array.prototype.slice.call(arguments);
            args[0] = tenantSafeUrl(url);
            return originalOpen.apply(window, args);
        };
    }

    function safeNavigate(rawUrl, replace) {
        var url = tenantSafeUrl(rawUrl);
        if (replace) {
            window.location.replace(url);
        } else {
            window.location.assign(url);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        normalizeTree(document);
        installJQueryProtection();

        if (window.MutationObserver && document.body) {
            new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    mutation.addedNodes.forEach(normalizeTree);
                    if (mutation.type === 'attributes') { normalizeElement(mutation.target); }
                });
            }).observe(document.body, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: URL_ATTRIBUTES.concat(['action'])
            });
        }
    });

    document.addEventListener('click', function (event) {
        var target = event.target && event.target.closest
            ? event.target.closest(
                'a[href], [data-href], [data-url], [data-action], [data-route], ' +
                '[data-link], [data-redirect], [data-download], [data-remote], [formaction]'
            )
            : null;
        normalizeElement(target);
    }, true);

    document.addEventListener('submit', function (event) {
        normalizeElement(event.target);
        if (event.submitter) { normalizeElement(event.submitter); }
    }, true);

    installFetchProtection();
    installXhrProtection();
    installWindowOpenProtection();
    installProgrammaticNavigationProtection();

    window.erpTenantSafeUrl = tenantSafeUrl;
    window.erpNormalizeTenantUrls = normalizeTree;
    window.erpTenantNavigate = safeNavigate;
    window.erpAuditTenantUrls = auditDocument;
})(window, document);
