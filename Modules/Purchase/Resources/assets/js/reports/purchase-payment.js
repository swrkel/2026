/* PUR-005 Purchase Payment Report */
$(function () {
    function purchaseModuleUrl(path) {
        var base = (window.PurchaseModuleBaseUrl || '/purchase').replace(/\/$/, '');
        return base + '/' + String(path || '').replace(/^\//, '');
    }

    if (! $('#purchase_payment_table').length) return;

    $('#purchase_payment_table').DataTable({
        processing: true,
        ajax: {
            url: purchaseModuleUrl('reports/purchase-payment/data'),
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
        $('#purchase_payment_table').DataTable().ajax.reload();
    });
});
