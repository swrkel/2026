/*
 * ERP Global Sidebar Auto Filter - 2026-09-07
 *
 * Behaviour:
 * - Filters automatically while the user types; Enter is not required.
 * - Matches parent module names and text inside collapsed child menus.
 * - Keeps the search box visible.
 * - Temporarily opens matching menu groups while searching.
 * - Restores the sidebar's previous open/closed state when the search is cleared.
 * - Works with both Bootstrap collapse and AdminLTE treeview/sidebar markup.
 */
(function () {
    'use strict';

    var emptyClass = 'sidebar-search-empty';
    var hiddenClass = 'sidebar-search-hidden';
    var searchingClass = 'sidebar-searching';
    var initializedInputs = typeof WeakSet !== 'undefined' ? new WeakSet() : null;

    function normalize(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/\s+/g, ' ')
            .trim();
    }

    function getSidebar() {
        return document.getElementById('accordionSidebar')
            || document.querySelector('.main-sidebar .sidebar-menu')
            || document.querySelector('.sidebar-menu')
            || document.querySelector('.main-sidebar')
            || document.querySelector('.sidebar');
    }

    function getFilterInput(sidebar) {
        if (!sidebar) {
            return null;
        }

        return document.getElementById('sidebarFilter')
            || sidebar.querySelector('[data-sidebar-filter]')
            || sidebar.querySelector('.sidebar-search input[type="search"]')
            || sidebar.querySelector('.sidebar-search input[type="text"]')
            || sidebar.querySelector('.sidebar-form input[type="search"]')
            || sidebar.querySelector('.sidebar-form input[type="text"]');
    }

    function directTopItems(sidebar) {
        if (!sidebar || !sidebar.children) {
            return [];
        }

        return Array.prototype.slice.call(sidebar.children).filter(function (node) {
            return node
                && node.tagName === 'LI'
                && !node.classList.contains(emptyClass);
        });
    }

    function isSearchHolder(item, input) {
        if (!item) {
            return false;
        }

        return item.classList.contains('erp-sidebar-search-holder')
            || item.classList.contains('sidebar-search')
            || (input && item.contains(input));
    }

    function directSubmenus(item) {
        if (!item || !item.children) {
            return [];
        }

        return Array.prototype.slice.call(item.children).filter(function (child) {
            if (!child || !child.classList) {
                return false;
            }

            return child.classList.contains('collapse')
                || child.classList.contains('treeview-menu')
                || child.classList.contains('sidebar-submenu')
                || child.tagName === 'UL';
        });
    }

    function captureState(sidebar, input) {
        if (sidebar.getAttribute('data-erp-search-state-captured') === '1') {
            return;
        }

        directTopItems(sidebar).forEach(function (item) {
            if (isSearchHolder(item, input)) {
                return;
            }

            item.setAttribute('data-erp-search-display', item.style.display || '');

            directSubmenus(item).forEach(function (menu) {
                var isOpen = menu.classList.contains('show')
                    || menu.style.display === 'block'
                    || item.classList.contains('menu-open');

                menu.setAttribute('data-erp-search-was-open', isOpen ? '1' : '0');
                menu.setAttribute('data-erp-search-display', menu.style.display || '');
            });
        });

        sidebar.setAttribute('data-erp-search-state-captured', '1');
    }

    function restoreState(sidebar, input) {
        directTopItems(sidebar).forEach(function (item) {
            if (isSearchHolder(item, input)) {
                item.classList.remove(hiddenClass);
                item.style.removeProperty('display');
                return;
            }

            item.classList.remove(hiddenClass);

            var priorDisplay = item.getAttribute('data-erp-search-display');
            if (priorDisplay) {
                item.style.display = priorDisplay;
            } else {
                item.style.removeProperty('display');
            }
            item.removeAttribute('data-erp-search-display');

            directSubmenus(item).forEach(function (menu) {
                var wasOpen = menu.getAttribute('data-erp-search-was-open') === '1';
                var priorMenuDisplay = menu.getAttribute('data-erp-search-display');

                menu.classList.toggle('show', wasOpen);
                item.classList.toggle('menu-open', wasOpen);

                if (priorMenuDisplay) {
                    menu.style.display = priorMenuDisplay;
                } else {
                    menu.style.removeProperty('display');
                }

                var toggle = item.querySelector(':scope > a[data-toggle="collapse"], :scope > a.nav-link, :scope > a');
                if (toggle) {
                    toggle.classList.toggle('collapsed', !wasOpen);
                    toggle.setAttribute('aria-expanded', wasOpen ? 'true' : 'false');
                }

                menu.removeAttribute('data-erp-search-was-open');
                menu.removeAttribute('data-erp-search-display');
            });
        });

        sidebar.removeAttribute('data-erp-search-state-captured');
        sidebar.classList.remove(searchingClass);
        removeEmptyMessage(sidebar);
    }

    function showMatchingItem(item) {
        item.classList.remove(hiddenClass);
        item.style.removeProperty('display');

        directSubmenus(item).forEach(function (menu) {
            menu.classList.add('show');
            menu.style.setProperty('display', 'block', 'important');
            item.classList.add('menu-open');

            var toggle = item.querySelector(':scope > a[data-toggle="collapse"], :scope > a.nav-link, :scope > a');
            if (toggle) {
                toggle.classList.remove('collapsed');
                toggle.setAttribute('aria-expanded', 'true');
            }
        });
    }

    function hideNonMatchingItem(item) {
        item.classList.add(hiddenClass);
        item.style.setProperty('display', 'none', 'important');
    }

    function removeEmptyMessage(sidebar) {
        var empty = sidebar.querySelector('.' + emptyClass);
        if (empty && empty.parentNode) {
            empty.parentNode.removeChild(empty);
        }
    }

    function showEmptyMessage(sidebar) {
        removeEmptyMessage(sidebar);

        var row = document.createElement('li');
        row.className = emptyClass;
        row.setAttribute('role', 'status');
        row.textContent = 'No matching menu found';
        row.style.cssText = [
            'display:block',
            'margin:10px 12px',
            'padding:12px 14px',
            'border-radius:10px',
            'color:#fff',
            'background:rgba(255,255,255,.10)',
            'font-weight:600'
        ].join(';');
        sidebar.appendChild(row);
    }

    function applyFilter(sidebar, input) {
        var term = normalize(input.value);

        // The search box must never be hidden by its own filter.
        var holder = input.closest('li');
        if (holder) {
            holder.classList.remove(hiddenClass);
            holder.style.setProperty('display', 'block', 'important');
        }

        if (!term) {
            restoreState(sidebar, input);
            return;
        }

        captureState(sidebar, input);
        sidebar.classList.add(searchingClass);
        removeEmptyMessage(sidebar);

        var matches = 0;

        directTopItems(sidebar).forEach(function (item) {
            if (isSearchHolder(item, input)) {
                item.classList.remove(hiddenClass);
                item.style.setProperty('display', 'block', 'important');
                return;
            }

            // textContent includes labels in currently collapsed submenus, unlike innerText.
            var text = normalize(item.textContent);
            var isMatch = text.indexOf(term) !== -1;

            if (isMatch) {
                matches++;
                showMatchingItem(item);
            } else {
                hideNonMatchingItem(item);
            }
        });

        if (!matches) {
            showEmptyMessage(sidebar);
        }
    }

    function bind(sidebar, input) {
        if (!sidebar || !input) {
            return false;
        }

        if ((initializedInputs && initializedInputs.has(input))
            || input.getAttribute('data-erp-auto-filter-bound') === '1') {
            return true;
        }

        if (initializedInputs) {
            initializedInputs.add(input);
        }
        input.setAttribute('data-erp-auto-filter-bound', '1');
        input.setAttribute('autocomplete', 'off');

        var scheduled = null;
        function runNow() {
            if (scheduled) {
                window.cancelAnimationFrame(scheduled);
                scheduled = null;
            }
            applyFilter(sidebar, input);
        }

        function schedule() {
            if (scheduled) {
                window.cancelAnimationFrame(scheduled);
            }
            scheduled = window.requestAnimationFrame(function () {
                scheduled = null;
                applyFilter(sidebar, input);
            });
        }

        // input is the primary event: typing, paste, cut, autofill and mobile keyboards.
        input.addEventListener('input', schedule, false);
        input.addEventListener('search', runNow, false);
        input.addEventListener('change', runNow, false);

        // Keyup is a compatibility fallback for old browser/input combinations.
        input.addEventListener('keyup', function (event) {
            if (event.key === 'Escape' || event.keyCode === 27) {
                input.value = '';
                runNow();
                return;
            }
            schedule();
        }, false);

        // Do not submit or navigate when Enter is pressed in the sidebar search.
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.keyCode === 13) {
                event.preventDefault();
                event.stopPropagation();
                runNow();
            }
        }, false);

        // Apply immediately if the browser restored a value into the field.
        runNow();
        return true;
    }

    function init() {
        var sidebar = getSidebar();
        var input = getFilterInput(sidebar);
        return bind(sidebar, input);
    }

    function onReady() {
        init();

        // A few legacy layouts insert/replace sidebar markup after DOM ready.
        [100, 350, 900, 1800].forEach(function (delay) {
            window.setTimeout(init, delay);
        });

        try {
            var observer = new MutationObserver(function () {
                init();
            });
            observer.observe(document.body, { childList: true, subtree: true });
        } catch (ignore) {}
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onReady, false);
    } else {
        onReady();
    }
})();
