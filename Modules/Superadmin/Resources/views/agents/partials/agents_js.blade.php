<script>
    var body = document.getElementsByTagName("body")[0];
    if (body) {
        body.className += " sidebar-collapse";
    }

    $('.select2').select2({ 
        width: '100%',
        minimumResultsForSearch: 1 
    });

    $('#date_range_tab').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#date_range_tab').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            agents_table.ajax.reload();
        }
    );
    $('#date_range_tab').on('cancel.daterangepicker', function(ev, picker) {
        $('#date_range_tab').val('');
        agents_table.ajax.reload();
    });

    $('#agent_dashboard_date_range').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#agent_dashboard_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
        }
    );
    $('#agent_dashboard_date_range').on('cancel.daterangepicker', function(ev, picker) {
        $('#agent_dashboard_date_range').val('');
    });

    var agents_cols = [
        { data: 'date', name: 'date' },
        { data: 'referral_code', name: 'referral_code' },
        { data: 'name', name: 'name' },
        { data: 'mobile_number', name: 'mobile_number' },
        { data: 'email', name: 'email' },
        { data: 'referral_group', name: 'referral_group' },
        { data: 'total_orders', name: 'total_orders' },
        { data: 'active_subscription', name: 'active_subscription' },
        { data: 'income', name: 'income' },
        { data: 'paid', name: 'paid' },
        { data: 'due', name: 'due' },
        { data: 'action', name: 'action' },
    ];

    var agents_table = $('#agents_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{action("\Modules\Superadmin\Http\Controllers\AgentController@index")}}',
            data: function (d) {
                if($('#date_range_tab').val()) {
                    d.start_date = $('#date_range_tab').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    d.end_date = $('#date_range_tab').data('daterangepicker').endDate.format('YYYY-MM-DD');
                }
                d.agent_name = $('#agent_name').val();
                d.country_id = $('#country_id').val();
                d.city = $('#city').val();
                d.mobile_number = $('#mobile_number').val();
                d.username = $('#username').val();
                d.nic_number = $('#nic_number').val();
                d.referral_code = $('#referral_code').val();
                d.added_by = $('#added_by').val();
            }
        },
        columns: agents_cols,
        buttons: [
            {
                extend: 'csv',
                text: '<i class="fa fa-file-csv"></i> Export to CSV',
                className: 'btn btn-default btn-sm',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'excel',
                text: '<i class="fa fa-file-excel"></i> Export to Excel',
                className: 'btn btn-default btn-sm',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'colvis',
                text: '<i class="fa fa-columns"></i> Column Visibility',
                className: 'btn btn-default btn-sm'
            },
            {
                extend: 'pdf',
                text: '<i class="fa fa-file-pdf"></i> Export to PDF',
                className: 'btn btn-default btn-sm',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'print',
                text: '<i class="fa fa-print"></i> Print',
                className: 'btn btn-default btn-sm',
                exportOptions: {
                    columns: ':visible'
                }
            }
        ],
        dom: 'Bfrtip',
        fnDrawCallback: function(oSettings) {
        
        },
    });

    $('#date_range_tab').change(function() {
        agents_table.ajax.reload();
    });

    $('#agent_name, #country_id, #city, #mobile_number, #username, #nic_number, #referral_code, #added_by').change(function() {
        agents_table.ajax.reload();
    });

    $(document).on('click', '.delete_agent', function (e) {
        e.preventDefault();
        swal({
            title: LANG.sure,
            text: 'This agent will be deleted',
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then((willDelete) => {
            if (willDelete) {
                var href = $(this).data('href');
                var data = $(this).serialize();
                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    data: data,
                    success: function (result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                        } else {
                            toastr.error(result.msg);
                        }
                        agents_table.ajax.reload();
                    },
                });
            }
        });
    });

    $(document).on('click', 'a.edit_entity', function(e) {
        e.preventDefault();
        $('div.view_modal').load($(this).attr('href'), function() {
            $(this).modal('show');
        });
    });
</script>
