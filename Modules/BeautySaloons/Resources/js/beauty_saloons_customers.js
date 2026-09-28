$(document).ready(function () {
    if ($.fn.datepicker) {
        $('.bs_datepicker').datepicker({ autoclose: true, todayHighlight: true, format: 'yyyy-mm-dd' });
    }

    if ($('#bs_customers_table').length && $.fn.DataTable) {
        $('#bs_customers_table').DataTable({
            processing: true,
            serverSide: true,
            scrollX: true,
            ajax: window.location.href,
            columns: [
                {data: 'action', name: 'action', orderable: false, searchable: false},
                {data: 'customer_code', name: 'customer_code'},
                {data: 'full_name', name: 'full_name'},
                {data: 'mobile', name: 'mobile'},
                {data: 'email', name: 'email'},
                {data: 'customer_type', name: 'customer_type'},
                {data: 'status', name: 'status'},
                {data: 'created_at', name: 'created_at'}
            ]
        });
    }
});
