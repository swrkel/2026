(function ($) {
    'use strict';

    $(function () {
        if ($.fn.select2) {
            $('.purchase-workspace .select2').select2({ width: '100%' });
        }

        $(document).on('click', '.purchase-return-delete', function (event) {
            event.preventDefault();
            var url = $(this).data('url');
            if (!url || !window.confirm('Delete this purchase return and reverse its stock/accounting effects?')) {
                return;
            }

            $.ajax({
                url: url,
                method: 'POST',
                data: { _method: 'DELETE', _token: $('meta[name="csrf-token"]').attr('content') },
                headers: { Accept: 'application/json' }
            }).done(function (response) {
                if (response && response.success === false) {
                    window.alert(response.message || response.msg || 'Unable to delete the purchase return.');
                    return;
                }
                window.location.reload();
            }).fail(function (xhr) {
                var message = (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.msg)) || 'Unable to delete the purchase return.';
                window.alert(message);
            });
        });
    });
})(jQuery);
