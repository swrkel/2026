(function ($) {
    'use strict';
    $(function () {
        $('.product-simple-table').each(function () {
            var $table = $(this);
            var dt = $table.DataTable({
                processing: true,
                serverSide: true,
                ajax: $table.data('url'),
                columns: [
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                    { data: 'name', name: 'name' }
                ],
                dom: 'Bfrtip',
                buttons: ['csv', 'excel', 'pdf', 'print', 'colvis']
            });
            $(document).on('click', '.product-delete', function (e) {
                e.preventDefault();
                if (!confirm('Delete?')) return;
                $.ajax({ url: $(this).data('href'), method: 'DELETE', data: { _token: $('meta[name="csrf-token"]').attr('content') }, success: function () { dt.ajax.reload(); } });
            });
        });
    });
})(jQuery);
