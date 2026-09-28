@once
<style>
    /*
    |--------------------------------------------------------------------------
    | Global ERP Action Dropdown Fix
    |--------------------------------------------------------------------------
    | Prevent Actions dropdown menus from being hidden behind DataTables toolbars,
    | scroll wrappers, cards, widgets, and table-responsive containers.
    */
    .erp-action-dropdown-fix .table-responsive,
    .erp-action-dropdown-fix .dataTables_wrapper,
    .erp-action-dropdown-fix .dataTables_scroll,
    .erp-action-dropdown-fix .dataTables_scrollBody,
    .erp-action-dropdown-fix .box,
    .erp-action-dropdown-fix .box-body,
    .erp-action-dropdown-fix .tab-content,
    .erp-action-dropdown-fix .tab-pane,
    .erp-action-dropdown-fix .content,
    .erp-action-dropdown-fix section.content {
        overflow: visible !important;
    }

    .erp-action-dropdown-fix .btn-group,
    .erp-action-dropdown-fix .dropdown,
    .erp-action-dropdown-fix td,
    .erp-action-dropdown-fix th {
        position: relative;
    }

    .erp-action-dropdown-fix .dropdown-menu {
        z-index: 999999 !important;
        max-height: none !important;
    }

    .erp-action-dropdown-fix .dropdown-menu.erp-open-down {
        top: 100% !important;
        bottom: auto !important;
        margin-top: 2px !important;
        margin-bottom: 0 !important;
    }

    .erp-action-dropdown-fix .dropdown-menu.erp-open-up {
        top: auto !important;
        bottom: 100% !important;
        margin-top: 0 !important;
        margin-bottom: 2px !important;
    }
</style>

<script type="text/javascript">
    (function ($) {
        "use strict";

        function applyErpActionDropdownFix() {
            $('body').addClass('erp-action-dropdown-fix');
        }

        function positionErpDropdown($group) {
            var $menu = $group.find('> .dropdown-menu');
            if (!$menu.length) {
                $menu = $group.find('.dropdown-menu').first();
            }
            if (!$menu.length) {
                return;
            }

            $menu.removeClass('erp-open-up erp-open-down');

            var buttonOffset = $group.offset();
            if (!buttonOffset) {
                $menu.addClass('erp-open-down');
                return;
            }

            var windowTop = $(window).scrollTop();
            var windowHeight = $(window).height();
            var buttonHeight = $group.outerHeight() || 30;
            var menuHeight = $menu.outerHeight() || 260;

            var spaceAbove = buttonOffset.top - windowTop;
            var spaceBelow = (windowTop + windowHeight) - (buttonOffset.top + buttonHeight);

            /*
             * Global rule:
             * - If there is enough space below, open down.
             * - If below space is poor and above space is better, open up.
             * - This prevents top rows and toolbar rows from hiding menu items.
             */
            if (spaceBelow >= menuHeight || spaceBelow >= spaceAbove) {
                $group.removeClass('dropup');
                $menu.addClass('erp-open-down');
            } else {
                $group.addClass('dropup');
                $menu.addClass('erp-open-up');
            }
        }

        $(document).ready(function () {
            applyErpActionDropdownFix();
        });

        $(document).on('shown.bs.dropdown', '.btn-group, .dropdown', function () {
            positionErpDropdown($(this));
        });

        $(window).on('resize scroll', function () {
            $('.btn-group.open, .dropdown.open').each(function () {
                positionErpDropdown($(this));
            });
        });

        $(document).on('draw.dt', function () {
            applyErpActionDropdownFix();
        });
    })(jQuery);
</script>
@endonce
