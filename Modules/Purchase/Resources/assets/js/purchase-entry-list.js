(function ($) {
    'use strict';

    $(function () {
        if ($.fn.select2) {
            $('.purchase-workspace .select2').select2({ width: '100%' });
        }

        // Bootstrap dropdowns inside an overflow-x:auto wrapper are clipped by the
        // wrapper. Move only the open Action menu to <body>, keeping its original
        // DOM position recorded so it can be restored on close.
        $(document).on('shown.bs.dropdown', '.purchase-action-group', function () {
            var group = $(this);
            var menu = group.children('.dropdown-menu');
            var button = group.children('.dropdown-toggle');
            if (!menu.length || !button.length || menu.data('purchasePortal')) return;

            var marker = $('<span class="purchase-action-menu-marker" style="display:none"></span>');
            menu.before(marker);
            var rect = button[0].getBoundingClientRect();
            var menuHeight = Math.max(menu.outerHeight() || 0, 120);
            var top = rect.bottom + 2;
            if (top + menuHeight > window.innerHeight - 8 && rect.top > menuHeight + 8) {
                top = Math.max(8, rect.top - menuHeight - 2);
            }
            var left = Math.min(rect.left, Math.max(8, window.innerWidth - (menu.outerWidth() || 160) - 8));

            menu.data('purchasePortal', true)
                .data('purchaseMarker', marker)
                .appendTo(document.body)
                .addClass('purchase-entry-action-menu-portal')
                .css({ top: Math.max(8, top) + 'px', left: Math.max(8, left) + 'px', right: 'auto' });
        });

        $(document).on('hide.bs.dropdown', '.purchase-action-group', function () {
            var menus = $('body > .purchase-entry-action-menu-portal');
            menus.each(function () {
                var menu = $(this);
                var marker = menu.data('purchaseMarker');
                if (marker && marker.length) {
                    menu.removeClass('purchase-entry-action-menu-portal').removeAttr('style').insertAfter(marker);
                    marker.remove();
                }
                menu.removeData('purchasePortal').removeData('purchaseMarker');
            });
        });

        $(document).on('click', '.purchase-entry-delete', function (event) {
            event.preventDefault();
            var link = $(this);
            if (link.hasClass('disabled')) return;
            var url = String(link.data('url') || '');
            var token = String(link.data('token') || $('meta[name="csrf-token"]').attr('content') || '');
            if (!url || !window.confirm('Delete this purchase entry? Stock, payments and accounting entries created by it will be reversed.')) {
                return;
            }

            link.addClass('disabled').attr('aria-disabled', 'true');
            $.ajax({
                url: url,
                type: 'DELETE',
                data: { _token: token },
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).done(function (response) {
                if (response && response.success === false) {
                    window.alert(response.message || response.msg || 'Unable to delete the purchase entry.');
                    link.removeClass('disabled').removeAttr('aria-disabled');
                    return;
                }

                var entryId = String(link.data('entry-id') || '');
                var row = entryId ? $('#purchase_entries_table tbody tr[data-entry-id="' + entryId.replace(/"/g, '\"') + '"]') : link.closest('tr[data-entry-id]');
                if (row.length) {
                    row.fadeOut(180, function () {
                        row.remove();
                        if (!$('#purchase_entries_table tbody tr[data-entry-id]').length) {
                            window.location.reload();
                        }
                    });
                } else {
                    window.location.reload();
                }
            }).fail(function (xhr) {
                var response = xhr.responseJSON || {};
                var errors = response.errors ? Object.keys(response.errors).map(function (key) {
                    return response.errors[key];
                }).reduce(function (all, value) {
                    return all.concat(value || []);
                }, []).join(' ') : '';
                window.alert(response.message || response.msg || errors || 'Unable to delete the purchase entry.');
                link.removeClass('disabled').removeAttr('aria-disabled');
            });
        });
    });
})(jQuery);
