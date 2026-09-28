/*
 * PDRW-006 - PetroPD Payment Summary Edit modal definitive handler.
 * Handles existing .btn-modal links and dedicated edit links for:
 * /petropd/pump-operators/payment/{id}/edit
 */
(function ($) {
    'use strict';

    function ensureModal() {
        var $modal = $('.view_modal').first();
        if (!$modal.length) {
            $('body').append('<div class="modal fade view_modal pump_operator_modal pump_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>');
            $modal = $('.view_modal').first();
        }
        if (!$modal.hasClass('pump_operator_modal')) {
            $modal.addClass('pump_operator_modal');
        }
        if (!$modal.hasClass('pump_modal')) {
            $modal.addClass('pump_modal');
        }
        return $modal;
    }

    function isPaymentSummaryEditLink($link) {
        var href = $link.data('href') || $link.attr('href') || '';
        return href.indexOf('/petropd/pump-operators/payment/') !== -1 && href.indexOf('/edit') !== -1;
    }

    $(document).off('click.pdrw006PaymentSummaryEdit');
    $(document).on('click.pdrw006PaymentSummaryEdit', 'a[data-href], a[href]', function (e) {
        var $link = $(this);
        if (!isPaymentSummaryEditLink($link)) {
            return true;
        }

        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        var href = $link.data('href') || $link.attr('href');
        var $modal = ensureModal();

        $modal.html('<div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-body text-center"><i class="fa fa-spinner fa-spin"></i> Loading edit form...</div></div></div>');
        $modal.modal('show');

        $.ajax({
            url: href,
            type: 'GET',
            dataType: 'html',
            cache: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (html) {
                if (!html || $.trim(html) === '') {
                    $modal.html('<div class="modal-dialog"><div class="modal-content"><div class="modal-body"><div class="alert alert-danger">Edit form returned empty response.</div></div></div></div>');
                    return;
                }

                if (html.indexOf('modal-dialog') === -1 && html.indexOf('modal-content') === -1) {
                    html = '<div class="modal-dialog modal-lg" role="document"><div class="modal-content">' + html + '</div></div>';
                }

                $modal.html(html).modal('show');
                if ($.fn.select2) {
                    $modal.find('.select2').select2({ dropdownParent: $modal });
                }
            },
            error: function (xhr) {
                var message = xhr.responseText || 'Unable to open edit form.';
                $modal.html('<div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Payment Edit Error</h4></div><div class="modal-body"><div class="alert alert-danger">' + message + '</div></div></div></div>');
            }
        });

        return false;
    });

    $(function () {
        ensureModal();
    });
})(jQuery);
