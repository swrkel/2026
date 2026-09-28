$(function () {
    if ($('#stn_freight_settlement_table').length) {
        $('#stn_freight_settlement_table').DataTable({
            processing: true,
            serverSide: false,
            ajax: window.location.href,
            columns: [
                {data: 'invoice_no'},
                {data: 'carrier_name'},
                {data: 'invoice_date'},
                {data: 'amount'},
                {data: 'approved_amount'},
                {data: 'status'}
            ]
        });
    }
});
