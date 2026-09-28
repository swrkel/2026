{{--
    Petro PD / List PD Settlement action-menu engine.
    This intentionally does not depend on Bootstrap's dropdown plugin because
    DataTables scroll wrappers and global table CSS can clip or suppress it.
--}}
<style>
    .petropd-settlement-action-menu {
        display: none;
        margin: 0;
        padding: 5px 0;
        list-style: none;
        min-width: 225px;
        text-align: left;
        background: #fff;
        border: 1px solid rgba(0, 0, 0, 0.16);
        border-radius: 4px;
        box-shadow: 0 8px 22px rgba(0, 0, 0, 0.24);
    }

    body > .petropd-settlement-action-menu.petropd-is-open {
        display: block !important;
        position: absolute !important;
        z-index: 2147483647 !important;
        max-height: calc(100vh - 24px);
        overflow-y: auto;
        overflow-x: hidden;
    }

    .petropd-settlement-action-menu > li > a {
        display: block;
        padding: 9px 16px;
        clear: both;
        color: #333;
        line-height: 1.35;
        white-space: nowrap;
        cursor: pointer;
        text-decoration: none;
    }

    .petropd-settlement-action-menu > li > a:hover,
    .petropd-settlement-action-menu > li > a:focus {
        color: #111;
        background: #f2f2f2;
        text-decoration: none;
    }

    .petropd-settlement-action-menu > li > a > .fa {
        width: 20px;
        text-align: center;
        margin-right: 5px;
    }

    .petropd-settlement-action-trigger .caret {
        margin-left: 6px;
    }

    .petropd-settlement-primary-action + .petropd-settlement-action-trigger .caret {
        margin-left: 0;
    }
</style>

<script>
(function ($) {
    'use strict';

    var activeMenu = null;
    var activeTrigger = null;
    var originalParent = null;

    function closePetroPdActionMenu() {
        if (!activeMenu || !activeMenu.length) {
            activeMenu = null;
            activeTrigger = null;
            originalParent = null;
            return;
        }

        if (activeTrigger && activeTrigger.length) {
            activeTrigger.attr('aria-expanded', 'false');
        }

        activeMenu
            .removeClass('petropd-is-open')
            .removeAttr('style')
            .attr('hidden', 'hidden');

        if (originalParent && originalParent.length && $.contains(document, originalParent[0])) {
            activeMenu.appendTo(originalParent);
        } else {
            activeMenu.remove();
        }

        activeMenu = null;
        activeTrigger = null;
        originalParent = null;
    }

    function positionPetroPdActionMenu($trigger, $menu) {
        var offset = $trigger.offset();
        var viewportLeft = $(window).scrollLeft();
        var viewportTop = $(window).scrollTop();
        var viewportRight = viewportLeft + $(window).width();
        var viewportBottom = viewportTop + $(window).height();
        var menuWidth = Math.max(225, $menu.outerWidth() || 225);
        var menuHeight = $menu.outerHeight() || 0;
        var left = offset.left;
        var top = offset.top + $trigger.outerHeight();

        if (left + menuWidth + 10 > viewportRight) {
            left = Math.max(viewportLeft + 8, viewportRight - menuWidth - 10);
        }

        if (top + menuHeight + 10 > viewportBottom) {
            var above = offset.top - menuHeight;
            if (above >= viewportTop + 8) {
                top = above;
            }
        }

        $menu.css({
            top: Math.round(top),
            left: Math.round(left),
            width: menuWidth,
            minWidth: menuWidth
        });
    }

    $(document)
        .off('click.petroPdSettlementActionTrigger', '.petropd-settlement-action-trigger')
        .on('click.petroPdSettlementActionTrigger', '.petropd-settlement-action-trigger', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var $trigger = $(this);
            var $shell = $trigger.closest('.petropd-settlement-action-shell');
            var $menu = $shell.children('.petropd-settlement-action-menu').first();

            if (!$menu.length) {
                return;
            }

            if (activeMenu && activeMenu[0] === $menu[0]) {
                closePetroPdActionMenu();
                return;
            }

            closePetroPdActionMenu();

            activeMenu = $menu;
            activeTrigger = $trigger;
            originalParent = $shell;

            $menu
                .appendTo(document.body)
                .removeAttr('hidden')
                .addClass('petropd-is-open');

            $trigger.attr('aria-expanded', 'true');
            positionPetroPdActionMenu($trigger, $menu);
        });

    $(document)
        .off('click.petroPdSettlementView', '.petropd-view-settlement')
        .on('click.petroPdSettlementView', '.petropd-view-settlement', function (event) {
            event.preventDefault();

            var url = $(this).data('href');
            var $modal = $('.settlement_modal').first();

            if (!url || !$modal.length) {
                if (window.toastr) {
                    toastr.error('Settlement view is unavailable.');
                }
                return;
            }

            $modal.load(url, function (response, status, xhr) {
                if (status === 'error') {
                    console.error('Petro PD settlement view error', xhr.status, xhr.responseText);
                    if (window.toastr) {
                        toastr.error('Unable to open the settlement.');
                    }
                    return;
                }

                $modal.modal('show');
            });
        });

    $(document)
        .off('click.petroPdSettlementMenuItem', 'body > .petropd-settlement-action-menu a')
        .on('click.petroPdSettlementMenuItem', 'body > .petropd-settlement-action-menu a', function () {
            window.setTimeout(closePetroPdActionMenu, 0);
        });

    $(document)
        .off('click.petroPdSettlementOutside')
        .on('click.petroPdSettlementOutside', function (event) {
            if ($(event.target).closest('.petropd-settlement-action-trigger, .petropd-settlement-action-menu').length) {
                return;
            }
            closePetroPdActionMenu();
        })
        .off('keydown.petroPdSettlementActions')
        .on('keydown.petroPdSettlementActions', function (event) {
            if (event.key === 'Escape' || event.keyCode === 27) {
                closePetroPdActionMenu();
            }
        });

    $(window)
        .off('scroll.petroPdSettlementActions resize.petroPdSettlementActions')
        .on('scroll.petroPdSettlementActions resize.petroPdSettlementActions', closePetroPdActionMenu);

    $('#list_pd_settlement')
        .off('preXhr.dt.petroPdSettlementActions draw.dt.petroPdSettlementActions destroy.dt.petroPdSettlementActions')
        .on('preXhr.dt.petroPdSettlementActions draw.dt.petroPdSettlementActions destroy.dt.petroPdSettlementActions', closePetroPdActionMenu);
})(jQuery);
</script>
