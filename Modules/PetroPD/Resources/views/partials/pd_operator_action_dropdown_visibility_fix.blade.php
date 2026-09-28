{{--
    ZIP 308 - PetroPD PD Operators Actions Dropdown Visibility Fix
    Keeps the full Actions dropdown visible above the table/card, same working behavior as Customers.
    Standalone PetroPD partial.
--}}
<style>
    .petropd-actions-dropdown .dropdown-menu {
        min-width: 235px;
        text-align: left;
    }

    body > .petropd-floating-action-menu {
        position: absolute !important;
        z-index: 2147483647 !important;
        display: block !important;
        min-width: 235px;
        max-height: none !important;
        overflow: visible !important;
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.18);
        border-radius: 4px;
    }

    body > .petropd-floating-action-menu > li > a {
        white-space: nowrap;
        padding: 7px 14px;
        line-height: 1.4;
    }

    .petropd-actions-dropdown .dropdown-toggle {
        white-space: nowrap;
    }
</style>

<script>
    (function ($) {
        'use strict';

        function moveMenuToBody($group) {
            var $button = $group.find('[data-toggle="dropdown"]').first();
            var $menu = $group.children('.dropdown-menu').first();

            if (!$button.length || !$menu.length) {
                return;
            }

            if (!$menu.data('petropd-original-parent')) {
                $menu.data('petropd-original-parent', $group);
            }

            var offset = $button.offset();
            var menuWidth = Math.max($menu.outerWidth(), 235);
            var left = offset.left;
            var top = offset.top + $button.outerHeight();
            var windowWidth = $(window).width();
            var scrollTop = $(window).scrollTop();
            var windowHeight = $(window).height();

            if ((left + menuWidth + 20) > windowWidth) {
                left = Math.max(10, windowWidth - menuWidth - 20);
            }

            $menu.appendTo('body')
                .addClass('petropd-floating-action-menu')
                .css({
                    top: top,
                    left: left,
                    width: menuWidth,
                    minWidth: menuWidth
                });

            var menuHeight = $menu.outerHeight();
            if ((top + menuHeight) > (scrollTop + windowHeight - 15)) {
                var newTop = offset.top - menuHeight;
                if (newTop > scrollTop + 10) {
                    $menu.css('top', newTop);
                }
            }
        }

        function restoreMenu($menu) {
            var $parent = $menu.data('petropd-original-parent');
            if ($parent && $parent.length) {
                $menu.removeClass('petropd-floating-action-menu')
                    .removeAttr('style')
                    .appendTo($parent);
            }
        }

        $(document)
            .off('show.bs.dropdown.petropdActionFix', '.petropd-actions-dropdown')
            .on('show.bs.dropdown.petropdActionFix', '.petropd-actions-dropdown', function () {
                moveMenuToBody($(this));
            })
            .off('hide.bs.dropdown.petropdActionFix', '.petropd-actions-dropdown')
            .on('hide.bs.dropdown.petropdActionFix', '.petropd-actions-dropdown', function () {
                var $group = $(this);
                setTimeout(function () {
                    var $menu = $('body > .petropd-floating-action-menu');
                    if ($menu.length) {
                        $menu.each(function () {
                            restoreMenu($(this));
                        });
                    } else {
                        restoreMenu($group.children('.dropdown-menu').first());
                    }
                }, 1);
            });

        $(document).off('click.petropdFloatingActionMenu').on('click.petropdFloatingActionMenu', 'body > .petropd-floating-action-menu a', function () {
            var $menu = $(this).closest('.petropd-floating-action-menu');
            restoreMenu($menu);
        });

        $(window).off('scroll.petropdActionFix resize.petropdActionFix').on('scroll.petropdActionFix resize.petropdActionFix', function () {
            $('body > .petropd-floating-action-menu').each(function () {
                restoreMenu($(this));
            });
        });
    })(jQuery);
</script>
