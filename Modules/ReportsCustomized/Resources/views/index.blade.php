@extends('layouts.app')
@section('title', __('Report Customized'))
<style>
        /* Basic styling to match client red table look */
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .business-header {
            flex: 1;
            text-align: center;
        }

        .business-header h2 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .invoice-header-right {
            text-align: right;
        }

        .invoice-title {
            color: #0b77d1;
            font-weight: 700;
            font-size: 28px;
            display: block;
            margin-bottom: 8px;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .customer-block {
            flex: 1;
            min-width: 300px;
        }

        .invoice-block {
            flex: 1;
            min-width: 300px;
            text-align: right;
        }

        .invoice-block .form-field-row {
            justify-content: flex-end;
        }

        .form-field-row {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            gap: 0;
        }

        .form-field-row label {
            min-width: 140px;
            margin: 0;
            margin-right: 0;
            font-weight: 700;
        }

        .invoice-block .form-field-row {
            justify-content: flex-end;
        }

        .invoice-block .form-field-row label {
            min-width: auto;
            margin-right: 4px;
            white-space: nowrap;
            font-weight: 700;
        }

        .form-field-row .value {
            flex: 0 0 250px;
            max-width: 250px;
        }

        .invoice-block .form-field-row .value {
            flex: 0 0 auto;
            max-width: none;
            min-width: auto;
            margin-left: 0;
        }

        /* red-bordered invoice grid */
        .invoice-table {
            border-collapse: collapse;
            width: 100%;
            border-top: 2px solid #d22;
        }

        .invoice-table th,
        .invoice-table td {
            border: 2px solid #d22;
            padding: 8px;
            vertical-align: middle;
        }

        .invoice-table thead th {
            background: #fff;
            font-weight: 700;
            color: #900;
            border-top: 2px solid #d22;
            text-align: center;
        }

        .invoice-table tbody td {
            height: 34px;
        }

        .invoice-table tfoot td {
            height: 34px;
            font-weight: 700;
            color: #900;
        }

        .invoice-table tfoot td.invoice-amount {
            border: 2px solid #d22 !important;
        }

        /* LIOC: Description is the last column — must stay visible (qty text lives here, not a separate column) */
        #sales-table .lioc-col-description {
            min-width: 260px;
            width: 36%;
            max-width: 50%;
            text-align: left !important;
            vertical-align: middle;
            white-space: normal;
            word-break: break-word;
        }

        #sales-table thead .lioc-col-description {
            border: 2px solid #d22;
        }

        #sales-table tfoot .lioc-col-description {
            border: 2px solid #d22 !important;
            padding: 8px !important;
            width: auto;
            max-width: none;
        }

        .invoice-index {
            width: 70px;
            text-align: center;
        }

        .invoice-qty {
            width: 100px;
            text-align: center;
        }

        .invoice-product {
            width: 35%;
        }

        .invoice-unitprice,
        .invoice-amount,
        .invoice-disc {
            width: 140px;
            text-align: right;
        }

        .payment-details {
            margin-top: 40px;
        }

        .btn-back {
            margin-top: 20px;
        }
    </style>
@section('content')
<!-- Main content -->
<section class="content">

    <div class="row">
        <div class="col-md-12">
            <div class="settlement_tabs">
               {{-- Modified by Engr. Alex -- task 7882: Issue 4 - rename tabs; Issue 5 - add List LIOC Statements tab; Issue 3 - rename last tab to Prefix & Numbers --}}
               <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#stock_report">Add LIOC Statement</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#list_lioc_statements">List LIOC Statements</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#stock_pumpers">Prefix &amp; Numbers</a>
                        </li>
                    </ul>
                {{-- Modified by Engr. Alex -- task 7882: fix – removed standalone date filter that was duplicating filter on List LIOC Statements tab --}}
                {{-- Modified by Engr. Alex -- task 7882: Issue 4 - fix duplicate tab-content; Issue 5 - add List LIOC Statements pane; Issue 3 - Prefix & Numbers pane --}}
                <div class="tab-content">
                    <div class="tab-pane active" id="stock_report">
                        @include('reportscustomized::statement.show')
                    </div>
                    <div class="tab-pane" id="list_lioc_statements">
                        @include('reportscustomized::partials.list_lioc_statements')
                    </div>
                    <div class="tab-pane" id="stock_pumpers">
                        @include('reportscustomized::settings.show')
                    </div>
                </div>

     <div class="modal fade settings_modal" role="dialog" aria-labelledby="gridSystemModalLabel"><div class="modal-dialog"><div class="modal-content"></div></div></div>
     <div class="modal fade report_model" role="dialog" aria-labelledby="gridSystemModalLabel"><div class="modal-dialog"><div class="modal-content"></div></div></div>

</section>
<!-- /.content -->

@endsection
 
@section('javascript')
 
<script>
   
 $(document).ready(function() {
    $('#myTab a').on('click', function (e) {
    e.preventDefault();
    $(this).tab('show');
});
 $('button[data-href]').on('click', function() {
    var url = $(this).data('href');
    var container = $(this).data('container');
    console.log(url);
    $.get(url, function(data) {
        $(container).html(data).modal('show');
    });
});
});
  
 $(document).ready(function() {
    var initStart = moment('{{ $start_date }}', 'YYYY-MM-DD');
    var initEnd = moment('{{ $end_date }}', 'YYYY-MM-DD');
    if (!initStart.isValid() || !initEnd.isValid()) {
        initStart = moment().startOf('month');
        initEnd = moment().endOf('month');
    }

    var drpOpts = $.extend(true, {}, dateRangeSettings, {
        startDate: initStart,
        endDate: initEnd
    });

    $('#pumper_details_date_range').daterangepicker(
        drpOpts,
        function(start, end) {
            $('#pumper_details_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            $("#report_date_range").text("Date Range: " + $('#pumper_details_date_range').val());
            var periodText = start.format('DDMMYYYY') + ' To ' + end.format('DDMMYYYY');
            $('#period-display').text(periodText);
            loadSalesTable(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
        }
    );

    $('#pumper_details_date_range').val(
        initStart.format(moment_date_format) + ' ~ ' + initEnd.format(moment_date_format)
    );
    $("#report_date_range").text("Date Range: " + $('#pumper_details_date_range').val());
    $('#period-display').text(initStart.format('DDMMYYYY') + ' To ' + initEnd.format('DDMMYYYY'));
    loadSalesTable(initStart.format('YYYY-MM-DD'), initEnd.format('YYYY-MM-DD'));

    // Handle custom date modal apply button
    $('#custom_date_apply_button').on('click', function() {
        let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + $('#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + $('#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $('#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
        let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $('#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $('#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $('#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

        if (startDate.length === 10 && endDate.length === 10) {
            // Format for display
            let formattedStartDate = moment(startDate).format(moment_date_format);
            let formattedEndDate = moment(endDate).format(moment_date_format);

            // Update the date picker input
            $('#pumper_details_date_range').val(formattedStartDate + ' ~ ' + formattedEndDate);
            $("#report_date_range").text("Date Range: " + $('#pumper_details_date_range').val());

            // Update period display
            var periodText = moment(startDate).format('DDMMYYYY') + ' To ' + moment(endDate).format('DDMMYYYY');
            $('#period-display').text(periodText);

            // Sync the daterangepicker internal dates
            $('#pumper_details_date_range').data('daterangepicker').setStartDate(moment(startDate));
            $('#pumper_details_date_range').data('daterangepicker').setEndDate(moment(endDate));

            // Load table data via AJAX
            loadSalesTable(startDate, endDate);

            // Hide custom modal
            $('.custom_date_typing_modal').modal('hide');
        } else {
            alert("Please select both start and end dates.");
        }
    });

    // Show custom date modal when "Custom Date Range" is chosen
    $('#pumper_details_date_range').on('apply.daterangepicker', function(ev, picker) {
        if (picker.chosenLabel === 'Custom Date Range') {
            $('.custom_date_typing_modal').modal('show');
        }
    });

    // Handle cancel: clear the date picker and reset period
    $('#pumper_details_date_range').on('cancel.daterangepicker', function(ev, picker) {
        $('#pumper_details_date_range').val('');
        $("#report_date_range").text("Date Range: —");
        $('#period-display').text('Select a date range');
        $('#sales-table-tbody').empty();
        $('#total-amount-cell').text('');
        $('#lioc-tfoot-order-date').text('');
    });

    function loadSalesTable(startDate, endDate) {
        $.ajax({
            url: '{{ route("reportscustomized.index") }}',
            type: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate
            },
            success: function(response) {
                $('#sales-table-tbody').html(response.html);
                $('#total-amount-cell').text(response.total_amount_formatted || response.total_amount);
                if (response.period_display) {
                    $('#period-display').text(response.period_display);
                }
                if (typeof response.last_order_date !== 'undefined') {
                    $('#lioc-tfoot-order-date').text(response.last_order_date);
                }
            },
            error: function(xhr) {
                console.error('Failed to load sales data', xhr);
            }
        });
    }

    function liocClearForNextStatement() {
        $('#pumper_details_date_range').val('');
        $("#report_date_range").text("Date Range: —");
        $('#period-display').text('Select a date range');
        $('#sales-table-tbody').empty();
        $('#total-amount-cell').text('');
        $('#lioc-tfoot-order-date').text('');
    }

    function liocSaveStatement(doPrint) {
        var drp = $('#pumper_details_date_range').data('daterangepicker');
        if (!drp || !$('#pumper_details_date_range').val()) {
            alert(@json(__('Please select a date range first.')));
            return;
        }
        var startDate = drp.startDate.format('YYYY-MM-DD');
        var endDate = drp.endDate.format('YYYY-MM-DD');
        $.ajax({
            url: '{{ route("reportscustomized.save-statement") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                start_date: startDate,
                end_date: endDate
            },
            success: function(res) {
                if (!res.success) {
                    alert(res.message || 'Save failed');
                    return;
                }
                if (res.bill_ref_header) {
                    $('#lioc-bill-ref-text').text(res.bill_ref_header);
                }
                if (res.tfoot_vehicle) {
                    $('#lioc-tfoot-vehicle').text(res.tfoot_vehicle);
                }
                if (res.tfoot_description) {
                    $('#lioc-tfoot-description').text(res.tfoot_description);
                }
                if (typeof toastr !== 'undefined') {
                    toastr.success(res.message);
                } else {
                    alert(res.message);
                }
                if (typeof window.reloadLiocSavedList === 'function') {
                    window.reloadLiocSavedList();
                }
                if (doPrint) {
                    window.print();
                    setTimeout(liocClearForNextStatement, 600);
                } else {
                    liocClearForNextStatement();
                }
            },
            error: function(xhr) {
                var msg = 'Save failed';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.errors) {
                        var e = xhr.responseJSON.errors;
                        var k = Object.keys(e)[0];
                        if (k && e[k] && e[k][0]) {
                            msg = e[k][0];
                        }
                    }
                }
                alert(msg);
            }
        });
    }

    $('#lioc_save_statement').on('click', function() {
        liocSaveStatement(false);
    });
    $('#lioc_save_and_print_statement').on('click', function() {
        liocSaveStatement(true);
    });

  // Modified by Engr. Alex -- task 7882: Issue 3 - update DataTable columns to match Prefix & Numbers tab (7 columns)
    lioc_customized_report_table = $('#lioc_customized_report_table').DataTable({
    processing: true,
    serverSide: true,
    ajax: '{{ route("reportscustomizedsettings.index") }}',
    columnDefs: [{ targets: 0, orderable: false, searchable: false }],
    columns: [
        { data: 'action', name: 'action' },
        { data: 'updated_at', name: 'updated_at' },
        { data: 'prefix', name: 'prefix' },
        { data: 'start_number', name: 'start_number' },
        { data: 'constant_value', name: 'constant_value' },
        { data: 'description_constant_details', name: 'description_constant_details', defaultContent: '' },
        { data: 'added_user', name: 'added_user', orderable: false, defaultContent: '' }
    ],
    "fnDrawCallback": function (oSettings) {}
});

// Modified by Engr. Alex -- task 7882: Issue 3 - fix duplicate edit form by replacing modal content not modal-body
$(document).on('click', '.edit_btn', function(e) {
    e.preventDefault();
    var url = $(this).data('href');
    var container = $(this).data('container');

    $.ajax({
        url: url,
        type: 'GET',
        success: function(response) {
            $(container).html(response).modal('show');
        },
        error: function(xhr) {
            alert('Error: ' + xhr.responseText);
        }
    });
});


$(document).on('click', '.province_delete', function(e) {
    e.preventDefault();
    var url = $(this).data('href');

   
        $.ajax({
            url: url,
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'   // Laravel CSRF token
            },
            success: function(response) {
                if (response.success) {
                    // Refresh the DataTable
                    $('#lioc_customized_report_table').DataTable().ajax.reload();
                    // Optional: show a success message
                    //toastr.success(response.message);
                }
            },
            error: function(xhr) {
                // Show error message
                alert('Delete failed: ' + (xhr.responseJSON.message || 'Unknown error'));
            }
        });
  
});
});
</script>
@endsection