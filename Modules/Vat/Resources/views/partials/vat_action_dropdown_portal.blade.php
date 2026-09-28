<style>
/* S560: VAT action menus use a body-level portal with their own styling.
 * The source menu is temporarily removed from Bootstrap's .dropdown-menu
 * rules, preventing DataTables/table overflow from hiding Edit/Delete. */
body > .vat-action-portal-menu {
    position: fixed !important;
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    pointer-events: auto !important;
    min-width: 185px;
    max-width: calc(100vw - 12px);
    max-height: calc(100vh - 12px);
    overflow-y: auto;
    margin: 0 !important;
    padding: 5px 0 !important;
    list-style: none !important;
    background: #fff !important;
    border: 1px solid rgba(15, 23, 42, .14) !important;
    border-radius: 6px !important;
    box-shadow: 0 10px 28px rgba(15, 23, 42, .22) !important;
    transform: none !important;
    z-index: 1000001 !important;
}

body > .vat-action-portal-menu > li {
    display: block !important;
    float: none !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
}

body > .vat-action-portal-menu > li.divider {
    height: 1px !important;
    margin: 5px 0 !important;
    overflow: hidden;
    background: #e5e7eb !important;
}

body > .vat-action-portal-menu > li > a {
    display: block !important;
    width: 100% !important;
    padding: 7px 16px !important;
    clear: both;
    color: #344054 !important;
    text-decoration: none !important;
    white-space: nowrap;
    background: transparent !important;
    cursor: pointer;
}

body > .vat-action-portal-menu > li > a:hover,
body > .vat-action-portal-menu > li > a:focus {
    color: #101828 !important;
    background: #f2f4f7 !important;
    outline: 0;
}

body > .vat-action-portal-menu > li.disabled > a,
body > .vat-action-portal-menu > li > a[aria-disabled="true"] {
    color: #98a2b3 !important;
    cursor: not-allowed !important;
    opacity: .78;
    background: #fff !important;
}

.vat-action-menu-group > .dropdown-toggle {
    position: relative;
    z-index: 1;
}
</style>

<script>
(function ($) {
    'use strict';

    if (window.__vatActionDropdownPortalS560) {
        return;
    }
    window.__vatActionDropdownPortalS560 = true;

    var portalDataKey = 's560VatActionPortal';
    var ownerDataKey = 's560VatActionOwner';

    function closestElement(element, selector) {
        var node = element;

        while (node && node !== document) {
            if (node.matches && node.matches(selector)) {
                return node;
            }
            node = node.parentNode;
        }

        return null;
    }

    function restoreMenu($group) {
        if (!$group || !$group.length) {
            return;
        }

        var portal = $group.data(portalDataKey);
        var $button = $group.children('.dropdown-toggle, .vat-action-menu-toggle').first();

        if (!portal) {
            $group.removeClass('open');
            $button.attr('aria-expanded', 'false');
            return;
        }

        var $menu = portal.menu;
        var placeholder = portal.placeholder;

        if ($menu && $menu.length) {
            $menu.removeData(ownerDataKey);

            if (portal.originalClass === null) {
                $menu.removeAttr('class');
            } else {
                $menu.attr('class', portal.originalClass);
            }

            if (portal.originalStyle === null) {
                $menu.removeAttr('style');
            } else {
                $menu.attr('style', portal.originalStyle);
            }

            if (placeholder && placeholder.parentNode) {
                $(placeholder).replaceWith($menu.detach());
            } else {
                $group.append($menu.detach());
            }
        }

        $group.removeData(portalDataKey).removeClass('open');
        $button.attr('aria-expanded', 'false');
    }

    function closeAllMenus(exceptGroup) {
        $('.vat-action-menu-group').each(function () {
            if (exceptGroup && this === exceptGroup) {
                return;
            }
            restoreMenu($(this));
        });

        $('body > .vat-action-portal-menu').each(function () {
            var $menu = $(this);
            var $owner = $menu.data(ownerDataKey);

            if ($owner && $owner.length && (!exceptGroup || $owner[0] !== exceptGroup)) {
                restoreMenu($owner);
            } else if (!$owner || !$owner.length) {
                $menu.remove();
            }
        });
    }

    function positionMenu($button, $menu) {
        if (!$button.length || !$menu.length || !$button[0]) {
            return;
        }

        var rect = $button[0].getBoundingClientRect();
        var viewportWidth = document.documentElement.clientWidth || window.innerWidth;
        var viewportHeight = document.documentElement.clientHeight || window.innerHeight;
        var menuWidth = Math.max($menu.outerWidth() || 185, 185);
        var menuHeight = Math.max($menu.outerHeight() || 76, 76);
        var left = rect.right - menuWidth;
        var top = rect.bottom + 3;

        left = Math.max(6, Math.min(left, viewportWidth - menuWidth - 6));

        if (top + menuHeight > viewportHeight - 6 && rect.top - menuHeight - 3 >= 6) {
            top = rect.top - menuHeight - 3;
        }

        top = Math.max(6, Math.min(top, viewportHeight - menuHeight - 6));

        $menu.css({
            top: top + 'px',
            left: left + 'px',
            right: 'auto'
        });
    }

    function openMenu($group) {
        if (!$group || !$group.length) {
            return;
        }

        if ($group.data(portalDataKey)) {
            restoreMenu($group);
            return;
        }

        closeAllMenus($group[0]);

        if (window.VatStatementColumnVisibility && typeof window.VatStatementColumnVisibility.close === 'function') {
            window.VatStatementColumnVisibility.close();
        }

        var $button = $group.children('.dropdown-toggle, .vat-action-menu-toggle').first();
        var $menu = $group.children('.dropdown-menu, [data-vat-action-menu="1"]').first();

        if (!$button.length || !$menu.length) {
            return;
        }

        var placeholder = document.createComment('VAT action menu portal placeholder');
        var originalClass = $menu.attr('class');
        var originalStyle = $menu.attr('style');

        $menu.before(placeholder);

        $group.data(portalDataKey, {
            menu: $menu,
            placeholder: placeholder,
            originalClass: typeof originalClass === 'undefined' ? null : originalClass,
            originalStyle: typeof originalStyle === 'undefined' ? null : originalStyle
        });

        $group.addClass('open');
        $button.attr('aria-expanded', 'true');

        $menu
            .detach()
            .attr('class', 'vat-action-portal-menu')
            .removeAttr('style')
            .data(ownerDataKey, $group)
            .appendTo('body');

        positionMenu($button, $menu);
    }

    window.closeVatActionMenus = function () {
        closeAllMenus();
    };

    function handleDocumentClick(event) {
        var toggle = closestElement(
            event.target,
            '.vat-action-menu-group > .dropdown-toggle, .vat-action-menu-group > .vat-action-menu-toggle'
        );

        if (toggle) {
            event.preventDefault();
            event.stopPropagation();
            if (event.stopImmediatePropagation) {
                event.stopImmediatePropagation();
            }

            openMenu($(toggle).closest('.vat-action-menu-group'));
            return;
        }

        var portalMenu = closestElement(event.target, '.vat-action-portal-menu');
        if (portalMenu) {
            var $portalMenu = $(portalMenu);
            var linkElement = closestElement(event.target, '.vat-action-portal-menu a');
            var $link = linkElement ? $(linkElement) : $();
            var disabled = $link.length && (
                $link.attr('aria-disabled') === 'true' ||
                $link.parent('li').hasClass('disabled')
            );

            if (disabled) {
                event.preventDefault();
                event.stopPropagation();
                if (event.stopImmediatePropagation) {
                    event.stopImmediatePropagation();
                }
                return;
            }

            var $owner = $portalMenu.data(ownerDataKey);

            if ($link.length
                && $link.hasClass('vat-btn-modal')
                && typeof window.openVatStableModalS536 === 'function') {
                restoreMenu($owner);
                window.openVatStableModalS536($link[0], event);
                return;
            }

            window.setTimeout(function () {
                if ($owner && $owner.length) {
                    restoreMenu($owner);
                }
            }, 0);
            return;
        }

        closeAllMenus();
    }

    document.addEventListener('click', handleDocumentClick, true);

    $(document)
        .off('preDraw.dt.s560VatAction draw.dt.s560VatAction destroy.dt.s560VatAction')
        .on('preDraw.dt.s560VatAction draw.dt.s560VatAction destroy.dt.s560VatAction', function () {
            closeAllMenus();
        })
        .off('hidden.bs.modal.s560VatAction')
        .on('hidden.bs.modal.s560VatAction', function () {
            closeAllMenus();
        });

    $(window)
        .off('scroll.s560VatAction resize.s560VatAction pageshow.s560VatAction')
        .on('scroll.s560VatAction resize.s560VatAction pageshow.s560VatAction', function () {
            closeAllMenus();
        });

    $(function () {
        closeAllMenus();
    });
})(jQuery);
</script>
