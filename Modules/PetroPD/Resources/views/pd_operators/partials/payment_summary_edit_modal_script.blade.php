<div class="modal fade pd_payment_edit_modal" tabindex="-1" role="dialog" aria-hidden="true"></div>

<script type="text/javascript">
(function ($) {
    'use strict';

    function ensurePaymentEditModal(container) {
        var $modal = $(container);
        if (!$modal.length) {
            $('body').append('<div class="modal fade pd_payment_edit_modal" tabindex="-1" role="dialog" aria-hidden="true"></div>');
            $modal = $('.pd_payment_edit_modal').last();
        }
        return $modal;
    }

    $(document)
        .off('click.petroPdPaymentSummaryEditOpen', 'a.pd-payment-edit-link')
        .on('click.petroPdPaymentSummaryEditOpen', 'a.pd-payment-edit-link', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            var $link = $(this);
            var href = $link.data('href') || $link.attr('href');
            var container = $link.data('container') || '.pd_payment_edit_modal';

            if (!href || $link.closest('li').hasClass('disabled')) {
                return false;
            }

            // PETROPD_PAY_SUM_EDIT_008
            // Close the Actions dropdown before opening the modal.
            // This prevents the Edit/Re-Print menu from remaining visible over the modal.
            $link.closest('.btn-group, .dropdown').removeClass('open show');
            $('.pd-payment-action-dropdown').removeClass('open show');
            $('.pd-payment-action-dropdown .dropdown-menu').hide();
            $('body').trigger('click');

            var $modal = ensurePaymentEditModal(container);
            $modal.html('<div class="modal-dialog"><div class="modal-content"><div class="modal-body text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</div></div></div>');
            $modal.modal('show');

            $.ajax({
                url: href,
                type: 'GET',
                dataType: 'html',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (result) {
                    $modal.html(result).modal('show');
                    $modal.find('.select2').select2({ width: '100%' });
                },
                error: function (xhr) {
                    var message = 'Unable to open Edit form.';
                    if (xhr.responseJSON && xhr.responseJSON.msg) {
                        message = xhr.responseJSON.msg;
                    } else if (xhr.responseText) {
                        message = $('<div>').html(xhr.responseText).text() || message;
                    }

                    $modal.html('<div class="modal-dialog"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Payment Edit</h4></div><div class="modal-body"><div class="alert alert-danger">' + message + '</div></div></div></div>');

                    if (typeof toastr !== 'undefined') {
                        toastr.error(message);
                    }
                }
            });

            return false;
        });
})(jQuery);
</script>
