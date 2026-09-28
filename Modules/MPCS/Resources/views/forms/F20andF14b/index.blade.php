@extends('layouts.app')
@section('title', __('mpcs::lang.F20andF14b_form'))

@section('content')
    <!-- Main content -->
    <section class="content">

        <div class="row">
            <div class="col-md-12">
                <div class="settlement_tabs">
                    <ul class="nav nav-tabs">
                        @if (auth()->user()->can('f20_form'))
                            <li class="active">
                                <a href="#20_form" class="20_form" data-toggle="tab">
                                    <i class="fa fa-file-text-o"></i> <strong>@lang('mpcs::lang.f20_form')</strong>
                                </a>
                            </li>
                        @endif
                    </ul>
                    <div class="tab-content">

                        @if (auth()->user()->can('f20_form'))
                            <div class="tab-pane active in" id="20_form">
                                @include('mpcs::forms.F20andF14b.partials.20_form')
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>

    </section>
    <!-- /.content -->

@endsection
@section('javascript')
    <script>
        $(document).ready(function() {
            // Global variables
            let form_20_table;

            // -----------------------------
            // Form 14b
            // -----------------------------
            if ($('#f14b_date').length === 1) {
                $('#f14b_date').daterangepicker(dateRangeSettings, function(start, end) {
                    $('#f14b_date').val(
                        start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
                    );
                });

                $('#f14b_date').on('cancel.daterangepicker', function() {
                    $('#f14b_date').val('');
                });

                let drp14b = $('#f14b_date').data('daterangepicker');
                if (drp14b) {
                    drp14b.setStartDate(moment().startOf('month'));
                    drp14b.setEndDate(moment().endOf('month'));
                    // Update the input field with the selected range
                    $('#f14b_date').val(
                        drp14b.startDate.format(moment_date_format) + ' - ' + drp14b.endDate.format(
                            moment_date_format)
                    );
                }

                // Load initial data
                getForm14b();
                $('#f14b_date, #f14b_location_id').change(getForm14b);
            }

            function getForm14b() {
                let date_range = $('#f14b_date').val();
                let location_id = $('#f14b_location_id').val();

                let start_date = '';
                let end_date = '';

                if (date_range) {
                    let dates = date_range.split(' - ');
                    if (dates.length === 2) {
                        start_date = moment(dates[0], moment_date_format).format('YYYY-MM-DD');
                        end_date = moment(dates[1], moment_date_format).format('YYYY-MM-DD');
                    }
                }

                $.ajax({
                    method: 'get',
                    url: '/mpcs/get-form-14b',
                    data: {
                        start_date: start_date,
                        end_date: end_date,
                        location_id: location_id
                    },
                    contentType: 'html',
                    success: function(result) {
                        $('#form14B_content').empty().append(result)
                    },
                });
            }

            // -----------------------------
            // Form 20 - Fixed Version
            // -----------------------------
            if ($('#form_20_date_range').length === 1) {
                // Initialize date range picker with proper settings
                $('#form_20_date_range').daterangepicker({
                    ...dateRangeSettings,
                    autoUpdateInput: true, // This ensures the input is updated with selected range
                    startDate: moment().startOf('month'),
                    endDate: moment().endOf('month')
                }, function(start, end, label) {
                    // This callback fires when dates are selected
                    let startFormatted = start.format(moment_date_format);
                    let endFormatted = end.format(moment_date_format);
                    let dateRangeString = startFormatted + ' - ' + endFormatted;

                    // Update the input field
                    $('#form_20_date_range').val(dateRangeString);

                    // Update display elements
                    $('.from_date').text(startFormatted);
                    $('.to_date').text(endFormatted);
                    $("#report_date_range").text("Date Range: " + dateRangeString);

                    // Reload table data
                    if (typeof form_20_table !== 'undefined') {
                        form_20_table.ajax.reload();
                    }
                });

                // Set initial value
                let initialStart = moment().startOf('month').format(moment_date_format);
                let initialEnd = moment().endOf('month').format(moment_date_format);
                $('#form_20_date_range').val(initialStart + ' - ' + initialEnd);

                // Update display with initial values
                $('.from_date').text(initialStart);
                $('.to_date').text(initialEnd);
                $("#report_date_range").text("Date Range: " + initialStart + ' - ' + initialEnd);

                // Handle cancel event
                $('#form_20_date_range').on('cancel.daterangepicker', function(ev, picker) {
                    $(this).val('');
                    $('.from_date').text('');
                    $('.to_date').text('');
                    $("#report_date_range").text("Date Range: Not Selected");
                    if (typeof form_20_table !== 'undefined') {
                        form_20_table.ajax.reload();
                    }
                });

                // Custom date range modal handling (if needed)
                $('#custom_date_apply_button').on('click', function() {
                    let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2')
                        .val() +
                        $('#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" +
                        $('#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" +
                        $('#custom_date_from_date1').val() + $('#custom_date_from_date2').val();

                    let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() +
                        $('#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" +
                        $('#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" +
                        $('#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

                    if (startDate.length === 10 && endDate.length === 10) {
                        let formattedStartDate = moment(startDate).format(moment_date_format);
                        let formattedEndDate = moment(endDate).format(moment_date_format);
                        let fullRange = formattedStartDate + ' - ' + formattedEndDate;

                        // Update the date range picker
                        $('#form_20_date_range').val(fullRange);

                        let drp = $('#form_20_date_range').data('daterangepicker');
                        if (drp) {
                            drp.setStartDate(moment(startDate));
                            drp.setEndDate(moment(endDate));
                        }

                        // Update display
                        $('.from_date').text(formattedStartDate);
                        $('.to_date').text(formattedEndDate);
                        $("#report_date_range").text("Date Range: " + fullRange);

                        // Reload table
                        if (typeof form_20_table !== 'undefined') {
                            form_20_table.ajax.reload();
                        }

                        $('.custom_date_typing_modal').modal('hide');
                    } else {
                        alert("Please select both start and end dates.");
                    }
                });
            }

            // Default selects
            $('#f14b_location_id option:eq(1)').prop('selected', true);
            $('#20_location_id option:eq(1)').prop('selected', true);

            // -----------------------------
            // Form 20 DataTable - Fixed Version
            // -----------------------------
            if ($('#form_20_table').length) {
                form_20_table = $('#form_20_table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '/mpcs/get-form-20',
                        data: function(d) {
                            // Get date range from input field
                            let date_range = $('#form_20_date_range').val();

                            if (date_range) {
                                let dates = date_range.split(' - ');
                                if (dates.length === 2) {
                                    d.start_date = moment(dates[0], moment_date_format).format(
                                        'YYYY-MM-DD');
                                    d.end_date = moment(dates[1], moment_date_format).format(
                                        'YYYY-MM-DD');
                                }
                            } else {
                                d.start_date = '';
                                d.end_date = '';
                            }

                            d.location_id = $('#20_location_id').val();
                        }
                    },
                    columns: [{
                            data: 'DT_Row_Index',
                            name: 'DT_Row_Index',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'sku',
                            name: 'sku'
                        },
                        {
                            data: 'product',
                            name: 'product'
                        },
                        {
                            data: 'sold_qty',
                            name: 'sold_qty',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'unit_price',
                            name: 'unit_price',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'total_amount',
                            name: 'total_amount',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'cash_sale_amount',
                            name: 'cash_sale_amount',
                            visible: false
                        }, // Hidden column for calculation
                        {
                            data: 'credit_sale_amount',
                            name: 'credit_sale_amount',
                            visible: false
                        } // Hidden column for calculation
                    ],
                    footerCallback: function(row, data, start, end, display) {
                        var api = this.api();

                        // Calculate totals from the data
                        var cash_sale = 0;
                        var credit_sale = 0;

                        // Sum up the cash_sale_amount and credit_sale_amount columns
                        data.forEach(function(row) {
                            cash_sale += parseFloat(row.cash_sale_amount) || 0;
                            credit_sale += parseFloat(row.credit_sale_amount) || 0;
                        });

                        var grand_total = cash_sale + credit_sale;

                        // Update footer
                        $('#cash_sale').text(__number_f(cash_sale, false, false, __currency_precision));
                        $('#credit_sale').text(__number_f(credit_sale, false, false,
                            __currency_precision));
                        $('#grand_total').text(__number_f(grand_total, false, false,
                            __currency_precision));
                    },
                    fnDrawCallback: function(oSettings) {
                        // Pagination form number handling
                        let pageInfo = form_20_table.page.info();
                        let pageNumber = pageInfo.page + 1;
                        let formNumber = "{{ $F20_form_sn }}";

                        if (pageInfo.pages > 1) {
                            let newFormNumber = formNumber + '-' + pageNumber;
                            $('#form_no1').text(newFormNumber);
                            $('#F20_form_sn').val(newFormNumber);
                        } else {
                            $('#form_no1').text(formNumber);
                            $('#F20_form_sn').val(formNumber);
                        }

                        // Manually trigger footer calculation
                        var data = form_20_table.rows({
                            page: 'current'
                        }).data();
                        var cash_sale = 0;
                        var credit_sale = 0;

                        for (var i = 0; i < data.length; i++) {
                            cash_sale += parseFloat(data[i].cash_sale_amount) || 0;
                            credit_sale += parseFloat(data[i].credit_sale_amount) || 0;
                        }

                        var grand_total = cash_sale + credit_sale;

                        $('#cash_sale').text(__number_f(cash_sale, false, false, __currency_precision));
                        $('#credit_sale').text(__number_f(credit_sale, false, false,
                            __currency_precision));
                        $('#grand_total').text(__number_f(grand_total, false, false,
                            __currency_precision));
                    },
                });

                // Reload on location change
                $('#20_location_id').change(function() {
                    form_20_table.ajax.reload();
                    if ($('#20_location_id').val()) {
                        $('.f20_location_name').text($('#20_location_id :selected').text());
                    } else {
                        $('.f20_location_name').text('All');
                    }
                });
            }

        });
    </script>
@endsection
