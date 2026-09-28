{{-- 
|--------------------------------------------------------------------------
| SEQ072 Global ERP Actions Dropdown Visibility Fix
|--------------------------------------------------------------------------
| Purpose:
| - Applies globally to DataTables / ERP register/list row "Actions" dropdowns.
| - Prevents dropdown menus from being clipped by DataTables wrappers, toolbars,
|   table containers, modals, responsive tables, or overflow-hidden parents.
| - Automatically opens the dropdown downward or upward based on available space.
|
| Include once globally, preferably near the end of layouts/app.blade.php before
| </body>, or inside the global javascripts partial after jQuery/Bootstrap loads:
|
| @include('layouts.partials.global-actions-dropdown-fix')
|--------------------------------------------------------------------------
--}}

<style>
    /*
    |--------------------------------------------------------------------------
    | Global ERP Actions Dropdown Layering
    |--------------------------------------------------------------------------
    */
    .erp-actions-floating-menu {
        position: absolute !important;
        z-index: 2147483647 !important;
        display: block !important;
        max-height: 70vh;
        overflow-y: auto;
        overflow-x: hidden;
        min-width: 220px;
        width: auto;
        margin: 0 !important;
        padding-top: 6px;
        padding-bottom: 6px;
        border-radius: 8px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
    }

    .erp-actions-floating-menu > li > a {
        white-space: nowrap;
        padding-top: 8px;
        padding-bottom: 8px;
    }

    .erp-actions-floating-menu .divider {
        margin: 6px 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Keep common table wrappers from visually clipping dropdown triggers.
    |--------------------------------------------------------------------------
    */
    .dataTables_wrapper,
    .table-responsive,
    .box,
    .box-body,
    .modal-body,
    .content,
    .main-content-inner {
        overflow: visible;
    }

    /*
    |--------------------------------------------------------------------------
    | Allow dropdown button cell to stay visible in DataTables.
    |--------------------------------------------------------------------------
    */
    table.dataTable tbody td,
    table.dataTable tbody th {
        overflow: visible;
    }
</style>

<script>
    /*
    |--------------------------------------------------------------------------
    | SEQ072 Global ERP Actions Dropdown Visibility Fix
    |--------------------------------------------------------------------------
    | This script is intentionally generic. It targets Bootstrap dropdown buttons
    | inside tables/DataTables and moves only the opened dropdown menu to <body>.
    | It then calculates whether to open upward or downward.
    |--------------------------------------------------------------------------
    */
    (function ($) {
        'use strict';

        if (typeof $ === 'undefined') {
            return;
        }

        var floatingMenu = null;
        var floatingParent = null;
        var floatingPlaceholder = null;
        var floatingButton = null;

        function isTableActionDropdown($button) {
            if (!$button || !$button.length) {
                return false;
            }

            var $menu = $button.closest('.btn-group, .dropdown').children('.dropdown-menu');

            if (!$menu.length) {
                return false;
            }

            /*
             * Only apply to table/register/list style dropdowns.
             * This avoids interfering with navbar/profile/header dropdowns.
             */
            if ($button.closest('table').length) {
                return true;
            }

            if ($button.closest('.dataTables_wrapper').length) {
                return true;
            }

            if ($button.closest('.erp-records-table, .erp-datatable, .erp-grid, .table-responsive').length) {
                return true;
            }

            /*
             * Many ERP row action buttons are named Actions/messages.actions.
             */
            var buttonText = $.trim($button.text()).toLowerCase();

            return buttonText === 'actions' ||
                buttonText.indexOf('actions') === 0 ||
                buttonText.indexOf('action') === 0;
        }

        function restoreFloatingMenu() {
            if (floatingMenu && floatingParent && floatingPlaceholder) {
                floatingMenu
                    .removeClass('erp-actions-floating-menu erp-actions-open-up erp-actions-open-down')
                    .removeAttr('style');

                floatingPlaceholder.replaceWith(floatingMenu);
            }

            if (floatingParent) {
                floatingParent.removeClass('open erp-actions-open');
            }

            floatingMenu = null;
            floatingParent = null;
            floatingPlaceholder = null;
            floatingButton = null;
        }

        function positionFloatingMenu() {
            if (!floatingMenu || !floatingButton || !floatingButton.length) {
                return;
            }

            var $button = floatingButton;
            var $menu = floatingMenu;

            $menu.css({
                visibility: 'hidden',
                display: 'block'
            });

            var buttonOffset = $button.offset();
            var buttonHeight = $button.outerHeight();
            var buttonWidth = $button.outerWidth();
            var menuWidth = Math.max($menu.outerWidth(), 220);
            var menuHeight = $menu.outerHeight();

            var windowTop = $(window).scrollTop();
            var windowLeft = $(window).scrollLeft();
            var windowHeight = $(window).height();
            var windowWidth = $(window).width();

            var viewportBottom = windowTop + windowHeight;
            var viewportRight = windowLeft + windowWidth;

            var spaceBelow = viewportBottom - (buttonOffset.top + buttonHeight);
            var spaceAbove = buttonOffset.top - windowTop;

            var openUp = spaceBelow < menuHeight && spaceAbove > spaceBelow;

            var top = openUp
                ? buttonOffset.top - menuHeight - 4
                : buttonOffset.top + buttonHeight + 4;

            var left = buttonOffset.left;

            if (left + menuWidth > viewportRight - 10) {
                left = viewportRight - menuWidth - 10;
            }

            if (left < windowLeft + 10) {
                left = windowLeft + 10;
            }

            if (top < windowTop + 10) {
                top = windowTop + 10;
            }

            if (top + menuHeight > viewportBottom - 10) {
                top = Math.max(windowTop + 10, viewportBottom - menuHeight - 10);
            }

            $menu
                .removeClass('erp-actions-open-up erp-actions-open-down')
                .addClass(openUp ? 'erp-actions-open-up' : 'erp-actions-open-down')
                .css({
                    top: top,
                    left: left,
                    minWidth: Math.max(buttonWidth, 220),
                    visibility: 'visible',
                    display: 'block'
                });
        }

        $(document).on('click', '.btn-group > .dropdown-toggle, .dropdown > .dropdown-toggle', function (e) {
            var $button = $(this);

            if (!isTableActionDropdown($button)) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            var $parent = $button.closest('.btn-group, .dropdown');
            var $menu = $parent.children('.dropdown-menu');

            if ($parent.hasClass('erp-actions-open')) {
                restoreFloatingMenu();
                return false;
            }

            restoreFloatingMenu();

            floatingMenu = $menu;
            floatingParent = $parent;
            floatingButton = $button;
            floatingPlaceholder = $('<span class="erp-actions-placeholder" style="display:none;"></span>');

            $menu.after(floatingPlaceholder);
            $('body').append($menu);

            $parent.addClass('open erp-actions-open');
            $menu.addClass('erp-actions-floating-menu');

            positionFloatingMenu();

            return false;
        });

        $(document).on('click', '.erp-actions-floating-menu', function (e) {
            e.stopPropagation();
        });

        $(document).on('click', '.erp-actions-floating-menu a, .erp-actions-floating-menu button', function () {
            restoreFloatingMenu();
        });

        $(document).on('click', function () {
            restoreFloatingMenu();
        });

        $(window).on('scroll resize', function () {
            restoreFloatingMenu();
        });

        $(document).on('draw.dt', function () {
            restoreFloatingMenu();
        });

        $(document).on('shown.bs.modal hidden.bs.modal', function () {
            restoreFloatingMenu();
        });
    })(jQuery);
</script>
