{{--
    Unified sidebar search controller v15 - IS2242 urgent filter fix (11 Sep 2026)

    Search has ONE responsibility only: visibility filtering.

    Behaviour:
    - search by TOP-LEVEL MODULE parent/title only;
    - never use child-page labels as the module match;
    - never auto-open a matching module;
    - never auto-close an opened module;
    - preserve each module's open/closed state while typing or clearing search;
    - support direct sidebar items and module-owned partials wrapped by harmless containers;
    - hide non-matching module parents immediately and restore them immediately when cleared.
--}}
@once
<style id="erp-unified-sidebar-search-style-20260911-v15">
    /*
     * IMPORTANT: sidebar-critical-runtime-css.blade.php forces .nav-item to
     * display:block !important. These selectors intentionally have higher
     * specificity so a filtered module really disappears.
     */
    html body ul#accordionSidebar > li.erp-sidebar-search-hidden,
    html body ul#accordionSidebar li.nav-item.erp-sidebar-search-hidden,
    html body ul#accordionSidebar li.treeview.erp-sidebar-search-hidden,
    html body ul#accordionSidebar .erp-sidebar-search-hidden,
    html body ul.sidebar-menu li.erp-sidebar-search-hidden,
    html body ul.sidebar-menu .erp-sidebar-search-hidden,
    html body .nav-sidebar li.erp-sidebar-search-hidden,
    html body .nav-sidebar .erp-sidebar-search-hidden {
        display: none !important;
    }

    html body #accordionSidebar > li.erp-sidebar-search-wrap,
    html body ul.sidebar-menu > li.erp-sidebar-search-wrap,
    html body .nav-sidebar > li.erp-sidebar-search-wrap {
        display: block !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
</style>
<script id="erp-unified-sidebar-search-20260911-v15">
(function () {
    'use strict';

    if (window.__erpUnifiedSidebarSearchV15Loaded) {
        if (window.ERPUnifiedSidebarSearch && typeof window.ERPUnifiedSidebarSearch.refresh === 'function') {
            window.ERPUnifiedSidebarSearch.refresh();
        }
        return;
    }
    window.__erpUnifiedSidebarSearchV15Loaded = true;

    var ROOT_SELECTOR = '#accordionSidebar, ul.sidebar-menu, .nav-sidebar';
    var INPUT_SELECTOR = '#sidebarFilter, #global_sidebar_menu_search, [data-erp-sidebar-search-input="1"]';
    var HIDDEN_CLASS = 'erp-sidebar-search-hidden';
    var SEARCH_WRAP_CLASS = 'erp-sidebar-search-wrap';
    var observedRoots = new WeakSet();
    var activeTerms = new WeakMap();

    function toArray(list) {
        return Array.prototype.slice.call(list || []);
    }

    function normalizeWords(value) {
        return String(value || '')
            .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
            .replace(/&/g, ' and ')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function compact(value) {
        return normalizeWords(value).replace(/\s+/g, '');
    }

    function roots() {
        var found = toArray(document.querySelectorAll(ROOT_SELECTOR));
        return found.filter(function (root, index) {
            if (!root || found.indexOf(root) !== index) return false;
            var outer = root.parentElement ? root.parentElement.closest(ROOT_SELECTOR) : null;
            return !outer || outer === root;
        });
    }

    function directAnchor(item) {
        if (!item || !item.children) return null;
        for (var i = 0; i < item.children.length; i++) {
            if (String(item.children[i].tagName || '').toLowerCase() === 'a') {
                return item.children[i];
            }
        }
        return null;
    }

    function isPanelNode(node) {
        if (!node || !node.classList) return false;
        return node.hasAttribute('data-erp-sidebar-panel')
            || node.classList.contains('erp-sidebar-panel')
            || node.classList.contains('collapse')
            || node.classList.contains('collapse-inner')
            || node.classList.contains('treeview-menu')
            || node.classList.contains('nav-treeview')
            || node.classList.contains('sidebar-submenu')
            || node.classList.contains('submenu');
    }

    function isSearchContainer(item) {
        if (!item || !item.classList) return false;
        return item.classList.contains(SEARCH_WRAP_CLASS) || !!item.querySelector(INPUT_SELECTOR);
    }

    function insideSubmenu(item, root) {
        var node = item ? item.parentElement : null;
        while (node && node !== root) {
            if (isPanelNode(node)) return true;
            node = node.parentElement;
        }
        return false;
    }

    function isModuleItem(item, root) {
        if (!item || !root || String(item.tagName || '').toLowerCase() !== 'li') return false;
        if (isSearchContainer(item) || insideSubmenu(item, root)) return false;

        var anchor = directAnchor(item);
        if (!anchor) return false;

        // A top-level link, parent menu, or explicitly declared module is a module result.
        return item.classList.contains('nav-item')
            || item.classList.contains('treeview')
            || item.hasAttribute('data-sidebar-module')
            || item.hasAttribute('data-auto-module')
            || item.hasAttribute('data-auto-fallback')
            || item.hasAttribute('data-module-key')
            || item.parentElement === root;
    }

    function moduleItems(root) {
        return toArray(root.querySelectorAll('li')).filter(function (item) {
            return isModuleItem(item, root);
        });
    }

    function decodeAliasList(value) {
        if (!value) return [];
        try {
            var decoded = JSON.parse(atob(value));
            return Array.isArray(decoded) ? decoded : [];
        } catch (ignore) {
            return [];
        }
    }

    function moduleSearchText(item) {
        var anchor = directAnchor(item);
        if (!anchor) return '';

        var parts = [
            item.getAttribute('data-auto-title'),
            item.getAttribute('data-sidebar-module'),
            item.getAttribute('data-auto-module'),
            item.getAttribute('data-auto-fallback'),
            item.getAttribute('data-module'),
            item.getAttribute('data-module-key'),
            item.getAttribute('data-sidebar-key'),
            anchor.getAttribute('data-module-find'),
            anchor.getAttribute('data-sidebar-title'),
            anchor.getAttribute('aria-label'),
            anchor.textContent
        ];

        decodeAliasList(item.getAttribute('data-auto-aliases')).forEach(function (alias) {
            parts.push(alias);
        });

        return parts.filter(Boolean).join(' ');
    }

    function matchesSearch(value, rawTerm) {
        var haystack = normalizeWords(value);
        var query = normalizeWords(rawTerm);
        if (!query) return true;
        if (!haystack) return false;

        if (haystack.indexOf(query) !== -1) return true;

        var compactHaystack = compact(value);
        var compactQuery = compact(rawTerm);
        if (compactQuery && compactHaystack.indexOf(compactQuery) !== -1) return true;

        var tokens = query.split(' ').filter(Boolean);
        var withoutModule = tokens.filter(function (token) { return token !== 'module'; });
        if (withoutModule.length) tokens = withoutModule;

        return tokens.length > 0 && tokens.every(function (token) {
            return haystack.indexOf(token) !== -1 || compactHaystack.indexOf(token) !== -1;
        });
    }

    function setHidden(node, hidden) {
        if (!node || !node.classList || !node.style) return;

        if (hidden) {
            /*
             * The global sidebar runtime CSS uses display:block !important on
             * .nav-item. Save any existing inline display once, then override
             * it with an inline !important hide. This makes filtering
             * deterministic regardless of CSS load order or module markup.
             */
            if (node.getAttribute('data-erp-search-display-saved-v15') !== '1') {
                node.setAttribute('data-erp-search-display-saved-v15', '1');
                node.setAttribute('data-erp-search-display-value-v15', node.style.getPropertyValue('display') || '');
                node.setAttribute('data-erp-search-display-priority-v15', node.style.getPropertyPriority('display') || '');
            }

            node.classList.add(HIDDEN_CLASS);
            node.style.setProperty('display', 'none', 'important');
            return;
        }

        node.classList.remove(HIDDEN_CLASS);

        if (node.getAttribute('data-erp-search-display-saved-v15') === '1') {
            var oldDisplay = node.getAttribute('data-erp-search-display-value-v15') || '';
            var oldPriority = node.getAttribute('data-erp-search-display-priority-v15') || '';

            if (oldDisplay) {
                node.style.setProperty('display', oldDisplay, oldPriority);
            } else {
                node.style.removeProperty('display');
            }

            node.removeAttribute('data-erp-search-display-saved-v15');
            node.removeAttribute('data-erp-search-display-value-v15');
            node.removeAttribute('data-erp-search-display-priority-v15');
        }
    }

    function filterSuperadminCatalogue(item, term) {
        var catalogue = item.querySelector('#superadmin-installed-modules-menu');
        if (!catalogue) return null;

        var matches = 0;
        toArray(catalogue.querySelectorAll('.superadmin-installed-module-link')).forEach(function (link) {
            var text = [link.getAttribute('data-module-find'), link.textContent].filter(Boolean).join(' ');
            var show = matchesSearch(text, term);
            setHidden(link, !show);
            if (show) matches++;
        });
        return matches;
    }

    function filterRoot(root, rawTerm) {
        if (!root) return;
        var term = normalizeWords(rawTerm);
        var modules = moduleItems(root);

        modules.forEach(function (item) {
            var show = matchesSearch(moduleSearchText(item), term);
            var catalogue = item.querySelector('#superadmin-installed-modules-menu');

            if (catalogue) {
                if (term && !show) {
                    show = filterSuperadminCatalogue(item, term) > 0;
                } else {
                    filterSuperadminCatalogue(item, '');
                }
            }

            setHidden(item, !show);
        });

        // Separators are noise while filtering. Search control always remains visible.
        toArray(root.children).forEach(function (child) {
            if (!child || !child.classList) return;
            if (isSearchContainer(child)) {
                child.classList.add(SEARCH_WRAP_CLASS);
                setHidden(child, false);
                return;
            }
            if (String(child.tagName || '').toLowerCase() === 'hr') {
                setHidden(child, !!term);
            }
        });

        // Search intentionally does not write collapse/show/menu-open/aria-expanded.
    }

    function applyFilter(value, targetRoot) {
        if (targetRoot) {
            filterRoot(targetRoot, value || '');
            activeTerms.set(targetRoot, normalizeWords(value || ''));
            return;
        }
        roots().forEach(function (root) {
            filterRoot(root, value || '');
            activeTerms.set(root, normalizeWords(value || ''));
        });
    }

    function inputForRoot(root) {
        return root ? root.querySelector(INPUT_SELECTOR) : null;
    }

    function ensureInput(root) {
        var existing = inputForRoot(root);
        if (existing) return existing;

        var wrap = document.createElement('li');
        wrap.className = SEARCH_WRAP_CLASS;
        wrap.innerHTML = '<div style="padding:0 10px 14px;position:relative;">'
            + '<i class="fa fa-search" aria-hidden="true" style="position:absolute;left:23px;top:50%;transform:translateY(-50%);color:#9ca3af;z-index:2;"></i>'
            + '<input type="text" data-erp-sidebar-search-input="1" autocomplete="off" placeholder="Filter menu..." '
            + 'style="display:block;width:100%;height:42px;border-radius:8px;border:1px solid #ddd;background:#fff;color:#5a5c69;padding:8px 12px 8px 34px;outline:none;box-sizing:border-box;">'
            + '</div>';

        var firstMenuItem = null;
        for (var i = 0; i < root.children.length; i++) {
            if (String(root.children[i].tagName || '').toLowerCase() === 'li') {
                firstMenuItem = root.children[i];
                break;
            }
        }
        root.insertBefore(wrap, firstMenuItem || root.firstChild);
        return wrap.querySelector('[data-erp-sidebar-search-input="1"]');
    }

    function bindInput(input, root) {
        if (!input || input.getAttribute('data-erp-sidebar-search-bound-v15') === '1') return;
        input.setAttribute('data-erp-sidebar-search-bound-v15', '1');
        input.setAttribute('autocomplete', 'off');

        // SIDEBAR-SEARCH-PERSIST-20260914
        // Preserve the current sidebar search while navigating within this browser tab.
        // It changes only when the user edits/clears the search; Escape clears it too.
        var storageKey = 'erp.sidebar.search.v16';
        var restoredValue = '';

        try {
            localStorage.removeItem('sidebarFilter');
            localStorage.removeItem('global_sidebar_menu_search_value');
            restoredValue = sessionStorage.getItem(storageKey) || '';
        } catch (ignore) {}

        input.value = restoredValue;
        applyFilter(restoredValue, root);

        input.addEventListener('input', function () {
            var value = this.value || '';

            try {
                if (value) {
                    sessionStorage.setItem(storageKey, value);
                } else {
                    sessionStorage.removeItem(storageKey);
                }
            } catch (ignore) {}

            applyFilter(value, root);
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' || event.key === 'Esc') {
                this.value = '';

                try {
                    sessionStorage.removeItem(storageKey);
                } catch (ignore) {}

                applyFilter('', root);
                this.focus();
            }
        });
    }

    function observeRoot(root) {
        if (!window.MutationObserver || !root || observedRoots.has(root)) return;
        observedRoots.add(root);

        var observer = new MutationObserver(function (mutations) {
            var term = activeTerms.get(root) || '';
            if (!term) return;

            var added = mutations.some(function (mutation) {
                return mutation.addedNodes && mutation.addedNodes.length;
            });
            if (added) filterRoot(root, term);
        });
        observer.observe(root, { childList: true, subtree: true });
    }

    function boot() {
        roots().forEach(function (root) {
            var input = ensureInput(root);
            var wrap = input && input.closest ? input.closest('li') : null;
            if (wrap) wrap.classList.add(SEARCH_WRAP_CLASS);
            bindInput(input, root);
            var currentValue = input ? (input.value || '') : '';
            activeTerms.set(root, normalizeWords(currentValue));
            filterRoot(root, currentValue);
            observeRoot(root);
        });
    }

    window.ERPUnifiedSidebarSearch = {
        filter: applyFilter,
        refresh: boot,
        clear: function () {
            try {
                sessionStorage.removeItem('erp.sidebar.search.v16');
            } catch (ignore) {}

            roots().forEach(function (root) {
                var input = inputForRoot(root);
                if (input) input.value = '';
                applyFilter('', root);
            });
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
})();
</script>
@endonce
