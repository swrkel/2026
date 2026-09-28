/*
 * PetroDirect Add Payment tab controller.
 *
 * The Add Payment modal body is fetched with jQuery.load().  Keeping the tab
 * controller in that response made its execution dependent on how jQuery
 * handled embedded script tags.  This module-owned asset is loaded by the
 * stable Create/Edit page, before any modal is fetched, and uses delegated
 * events so it also controls markup inserted later.
 */
(function (window, document) {
    'use strict';

    var rootSelector = '.s271-direct-payment-tabs';
    var buttonSelector = '[data-petrodirect-payment-tab]';

    function directChildWithClass(root, className) {
        if (!root) return null;

        for (var i = 0; i < root.children.length; i++) {
            if (root.children[i].classList.contains(className)) {
                return root.children[i];
            }
        }

        return null;
    }

    function closestPaymentButton(node) {
        while (node && node !== document) {
            if (node.nodeType === 1 && node.hasAttribute('data-petrodirect-payment-tab')) {
                return node;
            }
            node = node.parentNode;
        }

        return null;
    }

    function ownsButton(root, nav, button) {
        return Boolean(
            root && nav && button && button.parentNode &&
            button.parentNode.parentNode === nav &&
            button.closest(rootSelector) === root
        );
    }

    function resizeVisibleTables(pane) {
        if (!window.jQuery || !pane) return;

        window.setTimeout(function () {
            try {
                if (window.jQuery.fn && window.jQuery.fn.dataTable) {
                    window.jQuery(pane).find('table.dataTable').each(function () {
                        try {
                            window.jQuery(this).DataTable().columns.adjust();
                        } catch (ignore) {}
                    });
                }
            } catch (ignore) {}
        }, 0);
    }

    function activate(button) {
        if (!button) return false;

        var root = button.closest ? button.closest(rootSelector) : null;
        var nav = directChildWithClass(root, 'nav-tabs');
        var content = directChildWithClass(root, 's1667-payment-tab-content');
        var targetId = button.getAttribute('data-petrodirect-payment-tab');
        var pane = targetId && content ? document.getElementById(targetId) : null;

        if (!root || !nav || !content || !pane || pane.parentNode !== content ||
            !ownsButton(root, nav, button)) {
            return false;
        }

        if (button.parentNode.classList.contains('disabled')) {
            return false;
        }

        for (var i = 0; i < nav.children.length; i++) {
            var item = nav.children[i];
            var itemButton = null;

            for (var b = 0; b < item.children.length; b++) {
                if (item.children[b].hasAttribute('data-petrodirect-payment-tab')) {
                    itemButton = item.children[b];
                    break;
                }
            }

            var selected = itemButton === button;
            item.classList.remove('business-manage-disabled-tab');
            item.removeAttribute('disabled');
            item.removeAttribute('aria-hidden');
            item.classList.toggle('active', selected);
            item.classList.toggle('show', selected);

            if (itemButton) {
                itemButton.classList.remove('business-manage-disabled-tab');
                itemButton.removeAttribute('disabled');
                itemButton.removeAttribute('data-auto-permission-blocked');
                itemButton.removeAttribute('aria-hidden');
                itemButton.style.setProperty('pointer-events', 'auto', 'important');
                itemButton.classList.toggle('active', selected);
                itemButton.setAttribute('aria-selected', selected ? 'true' : 'false');
                itemButton.setAttribute('aria-expanded', selected ? 'true' : 'false');
                itemButton.setAttribute('aria-pressed', selected ? 'true' : 'false');
            }
        }

        for (var p = 0; p < content.children.length; p++) {
            var candidate = content.children[p];
            if (!candidate.classList.contains('tab-pane')) continue;

            var visible = candidate === pane;
            candidate.classList.remove('business-manage-disabled-tab');
            candidate.removeAttribute('disabled');
            candidate.removeAttribute('data-auto-permission-blocked');
            candidate.classList.toggle('active', visible);
            candidate.classList.toggle('show', visible);
            candidate.classList.toggle('in', visible);
            candidate.style.setProperty('display', visible ? 'block' : 'none', 'important');
            candidate.style.setProperty('visibility', visible ? 'visible' : 'hidden', 'important');
            candidate.style.setProperty('pointer-events', visible ? 'auto' : 'none', 'important');
            candidate.setAttribute('aria-hidden', visible ? 'false' : 'true');
        }

        root.setAttribute('data-petrodirect-active-payment-tab', targetId);
        resizeVisibleTables(pane);

        if (window.jQuery) {
            window.jQuery(button).trigger('shown.bs.tab');
        }

        return true;
    }

    function initialiseRoot(root) {
        if (!root) return false;

        var nav = directChildWithClass(root, 'nav-tabs');
        if (!nav) return false;

        var savedId = root.getAttribute('data-petrodirect-active-payment-tab');
        var button = savedId
            ? nav.querySelector('[data-petrodirect-payment-tab="' + savedId + '"]')
            : null;

        button = button ||
            nav.querySelector('li.active > ' + buttonSelector) ||
            nav.querySelector(buttonSelector);

        return activate(button);
    }

    function initialiseAll(scope) {
        var searchRoot = scope && scope.querySelectorAll ? scope : document;
        var roots = searchRoot.querySelectorAll(rootSelector);

        for (var i = 0; i < roots.length; i++) {
            initialiseRoot(roots[i]);
        }

        if (scope && scope.classList && scope.classList.contains(rootSelector.substring(1))) {
            initialiseRoot(scope);
        }
    }

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
        return initialiseRoot(node || document.querySelector(rootSelector));
    };

    function handleClick(event) {
        var button = closestPaymentButton(event.target);
        if (!button || !button.closest || !button.closest(rootSelector)) return;

        window.petroDirectOpenPaymentTab(button, event);
    }

    function handleKeydown(event) {
        if (event.key !== 'Enter' && event.key !== ' ') return;

        var button = closestPaymentButton(event.target);
        if (!button || !button.closest || !button.closest(rootSelector)) return;

        window.petroDirectOpenPaymentTab(button, event);
    }

    /* Capture phase wins over Bootstrap/global delegated tab handlers.  Register
       once on the stable document; AJAX modal replacement cannot remove it. */
    document.addEventListener('click', handleClick, true);
    document.addEventListener('keydown', handleKeydown, true);

    if (window.jQuery) {
        window.jQuery(document)
            .off('shown.bs.modal.petroDirectPaymentTabs')
            .on('shown.bs.modal.petroDirectPaymentTabs', '.add_payment', function () {
                initialiseAll(this);
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initialiseAll(document);
        }, { once: true });
    } else {
        initialiseAll(document);
    }
})(window, document);
