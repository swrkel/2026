(function () {
    'use strict';
    if (window.jQuery) {
        $('.tailoring-table').each(function () {
            if ($.fn.DataTable && !$.fn.DataTable.isDataTable(this)) {
                $(this).DataTable({responsive: true, pageLength: 25});
            }
        });
    }
})();
