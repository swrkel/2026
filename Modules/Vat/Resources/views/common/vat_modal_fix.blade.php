<script type="text/javascript">
(function($) {
    "use strict";

    function vatInitModalControls($container) {
        if ($.fn.select2) {
            $container.find('select.select2').each(function() {
                if ($(this).data('select2')) {
                    $(this).select2('destroy');
                }
                $(this).select2({ dropdownParent: $container });
            });
        }

        if ($.fn.datepicker) {
            $container.find('.datepicker').datepicker({
                autoclose: true,
                format: (typeof datepicker_date_format !== 'undefined' ? datepicker_date_format : 'yyyy-mm-dd')
            });
        }
    }

    $(document).off('click.vatModalFix', '.vat-btn-modal, .btn-modal[data-container=".fuel_tank_modal"], .btn-vat-modal').on('click.vatModalFix', '.vat-btn-modal, .btn-modal[data-container=".fuel_tank_modal"], .btn-vat-modal', function(e) {
        e.preventDefault();

        var href = $(this).data('href');
        var containerSelector = $(this).data('container') || '.fuel_tank_modal';
        var $container = $(containerSelector);

        if (!href || !$container.length) {
            return false;
        }

        $container.html('<div class="modal-dialog"><div class="modal-content"><div class="modal-body text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</div></div></div>');
        $container.modal('show');

        $.ajax({
            method: 'GET',
            url: href,
            dataType: 'html',
            success: function(result) {
                $container.html(result);
                $container.modal('show');
                vatInitModalControls($container);
            },
            error: function(xhr) {
                $container.modal('hide');
                if (typeof toastr !== 'undefined') {
                    toastr.error(xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : @json(__('messages.something_went_wrong')));
                } else {
                    alert(@json(__('messages.something_went_wrong')));
                }
            }
        });

        return false;
    });
})(jQuery);
</script>
