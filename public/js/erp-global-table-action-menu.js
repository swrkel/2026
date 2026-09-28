/*
 * erp-global-table-action-menu.js
 *
 * Action menus inside tables open in full, everywhere.
 *
 * THE PROBLEM THIS SOLVES
 *
 *   A row's Actions button opens a Bootstrap dropdown that is positioned inside
 *   its own cell. The theme then clips it, in three separate ways:
 *
 *       .box, .card, .info-box { overflow: hidden; }     layouts/app.blade.php
 *       .table                 { overflow: hidden; }     layouts/app.blade.php
 *       .table-responsive      { overflow-x: auto; }     bootstrap
 *
 *   Anything falling outside those boxes is cut away. On the last row the menu
 *   opens downward and disappears; on the first row it opens upward and only its
 *   bottom entry survives; in a horizontally scrolling table it is cut at the
 *   right edge. In every case the menu is generated correctly and the operator
 *   simply cannot see it, which is why it is always reported as "the buttons are
 *   not showing".
 *
 *   Raising z-index does not help: z-index cannot defeat overflow clipping.
 *   Widening the column does not help either, because the menu is taller than
 *   the row whatever the column width.
 *
 * WHY IT IS GLOBAL
 *
 *   The same fault has now been fixed one screen at a time on customer
 *   statements, supplier payments, customer payments and expenses, and it has
 *   just been reported again on three VAT prefix tables and the VAT statement
 *   list. Each of those fixes is the same forty lines with a different table id.
 *   Doing it once, here, fixes the screens nobody has reported yet and means the
 *   next new table inherits the fix rather than the bug.
 *
 * HOW IT WORKS
 *
 *   While a menu is open it is moved to <body> and positioned with
 *   position:fixed against its button's own screen coordinates, so no ancestor
 *   can clip it. On close it is returned to exactly where it came from, because
 *   DataTables owns that markup and rebuilds it on every redraw.
 *
 *   Nothing about the menu's CONTENT is touched: the links, their handlers and
 *   their delegated click bindings are the page's own and keep working while the
 *   menu is detached.
 *
 * OPTING OUT
 *
 *   one table  - add data-no-menu-lift to the table or any ancestor
 *   everywhere - set window.ERP_DISABLE_MENU_LIFT = true before this loads
 */
(function () {
    'use strict';

    if (window.ERP_DISABLE_MENU_LIFT) return;
    if (window.__erpTableActionMenuLoaded) return;
    window.__erpTableActionMenuLoaded = true;

    if (typeof jQuery === 'undefined') return;

    var $ = jQuery;
    var DETACHED = 'erp-lifted-action-menu';
    var PLACEHOLDER_DATA = 'erpMenuPlaceholder';

    /*
     * Only menus inside a table. A dropdown in a navbar, a filter panel or a
     * modal is not clipped and must be left alone - moving one of those could
     * break its own positioning.
     */
    function isTableMenu($group) {
        if (!$group.closest('table').length) return false;
        if ($group.closest('[data-no-menu-lift]').length) return false;

        return true;
    }

    function restoreAll() {
        $('body').children('.' + DETACHED).each(function () {
            var $menu = $(this);
            var placeholder = $menu.data(PLACEHOLDER_DATA);

            $menu.removeClass(DETACHED).removeAttr('style');

            if (placeholder && placeholder.parentNode) {
                placeholder.parentNode.insertBefore($menu[0], placeholder);
                placeholder.parentNode.removeChild(placeholder);
            }

            $menu.removeData(PLACEHOLDER_DATA);
        });
    }

    function place($toggle, $menu) {
        if (!$toggle.length || !$menu.length || !$menu.parent().is('body')) return;

        var rect = $toggle[0].getBoundingClientRect();
        var width = $menu.outerWidth();
        var height = $menu.outerHeight();
        var margin = 8;
        var gap = 4;
        var viewportH = window.innerHeight || document.documentElement.clientHeight;
        var viewportW = window.innerWidth || document.documentElement.clientWidth;
        var top;

        if (rect.bottom + gap + height <= viewportH - margin) {
            top = rect.bottom + gap;                       // below, the usual case
        } else if (rect.top - height - gap >= margin) {
            top = rect.top - height - gap;                 // above, when there is room
        } else {
            /*
             * Neither side fits. Sit against the bottom of the viewport rather
             * than drifting away from the button; the stylesheet caps the height
             * and scrolls, so every entry stays reachable.
             */
            top = Math.max(margin, viewportH - height - margin);
        }

        /*
         * dropdown-menu-right aligns to the button's right edge. Honouring that
         * keeps a wide menu from hanging off the side of a narrow last column.
         */
        var left = $menu.hasClass('dropdown-menu-right')
            ? rect.right - width
            : rect.left;

        if (left + width > viewportW - margin) left = viewportW - width - margin;
        if (left < margin) left = margin;

        $menu.css({ top: Math.round(top) + 'px', left: Math.round(left) + 'px' });
    }

    function lift($group) {
        var $toggle = $group.find('[data-toggle="dropdown"]').first();
        var $menu = $group.children('.dropdown-menu').first();

        if (!$toggle.length || !$menu.length || $menu.parent().is('body')) return;

        // A comment node marks the exact spot to put the menu back into.
        var placeholder = document.createComment('erp-menu');
        $menu[0].parentNode.insertBefore(placeholder, $menu[0]);
        $menu.data(PLACEHOLDER_DATA, placeholder);

        $menu.addClass(DETACHED).appendTo(document.body);

        place($toggle, $menu);

        /*
         * Measured again on the next frame. The first measurement can be taken
         * before the browser has applied the detached class's own padding and
         * min-width, and a height read too early puts the menu in the wrong
         * place - which is exactly how one of the per-screen fixes failed.
         */
        var again = function () { place($toggle, $menu); };

        if (window.requestAnimationFrame) {
            window.requestAnimationFrame(again);
        } else {
            window.setTimeout(again, 0);
        }
    }

    $(document)
        .off('.erpMenuLift')
        .on('shown.bs.dropdown.erpMenuLift', function (e) {
            var $group = $(e.target);

            if (!isTableMenu($group)) return;

            restoreAll();
            lift($group);
        })
        .on('hidden.bs.dropdown.erpMenuLift', function (e) {
            if (isTableMenu($(e.target))) restoreAll();
        });

    /*
     * A detached menu has no ancestor left to scroll with, so it would hang in
     * place while the page moved. Closing is what Bootstrap does on an outside
     * click anyway.
     */
    $(window).off('.erpMenuLift').on('scroll.erpMenuLift resize.erpMenuLift', function () {
        if (!$('body').children('.' + DETACHED).length) return;

        restoreAll();
        $('table').find('.btn-group.open, .dropdown.open').removeClass('open');
    });

    // DataTables replaces every row on a redraw; a menu still detached at that
    // moment would be orphaned on <body>.
    $(document).on('draw.dt.erpMenuLift destroy.dt.erpMenuLift', 'table', restoreAll);
})();
