(function ($) {
    'use strict';
    $(function () {
        $('.disnew-table').each(function () {
            if ($.fn.DataTable && !$.fn.DataTable.isDataTable(this)) {
                $(this).DataTable({processing: true, serverSide: false, pageLength: 25, order: [[1, 'desc']]});
            }
        });
    });
})(jQuery);
