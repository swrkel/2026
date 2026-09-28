{{--
    LA-1166 / IS1990 - Customer statements > List customer statement > Action

    REPORTED
        Only "Delete" is visible when Actions is opened. The other six entries -
        Convert to VAT statement, Pay due amount, Print, View, PDF, Excel - are
        cut off.

    WHY
        The menu is generated in full. CustomerStandaloneStatementController
        builds all seven entries, and Delete is the LAST one appended, so its
        being the one entry on screen proves the markup was produced completely
        and is simply being clipped.

        The clipping comes from the theme, in resources/views/layouts/app.blade.php:

            .box, .card, .info-box, ... { overflow: hidden; }
            .table                      { overflow: hidden; }

        The grid sits inside both. A Bootstrap dropdown is positioned absolutely
        inside its .btn-group, so anything of it that falls outside those boxes
        is cut away. On the first row the menu opens upward, so everything above
        the row is clipped and only the bottom entry survives.

        Widening the column or raising z-index cannot help: z-index does not
        defeat overflow clipping, and the menu is taller than the row whatever
        the column width.

    THE FIX
        While a menu is open it is moved to <body> and positioned with
        position:fixed against the button's own screen coordinates, so no
        ancestor can clip it. It is returned to its original place the moment it
        closes, so the markup DataTables owns is unchanged between redraws.

        This is the same approach the Suppliers module already uses for its
        payment action menu (Modules/Suppliers/Resources/assets/js/suppliers.js),
        so the two behave alike.

        Attached through the existing fixes mechanism rather than by editing the
        shared Customers view, which two modules render.
--}}
<style id="customer-statements-la1166-style">
    /*
     * A menu that has been moved to <body> is positioned from JavaScript.
     * Fixed positioning takes it out of every scrolling and overflow context on
     * the page, which is the whole point of moving it.
     */
    body > .cs-la1166-action-menu-detached {
        position: fixed !important;
        z-index: 2147483000 !important;
        display: block !important;
        margin: 0 !important;
        max-height: 80vh !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        min-width: 210px !important;
        background: #ffffff !important;
        border: 1px solid #dfe6e9 !important;
        border-radius: 10px !important;
        box-shadow: 0 12px 32px rgba(0, 0, 0, .22) !important;
        padding: 6px !important;
        list-style: none !important;
    }

    body > .cs-la1166-action-menu-detached > li {
        display: block !important;
        float: none !important;
        width: auto !important;
    }

    body > .cs-la1166-action-menu-detached > li > a {
        display: block !important;
        padding: 8px 14px !important;
        border-radius: 8px !important;
        color: #34495e !important;
        white-space: nowrap !important;
        text-decoration: none !important;
    }

    body > .cs-la1166-action-menu-detached > li > a:hover,
    body > .cs-la1166-action-menu-detached > li > a:focus {
        background: #f4f7fb !important;
        color: #17233b !important;
    }

    body > .cs-la1166-action-menu-detached > li.divider {
        height: 1px !important;
        margin: 6px 4px !important;
        background: #edf2f7 !important;
    }
</style>

<script id="customer-statements-la1166-script">
(function ($) {
    'use strict';

    if (window.__csLa1166ActionMenuLoaded) {
        return;
    }
    window.__csLa1166ActionMenuLoaded = true;

    var DETACHED = 'cs-la1166-action-menu-detached';

    /*
     * Only the statement grids. The workspace renders other tables, and their
     * menus are not clipped, so they are left exactly as they are.
     */
    var TABLES = '#customer_statement_list_table, #customer_statement_table';

    function restore($menu) {
        var placeholder = $menu.data('csLa1166Placeholder');

        $menu.removeClass(DETACHED).removeAttr('style');

        if (placeholder && placeholder.parentNode) {
            placeholder.parentNode.insertBefore($menu[0], placeholder);
            placeholder.parentNode.removeChild(placeholder);
        }

        $menu.removeData('csLa1166Placeholder');
    }

    function restoreAll() {
        $('body').children('.' + DETACHED).each(function () {
            restore($(this));
        });
    }

    /*
     * LA-1170: place the menu against the button, and place it AGAIN on the
     * next frame.
     *
     * The first attempt measured the menu the instant it was appended to
     * <body>. At that moment the browser has not necessarily applied the
     * detached class's own rules - min-width, padding, max-height - so
     * outerHeight() can come back as something quite different from the height
     * the menu finally paints at. A height read too large makes the "is there
     * room below?" test fail, the menu flips or is pushed down, and it lands
     * well away from the button it belongs to, which is what was reported.
     *
     * The placement is therefore repeated inside requestAnimationFrame, once
     * the browser has laid the menu out and the measurements are real. The
     * first pass still runs so nothing flashes in the wrong place at the far
     * corner of the screen.
     */
    function positionMenu($toggle, $menu) {
        if (!$toggle.length || !$menu.length || !$menu.parent().is('body')) {
            return;
        }

        var rect = $toggle[0].getBoundingClientRect();
        var menuWidth = $menu.outerWidth();
        var menuHeight = $menu.outerHeight();
        var margin = 8;
        var gap = 4;
        var viewportH = window.innerHeight || document.documentElement.clientHeight;
        var viewportW = window.innerWidth || document.documentElement.clientWidth;

        var below = rect.bottom + gap;
        var above = rect.top - menuHeight - gap;
        var top;

        if (below + menuHeight <= viewportH - margin) {
            top = below;                       // room underneath - the normal case
        } else if (above >= margin) {
            top = above;                       // no room below, but room above
        } else {
            /*
             * Neither side fits. Sit against the bottom of the viewport rather
             * than drifting far from the button - the menu scrolls internally
             * (max-height:80vh in the stylesheet), so every item stays reachable.
             */
            top = Math.max(margin, viewportH - menuHeight - margin);
        }

        var left = rect.left;

        if (left + menuWidth > viewportW - margin) {
            left = Math.max(margin, viewportW - menuWidth - margin);
        }

        if (left < margin) {
            left = margin;
        }

        $menu.css({ top: Math.round(top) + 'px', left: Math.round(left) + 'px' });
    }

    function lift($toggle) {
        var $group = $toggle.closest('.btn-group, .dropdown');
        var $menu = $group.children('.dropdown-menu').first();

        if (!$menu.length || $menu.parent().is('body')) {
            return;
        }

        // Leave a marker so the menu goes back exactly where it came from.
        var placeholder = document.createComment('cs-la1166-menu');
        $menu[0].parentNode.insertBefore(placeholder, $menu[0]);
        $menu.data('csLa1166Placeholder', placeholder);

        $menu.addClass(DETACHED).appendTo(document.body);

        positionMenu($toggle, $menu);

        // Measure again once the browser has actually laid the menu out.
        var reposition = function () {
            positionMenu($toggle, $menu);
        };

        if (window.requestAnimationFrame) {
            window.requestAnimationFrame(reposition);
        } else {
            window.setTimeout(reposition, 0);
        }
    }

    // Bootstrap fires these on the .btn-group / .dropdown, after it has toggled.
    $(document)
        .off('.csLa1166')
        .on('shown.bs.dropdown.csLa1166', function (e) {
            var $group = $(e.target);

            if (!$group.closest(TABLES).length) {
                return;
            }

            restoreAll();
            lift($group.find('[data-toggle="dropdown"]').first());
        })
        .on('hidden.bs.dropdown.csLa1166', function (e) {
            if (!$(e.target).closest(TABLES).length) {
                return;
            }

            restoreAll();
        });

    /*
     * A detached menu has no ancestor left to scroll with, so it would otherwise
     * stay behind when the page moves. Closing is the honest behaviour here, and
     * it is what Bootstrap does on an outside click anyway.
     */
    $(window).off('.csLa1166').on('scroll.csLa1166 resize.csLa1166', function () {
        if (!$('body').children('.' + DETACHED).length) {
            return;
        }

        restoreAll();
        $(TABLES).find('.btn-group.open, .dropdown.open').removeClass('open');
    });

    /*
     * DataTables replaces every row on a redraw. Any menu still detached at that
     * moment would be orphaned on <body>, so it is put back first.
     */
    $(document).on('draw.dt.csLa1166 destroy.dt.csLa1166', TABLES, function () {
        restoreAll();
    });
})(jQuery);
</script>
