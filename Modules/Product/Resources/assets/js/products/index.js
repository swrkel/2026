(function ($) {
    'use strict';
    $(function () {
        var table = $('#product_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: window.location.origin + '/product/datatable/list',
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'sku', name: 'sku' },
                { data: 'type', name: 'type' },
                { data: 'enable_stock', name: 'enable_stock' }
            ],
            dom: 'Bfrtip',
            buttons: ['csv', 'excel', 'pdf', 'print', 'colvis']
        });
        $('#product_search').on('keyup change', function () { table.search(this.value).draw(); });
        $(document).on('click', '.product-delete', function (e) {
            e.preventDefault();
            if (!confirm('Delete?')) return;
            $.ajax({ url: $(this).data('href'), method: 'DELETE', data: { _token: $('meta[name="csrf-token"]').attr('content') }, success: function () { table.ajax.reload(); } });
        });
    });
})(jQuery);
