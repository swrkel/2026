/* PUR-005 Product Purchase Report */
$(function () {
    function purchaseModuleUrl(path) {
        var base = (window.PurchaseModuleBaseUrl || '/purchase').replace(/\/$/, '');
        return base + '/' + String(path || '').replace(/^\//, '');
    }

    if (! $('#product_purchase_table').length) return;

    $('#product_purchase_table').DataTable({
        processing: true,
        ajax: {
            url: purchaseModuleUrl('reports/product-purchase/data'),
            dataSrc: 'data',
            data: function (d) {
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
                d.location_id = $('#location_id').val();
            }
        },
        columns: [
            {data: 'transaction_date'},
            {data: 'ref_no'},
            {data: 'supplier_name', defaultContent: ''},
            {data: 'status'},
            {data: 'payment_status'},
            {data: 'final_total', className: 'text-right'}
        ]
    });

    $(document).on('change', '.purchase-report-filter', function () {
        $('#product_purchase_table').DataTable().ajax.reload();
    });
});
