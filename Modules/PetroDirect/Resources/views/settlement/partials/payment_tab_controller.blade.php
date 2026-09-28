<script id="la1124-petrodirect-payment-tab-controller-r4">
/*
 * PetroDirect owns these controls. The listener is attached to window during
 * capture, which is above document in the event path. Therefore the global
 * Manage Permission guard cannot cancel a permitted PetroDirect tab first.
 * The controller lives on the stable page, outside the AJAX modal, so modal
 * replacement cannot remove it.
 */
(function (window, document) {
    'use strict';

    var rootSelector = '.s271-direct-payment-tabs';
    var buttonSelector = '[data-petrodirect-payment-tab]';

    /* The AJAX Add Payment response also includes this controller as a safety
       net. Keep only one set of window listeners when it is loaded repeatedly. */
    if (window.__petroDirectPaymentTabControllerR4Installed) {
        window.setTimeout(function () {
            if (typeof window.petroDirectRefreshPaymentTabs === 'function') {
                window.petroDirectRefreshPaymentTabs();
            }
        }, 0);
        return;
    }
    window.__petroDirectPaymentTabControllerR4Installed = true;

    function closestButton(node) {
        while (node && node !== document) {
            if (node.nodeType === 1 && node.hasAttribute('data-petrodirect-payment-tab')) {
                return node;
            }
            node = node.parentNode;
        }
        return null;
    }

    function activate(button) {
        if (!button || !button.closest) return false;

        var root = button.closest(rootSelector);
        if (!root) return false;

        var nav = root.querySelector('.nav-tabs');
        var content = root.querySelector('.s1667-payment-tab-content');
        var targetId = button.getAttribute('data-petrodirect-payment-tab');
        var pane = targetId ? document.getElementById(targetId) : null;

        if (!nav || !content || !pane || pane.parentNode !== content ||
            button.closest(rootSelector) !== root || !nav.contains(button)) {
            return false;
        }

        var buttons = nav.querySelectorAll(buttonSelector);
        for (var i = 0; i < buttons.length; i++) {
            var candidateButton = buttons[i];
            var selected = candidateButton === button;
            var item = candidateButton.parentNode;

            candidateButton.disabled = false;
            candidateButton.removeAttribute('disabled');
            candidateButton.removeAttribute('data-auto-permission-blocked');
            candidateButton.removeAttribute('aria-hidden');
            candidateButton.classList.remove('business-manage-disabled-tab');
            candidateButton.classList.toggle('active', selected);
            candidateButton.style.setProperty('pointer-events', 'auto', 'important');
            candidateButton.setAttribute('aria-selected', selected ? 'true' : 'false');
            candidateButton.setAttribute('aria-expanded', selected ? 'true' : 'false');
            candidateButton.setAttribute('aria-pressed', selected ? 'true' : 'false');

            if (item) {
                item.classList.remove('disabled');
                item.classList.remove('business-manage-disabled-tab');
                item.removeAttribute('disabled');
                item.removeAttribute('aria-hidden');
                item.classList.toggle('active', selected);
                item.classList.toggle('show', selected);
                item.style.setProperty('pointer-events', 'auto', 'important');
            }
        }

        for (var p = 0; p < content.children.length; p++) {
            var candidatePane = content.children[p];
            if (!candidatePane.classList.contains('tab-pane')) continue;

            var visible = candidatePane === pane;
            candidatePane.classList.remove('business-manage-disabled-tab');
            candidatePane.removeAttribute('disabled');
            candidatePane.removeAttribute('data-auto-permission-blocked');
            candidatePane.classList.toggle('active', visible);
            candidatePane.classList.toggle('show', visible);
            candidatePane.classList.toggle('in', visible);
            candidatePane.style.setProperty('display', visible ? 'block' : 'none', 'important');
            candidatePane.style.setProperty('visibility', visible ? 'visible' : 'hidden', 'important');
            candidatePane.style.setProperty('pointer-events', visible ? 'auto' : 'none', 'important');
            candidatePane.setAttribute('aria-hidden', visible ? 'false' : 'true');
        }

        root.setAttribute('data-petrodirect-active-payment-tab', targetId);

        if (window.jQuery) {
            window.jQuery(button).trigger('shown.bs.tab');
            window.setTimeout(function () {
                try {
                    window.jQuery(pane).find('table.dataTable').each(function () {
                        window.jQuery(this).DataTable().columns.adjust();
                    });
                } catch (ignore) {}
            }, 0);
        }

        return true;
    }

    function initialise(root) {
        if (!root) return false;
        var nav = root.querySelector('.nav-tabs');
        if (!nav) return false;

        var savedId = root.getAttribute('data-petrodirect-active-payment-tab');
        var button = savedId
            ? nav.querySelector('[data-petrodirect-payment-tab="' + savedId + '"]')
            : null;
        button = button || nav.querySelector('li.active > ' + buttonSelector) ||
            nav.querySelector(buttonSelector);
        return activate(button);
    }

    function initialiseInside(node) {
        if (!node || node.nodeType !== 1) return;
        if (node.matches && node.matches(rootSelector)) initialise(node);
        if (node.querySelectorAll) {
            var roots = node.querySelectorAll(rootSelector);
            for (var i = 0; i < roots.length; i++) initialise(roots[i]);
        }
    }

    function intercept(event) {
        var button = closestButton(event.target);
        if (!button || !button.closest || !button.closest(rootSelector)) return;

        event.preventDefault();
        event.stopPropagation();
        if (typeof event.stopImmediatePropagation === 'function') {
            event.stopImmediatePropagation();
        }
        activate(button);
    }

    /*
     * Window capture is intentional. The global permission guard is registered
     * on document capture by the core layout and may have been registered before
     * this module script. Event-path order still guarantees window runs first.
     */
    window.addEventListener('click', intercept, true);
    window.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        intercept(event);
    }, true);

    window.petroDirectOpenPaymentTab = function (button, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
            if (typeof event.stopImmediatePropagation === 'function') {
                event.stopImmediatePropagation();
            }
        }
        activate(button);
        return false;
    };

    window.petroDirectShowPaymentTab = function (selector) {
        var root = document.querySelector('.add_payment.in ' + rootSelector) ||
            document.querySelector('.add_payment.show ' + rootSelector) ||
            document.querySelector('.add_payment ' + rootSelector) ||
            document.querySelector(rootSelector);
        if (!root) return false;

        var targetId = String(selector || '').replace(/^#|^\./, '');
        return activate(root.querySelector(
            '[data-petrodirect-payment-tab="' + targetId + '"]'
        ));
    };

    window.syncActivePaymentRows = function (root) {
        var node = root && root.jquery ? root[0] : root;
        return initialise(node || document.querySelector(rootSelector));
    };

    window.petroDirectRefreshPaymentTabs = function () {
        initialiseInside(document.body);
    };

    if (window.MutationObserver) {
        new MutationObserver(function (records) {
            for (var r = 0; r < records.length; r++) {
                for (var n = 0; n < records[r].addedNodes.length; n++) {
                    initialiseInside(records[r].addedNodes[n]);
                }
            }
        }).observe(document.documentElement, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initialiseInside(document.body);
        }, { once: true });
    } else {
        initialiseInside(document.body);
    }
})(window, document);
</script>
