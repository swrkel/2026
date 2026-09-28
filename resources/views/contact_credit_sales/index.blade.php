@extends('layouts.app')
@section('title', __('contact_credit_sales.credit_sales'))

@section('content')

    <section class="content">
        <div class="page-title-area">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <div class="breadcrumbs-area clearfix">
                        <h4 class="page-title pull-left">{{ __('contact_credit_sales.credit_sales') }}</h4>
                        <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                            <li><span>{{ __('contact_credit_sales.list_daily_sales') }}</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="settlement_tabs">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item active">
                            <a class="nav-link active" data-toggle="tab" href="#all_sales_tab" role="tab">
                                {{ __('contact_credit_sales.list_all_sales') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#card_sales_tab" role="tab">
                                {{ __('contact_credit_sales.card_sales') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#cash_sales_tab" role="tab">
                                {{ __('contact_credit_sales.cash_sales') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#credit_sales_tab" role="tab">
                                {{ __('contact_credit_sales.credit_sales') }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="tab-content">
            <div class="tab-pane active" id="all_sales_tab" role="tabpanel">
                @include('contact_credit_sales.partials.all_sales')
            </div>
            <div class="tab-pane" id="card_sales_tab" role="tabpanel">
                @include('contact_credit_sales.partials.card_sales')
            </div>
            <div class="tab-pane" id="cash_sales_tab" role="tabpanel">
                @include('contact_credit_sales.partials.cash_sales')
            </div>
            <div class="tab-pane" id="credit_sales_tab" role="tabpanel">
                @include('contact_credit_sales.partials.credit_sales')
            </div>
        </div>
    </section>

@endsection

@section('javascript')
    <script>
        $(document).ready(function () {
            // Credit Sales Table
            all_sales_table = $('#all_sales_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [[0, 'desc']],
                ajax: {
                    url: "{{ action('ContactCreditSales@index') }}",
                    data: function (d) {
                        d.report_type = 'all';

                        if ($('#date_filter').val()) {
                            var start = $('#date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                            var end = $('#date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                        }
                        d.invoice_no = $('#invoice_no').val();
                        d.customer_id = $('#customer_id').val();
                        d.location_id = $('#location').val();
                    }
                },
                columns: [
                    {
                        data: 'transaction_date',
                        name: 'transaction_date'
                    },
                    {
                        data: 'customer_name',
                        name: 'customer_name'
                    },
                    {
                        data: 'invoice_no',
                        name: 'invoice_no'
                    },
                    {
                        data: 'payment_status',
                        name: 'payment_status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'payment_method',
                        name: 'payment_method',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'amount',
                        name: 'amount'
                    },
                ],
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'csv',
                        text: '<i class="fa fa-file"></i> Export to CSV',
                        className: 'btn btn-default btn-sm',
                        title: 'Credit Sales Report',
                        exportOptions: {
                            columns: ':visible:not(.notexport)'
                        },
                    },
                    {
                        extend: 'excel',
                        text: '<i class="fa fa-file-excel-o"></i> Export to Excel',
                        className: 'btn btn-default btn-sm',
                        title: 'Credit Sales Report',
                        exportOptions: {
                            columns: ':visible:not(.notexport)'
                        },
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fa fa-file-pdf-o"></i> Export to PDF',
                        className: 'btn btn-default btn-sm',
                        title: 'Credit Sales Report',
                        exportOptions: {
                            columns: ':visible:not(.notexport)'
                        },
                    },
                    {
                        extend: 'print',
                        text: '<i class="fa fa-print"></i> Print',
                        className: 'btn btn-default btn-sm',
                        title: 'Credit Sales Report',
                        exportOptions: {
                            columns: ':visible:not(.notexport)'
                        },
                        customize: function (win) {
                            $(win.document.body).find('h1').css('text-align', 'center');
                            $(win.document.body).find('h1').css('font-size', '25px');
                        },
                    },
                    {
                        extend: 'colvis',
                        text: '<i class="fa fa-columns"></i> Column Visibility',
                        className: 'btn btn-default btn-sm'
                    }
                ],
                fnDrawCallback: function (oSettings) {
                    var total = sum_table_col($('#all_sales_table'), 'final-total');
                    $('#all_total').text(__number_f(total));
                    __currency_convert_recursively($('#all_sales_table'));
                },
            });

// Card Sales Table
            card_sales_table = $('#card_sales_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [[0, 'desc']],
                ajax: {
                    url: "{{ action('ContactCreditSales@index') }}",
                    data: function (d) {
                        d.report_type = 'card';

                        if ($('#card_date_filter').val()) {
                            d.start_date = $('#card_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                            d.end_date = $('#card_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
                        }
                        d.location_id = $('#card_location').val();
                        d.invoice_no = $('#card_invoice_no').val();
                        d.card_type = $('#card_type').val();
                        d.slip_no = $('#slip_no').val();
                    }
                },
                columns: [
                    {
                        data: 'transaction_date',
                        name: 'transactions.transaction_date'
                    },
                    {
                        data: 'location_name',
                        name: 'bl.name',
                        defaultContent: 'N/A'
                    },
                    {
                        data: 'payment_method',
                        name: 'transaction_payments.method'
                    },
                    {
                        data: 'invoice_no',
                        name: 'transactions.invoice_no'
                    },
                    {
                        data: 'card_type',
                        name: 'card_accounts.name',
                        defaultContent: 'N/A'
                    },
                    {
                        data: 'slip_no',
                        name: 'transaction_payments.card_number',
                        render: function (data, type, row) {
                            return data && data !== '' ? data : 'N/A';
                        }
                    },
                    {
                        data: 'amount',
                        name: 'transaction_payments.amount'
                    }
                ],
                fnDrawCallback: function () {
                    var total = sum_table_col($('#card_sales_table'), 'final-total');
                    $('#card_total').text(__number_f(total));
                    __currency_convert_recursively($('#card_sales_table'));
                },
            });

            //cash sales table
            cash_sales_table = $('#cash_sales_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [[0, 'desc']],
                ajax: {
                    url: "{{ action('ContactCreditSales@index') }}",
                    data: function (d) {
                        d.report_type = 'cash';

                        if ($('#cash_date_filter').val()) {
                            d.start_date = $('#cash_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                            d.end_date = $('#cash_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
                        }
                        d.location_id = $('#cash_location').val();
                        d.invoice_no = $('#cash_invoice_no').val();
                    }
                },
                columns: [
                    {
                        data: 'transaction_date',
                        name: 'transactions.transaction_date'
                    },
                    {
                        data: 'location_name',
                        name: 'bl.name',
                        defaultContent: 'N/A'
                    },
                    {
                        data: 'invoice_no',
                        name: 'transactions.invoice_no'
                    },
                    {
                        data: 'amount',
                        name: 'transaction_payments.amount'
                    }
                ],
                fnDrawCallback: function () {
                    var total = sum_table_col($('#cash_sales_table'), 'final-total');
                    $('#cash_total').text(__number_f(total));
                    __currency_convert_recursively($('#cash_sales_table'));
                },
            });

            credit_sales_table = $('#credit_sales_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [[0, 'desc']],
                ajax: {
                    url: "{{ action('ContactCreditSales@index') }}",
                    data: function (d) {
                        d.report_type = 'credit';

                        if ($('#credit_date_filter').val()) {
                            d.start_date = $('#credit_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                            d.end_date = $('#credit_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
                        }
                        d.location_id = $('#credit_location').val();
                        d.invoice_no = $('#credit_invoice_no').val();
                    }
                },
                columns: [
                    {
                        data: 'transaction_date',
                        name: 'transactions.transaction_date'
                    },
                    {
                        data: 'location_name',
                        name: 'bl.name',
                        defaultContent: 'N/A'
                    },
                    {
                        data: 'invoice_no',
                        name: 'transactions.invoice_no'
                    },
                    {
                        data: 'amount',
                        name: 'transaction_payments.amount'
                    }
                ],
                fnDrawCallback: function () {
                    var total = sum_table_col($('#credit_sales_table'), 'final-total');
                    $('#credit_total').text(__number_f(total));
                    __currency_convert_recursively($('#credit_sales_table'));
                }
            });

            // Initialize Date Range Pickers using system settings
            if ($('#credit_date_filter').length == 1) {
                $('#credit_date_filter').daterangepicker(dateRangeSettings, function (start, end) {
                    $('#credit_date_filter').val(
                        start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                    );
                    credit_sales_table.ajax.reload();
                });

                $('#credit_date_filter').on('cancel.daterangepicker', function (ev, picker) {
                    $('#credit_date_filter').val('');
                    credit_sales_table.ajax.reload();
                });

                // Set default date range to current month
                $('#credit_date_filter')
                    .data('daterangepicker')
                    .setStartDate(moment().startOf('month'));
                $('#credit_date_filter')
                    .data('daterangepicker')
                    .setEndDate(moment().endOf('month'));

                credit_sales_table.ajax.reload();
            }

            if ($('#card_date_filter').length == 1) {
                $('#card_date_filter').daterangepicker(dateRangeSettings, function (start, end) {
                    $('#card_date_filter').val(
                        start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                    );
                    card_sales_table.ajax.reload();
                });

                $('#card_date_filter').on('cancel.daterangepicker', function (ev, picker) {
                    $('#card_date_filter').val('');
                    card_sales_table.ajax.reload();
                });

                // Set default date range to current month
                $('#card_date_filter')
                    .data('daterangepicker')
                    .setStartDate(moment().startOf('month'));
                $('#card_date_filter')
                    .data('daterangepicker')
                    .setEndDate(moment().endOf('month'));

                card_sales_table.ajax.reload();
            }

            if ($('#cash_date_filter').length == 1) {
                $('#cash_date_filter').daterangepicker(dateRangeSettings, function (start, end) {
                    $('#cash_date_filter').val(
                        start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                    );
                    cash_sales_table.ajax.reload();
                });

                $('#cash_date_filter').on('cancel.daterangepicker', function (ev, picker) {
                    $('#cash_date_filter').val('');
                    cash_sales_table.ajax.reload();
                });

                // Set default date range to current month
                $('#cash_date_filter')
                    .data('daterangepicker')
                    .setStartDate(moment().startOf('month'));
                $('#cash_date_filter')
                    .data('daterangepicker')
                    .setEndDate(moment().endOf('month'));

                cash_sales_table.ajax.reload();
            }

            if ($('#date_filter').length == 1) {
                $('#date_filter').daterangepicker(dateRangeSettings, function (start, end) {
                    $('#date_filter').val(
                        start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                    );
                    all_sales_table.ajax.reload();
                });

                $('#date_filter').on('cancel.daterangepicker', function (ev, picker) {
                    $('#all_date_filter').val('');
                    all_sales_table.ajax.reload();
                });

                // Set default date range to current month
                $('#date_filter')
                    .data('daterangepicker')
                    .setStartDate(moment().startOf('month'));
                $('#date_filter')
                    .data('daterangepicker')
                    .setEndDate(moment().endOf('month'));

                all_sales_table.ajax.reload();
            }

            // Filter change events
            $(document).on('change', '#date_filter, #customer_id, #invoice_no, #location', function () {
                all_sales_table.ajax.reload();
            });

            $(document).on('change', '#card_date_filter, #card_location, #card_invoice_no, #card_type, #slip_no', function () {
                card_sales_table.ajax.reload();
            });

            $(document).on('change', '#cash_date_filter, #cash_location, #cash_invoice_no', function () {
                cash_sales_table.ajax.reload();
            });

            $(document).on('change', '#credit_date_filter, #credit_location, #credit_invoice_no', function () {
                credit_sales_table.ajax.reload();
            });

            // Invoice number change handler
            $(document).on('change', '#invoice_no', function () {
                const selectedInvoice = $(this).val();
                if (selectedInvoice) {
                    $('.date-filter-wrapper').slideDown(200);
                    $('#date_filter').prop('disabled', false);
                } else {
                    $('.date-filter-wrapper').slideUp(200);
                    $('#date_filter').val('').prop('disabled', true);
                }
                credit_sales_table.ajax.reload();
            });

            // Initialize with date filter disabled
            $('.date-filter-wrapper').hide();
        });
    </script>
@endsection
