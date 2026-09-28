$(function () {
    if ($('#bs_reception_queue_table').length) {
        $('#bs_reception_queue_table').DataTable({
            processing: true,
            serverSide: false,
            ajax: window.location.href,
            scrollX: true,
            columns: [
                {data: 'queue_no'},
                {data: 'customer_name'},
                {data: 'mobile'},
                {data: 'visit_type'},
                {data: 'priority'},
                {data: 'status'},
                {data: 'arrival_at'},
                {data: null, orderable: false, searchable: false, render: function (row) {
                    var html = '<div class="btn-group"><button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">Action <span class="caret"></span></button><ul class="dropdown-menu dropdown-menu-right">';
                    html += '<li><a href="#" class="bs-checkin" data-id="'+ row.id +'">Check In</a></li>';
                    html += '<li><a href="#" class="bs-start-service" data-id="'+ row.id +'">Start Service</a></li>';
                    html += '<li><a href="#" class="bs-complete" data-id="'+ row.id +'">Complete</a></li>';
                    html += '</ul></div>';
                    return html;
                }}
            ]
        });
    }
});
