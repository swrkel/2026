/** ERP Global Design System v1 - tab compatibility 2026.08.02-4 */
(function (window, document, $) {
    'use strict';

    var TAB_CONTROL_SELECTOR = [
        'a[data-toggle="tab"]',
        'button[data-toggle="tab"]',
        'a[data-toggle="pill"]',
        'button[data-toggle="pill"]',
        'a[data-bs-toggle="tab"]',
        'button[data-bs-toggle="tab"]',
        'a[data-bs-toggle="pill"]',
        'button[data-bs-toggle="pill"]',
        '[role="tab"]',
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
    ].join(',');

    function samePageHash(rawValue) {
        var value = String(rawValue || '').trim();
        if (!value || value === '#') {
            return '';
        }

        if (value.charAt(0) === '#') {
            return value;
        }

        try {
            var parsed = new window.URL(value, window.location.href);
            var current = new window.URL(window.location.href);
            var parsedPath = parsed.pathname.replace(/\/+$/, '') || '/';
            var currentPath = current.pathname.replace(/\/+$/, '') || '/';

            if (parsed.origin === current.origin && parsedPath === currentPath && parsed.hash.length > 1) {
                return parsed.hash;
            }
        } catch (error) {
            return '';
        }

        return '';
    }

    function selectorFromTab(tab) {
        if (!tab) {
            return '';
        }

        var selector = tab.getAttribute('data-bs-target') ||
            tab.getAttribute('data-target') ||
            tab.getAttribute('data-tab-target') ||
            tab.getAttribute('data-mpcs-tab-target') ||
            tab.getAttribute('href') || '';

        return samePageHash(selector);
    }

    function paneFromTab(tab) {
        var selector = selectorFromTab(tab);
        var pane = null;

        if (!selector) {
            return null;
        }

        try {
            pane = document.querySelector(selector);
        } catch (error) {
            return null;
        }

        if (!pane) {
            return null;
        }

        var isExplicitTab = tab.matches(TAB_CONTROL_SELECTOR);
        var isTabPane = pane.classList.contains('tab-pane') ||
            pane.getAttribute('role') === 'tabpanel' ||
            (pane.parentElement && pane.parentElement.classList.contains('tab-content'));

        return (isExplicitTab || isTabPane) ? pane : null;
    }

    /*
     * Several legacy pages read the href and pass it straight to jQuery as a
     * selector. Tenant URL helpers may expand #pane into /route#pane, which is
     * a valid URL but an invalid jQuery selector. Restore the hash-only href
     * before any page-specific bubble handler receives the click.
     */
    function normaliseTabHref(tab, pane) {
        if (!tab || !pane || tab.tagName !== 'A' || !tab.hasAttribute('href')) {
            return;
        }

        var originalHref = String(tab.getAttribute('href') || '').trim();
        var selector = selectorFromTab(tab);
        if (!selector || originalHref === selector) {
            return;
        }

        if (!tab.hasAttribute('data-erp-original-tab-href')) {
            tab.setAttribute('data-erp-original-tab-href', originalHref);
        }
        tab.setAttribute('href', selector);
    }

    function normaliseTabLinks(context) {
        var root = context || document;
        if (!root.querySelectorAll) {
            return;
        }

        var candidates = root.querySelectorAll(TAB_CONTROL_SELECTOR + ', a[href*="#"]');
        Array.prototype.forEach.call(candidates, function (candidate) {
            var pane = paneFromTab(candidate);
            if (pane) {
                normaliseTabHref(candidate, pane);
            }
        });
    }

    function tabControlFromEventTarget(target) {
        if (!target || !target.closest) {
            return null;
        }

        var tab = target.closest('a, button, [role="tab"]');
        if (!tab || !paneFromTab(tab)) {
            return null;
        }

        return tab;
    }

    function isDisabledTab(tab) {
        return !tab ||
            tab.disabled === true ||
            tab.getAttribute('aria-disabled') === 'true' ||
            tab.classList.contains('disabled') ||
            tab.classList.contains('business-manage-disabled-tab') ||
            (tab.parentElement && tab.parentElement.classList.contains('disabled'));
    }

    function paneIsActive(pane) {
        if (!pane) {
            return false;
        }

        var style = window.getComputedStyle ? window.getComputedStyle(pane) : null;
        return pane.classList.contains('active') &&
            (!style || (style.display !== 'none' && style.visibility !== 'hidden'));
    }

    function deactivateSiblingTabs(tab, activePane) {
        var tabList = tab.closest('[role="tablist"], .nav-tabs, .nav-pills, .erp-tabs, .module-tabs, ul.nav, ol.nav');
        if (!tabList && tab.parentElement) {
            tabList = tab.parentElement.tagName === 'LI' && tab.parentElement.parentElement
                ? tab.parentElement.parentElement
                : tab.parentElement;
        }
        if (!tabList) {
            return;
        }

        Array.prototype.forEach.call(tabList.querySelectorAll('a, button, [role="tab"]'), function (candidate) {
            if (candidate === tab || !paneFromTab(candidate)) {
                return;
            }

            candidate.classList.remove('active');
            candidate.setAttribute('aria-selected', 'false');
            if (candidate.parentElement) {
                candidate.parentElement.classList.remove('active');
            }

            var candidatePane = paneFromTab(candidate);
            if (candidatePane && candidatePane !== activePane) {
                candidatePane.classList.remove('active', 'show', 'in');
                candidatePane.setAttribute('aria-hidden', 'true');
                if (candidatePane.style && candidatePane.style.display === 'block') {
                    candidatePane.style.display = '';
                }
            }
        });
    }

    function activatePaneFallback(tab, pane) {
        if (!tab || !pane || paneIsActive(pane)) {
            return false;
        }

        deactivateSiblingTabs(tab, pane);

        if (pane.parentElement) {
            Array.prototype.forEach.call(pane.parentElement.children, function (candidate) {
                if (candidate === pane || !candidate.classList.contains('tab-pane')) {
                    return;
                }

                candidate.classList.remove('active', 'show', 'in');
                candidate.setAttribute('aria-hidden', 'true');
                if (candidate.style && candidate.style.display === 'block') {
                    candidate.style.display = '';
                }
            });
        }

        pane.classList.add('active', 'show', 'in');
        pane.setAttribute('aria-hidden', 'false');
        if (window.getComputedStyle && window.getComputedStyle(pane).display === 'none') {
            pane.style.display = 'block';
        }

        tab.classList.add('active');
        tab.setAttribute('aria-selected', 'true');
        if (tab.parentElement) {
            tab.parentElement.classList.add('active');
        }

        if ($) {
            $(tab).trigger('shown.bs.tab', { relatedTarget: null, erpGlobalFallback: true });
        }

        return true;
    }

    function requestTabActivation(tab) {
        var pane = paneFromTab(tab);
        if (!pane || isDisabledTab(tab)) {
            return;
        }

        normaliseTabHref(tab, pane);

        try {
            if (window.bootstrap && window.bootstrap.Tab) {
                var bootstrapTab = typeof window.bootstrap.Tab.getOrCreateInstance === 'function'
                    ? window.bootstrap.Tab.getOrCreateInstance(tab)
                    : new window.bootstrap.Tab(tab);
                bootstrapTab.show();
            } else if ($ && $.fn && typeof $.fn.tab === 'function') {
                $(tab).tab('show');
            }
        } catch (error) {
            // The deterministic fallback below handles mixed/legacy markup.
        }

        window.setTimeout(function () {
            activatePaneFallback(tab, pane);
            syncTabs(document);
        }, 25);
    }

    function syncTabs(context) {
        var root = context || document;
        normaliseTabLinks(root);
        $(root).find(TAB_CONTROL_SELECTOR).each(function () {
            var $tab = $(this);
            var active = $tab.closest('li').hasClass('active') || $tab.hasClass('active') || $tab.attr('aria-selected') === 'true';
            $tab.toggleClass('active', active).attr('aria-selected', active ? 'true' : 'false');
        });
    }

    function enhanceButtons(context) {
        var root = context || document;
        $(root).find('.btn-add, .add-btn, [data-erp-action="add"]').addClass('erp-btn erp-btn-add');
        $(root).find('.btn-save, .save-btn, [data-erp-action="save"]').addClass('erp-btn erp-btn-save');
        $(root).find('.btn-delete, .delete-btn, [data-erp-action="delete"]').addClass('erp-btn erp-btn-delete');
        $(root).find('.btn-print, .print-btn, [data-erp-action="print"]').addClass('erp-btn erp-btn-print');
    }

    function enhanceDatatables() {
        if (!$.fn || !$.fn.dataTable) {
            return;
        }
        $(document).on('init.dt', function (e, settings) {
            var $table = $(settings.nTable);
            $table.addClass('erp-table');
            $table.closest('.dataTables_wrapper').addClass('erp-datatable-wrapper');
        });
    }

    window.ERPDesignSystem = {
        version: '2026.08.02-4',
        syncTabs: syncTabs,
        enhanceButtons: enhanceButtons,
        openTab: requestTabActivation,
        refresh: function (context) {
            syncTabs(context);
            enhanceButtons(context);
        }
    };

    $(function () {
        syncTabs(document);
        enhanceButtons(document);
        enhanceDatatables();
    });

    document.addEventListener('click', function (event) {
        var tab = tabControlFromEventTarget(event.target);
        if (tab && !isDisabledTab(tab)) {
            normaliseTabHref(tab, paneFromTab(tab));
            requestTabActivation(tab);
        }
    }, true);

    $(document).on('shown.bs.tab click', TAB_CONTROL_SELECTOR, function () {
        setTimeout(function () { syncTabs(document); }, 10);
    });

    $(document).ajaxComplete(function () {
        window.ERPDesignSystem.refresh(document);
    });
})(window, document, window.jQuery);

// ERP Global Design System V2 helpers
window.ERPDesign = window.ERPDesign || {};
window.ERPDesign.initDatatable = function(selector, options) {
    if (!window.jQuery || !jQuery.fn.DataTable || !jQuery(selector).length) return null;
    var defaults = {
        responsive: true,
        stateSave: true,
        autoWidth: false,
        scrollX: true,
        language: { search: '', searchPlaceholder: 'Search...' }
    };
    return jQuery(selector).DataTable(jQuery.extend(true, {}, defaults, options || {}));
};
window.ERPDesign.showLoading = function(target) {
    var $target = window.jQuery ? jQuery(target || 'body') : null;
    if (!$target || !$target.length) return;
    $target.addClass('erp-loading');
};
window.ERPDesign.hideLoading = function(target) {
    var $target = window.jQuery ? jQuery(target || 'body') : null;
    if (!$target || !$target.length) return;
    $target.removeClass('erp-loading');
};
window.ERPDesign.refreshSelect2 = function(scope) {
    if (!window.jQuery || !jQuery.fn.select2) return;
    jQuery(scope || document).find('select.select2, select.form-control').each(function() {
        if (!jQuery(this).data('select2')) jQuery(this).select2({ width: '100%' });
    });
};
