(function ($) {
    'use strict';
    $(function () {
        if ($.fn.datepicker) {
            $('.datepicker').datepicker({ autoclose: true, todayHighlight: true, orientation: 'bottom' });
        }
        if ($.fn.DataTable) {
            $('.bs-voucher-table').DataTable({ scrollX: true, autoWidth: false, order: [[0, 'desc']] });
        }
    });
})(jQuery);
