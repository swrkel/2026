(function ($) {
    'use strict';
    $(document).on('click', '.rn-online-status', function () {
        var btn = $(this), id = btn.data('id'), status = btn.data('status');
        $.post('/restaurant-new/online-admin/orders/' + id + '/status', {_token: $('meta[name="csrf-token"]').attr('content'), status: status})
            .done(function () { window.location.reload(); })
            .fail(function () { alert('Unable to update online order status.'); });
    });
})(jQuery);
