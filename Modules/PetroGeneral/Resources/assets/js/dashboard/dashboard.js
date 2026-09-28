$(document).ready(function () {
    $('.pg-table').each(function () {
        if ($.fn.DataTable && ! $.fn.DataTable.isDataTable(this)) {
            $(this).DataTable({
                responsive: true,
                pageLength: 25,
                order: []
            });
        }
    });
});
