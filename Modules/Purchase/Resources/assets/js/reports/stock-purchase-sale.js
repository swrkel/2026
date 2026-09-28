/* PUR-005 Stock Purchase Sale Report */
$(function () {
    function purchaseModuleUrl(path) {
        var base = (window.PurchaseModuleBaseUrl || '/purchase').replace(/\/$/, '');
        return base + '/' + String(path || '').replace(/^\//, '');
    }

    if (! $('#stock_purchase_sale_table').length) return;

    $('#stock_purchase_sale_table').DataTable({
        processing: true,
        ajax: {
            url: purchaseModuleUrl('reports/stock-purchase-sale/data'),
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
        $('#stock_purchase_sale_table').DataTable().ajax.reload();
    });
});
