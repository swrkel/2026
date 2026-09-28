{{--
    MPCS-owned tab controller.

    The application contains more than one Bootstrap/tab implementation and
    several modules reuse generic pane IDs such as "settings_tab".  Keep MPCS
    navigation local to the nearest data-mpcs-tabs container so another
    module's permission or tab handler cannot consume the click.
--}}
<script id="mpcs-safe-tabs" type="text/javascript">
    (function () {
        'use strict';

        if (window.MpcsSafeTabs && window.MpcsSafeTabs.version >= 2) {
            window.MpcsSafeTabs.bindAll();
            return;
        }

        function directChildrenWithClass(parent, className) {
            var matches = [];
            if (!parent) {
                return matches;
            }

            Array.prototype.forEach.call(parent.children || [], function (child) {
                if (child.classList && child.classList.contains(className)) {
                    matches.push(child);
                }
            });
            return matches;
        }

        function isDisabled(link) {
            return !link ||
                link.getAttribute('aria-disabled') === 'true' ||
                link.getAttribute('data-business-page-disabled') === '1' ||
                link.classList.contains('business-manage-disabled-tab') ||
                link.classList.contains('disabled');
        }

        function normalizeTarget(target) {
            target = String(target || '').trim();
            if (!target) {
                return '';
            }

            if (target.charAt(0) !== '#') {
                try {
                    var targetUrl = new URL(target, window.location.href);
                    if (targetUrl.origin !== window.location.origin ||
                        targetUrl.pathname !== window.location.pathname) {
                        return '';
                    }
                    target = targetUrl.hash;
                } catch (error) {
                    return '';
                }
            }

            return /^#[A-Za-z][A-Za-z0-9_:.-]*$/.test(target) ? target : '';
        }

        function targetSelector(link) {
            return normalizeTarget(
                link.getAttribute('data-mpcs-tab-target') ||
                link.getAttribute('data-target') ||
                link.getAttribute('href') || ''
            );
        }

        function emitShown(link, previousLink) {
            if (window.jQuery) {
                window.jQuery(link).trigger({
                    type: 'shown.bs.tab',
                    relatedTarget: previousLink || null
                });
                window.jQuery(link).trigger('mpcs.tab.shown');
            } else if (typeof window.CustomEvent === 'function') {
                link.dispatchEvent(new CustomEvent('mpcs.tab.shown', { bubbles: true }));
            }
        }

        function open(link, updateHash) {
            if (isDisabled(link)) {
                return false;
            }

            var container = link.closest('[data-mpcs-tabs]');
            var selector = targetSelector(link);
            var pane = selector ? document.getElementById(selector.slice(1)) : null;

            if (!container || !pane) {
                return false;
            }

            var paneGroup = pane.parentElement;
            if (!paneGroup || !paneGroup.classList.contains('tab-content')) {
                return false;
            }

            var nav = link.closest('.nav-tabs');
            var previousLink = nav ? nav.querySelector('li.active a, a.active') : null;

            if (nav) {
                Array.prototype.forEach.call(nav.querySelectorAll('li'), function (item) {
                    item.classList.remove('active');
                });
                Array.prototype.forEach.call(nav.querySelectorAll('a'), function (item) {
                    item.classList.remove('active');
                    item.setAttribute('aria-selected', 'false');
                    item.setAttribute('aria-expanded', 'false');
                });
            }

            directChildrenWithClass(paneGroup, 'tab-pane').forEach(function (item) {
                item.classList.remove('active', 'in', 'show');
                item.hidden = true;
                item.style.display = 'none';
            });

            var listItem = link.closest('li');
            if (listItem) {
                listItem.classList.add('active');
            }
            link.classList.add('active');
            link.setAttribute('aria-selected', 'true');
            link.setAttribute('aria-expanded', 'true');

            pane.hidden = false;
            pane.style.removeProperty('display');
            pane.classList.add('active', 'in', 'show');

            emitShown(link, previousLink);

            if (updateHash && window.history && window.history.replaceState) {
                window.history.replaceState(
                    null,
                    document.title,
                    window.location.pathname + window.location.search + selector
                );
            }

            window.setTimeout(function () {
                if (window.jQuery && window.jQuery.fn && window.jQuery.fn.dataTable) {
                    window.jQuery.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                }
                window.dispatchEvent(new Event('resize'));
            }, 0);

            return true;
        }

        function bind(container) {
            if (!container || container.getAttribute('data-mpcs-tabs-bound') === '1') {
                return;
            }

            container.setAttribute('data-mpcs-tabs-bound', '1');
            container.addEventListener('click', function (event) {
                var link = event.target.closest('a[data-toggle="tab"], a[data-bs-toggle="tab"], a[data-mpcs-tab-target]');
                if (!link || !container.contains(link) || isDisabled(link)) {
                    return;
                }

                if (!targetSelector(link)) {
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();
                open(link, true);
            }, true);

            var requested = null;
            if (window.location.hash) {
                Array.prototype.some.call(
                    container.querySelectorAll('a[data-toggle="tab"], a[data-bs-toggle="tab"], a[data-mpcs-tab-target]'),
                    function (candidate) {
                        if (targetSelector(candidate) === window.location.hash) {
                            requested = candidate;
                            return true;
                        }
                        return false;
                    }
                );
            }
            var initial = requested ||
                container.querySelector('.nav-tabs li.active a') ||
                container.querySelector('.nav-tabs a[data-toggle="tab"], .nav-tabs a[data-bs-toggle="tab"]');

            if (initial && !isDisabled(initial)) {
                open(initial, false);
            }
        }

        function bindAll(root) {
            root = root || document;
            Array.prototype.forEach.call(root.querySelectorAll('[data-mpcs-tabs]'), bind);
        }

        window.MpcsSafeTabs = {
            version: 2,
            bindAll: bindAll,
            open: open
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () { bindAll(); });
        } else {
            bindAll();
        }
    }());
</script>
