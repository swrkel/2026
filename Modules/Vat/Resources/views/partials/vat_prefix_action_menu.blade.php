{{--
    S664: stop the VAT prefix Action menus being clipped.

    On all three prefix screens - VAT Invoice, VAT Invoice 2, and User to Invoice
    Prefix - Edit and Delete were ALWAYS rendered by the controller. The reported
    "buttons not showing" was the menu being cut off by the scrollable table
    wrapper, which sets overflow-x: auto together with overflow-y: hidden: a menu
    opening downward out of a row is clipped at the bottom edge, leaving the thin
    empty box in the ticket.

    Lifting the wrapper's overflow is not an option - these tables have many
    columns and genuinely need to scroll sideways. Instead the open menu is moved
    to <body> and positioned against the viewport, so no ancestor overflow can
    reach it. Same approach already proven on the Supplier Payments, Purchase
    Entries and MPCS F10 lists.

    Include this once per page that renders a VAT prefix table.
--}}
<style>
    .vat-prefix-action-menu-detached {
        position: fixed;
        z-index: 1065;
        display: block;
        margin: 0;
        padding: 5px 0;
        box-sizing: border-box;
        background: #fff;
        border: 1px solid rgba(0, 0, 0, .15);
        border-radius: 4px;
        box-shadow: 0 6px 12px rgba(0, 0, 0, .175);
        list-style: none;
    }

    .vat-prefix-action-menu-detached > li,
    .vat-prefix-action-menu-detached > li > a {
        display: block;
        float: none !important;
        width: 100%;
        box-sizing: border-box;
    }

    .vat-prefix-action-menu-detached > li > a {
        padding: 6px 16px;
        clear: both;
        color: #333;
        font-weight: 400;
        line-height: 1.42857143;
        white-space: nowrap;
        text-decoration: none;
    }

    .vat-prefix-action-menu-detached > li > a:hover,
    .vat-prefix-action-menu-detached > li > a:focus {
        background-color: #f5f5f5;
        color: #262626;
    }

    /* Disabled entries must not look clickable on hover. */
    .vat-prefix-action-menu-detached > li.disabled > a:hover,
    .vat-prefix-action-menu-detached > li.disabled > a:focus {
        background-color: transparent;
        color: #999;
    }

    .vat-prefix-action-menu-detached > li.divider {
        height: 1px;
        margin: 6px 0;
        padding: 0;
        background-color: #e5e5e5;
        overflow: hidden;
    }
</style>

<script>
(function ($) {
    'use strict';

    if (!$) { return; }

    var VatPrefixMenu = {
        selector: '.vat-prefix-action-group',

        bind: function () {
            $(document).off('.vatPrefixMenu');
            $(window).off('.vatPrefixMenu');

            VatPrefixMenu.closeAll();

            $(document).on('show.bs.dropdown.vatPrefixMenu', VatPrefixMenu.selector, function () {
                var $current = $(this);
                VatPrefixMenu.restore($current);
                VatPrefixMenu.closeAll($current);
            });

            $(document).on('shown.bs.dropdown.vatPrefixMenu', VatPrefixMenu.selector, function () {
                VatPrefixMenu.detach($(this));
            });

            $(document).on(
                'hide.bs.dropdown.vatPrefixMenu hidden.bs.dropdown.vatPrefixMenu',
                VatPrefixMenu.selector,
                function () { VatPrefixMenu.restore($(this)); }
            );

            // A detached menu is positioned against the viewport, so any movement
            // leaves it pointing at the wrong row. Closing beats relocating.
            $(window).on('resize.vatPrefixMenu scroll.vatPrefixMenu', function () {
                VatPrefixMenu.closeAll();
            });

            // Paging, search and reload replace every row; a menu still attached
            // to <body> would outlive its owner.
            $(document).on('draw.dt.vatPrefixMenu preXhr.dt.vatPrefixMenu', function () {
                VatPrefixMenu.closeAll();
            });
        },

        detach: function ($group) {
            var $toggle = $group.children('.dropdown-toggle');
            var $menu = $group.children('.vat-prefix-action-menu');

            if (!$toggle.length || !$menu.length || $menu.parent().is('body')) {
                return;
            }

            var rect = $toggle.get(0).getBoundingClientRect();
            var vw = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
            var vh = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
            var gap = 10;
            var menuWidth = Math.min(260, Math.max(170, vw - (gap * 2)));

            $group.data('vat-detached-menu', $menu);
            $menu.data('vat-menu-owner', $group.get(0));

            $menu.addClass('vat-prefix-action-menu-detached')
                .appendTo(document.body)
                .css({
                    display: 'block', position: 'fixed', visibility: 'hidden',
                    top: 0, left: 0, right: 'auto',
                    width: menuWidth, minWidth: menuWidth, maxWidth: menuWidth,
                    maxHeight: 'none'
                });

            var natural = $menu.get(0).scrollHeight + 2;
            var below = Math.max(0, vh - rect.bottom - gap);
            var above = Math.max(0, rect.top - gap);
            var full = Math.max(120, vh - (gap * 2));
            var maxHeight, top;

            if (natural <= below) {
                maxHeight = below; top = rect.bottom + 4;
            } else if (natural <= above) {
                maxHeight = above; top = Math.max(gap, rect.top - natural - 4);
            } else {
                // Fits neither side: use the whole viewport rather than the
                // larger gap. The menu overlays the page anyway once detached.
                maxHeight = full; top = gap;
            }

            maxHeight = Math.min(maxHeight, full);

            // The markup is dropdown-menu-right, so align the menu's right edge
            // to the toggle's, then clamp into the viewport.
            var left = Math.min(
                Math.max(gap, rect.right - menuWidth),
                Math.max(gap, vw - menuWidth - gap)
            );

            $menu.css({
                top: Math.round(top),
                left: Math.round(left),
                maxHeight: Math.round(maxHeight),
                overflowX: 'hidden',
                // Always auto: content is clipped to the box however wrong the
                // height estimate turns out to be, and auto shows no scrollbar
                // when everything fits.
                overflowY: 'auto',
                visibility: 'visible'
            });
        },

        closeAll: function ($except) {
            $(VatPrefixMenu.selector).each(function () {
                var $group = $(this);
                if ($except && $except.length && $group.get(0) === $except.get(0)) { return; }
                $group.removeClass('open');
                $group.children('.dropdown-toggle').attr('aria-expanded', 'false');
                VatPrefixMenu.restore($group);
            });

            $('body > .vat-prefix-action-menu-detached').each(function () {
                var $menu = $(this);
                var owner = $menu.data('vat-menu-owner');
                if (owner && document.documentElement.contains(owner)) {
                    VatPrefixMenu.restore($(owner));
                } else {
                    $menu.remove();
                }
            });
        },

        restore: function ($group) {
            var $menu = $group.data('vat-detached-menu');
            if (!$menu || !$menu.length) { return; }

            $menu.removeClass('vat-prefix-action-menu-detached')
                .removeData('vat-menu-owner')
                .removeAttr('style')
                .appendTo($group);

            $group.removeData('vat-detached-menu');
        }
    };

    $(function () { VatPrefixMenu.bind(); });
})(window.jQuery);
</script>
