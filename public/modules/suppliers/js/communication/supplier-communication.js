(function ($) {
    'use strict';

    $(document).on('submit', '.supplier-communication-form', function (event) {
        var action = $(this).attr('action');
        if (!action || action === '#') {
            event.preventDefault();
            toastr.info('This tab is ready for table binding in the next database package.');
        }
    });

    $('.supplier-communication-table').each(function () {
        if ($.fn.DataTable && !$.fn.DataTable.isDataTable(this)) {
            $(this).DataTable({
                responsive: false,
                scrollX: true,
                pageLength: 25,
                order: []
            });
        }
    });
})(jQuery);
