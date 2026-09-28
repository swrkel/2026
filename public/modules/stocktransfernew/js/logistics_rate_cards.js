(function ($) {
    'use strict';
    $(document).ready(function () {
        if ($.fn.DataTable) {
            $('.stn-rate-card-table, .stn-variance-table').DataTable({
                pageLength: 25,
                order: [],
                dom: 'Bfrtip',
                buttons: ['csv', 'excel', 'pdf', 'print', 'colvis']
            });
        }
    });
})(jQuery);
