(function () {
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.rn-datatable').forEach(function (table) {
            if (window.jQuery && jQuery.fn.DataTable && !jQuery.fn.DataTable.isDataTable(table)) {
                jQuery(table).DataTable({ responsive: true, pageLength: 25, dom: 'Bfrtip', buttons: ['csv', 'excel', 'pdf', 'print', 'colvis'] });
            }
        });
    });
})();
