(function ($) {
    'use strict';
    $(function () {
        if ($.fn.DataTable) {
            $('.rn-datatable').DataTable({ responsive: true, pageLength: 25 });
        }
    });
})(jQuery);
