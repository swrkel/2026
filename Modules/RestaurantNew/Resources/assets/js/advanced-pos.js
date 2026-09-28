(function ($) {
    'use strict';

    function postJson(url, payload, onSuccess) {
        $.ajax({
            url: url,
            method: 'POST',
            data: payload || {},
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            success: function (response) {
                if (response.success) {
                    if (typeof onSuccess === 'function') { onSuccess(response); }
                    if (window.toastr) { toastr.success('Operation completed successfully'); }
                } else if (window.toastr) {
                    toastr.error(response.message || 'Operation failed');
                }
            },
            error: function (xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Restaurant operation failed';
                if (window.toastr) { toastr.error(message); } else { alert(message); }
            }
        });
    }

    $(document).on('click', '.rn-hold-order', function () {
        var reason = prompt('Reason for hold/resume note', 'Customer waiting');
        postJson($(this).data('url'), {reason: reason}, function () { window.location.reload(); });
    });

    $(document).on('click', '.rn-transfer-table', function () {
        var toTable = prompt('Transfer to table ID');
        if (!toTable) { return; }
        postJson($(this).data('url'), {to_table_id: toTable}, function () { window.location.reload(); });
    });

    $(document).on('click', '.rn-change-waiter', function () {
        var waiter = prompt('New waiter/staff ID');
        if (!waiter) { return; }
        postJson($(this).data('url'), {to_waiter_id: waiter}, function () { window.location.reload(); });
    });

    $(document).on('click', '.rn-multi-payment', function () {
        var amount = prompt('Payment amount');
        if (!amount) { return; }
        var method = prompt('Payment method: cash/card/bank/credit', 'cash');
        postJson($(this).data('url'), {payments: [{payment_method: method || 'cash', amount: amount}]}, function () { window.location.reload(); });
    });

    $(document).on('click', '.rn-split-bill', function () {
        alert('Split bill endpoint is ready. Detailed drag-and-drop item splitter will be enhanced in the next UI hardening stage.');
    });
})(jQuery);
