@once
<style id="erp-unified-sidebar-style-20260910-v12">
    /*
     * ERP Sidebar Standard v12
     * -----------------------
     * One deterministic controller for Bootstrap, AdminLTE and ERP-native module menus.
     * A module heading changes state exactly once on the first click.
     * Each opened module remains open until that same module is clicked to close.
     * Panel visibility is immediate; no accordion auto-close is used.
     */
    #accordionSidebar .erp-sidebar-panel,
    ul.sidebar-menu .erp-sidebar-panel,
    .nav-sidebar .erp-sidebar-panel {
        transition: none !important;
        -webkit-transition: none !important;
        animation: none !important;
        -webkit-animation: none !important;
    }

    #accordionSidebar .erp-sidebar-panel *,
    ul.sidebar-menu .erp-sidebar-panel *,
    .nav-sidebar .erp-sidebar-panel * {
        animation: none !important;
    }

    #accordionSidebar .erp-sidebar-panel[hidden],
    ul.sidebar-menu .erp-sidebar-panel[hidden],
    .nav-sidebar .erp-sidebar-panel[hidden] {
        display: none !important;
        height: 0 !important;
        min-height: 0 !important;
        margin-top: 0 !important;
        margin-bottom: 0 !important;
        overflow: hidden !important;
    }

    #accordionSidebar .erp-sidebar-panel:not([hidden]),
    ul.sidebar-menu .erp-sidebar-panel:not([hidden]),
    .nav-sidebar .erp-sidebar-panel:not([hidden]) {
        display: block !important;
        height: auto !important;
        max-height: none !important;
    }

    #accordionSidebar .erp-sidebar-toggle,
    ul.sidebar-menu .erp-sidebar-toggle,
    .nav-sidebar .erp-sidebar-toggle {
        cursor: pointer;
        touch-action: manipulation;
        -ms-touch-action: manipulation;
        -webkit-tap-highlight-color: transparent;
        user-select: none;
        -webkit-user-select: none;
    }

    /* Fast visual feedback without delaying the panel itself. */
    #accordionSidebar .erp-sidebar-toggle .fa-angle-left,
    ul.sidebar-menu .erp-sidebar-toggle .fa-angle-left,
    .nav-sidebar .erp-sidebar-toggle .fa-angle-left {
        transition: none !important;
        -webkit-transition: none !important;
        transform-origin: 50% 50%;
        will-change: transform;
    }

    #accordionSidebar .erp-sidebar-open > .erp-sidebar-toggle .fa-angle-left,
    ul.sidebar-menu .erp-sidebar-open > .erp-sidebar-toggle .fa-angle-left,
    .nav-sidebar .erp-sidebar-open > .erp-sidebar-toggle .fa-angle-left {
        transform: rotate(-90deg);
    }

    @media (prefers-reduced-motion: reduce) {
        #accordionSidebar .erp-sidebar-toggle .fa-angle-left,
        ul.sidebar-menu .erp-sidebar-toggle .fa-angle-left,
        .nav-sidebar .erp-sidebar-toggle .fa-angle-left {
            transition: none !important;
        }
    }
</style>

<script id="erp-unified-sidebar-controller-20260910-v12">
(function () {
    'use strict';

    if (window.__erpUnifiedSidebarV12Loaded) {
        if (window.ERPUnifiedSidebar && typeof window.ERPUnifiedSidebar.refresh === 'function') {
            window.ERPUnifiedSidebar.refresh();
        }
        return;
    }
    window.__erpUnifiedSidebarV12Loaded = true;

    var ROOT_SELECTOR = '#accordionSidebar, ul.sidebar-menu, .nav-sidebar';
    var PANEL_ATTR = 'data-erp-sidebar-panel';
    var TOGGLE_ATTR = 'data-erp-sidebar-toggle';
    var BOUND_ATTR = 'data-erp-sidebar-bound';
    var uid = 0;

    /* Cached records mean a click never rescans the complete sidebar. */
    var itemRecords = new WeakMap();
    var toggleRecords = new WeakMap();
    var observedRoots = new WeakSet();
    
    function toArray(list) {
        return Array.prototype.slice.call(list || []);
    }

    function uniqueRoots() {
        var candidates = toArray(document.querySelectorAll(ROOT_SELECTOR));
        var result = [];

        candidates.forEach(function (root) {
            if (!root || result.indexOf(root) !== -1) return;

            /* Do not bind nested sidebar roots twice. */
            var outer = root.parentElement ? root.parentElement.closest(ROOT_SELECTOR) : null;
            if (outer && outer !== root) return;

            result.push(root);
        });

        return result;
    }

    function directAnchor(item) {
        if (!item) return null;
        for (var i = 0; i < item.children.length; i++) {
            if (String(item.children[i].tagName || '').toLowerCase() === 'a') return item.children[i];
        }
        return null;
    }

    function directPanel(item) {
        if (!item) return null;
        for (var i = 0; i < item.children.length; i++) {
            var child = item.children[i];
            if (!child || !child.classList) continue;
            if (child.hasAttribute(PANEL_ATTR)
                || child.classList.contains('collapse')
                || child.classList.contains('treeview-menu')) {
                return child;
            }
        }
        return null;
    }

    function safeSlug(value) {
        value = String(value || '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .substring(0, 48);
        return value || 'module';
    }

    function ensurePanelId(toggle, panel) {
        if (panel.id) return panel.id;

        var label = '';
        if (toggle) {
            var textNode = toggle.querySelector('[data-sidebar-title], .menu-title, .title, span, p');
            label = textNode ? textNode.textContent : toggle.textContent;
        }

        var base = 'erp-sidebar-' + safeSlug(label) + '-menu';
        var id = base;
        while (document.getElementById(id)) {
            uid++;
            id = base + '-' + uid;
        }
        panel.id = id;
        return id;
    }

    function initialOpenState(item, toggle, panel) {
        if (!item || !panel) return false;

        var hasActiveChild = !!panel.querySelector('.active, .active-sub, [aria-current="page"]');
        var parentIsActive = item.classList.contains('active') || item.classList.contains('active-sub');
        var serverMarkedOpen = item.classList.contains('menu-open')
            || item.classList.contains('erp-sidebar-open')
            || panel.classList.contains('show')
            || panel.classList.contains('in')
            || (toggle && toggle.getAttribute('aria-expanded') === 'true' && !toggle.classList.contains('collapsed'));

        return hasActiveChild || (parentIsActive && serverMarkedOpen) || serverMarkedOpen;
    }

    function applyOpenState(record, open) {
        if (!record) return;
        open = !!open;

        var item = record.item;
        var toggle = record.toggle;
        var panel = record.panel;

        /* All writes are synchronous. No timeout, jQuery animation or rAF. */
        item.classList.toggle('erp-sidebar-open', open);
        item.classList.toggle('menu-open', open);
        toggle.classList.toggle('collapsed', !open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        panel.classList.toggle('show', open);
        panel.classList.remove('in', 'collapsing');
        panel.hidden = !open;

        /* Clear any stale Bootstrap/jQuery inline animation residue. */
        if (panel.style && panel.style.length) {
            panel.style.removeProperty('height');
            panel.style.removeProperty('max-height');
            panel.style.removeProperty('display');
            panel.style.removeProperty('transition');
            panel.style.removeProperty('animation');
        }
    }

    function setOpen(record, open) {
        if (!record) return false;

        /*
         * Module states are independent. Opening one module must never close
         * another module; an opened module remains open until the user clicks
         * that same heading again (or search deliberately removes that result).
         */
        applyOpenState(record, !!open);
        return true;
    }

    function recordForItem(item) {
        if (!item) return null;
        var cached = itemRecords.get(item);
        if (cached) return cached;

        var toggle = directAnchor(item);
        var panel = directPanel(item);
        if (!toggle || !panel) return null;

        var record = { item: item, toggle: toggle, panel: panel };
        itemRecords.set(item, record);
        toggleRecords.set(toggle, record);
        return record;
    }

    function normaliseItem(item) {
        if (!item || String(item.tagName || '').toLowerCase() !== 'li') return null;

        var record = recordForItem(item);
        if (!record) return null;

        var toggle = record.toggle;
        var panel = record.panel;
        var alreadyBound = item.getAttribute(BOUND_ATTR) === '1';
        var shouldOpen = alreadyBound ? !panel.hidden : initialOpenState(item, toggle, panel);
        var panelId = ensurePanelId(toggle, panel);

        item.setAttribute(BOUND_ATTR, '1');
        item.classList.add('erp-sidebar-module');
        toggle.setAttribute(TOGGLE_ATTR, '1');
        toggle.setAttribute('data-erp-sidebar-target', '#' + panelId);
        toggle.setAttribute('aria-controls', panelId);
        toggle.classList.add('erp-sidebar-toggle');
        panel.setAttribute(PANEL_ATTR, '1');
        panel.classList.add('erp-sidebar-panel');

        /* Bootstrap/AdminLTE must not own the same click. */
        toggle.removeAttribute('data-toggle');
        toggle.removeAttribute('data-bs-toggle');
        panel.removeAttribute('data-parent');
        panel.removeAttribute('data-bs-parent');

        applyOpenState(record, shouldOpen);
        return record;
    }

    function normaliseSubtree(node) {
        if (!node || node.nodeType !== 1) return [];
        var records = [];

        if (String(node.tagName || '').toLowerCase() === 'li') {
            var own = normaliseItem(node);
            if (own) records.push(own);
        }

        toArray(node.querySelectorAll ? node.querySelectorAll('li') : []).forEach(function (item) {
            var record = normaliseItem(item);
            if (record && records.indexOf(record) === -1) records.push(record);
        });

        return records;
    }

    function normaliseRoot(root) {
        if (!root) return;
        root.setAttribute('data-erp-unified-sidebar', '1');

        toArray(root.querySelectorAll('li')).forEach(function (item) {
            normaliseItem(item);
        });
    }

    function managedRecordFromTarget(root, target) {
        if (!target || !target.closest || !root) return null;

        /*
         * Resolve the clicked module from its direct <li> > <a> relationship.
         * This intentionally does not depend on data attributes being present
         * already, so even a module inserted immediately before the click works
         * on the FIRST click (MutationObserver timing cannot swallow it).
         */
        var toggle = target.closest('a');
        if (!toggle || !root.contains(toggle)) return null;

        var item = toggle.parentElement;
        if (!item || String(item.tagName || '').toLowerCase() !== 'li') return null;
        if (directAnchor(item) !== toggle || !directPanel(item)) return null;

        var record = toggleRecords.get(toggle) || itemRecords.get(item);
        if (!record || item.getAttribute(BOUND_ATTR) !== '1') {
            record = normaliseItem(item);
        }
        return record;
    }

    function consumeToggleEvent(event) {
        if (!event) return;
        event.preventDefault();
        event.stopPropagation();
        if (typeof event.stopImmediatePropagation === 'function') {
            event.stopImmediatePropagation();
        }
    }

    function isRecordOpen(record) {
        if (!record) return false;
        return record.panel.hidden === false
            && record.toggle.getAttribute('aria-expanded') === 'true'
            && record.item.classList.contains('erp-sidebar-open');
    }

    function activateRecord(record, event) {
        if (!record) return false;

        consumeToggleEvent(event);

        /*
         * Exactly one synchronous state change.  The state is read from the
         * controller's own canonical markers instead of legacy Bootstrap/AdminLTE
         * classes, so a stale .show/.in/menu-open class cannot force a second click.
         */
        setOpen(record, !isRecordOpen(record));
        return true;
    }

    function bindRootEvents(root) {
        if (!root || root.getAttribute('data-erp-sidebar-events-v12') === '1') return;
        root.setAttribute('data-erp-sidebar-events-v12', '1');

        /*
         * Desktop mouse response happens on mousedown, so the panel changes the
         * instant the user presses the module heading.  The following browser
         * click is consumed without toggling again.  Touch/keyboard still use
         * the normal click path.
         */
        var mouseHandledToggle = null;
        var mouseHandledAt = 0;

        root.addEventListener('mousedown', function (event) {
            if (typeof event.button === 'number' && event.button !== 0) return;
            var record = managedRecordFromTarget(root, event.target);
            if (!record) return;

            mouseHandledToggle = record.toggle;
            mouseHandledAt = Date.now();
            activateRecord(record, event);
        }, true);

        root.addEventListener('click', function (event) {
            var record = managedRecordFromTarget(root, event.target);
            if (!record) return;

            if (mouseHandledToggle === record.toggle && (Date.now() - mouseHandledAt) < 1200) {
                consumeToggleEvent(event);
                mouseHandledToggle = null;
                mouseHandledAt = 0;
                return;
            }

            activateRecord(record, event);
        }, true);

        /* Space toggles the focused module heading. Enter naturally emits click. */
        root.addEventListener('keydown', function (event) {
            if (event.key !== ' ' && event.key !== 'Spacebar') return;
            var record = managedRecordFromTarget(root, event.target);
            if (!record) return;
            activateRecord(record, event);
        }, true);
    }

    function observeRoot(root) {
        if (!window.MutationObserver || !root || observedRoots.has(root)) return;
        observedRoots.add(root);

        var observer = new MutationObserver(function (mutations) {
            /* Normalise only newly inserted sidebar nodes; never rescan the root. */
            var records = [];
            for (var i = 0; i < mutations.length; i++) {
                var added = mutations[i].addedNodes || [];
                for (var j = 0; j < added.length; j++) {
                    var found = normaliseSubtree(added[j]);
                    for (var k = 0; k < found.length; k++) {
                        if (records.indexOf(found[k]) === -1) records.push(found[k]);
                    }
                }
            }
            /* normaliseSubtree() already applies each new module's own state. */
        });

        observer.observe(root, { childList: true, subtree: true });
    }

    function refresh() {
        uniqueRoots().forEach(function (root) {
            normaliseRoot(root);
            bindRootEvents(root);
            observeRoot(root);
        });
    }

    window.ERPUnifiedSidebar = {
        refresh: refresh,
        setOpen: function (item, open) {
            var record = recordForItem(item);
            if (!record) return false;
            return setOpen(record, !!open);
        }
    };

    function boot() {
        refresh();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
    else boot();
})();
</script>
@endonce
