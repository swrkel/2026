(function ($) {
    'use strict';
    $(function () {
        ProductModule.bindConfirmDelete('.product-delete-btn');
        if ($.fn.DataTable && $('#product_table').length && !$.fn.DataTable.isDataTable('#product_table')) {
            $('#product_table').DataTable({ responsive: true, order: [] });
        }
    });
})(jQuery);
