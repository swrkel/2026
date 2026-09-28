@extends('layouts.app')
@section('title', __('petro::lang.pump_operators'))
<style>
    .disabled {
        pointer-events: none;
        opacity: 0.6;
    }
</style>

<style>
    /*
    |--------------------------------------------------------------------------
    | PetroPD PD Operators - Actions Dropdown Visibility Fix
    |--------------------------------------------------------------------------
    | The DataTables export toolbar can visually cover the row Actions dropdown
    | on smaller screens. Keep dropdown menus above the table toolbar without
    | changing any business logic.
    */
    #pump_operators .btn-group.open,
    #pump_operators .dropdown.open {
        position: relative !important;
        z-index: 100000 !important;
    }

    #pump_operators .dropdown-menu {
        z-index: 100001 !important;
    }

    #pump_operators .dataTables_wrapper,
    #pump_operators #list_pump_operators_table_wrapper,
    #pump_operators .box,
    #pump_operators .box-body {
        overflow: visible !important;
    }

    #pump_operators .dt-buttons,
    #pump_operators .dataTables_length,
    #pump_operators .dataTables_filter {
        position: relative;
        z-index: 1;
    }

    #pump_operators table.dataTable tbody td:first-child {
        overflow: visible !important;
    }
</style>

<style>
    /*
    |--------------------------------------------------------------------------
    | PetroPD PD Operators - Stable floating Action dropdown
    |--------------------------------------------------------------------------
    | Keep the row Action menu attached to the clicked button while allowing the
    | menu to escape DataTables/table-responsive clipping. No action/permission
    | logic is changed.
    */
    body > .petropd-pdoperator-action-menu {
        position: fixed !important;
        z-index: 2147483000 !important;
        display: block !important;
        float: none !important;
        margin: 0 !important;
    }

    body > .petropd-pdoperator-action-menu.petropd-action-scrollable {
        overflow-y: auto !important;
        overflow-x: hidden !important;
    }
</style>

@section('content')


    <section class="content-header main-content-inner">
        <div class="row">
            <div class="col-md-12 dip_tab">
                <div class="settlement_tabs">
                    <ul class="nav nav-tabs">
                        <li class=" @if (empty(session('status.tab'))) active @endif" style="margin-left: 20px;">
                            <a style="font-size:13px;" href="#pump_operators" class="" data-toggle="tab">
                                <i class="fa fa-users"></i> <strong>@lang('petro::lang.pump_operators')</strong>
                            </a>
                        </li>
                        <li class="@if (session('status.tab') == 'pumper_excess_shortage_payments') active @endif">
                            <a style="font-size:13px;" href="#pumper_excess_shortage_payments" data-toggle="tab">
                                <i class="fa fa-minus"></i>
                                <strong>@lang('petro::lang.pumper_excess_shortage_payments')</strong>
                            </a>
                        </li>
                        <li class="@if (session('status.tab') == 'pumper_day_entries') active @endif">
                            <a style="font-size:13px;" href="#pumper_day_entries" data-toggle="tab">
                                <i class="fa fa-calculator"></i>
                                <strong>@lang('petro::lang.pumper_day_entries')</strong>
                            </a>
                        </li>
                        <li class="@if (session('status.tab') == 'shift_summary') active @endif">
                            <a style="font-size:13px;" href="#shift_summary" data-toggle="tab">
                                <i class="fa fa-clock-o"></i>
                                <strong>@lang('petro::lang.shift_summary')</strong>
                            </a>
                        </li>
                        <li class="@if (session('status.tab') == 'payment_summary') active @endif">
                            <a style="font-size:13px;" href="#payment_summary" data-toggle="tab">
                                <i class="fa fa-money"></i>
                                <strong>@lang('petro::lang.payment_summary')</strong>
                            </a>
                        </li>
                        <li class="@if (session('status.tab') == 'meters_with_payments') active @endif">
                            <a style="font-size:13px;" href="#meters_with_payments" data-toggle="tab">
                                <i class="fa fa-money"></i>
                                <strong>@lang('petro::lang.meters_with_payments')</strong>
                            </a>
                        </li>
                        <li class="@if (session('status.tab') == 'daily_pump_status') active @endif">
                            <a style="font-size:13px;" href="#daily_pump_status" data-toggle="tab">
                                <i class="fa fa-calculator"></i>
                                <strong>@lang('petro::lang.daily_pump_status')</strong>
                            </a>
                        </li>
                        <li class="@if (session('status.tab') == 'close_shift') active @endif">
                            <a style="font-size:13px;" href="#close_shift" data-toggle="tab">
                                <i class="fa fa-ban"></i>
                                <strong>@lang('petro::lang.close_shift')</strong>
                            </a>
                        </li>
                        <li class="@if (session('status.tab') == 'current_meter') active @endif">
                            <a style="font-size:13px;" href="#current_meter" data-toggle="tab">
                                <i class="fa fa-thermometer-full"></i>
                                <strong>@lang('petro::lang.current_meter')</strong>
                            </a>
                        </li>
                        <li class="@if (session('status.tab') == 'unload_stock') active @endif">
                            <a style="font-size:13px;" href="#unload_stock" data-toggle="tab">
                                <i class="fa fa-arrow-down"></i>
                                <strong>@lang('petro::lang.unload_stock')</strong>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="tab-content">
            <div class="tab-pane  @if (empty(session('status.tab'))) active @endif" id="pump_operators">
                @if (!empty($message))
                    {!! $message !!}
                @endif
                @include('petropd::pd_operators.partials.pump_operators')
            </div>
            <div class="tab-pane @if (session('status.tab') == 'pumper_excess_shortage_payments') active @endif" id="pumper_excess_shortage_payments">
                @if (!empty($message))
                    {!! $message !!}
                @endif
                @include('petropd::pd_operators.partials.pumper_excess_shortage_payments')
            </div>
            <div class="tab-pane @if (session('status.tab') == 'pumper_day_entries') active @endif" id="pumper_day_entries">
                @if (!empty($message))
                    {!! $message !!}
                @endif
                @include('petropd::pd_operators.partials.pumper_day_entries')
            </div>
            <div class="tab-pane @if (session('status.tab') == 'shift_summary') active @endif" id="shift_summary">
                @if (!empty($message))
                    {!! $message !!}
                @endif
                @include('petropd::pd_operators.partials.shift_summary')
            </div>
            <div class="tab-pane @if (session('status.tab') == 'payment_summary') active @endif" id="payment_summary">
                @if (!empty($message))
                    {!! $message !!}
                @endif
                @include('petropd::pd_operators.partials.payment_summary')
            </div>
            <div class="tab-pane @if (session('status.tab') == 'meters_with_payments') active @endif" id="meters_with_payments">
                @if (!empty($message))
                    {!! $message !!}
                @endif
                @include('petropd::pd_operators.partials.meters_with_payments')
            </div>
            <div class="tab-pane @if (session('status.tab') == 'daily_pump_status') active @endif" id="daily_pump_status">
                @if (!empty($message))
                    {!! $message !!}
                @endif
                @include('petropd::pd_operators.partials.daily_pump_status_admin')
            </div>
            <div class="tab-pane @if (session('status.tab') == 'closing_shift') active @endif" id="close_shift">
                @if (!empty($message))
                    {!! $message !!}
                @endif
                @include('petropd::pd_operators.partials.closing_shift')
            </div>
            <div class="tab-pane @if (session('status.tab') == 'current_meter') active @endif" id="current_meter">
                @if (!empty($message))
                    {!! $message !!}
                @endif
                @include('petropd::pd_operators.partials.current_meter')
            </div>
            <div class="tab-pane @if (session('status.tab') == 'unload_stock') active @endif" id="unload_stock">
                @if (!empty($message))
                    {!! $message !!}
                @endif
                @include('petropd::pd_operators.partials.unload_stock')
            </div>
        </div>

        <div class="modal fade pump_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

        <div class="modal fade pump_operator_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

        <div class="modal fade payment_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

        <div class="modal fade edit_payment_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

    </section>

@endsection
@section('javascript')
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        var body = document.getElementsByTagName("body")[0];
        body.className += " sidebar-collapse";
        $(document).ready(function() {
            if ($('#expense_date_range').length == 1 && $('#expense_date_range').data('daterangepicker')) {
                var start = moment().startOf('day');
                var end = moment().endOf('day');

                $('#expense_date_range').data('daterangepicker').setStartDate(start);
                $('#expense_date_range').data('daterangepicker').setEndDate(end);
                $('#expense_date_range').val(
                    start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                );
            }

            var columns = [{
                    data: 'action',
                    searchable: false,
                    orderable: false
                },
                {
                    data: 'current_balance',
                    name: 'current_balance'
                },
                {
                    data: 'balance_for_period',
                    name: 'balance_for_period',
                    render: function(data, type, row) {
                        return '<span style="color:red;">' + data + '</span>';
                    }
                },
                {
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'location_name',
                    name: 'business_locations.name'
                },
                {
                    data: 'sold_fuel_qty',
                    name: 'sold_fuel_qty'
                },
                {
                    data: 'sale_amount_fuel',
                    name: 'sale_amount_fuel'
                },
                {
                    data: 'commission_type',
                    name: 'commission_type'
                },
                {
                    data: 'commission_rate',
                    name: 'commission_ap'
                },
                {
                    data: 'commission_amount',
                    searchable: false
                },
                {
                    data: 'excess_amount',
                    name: 'excess_amount'
                },
                {
                    data: 'short_amount',
                    name: 'short_amount'
                },
            ];

            list_pump_operators_table = $('#list_pump_operators_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    url: '{{ route('petropd.pd-operators') }}',
                    data: function(d) {
                        d.location_id = $('select#location_id').val();
                        d.pump_operator = $('select#pump_operator').val();
                        d.settlement_no = $('select#settlement_no').val();
                        d.type = $('select#type').val();
                        d.status = $('select#status').val();
                        d.start_date = $('input#expense_date_range')
                            .data('daterangepicker')
                            .startDate.format('YYYY-MM-DD');
                        d.end_date = $('input#expense_date_range')
                            .data('daterangepicker')
                            .endDate.format('YYYY-MM-DD');
                    },
                },
                columnDefs: [{
                        "targets": 0,
                        "orderable": false,
                        "searchable": false
                    }, {
                        "targets": 3,
                        "width": "1%"
                    }, {
                        "targets": 1,
                        "width": "64px",
                        "className": "petropd-w-balance"
                    }, {
                        "targets": 2,
                        "width": "64px",
                        "className": "petropd-w-period"
                    }, {
                        "targets": 5,
                        "width": "76px",
                        "className": "petropd-w-fuel-qty"
                    }, {
                        "targets": 6,
                        "width": "76px",
                        "className": "petropd-w-fuel-sale"
                    }, {
                        "targets": 7,
                        "width": "72px",
                        "className": "petropd-w-commission-type"
                    }, {
                        "targets": 8,
                        "width": "72px",
                        "className": "petropd-w-commission-rate"
                    }, {
                        "targets": 9,
                        "width": "78px",
                        "className": "petropd-w-commission-amount"
                    }, {
                        "targets": 10,
                        "width": "68px",
                        "className": "petropd-w-excess"
                    }, {
                        "targets": 11,
                        "width": "68px",
                        "className": "petropd-w-short"
                    },
                ],
                columns: columns,
                fnDrawCallback: function(oSettings) {
                    this.api().column(4).visible(false);
                    var sold_fuel_qty = sum_table_col($('#list_pump_operators_table'), 'sold_fuel_qty');
                    $('#footer_sold_fuel_qty').text(sold_fuel_qty);
                    var sale_amount_fuel = sum_table_col($('#list_pump_operators_table'),
                        'sale_amount_fuel');
                    $('#footer_sale_amount_fuel').text(sale_amount_fuel);
                    var commission_amount = sum_table_col($('#list_pump_operators_table'),
                        'commission_amount');
                    $('#footer_commission_amount').text(commission_amount);
                    var excess_amount = sum_table_col($('#list_pump_operators_table'), 'excess_amount');
                    $('#footer_excess_amount').text(excess_amount);
                    var short_amount = sum_table_col($('#list_pump_operators_table'), 'short_amount');
                    $('#footer_short_amount').text(short_amount);

                    __currency_convert_recursively($('#list_pump_operators_table'));
                },
            });

            $('#location_id, #pump_operator, #pump_operator, #settlement_no, #type, #date_range, #status, #expense_date_range')
                .change(function() {
                    list_pump_operators_table.ajax.reload();
                });


            $(document).on('click', 'a.delete_reference_button', function(e) {
                var page_details = $(this).closest('div.page_details')
                e.preventDefault();
                swal({
                    title: LANG.sure,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(willDelete => {
                    if (willDelete) {
                        var href = $(this).attr('href');
                        var data = $(this).serialize();
                        console.log(href);
                        $.ajax({
                            method: 'DELETE',
                            url: href,
                            dataType: 'json',
                            data: data,
                            success: function(result) {
                                if (result.success == true) {
                                    page_details.remove();
                                    toastr.success(result.msg);
                                } else {
                                    toastr.error(result.msg);
                                }
                                list_pump_operators_table.ajax.reload();
                            },
                        });
                    }
                });
            });



            $(document).on('click', 'a.toggle_active_button', function(e) {
                e.preventDefault();
                swal({
                    title: LANG.sure,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(willDelete => {
                    if (willDelete) {
                        var href = $(this).attr('href');
                        $.ajax({
                            method: 'GET',
                            url: href,
                            dataType: 'json',
                            data: {},
                            success: function(result) {
                                if (result.success == true) {
                                    toastr.success(result.msg);
                                } else {
                                    toastr.error(result.msg);
                                }
                                list_pump_operators_table.ajax.reload();
                            },
                        });
                    }
                });
            });
        });

        $(document).on('click', '.edit_contact_button', function(e) {
            e.preventDefault();
            $('div.pump_operator_modal').load($(this).attr('href'), function() {
                $(this).modal('show');
            });
        });

        /*
        |--------------------------------------------------------------------------
        | PetroPD Modal Loader Protection
        |--------------------------------------------------------------------------
        | These pages must always open inside the Bootstrap modal. If the row action
        | link is clicked, prevent full-page navigation and load the form into the
        | existing modal container.
        */
        $(document).on('click',
            'a[href*="/petropd/pd-operators/"][href$="/edit"], a[href*="/petropd/excess-comission/create"], a[href*="/petropd/recover-shortage/create"]',
            function(e) {
                e.preventDefault();
                var href = $(this).attr('href');
                $('div.pump_operator_modal').load(href, function() {
                    $(this).modal('show');
                    $('.select2').select2();
                    if ($('.dob').length) {
                        $('.dob').datepicker();
                    }
                    if ($('#paid_on').length) {
                        $('#paid_on').datepicker();
                    }
                });
            }
        );


        /*
        |--------------------------------------------------------------------------
        | PetroPD Excess / Shortage Payment Submit Protection
        |--------------------------------------------------------------------------
        | The PetroPD create/edit forms use #petropd_excess_shortage_payment_form.
        | Submit them through AJAX so the JSON response is handled inside the modal
        | instead of being rendered as a raw browser page.  The namespaced handler
        | is removed first to prevent duplicate submissions if this view is loaded
        | more than once.
        */
        $(document)
            .off('submit.petropdExcessShortage', '#petropd_excess_shortage_payment_form')
            .on('submit.petropdExcessShortage', '#petropd_excess_shortage_payment_form', function(e) {
                e.preventDefault();

                var $form = $(this);
                if ($form.data('petropdSubmitting')) {
                    return false;
                }

                $form.data('petropdSubmitting', true);

                var $submitButton = $form.find('button[type="submit"]');
                var originalButtonHtml = $submitButton.html();
                $submitButton.prop('disabled', true);

                var formData = new FormData(this);

                $.ajax({
                    method: 'POST',
                    url: $form.attr('action'),
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    success: function(result) {
                        if (result && (result.success === true || result.success == 1)) {
                            var $modal = $form.closest('.pump_operator_modal');
                            if (!$modal.length) {
                                $modal = $('.pump_operator_modal');
                            }

                            $modal.one('hidden.bs.modal.petropdExcessShortage', function() {
                                $(this).empty();
                            });
                            $modal.modal('hide');

                            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#list_pump_operators_table')) {
                                $('#list_pump_operators_table').DataTable().ajax.reload(null, false);
                            }

                            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#pumper_excess_shortage_payments_table')) {
                                $('#pumper_excess_shortage_payments_table').DataTable().ajax.reload(null, false);
                            }

                            toastr.success(result.msg || 'Payment added successfully');
                        } else {
                            toastr.error((result && result.msg) ? result.msg : LANG.something_went_wrong);
                        }
                    },
                    error: function(xhr) {
                        var response = xhr.responseJSON || {};
                        var message = response.msg || response.message;

                        if (!message && response.errors) {
                            $.each(response.errors, function(key, messages) {
                                if ($.isArray(messages) && messages.length) {
                                    message = messages[0];
                                    return false;
                                }
                            });
                        }

                        toastr.error(message || LANG.something_went_wrong);
                    },
                    complete: function() {
                        $form.data('petropdSubmitting', false);
                        $submitButton.prop('disabled', false).html(originalButtonHtml);
                    }
                });

                return false;
            });


        //pumper excess and shortage payments
        $(document).ready(function() {

            if ($('#pesp_date_range').length == 1) {
                $('#pesp_date_range').daterangepicker(dateRangeSettings, function(start, end) {
                    $('#pesp_date_range').val(
                        start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
                    );

                    pumper_excess_shortage_payments_table.ajax.reload();
                });
                $('#custom_date_apply_button').on('click', function() {
                    if ($('#target_custom_date_input').val() == "pesp_date_range") {
                        let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2')
                            .val() + $('#custom_date_from_year3').val() + $('#custom_date_from_year4')
                            .val() + "-" + $('#custom_date_from_month1').val() + $(
                                '#custom_date_from_month2')
                            .val() + "-" + $('#custom_date_from_date1').val() + $('#custom_date_from_date2')
                            .val();
                        let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() +
                            $('#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $(
                                '#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" +
                            $('#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

                        if (startDate.length === 10 && endDate.length === 10) {
                            let formattedStartDate = moment(startDate).format(moment_date_format);
                            let formattedEndDate = moment(endDate).format(moment_date_format);

                            $('#pesp_date_range').val(
                                formattedStartDate + ' ~ ' + formattedEndDate
                            );

                            $('#pesp_date_range').data('daterangepicker').setStartDate(moment(startDate));
                            $('#pesp_date_range').data('daterangepicker').setEndDate(moment(endDate));

                            pumper_excess_shortage_payments_table.ajax.reload();

                            $('.custom_date_typing_modal').modal('hide');
                        } else {
                            alert("Please select both start and end dates.");
                        }
                    }
                });
                $('#pesp_date_range').on('apply.daterangepicker', function(ev, picker) {
                    if (picker.chosenLabel === 'Custom Date Range') {
                        $('#target_custom_date_input').val('pesp_date_range');
                        $('.custom_date_typing_modal').modal('show');
                    }
                });
                $('#pesp_date_range').on('cancel.daterangepicker', function(ev, picker) {
                    $('#pesp_date_range').val('');
                    pumper_excess_shortage_payments_table.ajax.reload();
                });
                $('#pesp_date_range')
                    .data('daterangepicker')
                    .setStartDate(moment().startOf('day'));
                $('#pesp_date_range')
                    .data('daterangepicker')
                    .setEndDate(moment().endOf('day'));
            }

            pumper_excess_shortage_payments_table = $('#pumper_excess_shortage_payments_table').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    url: '{{ route('petropd.pumper-excess-shortage-payments') }}',
                    data: function(d) {
                        d.location_id = $('select#pesp_location_id').val();
                        d.pump_operator = $('select#pesp_pump_operator').val();
                        d.type = $('select#pesp_type').val();
                        d.payment_type = $('select#pesp_payment_type').val();
                        d.start_date = $('input#pesp_date_range')
                            .data('daterangepicker')
                            .startDate.format('YYYY-MM-DD');
                        d.end_date = $('input#pesp_date_range')
                            .data('daterangepicker')
                            .endDate.format('YYYY-MM-DD');
                    },
                },
                columns: [{
                        data: 'action',
                        name: 'action'
                    },
                    {
                        data: 'paid_on',
                        name: 'transaction_payments.paid_on'
                    },
                    {
                        data: 'payment_ref_no',
                        name: 'transaction_payments.payment_ref_no'
                    },
                    {
                        data: 'location_name',
                        name: 'business_locations.name'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'short_amount',
                        name: 'short_amount'
                    },
                    {
                        data: 'excess_amount',
                        name: 'excess_amount'
                    },
                    {
                        data: 'shortage_recover',
                        name: 'transaction_payments.amount'
                    },
                    {
                        data: 'excess_paid',
                        name: 'transaction_payments.amount'
                    },
                ],
                fnDrawCallback: function(oSettings) {

                    __currency_convert_recursively($('#pumper_excess_shortage_payments_table'));
                },
            });
            $('#pesp_location_id, #pesp_pump_operator, #pesp_pump_operator, #pesp_type, #pesp_date_range, #pesp_payment_type')
                .change(function() {
                    pumper_excess_shortage_payments_table.ajax.reload();
                });
            // Delete payment and refresh both tables
            $(document).on('click', 'a.delete_payment', function(e) {
                e.preventDefault();
                var href = $(this).data('href');
                swal({
                    title: LANG.sure,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        $.ajax({
                            method: 'DELETE',
                            url: href,
                            dataType: 'json',
                            success: function(result) {
                                if (result.success) {
                                    toastr.success(result.msg);
                                } else {
                                    toastr.error(result.msg);
                                }
                                // Reload both tables
                                pumper_excess_shortage_payments_table.ajax.reload();
                                list_pump_operators_table.ajax.reload();
                            },
                        });
                    }
                });
            });

            $(document).on('submit', '#pay_contact_due_form', function(e){
                e.preventDefault();
                var form = $(this);

                $.ajax({
                    method: "POST",
                    url: form.attr("action"),
                    data: new FormData(this),
                    contentType: false,
                    processData: false,
                    success: function (result) {
                        if(result.success){
                            $('.pump_operator_modal').modal('hide');

                            // Reload Pump Operator Ledger table
                            if($('#pump_operator_ledger_table').length){
                                $('#pump_operator_ledger_table').DataTable().ajax.reload();
                            }

                            // Reload Pump Operator Page table (front page)
                            if($('#pump_operator_table').length){
                                $('#pump_operator_table').DataTable().ajax.reload();
                            }

                             if($('#list_pump_operators_table').length){
                                $('#list_pump_operators_table').DataTable().ajax.reload();
                            }

                            toastr.success(result.msg);
                        }
                    }
                });
            });

        });
    </script>

    <script>
        console.log("Index URL: {{ route('petropd.day_entries.index') }}");
    </script>

    <script type="text/javascript">
        $(document).ready(function() {
            list_daily_collection_table = $('#list_daily_collection_table').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    url: '{{ route('petropd.daily-collection') }}',
                    data: function(d) {},
                },
                columnDefs: [{
                        "targets": 0,
                        "orderable": false,
                        "searchable": false,
                        "defaultContent": '-'
                    },
                    {
                        "targets": 2,
                        "visible": true
                    },
                    { "targets": 2, "width": "5%" },
                    { "targets": 3, "width": "7%" },
                    { "targets": 5, "width": "5%" },
                    { "targets": 6, "width": "8%" },
                    { "targets": 7, "width": "8%" },
                    { "targets": 8, "width": "7%" },
                    { "targets": 9, "width": "7%" },
                    { "targets": 10, "width": "9%" }
                ],
                columns: [{
                        data: 'action',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'date_and_time',
                        name: 'date_and_time'
                    },
                    {
                        data: 'shift_number',
                        name: 'shift_number'
                    },
                    {
                        data: 'settlement_no',
                        name: 'settlement_no'
                    },
                    // { data: 'location_name', name: 'business_locations.name' },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'pump_no',
                        name: 'pump_no'
                    },
                    {
                        data: 'starting_meter',
                        name: 'starting_meter',
                        render: function(data, type, row) {
                            // Format the number to three decimal places
                            return parseFloat(data).toFixed(3);
                        },
                        createdCell: function(td, cellData, rowData, row, col) {
                            $(td).addClass('text-right');
                        }
                    },
                    {
                        data: 'closing_meter',
                        name: 'closing_meter'
                    },
                    {
                        data: 'sold_ltr',
                        name: 'sold_ltr'
                    },
                    {
                        data: 'testing_ltr',
                        name: 'testing_ltr'
                    },
                    {
                        data: 'sold_amount',
                        name: 'sold_amount'
                    },
                ],
                fnDrawCallback: function(oSettings) {
                    var footer_sold_fuel_qty = sum_table_col($('#list_daily_collection_table'),
                        'footer_sold_fuel_qty');
                    $('#dc_footer_sold_fuel_qty').text(footer_sold_fuel_qty);

                    var footer_testing_qty = sum_table_col($('#list_daily_collection_table'),
                        'footer_testing_qty');
                    $('#dc_footer_testing_qty').text(footer_testing_qty);

                    var footer_sold_fuel_amount = sum_table_col($('#list_daily_collection_table'),
                        'footer_sold_fuel_amount');
                    $('#dc_footer_sold_fuel_amount').text(footer_sold_fuel_amount);

                    console.log(footer_sold_fuel_amount);

                    __currency_convert_recursively($('#list_daily_collection_table'));
                },
            });
        });



        $(document).on('click', 'a.delete_daily_collection', function(e) {
            var page_details = $(this).closest('div.page_details')
            e.preventDefault();
            swal({
                title: LANG.sure,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(willDelete => {
                if (willDelete) {
                    var href = $(this).attr('href');
                    var data = $(this).serialize();
                    $.ajax({
                        method: 'DELETE',
                        url: href,
                        dataType: 'json',
                        data: data,
                        success: function(result) {
                            if (result.success == true) {
                                toastr.success(result.msg);
                            } else {
                                toastr.error(result.msg);
                            }
                            list_daily_collection_table.ajax.reload();
                        },
                    });
                }
            });
        });


        if ($('#date_range').length == 1) {
            $('#date_range').daterangepicker(dateRangeSettings, function(start, end) {
                $('#date_range').val(
                    start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
                );
            });
            $('#date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#date_range').val('');
            });
            $('#date_range')
                .data('daterangepicker')
                .setStartDate(moment().startOf('month'));
            $('#date_range')
                .data('daterangepicker')
                .setEndDate(moment().endOf('month'));
        }
        $(document).ready(function() {
            reloadDayEntries();
            console.log('sending request for pump_operators_day_entries_table');
            function sum_day_entry_payment_amount(api) {
                var total = 0;

                api.rows({
                        page: 'current'
                    })
                    .data()
                    .each(function(row) {
                        total += parseFloat(row.payment_amount_for_total) || 0;
                    });

                return total;
            }

            pump_operators_day_entries_table = $('#pump_operators_day_entries_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    // url: '{{ action('\Modules\Petro\Http\Controllers\PumperDayEntryController@index') }}',
                    url: '{{ route('petropd.day_entries.index') }}',
                    data: function(d) {
                        d.shift_id = $("#shift_id").val();
                    },
                },
                columnDefs: [{
                        "targets": 0,
                        "orderable": false,
                        "searchable": false
                    }
                ],
                columns: [{
                        data: 'action'
                    },
                    {
                        data: 'date'
                    },
                    {
                        data: 'location_name'
                    },
                    @if (empty(auth()->user()->pump_operator_id))
                        {
                            data: 'settlement_no'
                        },
                    @endif {
                        data: 'name'
                    },
                    {
                        data: 'shift_number'
                    },
                    {
                        data: 'pump'
                    },
                    {
                        data: 'starting_meter'
                    },
                    {
                        data: 'closing_meter'
                    },
                    {
                        data: 'testing_ltr'
                    },
                    {
                        data: 'sold_ltr'
                    },
                    {
                        data: 'amount'
                    },
                    {
                        data: 'short_amount'
                    },
                ],

                fnDrawCallback: function(oSettings) {
                    var api = this.api();
                    var sold_ltr = sum_table_col($('#pump_operators_day_entries_table'), 'sold_ltr');
                    $('#footer_sold_ltr').text(sold_ltr);
                    var payment_amount = sum_day_entry_payment_amount(api);
                    $('#footer_sold_amount').text(payment_amount);
                    var credit_sale = sum_table_col($('#pump_operators_day_entries_table'),
                        'credit_sale');
                    $('#footer_credit_sale').text(credit_sale);
                    var cards = sum_table_col($('#pump_operators_day_entries_table'), 'cards');
                    $('#footer_cards').text(cards);
                    var cash = sum_table_col($('#pump_operators_day_entries_table'), 'cash');
                    $('#footer_cash').text(cash);
                    var cheque = sum_table_col($('#pump_operators_day_entries_table'), 'cheque');
                    $('#footer_cheque').text(cheque);
                    var total_amount = sum_table_col($('#pump_operators_day_entries_table'),
                        'total_amount');
                    $('#footer_total_amount').text(total_amount);


                    __currency_convert_recursively($('#pump_operators_day_entries_table'));
                },
            });

            console.log('sent the request');

            $('#day_entries_location_id, #day_entries_pump_operator, #day_entries_pump_operator, #day_entries_payment_method, #day_entries_date_range, #day_entries_difference,#shift_id')
                .change(function() {
                    pump_operators_day_entries_table.ajax.reload();
                    reloadDayEntries();
                });
        });

        function reloadDayEntries() {
            $("#pumper_day_entry_summary").empty();

            $.ajax({
                method: 'GET',
                // url:  '{{ route('petropd.day_entries.summary') }}',
                url: '{{ route('petropd.day_entries.summary') }}',
                dataType: 'html',
                data: {
                    'shift_id': $("#shift_id").val()
                },
                success: function(result) {
                    $("#pumper_day_entry_summary").html(result);
                },
            });
        }

        if ($('#shift_summary_date_range').length == 1) {
            $('#shift_summary_date_range').daterangepicker(dateRangeSettings, function(start, end) {
                $('#shift_summary_date_range').val(
                    start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
                );
                if (typeof pump_operators_shift_summary_table !== 'undefined') {
                    pump_operators_shift_summary_table.ajax.reload();
                }
            });
            $('#shift_summary_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#shift_summary_date_range').val('');
                if (typeof pump_operators_shift_summary_table !== 'undefined') {
                    pump_operators_shift_summary_table.ajax.reload();
                }
            });
            $('#shift_summary_date_range')
                .data('daterangepicker')
                .setStartDate(moment().startOf('month'));
            $('#shift_summary_date_range')
                .data('daterangepicker')
                .setEndDate(moment().endOf('month'));
        }
        $(document).ready(function() {
            pump_operators_shift_summary_table = $('#pump_operators_shift_summary_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    url: '{{ route('petropd.shift-summary.index') }}',
                    data: function(d) {
                        d.start_date = $('input#shift_summary_date_range')
                            .data('daterangepicker')
                            .startDate.format('YYYY-MM-DD');
                        d.end_date = $('input#shift_summary_date_range')
                            .data('daterangepicker')
                            .endDate.format('YYYY-MM-DD');
                        d.location_id = $('#shift_summary_location_id').val();
                        d.shift_id = $('#shift_summary_shift_id').val();
                        d.pump_operator_id = $('#shift_summary_pump_operators').val();
                        d.pump_id = $('#shift_summary_pumps').val();
                        d.payment_method = $('#shift_summary_payment_method').val();
                        d.difference = $('#shift_summary_difference').val();
                    },
                },
                columnDefs: [{
                    "targets": 0,
                    "orderable": false,
                    "searchable": false
                }],
                columns: [{
                        data: 'action',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'date',
                        name: 'date'
                    },
                    {
                        data: 'name',
                        name: 'pump_operators.name'
                    },
                    {
                        data: 'pump_no',
                        name: 'pump_no'
                    },
                    {
                        data: 'starting_meter',
                        name: 'starting_meter'
                    },
                    {
                        data: 'closing_meter',
                        name: 'closing_meter'
                    },
                    {
                        data: 'testing_ltr',
                        name: 'testing_ltr'
                    },
                    {
                        data: 'sold_ltr',
                        name: 'sold_ltr'
                    },
                    {
                        data: 'amount',
                        name: 'amount',
                        searchable: false
                    },
                    {
                        data: 'other_sales',
                        name: 'other_sales',
                        searchable: false
                    },
                    {
                        data: 'credit_sale',
                        name: 'credit_sale',
                        searchable: false
                    },
                    {
                        data: 'cards',
                        name: 'cards',
                        searchable: false
                    },
                    {
                        data: 'cash',
                        name: 'cash',
                        searchable: false
                    },
                    {
                        data: 'cheque',
                        name: 'cheque',
                        searchable: false
                    },
                    {
                        data: 'other',
                        name: 'other',
                        searchable: false
                    },
                    {
                        data: 'shortage',
                        name: 'shortage',
                        searchable: false
                    },
                    {
                        data: 'excess',
                        name: 'excess',
                        searchable: false
                    },
                    {
                        data: 'total_amount',
                        name: 'total_amount',
                        searchable: false
                    },
                    {
                        data: 'difference',
                        name: 'difference',
                        searchable: false
                    },
                ],
                fnDrawCallback: function(oSettings) {
                    var sold_ltr = sum_table_col($('#pump_operators_shift_summary_table'), 'sold_ltr');
                    $('#footer_shift_summary_sold_ltr').text(sold_ltr);
                    
                    var sold_amount = sum_table_col($('#pump_operators_shift_summary_table'), 'sold_amount');
                    $('#footer_shift_summary_sold_amount').text(sold_amount);
                    $('#shift_summary_total_sale_val').text(sold_amount);

                    var other_sales = sum_table_col($('#pump_operators_shift_summary_table'), 'other_sales');
                    $('#footer_shift_summary_other_sales').text(other_sales);
                    $('#shift_summary_total_other_sales_val').text(other_sales);
                    
                    var credit_sale = sum_table_col($('#pump_operators_shift_summary_table'), 'credit_sale');
                    $('#footer_shift_summary_credit_sale').text(credit_sale);
                    $('#shift_summary_credit_sales_val').text(credit_sale);
                    
                    var cards = sum_table_col($('#pump_operators_shift_summary_table'), 'cards');
                    $('#footer_shift_summary_cards').text(cards);
                    $('#shift_summary_credit_cards_val').text(cards);
                    
                    var cash = sum_table_col($('#pump_operators_shift_summary_table'), 'cash');
                    $('#footer_shift_summary_cash').text(cash);
                    $('#shift_summary_cash_val').text(cash);
                    
                    var cheque = sum_table_col($('#pump_operators_shift_summary_table'), 'cheque');
                    $('#footer_shift_summary_cheque').text(cheque);
                    $('#shift_summary_cheque_sales_val').text(cheque);

                    var other = sum_table_col($('#pump_operators_shift_summary_table'), 'other');
                    $('#footer_shift_summary_other').text(other);
                    $('#shift_summary_other_val').text(other);

                    var shortage = sum_table_col($('#pump_operators_shift_summary_table'), 'shortage');
                    $('#footer_shift_summary_shortage').text(shortage);
                    $('#shift_summary_shortage_val').text(shortage);

                    var excess = sum_table_col($('#pump_operators_shift_summary_table'), 'excess');
                    $('#footer_shift_summary_excess').text(excess);
                    $('#shift_summary_excess_val').text(excess);
                    
                    var total_amount = sum_table_col($('#pump_operators_shift_summary_table'), 'total_amount');
                    $('#footer_shift_summary_total_amount').text(total_amount);
                    $('#shift_summary_total_payments_val').text(total_amount);
                    
                    var difference = sum_table_col($('#pump_operators_shift_summary_table'), 'difference');
                    $('#footer_shift_summary_difference').text(difference);
                    
                    var balance_to_settle = (sold_amount + other_sales) - total_amount;
                    $('#shift_summary_balance_to_settle_val').text(balance_to_settle);

                    var unique_pumps = [];
                    $('#pump_operators_shift_summary_table tbody tr').each(function() {
                        if ($(this).find('td').length > 1) {
                            var pump_no = $(this).find('td:eq(3)').text().trim();
                            if (pump_no && pump_no !== '' && pump_no !== '—' && unique_pumps.indexOf(pump_no) === -1) {
                                unique_pumps.push(pump_no);
                            }
                        }
                    });
                    $('#shift_summary_pumps_today_val').text(unique_pumps.length);

                    __currency_convert_recursively($('#shift_summary'));
                },
            });

            $('#shift_summary_location_id, #shift_summary_shift_id, #shift_summary_pump_operators, #shift_summary_pumps, #shift_summary_payment_method, #shift_summary_date_range, #shift_summary_difference')
                .change(function() {
                    pump_operators_shift_summary_table.ajax.reload();
                });
        });

        if ($('#payment_summary_date_range').length == 1) {
            $('#payment_summary_date_range').daterangepicker(dateRangeSettings, function(start, end) {
                $('#payment_summary_date_range').val(
                    start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
                );
                pump_operators_payment_summary_table.ajax.reload();
            });
            $('#custom_date_apply_button').on('click', function() {
                if ($('#target_custom_date_input').val() == "payment_summary_date_range") {
                    let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + $(
                        '#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + $(
                        '#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $(
                        '#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
                    let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $(
                        '#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $(
                        '#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $(
                        '#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

                    if (startDate.length === 10 && endDate.length === 10) {
                        let formattedStartDate = moment(startDate).format(moment_date_format);
                        let formattedEndDate = moment(endDate).format(moment_date_format);

                        $('#payment_summary_date_range').val(
                            formattedStartDate + ' ~ ' + formattedEndDate
                        );

                        $('#payment_summary_date_range').data('daterangepicker').setStartDate(moment(startDate));
                        $('#payment_summary_date_range').data('daterangepicker').setEndDate(moment(endDate));

                        pump_operators_payment_summary_table.ajax.reload();

                        $('.custom_date_typing_modal').modal('hide');
                    } else {
                        alert("Please select both start and end dates.");
                    }
                }
            });
            $('#payment_summary_date_range').on('apply.daterangepicker', function(ev, picker) {
                if (picker.chosenLabel === 'Custom Date Range') {
                    $('#target_custom_date_input').val('payment_summary_date_range');
                    $('.custom_date_typing_modal').modal('show');
                }
            });
            $('#payment_summary_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#payment_summary_date_range').val('');
            });
            $('#payment_summary_date_range')
                .data('daterangepicker')
                .setStartDate(moment().startOf('day'));
            $('#payment_summary_date_range')
                .data('daterangepicker')
                .setEndDate(moment().endOf('day'));
        }
        $(document).ready(function() {
            pump_operators_payment_summary_table = $('#pump_operators_payment_summary_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    url: '{{ route('petropd.payment-summary.dashboard') }}',
                    data: function(d) {
                        d.start_date = $('input#payment_summary_date_range')
                            .data('daterangepicker')
                            .startDate.format('YYYY-MM-DD');
                        d.end_date = $('input#payment_summary_date_range')
                            .data('daterangepicker')
                            .endDate.format('YYYY-MM-DD');
                        d.pump_operator_id = $('#payment_summary_pump_operators').val();
                        d.payment_method = $('#payment_summary_payment_method').val();
                        d.location_id = $('#payment_summary_location_id').val();
                        d.shift_id = $("#payment_summary_shift_id").val();
                        d.customer_id = $('#payment_summary_customer').val();
                        d.slip_no = $('#payment_summary_slip_no').val();
                        d.order_no = $('#payment_summary_order_no').val();
                    },
                    error: function (xhr, textStatus, errorThrown) {
                        console.error('pump_operators_payment_summary_table AJAX error:', {
                            status: xhr.status,
                            statusText: xhr.statusText,
                            responseText: xhr.responseText,
                            textStatus: textStatus,
                            errorThrown: errorThrown
                        });
                    }
                },
                columnDefs: [{
                        "targets": 0,
                        "orderable": false,
                        "searchable": false
                    },
                    {
                        "targets": 2,
                        "visible": false
                    }
                ],
                columns: [{
                        data: 'action',
                        name: 'action'
                    },
                    {
                        data: 'date',
                        name: 'date'
                    },
                    {
                        data: 'location_name',
                        name: 'business_locations.name'
                    },
                    {
                        data: 'time',
                        name: 'time'
                    },
                    {
                        data: 'pump_operator_name',
                        name: 'pump_operators.name'
                    },
                    {
                        data: 'shift_number',
                        name: 'shift_number'
                    },
                    {
                        data: 'collection_form_no',
                        name: 'collection_form_no'
                    },
                    {
                        data: 'payment_type',
                        name: 'payment_type'
                    },
                    {
                        data: 'customer_name',
                        name: 'customer_name'
                    },
                    {
                        data: 'slip_no',
                        name: 'slip_no'
                    },
                    {
                        data: 'order_number',
                        name: 'order_number'
                    },
                    {
                        data: 'amount',
                        name: 'amount'
                    },
                    {
                        data: 'note',
                        name: 'note'
                    },
                    {
                        data: 'edited_by',
                        name: 'edited_by'
                    }
                ],

                fnDrawCallback: function(oSettings) {
                    var footer_payment_summary_amount = sum_table_col($(
                        '#pump_operators_payment_summary_table'), 'amount');
                    $('#footer_payment_summary_amount').text(footer_payment_summary_amount);

                    __currency_convert_recursively($('#pump_operators_payment_summary_table'));
                },
            });

            $('#payment_summary_pump_operators, #payment_summary_payment_method, #payment_summary_date_range, #payment_summary_location_id, #payment_summary_shift_id, #payment_summary_customer, #payment_summary_slip_no, #payment_summary_order_no')
                .on('change keyup', function() {
                    pump_operators_payment_summary_table.ajax.reload();
                });
        });

        $(document).ready(function() {
            if ($('#meters_with_payments_date_range').length == 1) {
                $('#meters_with_payments_date_range').daterangepicker(dateRangeSettings, function(start, end) {
                    $('#meters_with_payments_date_range').val(
                        start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
                    );
                    pump_operators_meters_with_payments_table.ajax.reload();
                });
                $('#custom_date_apply_button').on('click', function() {
                    let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2')
                        .val() + $('#custom_date_from_year3').val() + $('#custom_date_from_year4').val() +
                        "-" +
                        $('#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $(
                            '#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
                    let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $(
                        '#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $(
                        '#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $(
                        '#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

                    if (startDate.length === 10 && endDate.length === 10) {
                        let formattedStartDate = moment(startDate).format(moment_date_format);
                        let formattedEndDate = moment(endDate).format(moment_date_format);

                        $('#meters_with_payments_date_range').val(
                            formattedStartDate + ' ~ ' + formattedEndDate
                        );

                        $('#meters_with_payments_date_range').data('daterangepicker').setStartDate(moment(
                            startDate));
                        $('#meters_with_payments_date_range').data('daterangepicker').setEndDate(moment(
                            endDate));

                        $('.custom_date_typing_modal').modal('hide');
                        pump_operators_meters_with_payments_table.ajax.reload();
                    } else {
                        alert("Please select both start and end dates.");
                    }
                });
                $('#meters_with_payments_date_range').on('apply.daterangepicker', function(ev, picker) {
                    if (picker.chosenLabel === 'Custom Date Range') {
                        $('.custom_date_typing_modal').modal('show');
                    }
                });
                $('#meters_with_payments_date_range').on('cancel.daterangepicker', function(ev, picker) {
                    $('#meters_with_payments_date_range').val('');
                });
                $('#meters_with_payments_date_range')
                    .data('daterangepicker')
                    .setStartDate(moment().startOf('day'));
                $('#meters_with_payments_date_range')
                    .data('daterangepicker')
                    .setEndDate(moment().endOf('day'));
            }
            pump_operators_meters_with_payments_table = $('#pump_operators_meters_with_payments_table').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    url: '{{ route('petropd.meters-with-payments') }}',
                    data: function(d) {
                        d.start_date = $('input#meters_with_payments_date_range')
                            .data('daterangepicker')
                            .startDate.format('YYYY-MM-DD');
                        d.end_date = $('input#meters_with_payments_date_range')
                            .data('daterangepicker')
                            .endDate.format('YYYY-MM-DD');
                        d.pump_id = $('#meters_with_payments_pump_no').val();
                        d.pump_operator_id = $('#meters_with_payments_pump_operators').val();
                    },
                },
                columnDefs: [{
                        "targets": 0,
                        "orderable": false,
                        "searchable": false
                    },
                    {
                        "targets": 2,
                        "visible": true
                    },
                    {
                        targets: 11, // Index of the 'amount' column (starting from 0)
                        searchable: false, // Disable searching for this column
                    },
                ],
                columns: [{
                        data: 'date',
                        name: 'date'
                    },
                    {
                        data: 'time',
                        name: 'time'
                    },
                    {
                        data: 'pump_operator_name',
                        name: 'pump_operators.name'
                    },
                    {
                        data: 'collection_form_no',
                        name: 'collection_form_no'
                    },
                    {
                        data: 'pumps',
                        name: 'pumps'
                    },
                    {
                        data: 'unit_price',
                        name: 'unit_price'
                    },
                    {
                        data: 'last_meter',
                        name: 'last_meter'
                    },
                    {
                        data: 'new_meter',
                        name: 'new_meter'
                    },
                    {
                        data: 'qty_sold',
                        name: 'qty_sold'
                    },
                    {
                        data: 'total_sold_amount',
                        name: 'total_sold_amount'
                    },
                    {
                        data: 'payment_type',
                        name: 'payment_type'
                    },
                    {
                        data: 'amount',
                        name: 'amount'
                    }
                ],
            });

            $('#meters_with_payments_pump_operators, #meters_with_payments_pump_no').change(function() {
                pump_operators_meters_with_payments_table.ajax.reload();
            });
        });

        if ($('#close_shift_date_range').length == 1) {
            $('#close_shift_date_range').daterangepicker(dateRangeSettings, function(start, end) {
                $('#close_shift_date_range').val(
                    start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
                );
            });
            $('#close_shift_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#close_shift_date_range').val('');
            });
            $('#close_shift_date_range')
                .data('daterangepicker')
                .setStartDate(moment().startOf('month'));
            $('#close_shift_date_range')
                .data('daterangepicker')
                .setEndDate(moment().endOf('month'));
        }
        $(document).ready(function() {
            reloadClosingShift();
            pump_operators_closing_shift_table = $('#pump_operators_closing_shift_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    url: "{{ route('petropd.closing-shift.index', ['only_pumper' => false]) }}",
                    data: function(d) {
                        @if (empty(auth()->user()->pump_operator_id))
                            // d.start_date = $('input#close_shift_date_range')
                            //     .data('daterangepicker')
                            //     .startDate.format('YYYY-MM-DD');
                            // d.end_date = $('input#close_shift_date_range')
                            //     .data('daterangepicker')
                            //     .endDate.format('YYYY-MM-DD');
                            // d.location_id = $('#close_shift_location_id').val();
                            // d.pump_operator_id = $('#close_shift_pump_operators').val();
                            // d.pump_id = $('#close_shift_pumps').val();
                            // d.payment_method = $('#close_shift_payment_method').val();
                        @endif
                        d.shift_id = $("#closing_shift_id").val();
                    },
                },
                columnDefs: [{
                        "targets": 0,
                        "orderable": false,
                        "searchable": false
                    },
                    {
                        "targets": 2,
                        "visible": false
                    }
                ],
                columns: [{
                        data: 'action',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'date',
                        name: 'date'
                    },
                    {
                        data: 'location_name',
                        name: 'business_locations.name'
                    },
                    {
                        data: 'time',
                        name: 'time'
                    },
                    {
                        data: 'name',
                        name: 'pump_operators.name'
                    },
                    {
                        data: 'shift_number',
                        name: 'shift_number'
                    },
                    {
                        data: 'pump_no',
                        name: 'pump_no'
                    },
                    {
                        data: 'starting_meter',
                        name: 'starting_meter'
                    },
                    {
                        data: 'closing_meter',
                        name: 'closing_meter'
                    },
                    {
                        data: 'testing_ltr',
                        name: 'testing_ltr'
                    },
                    {
                        data: 'sold_ltr',
                        name: 'sold_ltr'
                    },
                    {
                        data: 'amount',
                        name: 'amount',
                        searchable: false
                    },
                    {
                        data: 'short_amount',
                        name: 'short_amount',
                        searchable: false
                    },
                ],
                fnDrawCallback: function(oSettings) {
                    var testing_ltr = sum_table_col($('#pump_operators_closing_shift_table'),
                        'testing_ltr');
                    $('#footer_cs_testing_ltr').text(testing_ltr);
                    var sold_ltr = sum_table_col($('#pump_operators_closing_shift_table'), 'sold_ltr');
                    $('#footer_cs_sold_ltr').text(sold_ltr);
                    var sold_amount = sum_table_col($('#pump_operators_closing_shift_table'),
                        'sold_amount');
                    $('#footer_cs_sold_amount').text(sold_amount);
                    var short_amount = sum_table_col($('#pump_operators_closing_shift_table'),
                        'short_amount');
                    $('#footer_cs_short_amount').text(short_amount);


                    __currency_convert_recursively($('#pump_operators_closing_shift_table'));
                },
            });

            $('#close_shift_location_id, #close_shift_pump_operators, #close_shift_pumps, #close_shift_payment_method, #close_shift_date_range,#closing_shift_id')
                .change(function() {
                    pump_operators_closing_shift_table.ajax.reload();
                    reloadClosingShift();
                });
        });

        function reloadClosingShift() {
            $("#pumper_day_entry_summary").empty();

            $.ajax({
                method: 'GET',
                url: '{{ route('petropd.closing-shift.summary') }}',
                dataType: 'html',
                data: {
                    'shift_id': $("#closing_shift_id").val()
                },
                success: function(result) {
                    $("#closing_shift_summary").html(result);
                },
            });
        }

        // current meter tab script
        if ($('#current_meter_date_range').length == 1) {
            $('#current_meter_date_range').daterangepicker(dateRangeSettings, function(start, end) {
                $('#current_meter_date_range').val(
                    start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
                );
                pump_operators_current_meter_table.ajax.reload();
            });
            $('#custom_date_apply_button').on('click', function() {
                if ($('#target_custom_date_input').val() == "current_meter_date_range") {
                    let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + $(
                        '#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + $(
                        '#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $(
                        '#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
                    let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $(
                        '#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $(
                        '#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $(
                        '#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

                    if (startDate.length === 10 && endDate.length === 10) {
                        let formattedStartDate = moment(startDate).format(moment_date_format);
                        let formattedEndDate = moment(endDate).format(moment_date_format);

                        $('#current_meter_date_range').val(
                            formattedStartDate + ' ~ ' + formattedEndDate
                        );

                        $('#current_meter_date_range').data('daterangepicker').setStartDate(moment(startDate));
                        $('#current_meter_date_range').data('daterangepicker').setEndDate(moment(endDate));

                        pump_operators_current_meter_table.ajax.reload();

                        $('.custom_date_typing_modal').modal('hide');
                    } else {
                        alert("Please select both start and end dates.");
                    }
                }
            });
            $('#current_meter_date_range').on('apply.daterangepicker', function(ev, picker) {
                if (picker.chosenLabel === 'Custom Date Range') {
                    $('#target_custom_date_input').val('current_meter_date_range');
                    $('.custom_date_typing_modal').modal('show');
                }
            });
            $('#current_meter_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#current_meter_date_range').val('');
            });
            $('#current_meter_date_range')
                .data('daterangepicker')
                .setStartDate(moment().startOf('day'));
            $('#current_meter_date_range')
                .data('daterangepicker')
                .setEndDate(moment().endOf('day'));
        }
        $(document).ready(function() {
            pump_operators_current_meter_table = $('#pump_operators_current_meter_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    url: "{{ route('petropd.current-meter.index', ['only_pumper' => false]) }}",
                    data: function(d) {
                        d.start_date = $('input#current_meter_date_range')
                            .data('daterangepicker')
                            .startDate.format('YYYY-MM-DD');
                        d.end_date = $('input#current_meter_date_range')
                            .data('daterangepicker')
                            .endDate.format('YYYY-MM-DD');
                        d.pump_operator_id = $('#current_meter_pump_operators').val();
                        d.pump_id = $('#current_meter_pump_no').val();
                        d.location_id = $('#current_meter_location_id').val();
                    },
                },
                columnDefs: [{
                        "targets": 0,
                        "orderable": false,
                        "searchable": false
                    },
                    {
                        "targets": 1,
                        "visible": false
                    }
                ],
                columns: [{
                        data: 'date_and_time',
                        name: 'date_and_time'
                    },
                    {
                        data: 'location_name',
                        name: 'business_locations.location_name'
                    },
                    {
                        data: 'pump_no',
                        name: 'pump_no'
                    },
                    {
                        data: 'name',
                        name: 'pump_operators.name'
                    },
                    {
                        data: 'starting_meter',
                        name: 'starting_meter'
                    },
                    {
                        data: 'last_time_meter',
                        name: 'last_time_meter'
                    },
                    {
                        data: 'current_meter',
                        name: 'current_meter'
                    },
                    {
                        data: 'amount',
                        name: 'amount'
                    },
                    {
                        data: 'total_sale_amount',
                        name: 'total_sale_amount'
                    },
                ],
                fnDrawCallback: function(oSettings) {
                    __currency_convert_recursively($('#pump_operators_current_meter_table'));
                },
            });

            $('#current_meter_pump_operators, #current_meter_pump_no, #current_meter_date_range,#current_meter_location_id')
                .change(function() {
                    pump_operators_current_meter_table.ajax.reload();
                });
        });


        // current meter tab script
        if ($('#unload_stock_date_range').length == 1) {
            $('#unload_stock_date_range').daterangepicker(dateRangeSettings, function(start, end) {
                $('#unload_stock_date_range').val(
                    start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
                );
                pump_operators_unload_stock_table.ajax.reload();
            });
            $('#custom_date_apply_button').on('click', function() {
                if ($('#target_custom_date_input').val() == "unload_stock_date_range") {
                    let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + $(
                        '#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + $(
                        '#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $(
                        '#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
                    let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $(
                        '#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $(
                        '#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $(
                        '#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

                    if (startDate.length === 10 && endDate.length === 10) {
                        let formattedStartDate = moment(startDate).format(moment_date_format);
                        let formattedEndDate = moment(endDate).format(moment_date_format);

                        $('#unload_stock_date_range').val(
                            formattedStartDate + ' ~ ' + formattedEndDate
                        );

                        $('#unload_stock_date_range').data('daterangepicker').setStartDate(moment(startDate));
                        $('#unload_stock_date_range').data('daterangepicker').setEndDate(moment(endDate));

                        pump_operators_unload_stock_table.ajax.reload();

                        $('.custom_date_typing_modal').modal('hide');
                    } else {
                        alert("Please select both start and end dates.");
                    }
                }
            });
            $('#unload_stock_date_range').on('apply.daterangepicker', function(ev, picker) {
                if (picker.chosenLabel === 'Custom Date Range') {
                    $('#target_custom_date_input').val('unload_stock_date_range');
                    $('.custom_date_typing_modal').modal('show');
                }
            });
            $('#unload_stock_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#unload_stock_date_range').val('');
            });
            $('#unload_stock_date_range')
                .data('daterangepicker')
                .setStartDate(moment().startOf('day'));
            $('#unload_stock_date_range')
                .data('daterangepicker')
                .setEndDate(moment().endOf('day'));
        }
        $(document).ready(function() {
            pump_operators_unload_stock_table = $('#pump_operators_unload_stock_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    url: "{{ route('petropd.unload-stock.index', ['only_pumper' => true]) }}",
                    data: function(d) {
                        d.start_date = $('input#unload_stock_date_range')
                            .data('daterangepicker')
                            .startDate.format('YYYY-MM-DD');
                        d.end_date = $('input#unload_stock_date_range')
                            .data('daterangepicker')
                            .endDate.format('YYYY-MM-DD');
                        d.tank_id = $('#unload_stock_tank_id').val();
                        d.product_id = $('#unload_stock_product_id').val();
                        d.location_id = $('#unload_stock_location_id').val();
                    },
                },
                columnDefs: [{
                        "targets": 0,
                        "orderable": false,
                        "searchable": false
                    },
                    {
                        "targets": 1,
                        "visible": false
                    }
                ],
                columns: [{
                        data: 'date_and_time',
                        name: 'date_and_time'
                    },
                    {
                        data: 'location_name',
                        name: 'business_locations.name'
                    },
                    {
                        data: 'fuel_tank_number',
                        name: 'fuel_tank_number'
                    },
                    {
                        data: 'product',
                        name: 'product'
                    },
                    {
                        data: 'dip_reading',
                        name: 'dip_reading'
                    },
                    {
                        data: 'current_stock',
                        name: 'current_stock'
                    },
                    {
                        data: 'unloaded_qty',
                        name: 'unloaded_qty'
                    },
                    {
                        data: 'total_qty',
                        name: 'total_qty'
                    },
                    {
                        data: 'username',
                        name: 'users.username'
                    },
                ],
                fnDrawCallback: function(oSettings) {
                    __currency_convert_recursively($('#pump_operators_unload_stock_table'));
                },
            });

            $('#unload_stock_product_id, #unload_stock_tank_id, #unload_stock_date_range, #unload_stock_location_id')
                .change(function() {
                    pump_operators_unload_stock_table.ajax.reload();
                });
        });
    </script>

    <script type="text/javascript">
        /*
        |--------------------------------------------------------------------------
        | PetroPD PD Operators - Action dropdown anchoring fix
        |--------------------------------------------------------------------------
        | The menu is temporarily moved to <body> while open so table-responsive,
        | DataTables and tab containers cannot clip bottom-row menus. It is then
        | positioned directly beside the clicked Action button.
        */
        (function ($) {
            'use strict';

            var activeMenu = null;
            var activeGroup = null;
            var activeToggle = null;
            var menuGap = 3;
            var edgeGap = 8;

            function clearFloatingStyles($menu) {
                $menu
                    .removeClass('petropd-pdoperator-action-menu petropd-action-scrollable')
                    .css({
                        position: '',
                        top: '',
                        left: '',
                        right: '',
                        bottom: '',
                        display: '',
                        float: '',
                        margin: '',
                        zIndex: '',
                        maxHeight: '',
                        overflowY: '',
                        overflowX: '',
                        visibility: '',
                        minWidth: ''
                    });
            }

            function restoreActiveMenu() {
                if (!activeMenu || !activeMenu.length) {
                    activeMenu = activeGroup = activeToggle = null;
                    return;
                }

                var $menu = activeMenu;
                var $group = activeGroup;

                clearFloatingStyles($menu);

                if ($group && $group.length && $.contains(document, $group[0])) {
                    $group.append($menu);
                } else {
                    $menu.remove();
                }

                activeMenu = activeGroup = activeToggle = null;
            }

            function findMenu($group) {
                var $menu = $group.children('.dropdown-menu').first();
                if (!$menu.length) {
                    $menu = $group.find('.dropdown-menu').first();
                }
                return $menu;
            }

            function findToggle($group) {
                var $toggle = $group.children('[data-toggle="dropdown"], .dropdown-toggle').first();
                if (!$toggle.length) {
                    $toggle = $group.find('[data-toggle="dropdown"], .dropdown-toggle').first();
                }
                return $toggle;
            }

            function positionActiveMenu() {
                if (!activeMenu || !activeMenu.length ||
                    !activeToggle || !activeToggle.length ||
                    !$.contains(document, activeToggle[0])) {
                    return;
                }

                var rect = activeToggle[0].getBoundingClientRect();
                var viewportWidth = window.innerWidth || document.documentElement.clientWidth;
                var viewportHeight = window.innerHeight || document.documentElement.clientHeight;

                activeMenu.css({
                    display: 'block',
                    position: 'fixed',
                    top: '0px',
                    left: '0px',
                    right: 'auto',
                    bottom: 'auto',
                    visibility: 'hidden',
                    maxHeight: 'none',
                    overflowY: 'visible',
                    overflowX: 'hidden',
                    minWidth: Math.max(activeToggle.outerWidth() || 0, 160) + 'px'
                });

                var menuWidth = activeMenu.outerWidth();
                var naturalHeight = activeMenu.outerHeight();
                var spaceBelow = Math.max(0, viewportHeight - rect.bottom - menuGap - edgeGap);
                var spaceAbove = Math.max(0, rect.top - menuGap - edgeGap);
                var openDown = (naturalHeight <= spaceBelow) || (spaceBelow >= spaceAbove);
                var availableHeight = Math.max(80, openDown ? spaceBelow : spaceAbove);
                var renderedHeight = Math.min(naturalHeight, availableHeight);

                var left = rect.left;
                if (left + menuWidth > viewportWidth - edgeGap) {
                    left = rect.right - menuWidth;
                }
                left = Math.max(edgeGap, Math.min(left, viewportWidth - menuWidth - edgeGap));

                var top;
                if (openDown) {
                    top = rect.bottom + menuGap;
                } else {
                    top = rect.top - renderedHeight - menuGap;
                }
                top = Math.max(edgeGap, Math.min(top, viewportHeight - renderedHeight - edgeGap));

                if (naturalHeight > availableHeight) {
                    activeMenu
                        .addClass('petropd-action-scrollable')
                        .css({
                            maxHeight: availableHeight + 'px',
                            overflowY: 'auto'
                        });
                } else {
                    activeMenu
                        .removeClass('petropd-action-scrollable')
                        .css({
                            maxHeight: 'none',
                            overflowY: 'visible'
                        });
                }

                activeMenu.css({
                    top: Math.round(top) + 'px',
                    left: Math.round(left) + 'px',
                    visibility: 'visible'
                });
            }

            function floatMenu($group) {
                var $menu = findMenu($group);
                var $toggle = findToggle($group);

                if (!$menu.length || !$toggle.length) {
                    return;
                }

                if (activeMenu && activeMenu.length && activeMenu[0] !== $menu[0]) {
                    restoreActiveMenu();
                }

                activeMenu = $menu;
                activeGroup = $group;
                activeToggle = $toggle;

                // Moving the menu outside the responsive table avoids all clipping.
                $menu
                    .addClass('petropd-pdoperator-action-menu')
                    .appendTo(document.body);

                positionActiveMenu();
            }

            // Remove only our handlers if this view is evaluated more than once.
            $(document).off('.petropdPdOperatorActionMenu');
            $(window).off('.petropdPdOperatorActionMenu');

            $(document).on(
                'shown.bs.dropdown.petropdPdOperatorActionMenu',
                '#list_pump_operators_table .btn-group, #list_pump_operators_table .dropdown',
                function () {
                    floatMenu($(this));
                }
            );

            $(document).on(
                'hidden.bs.dropdown.petropdPdOperatorActionMenu',
                '#list_pump_operators_table .btn-group, #list_pump_operators_table .dropdown',
                function () {
                    if (activeGroup && activeGroup.length && activeGroup[0] === this) {
                        restoreActiveMenu();
                    }
                }
            );

            // Reposition immediately while the user scrolls/resizes. The menu remains
            // attached visually to the same Action button instead of disappearing.
            $(window).on('scroll.petropdPdOperatorActionMenu resize.petropdPdOperatorActionMenu', function () {
                if (activeMenu) {
                    positionActiveMenu();
                }
            });

            // DataTables may replace row DOM during filtering/paging/reload. Restore the
            // menu before redraw so no detached element or stale reference is left behind.
            $(document).on('preDraw.dt.petropdPdOperatorActionMenu', '#list_pump_operators_table', function () {
                restoreActiveMenu();
            });

            // Safety fallback for Bootstrap variants/themes that do not emit hidden on
            // every outside-click path.
            $(document).on('click.petropdPdOperatorActionMenu', function (e) {
                if (!activeMenu || !activeMenu.length) {
                    return;
                }

                var clickedInsideMenu = $(e.target).closest(activeMenu).length > 0;
                var clickedSameToggle = activeToggle && activeToggle.length &&
                    ($(e.target).is(activeToggle) || $(e.target).closest(activeToggle).length > 0);

                if (!clickedInsideMenu && !clickedSameToggle &&
                    activeGroup && !activeGroup.hasClass('open') && !activeGroup.hasClass('show')) {
                    restoreActiveMenu();
                }
            });
        })(jQuery);
    </script>

    <script>
        /*
         * IS2312 - DataTables inside inactive Bootstrap tabs can retain widths
         * measured while hidden. Recalculate the three requested table sections
         * whenever their tab is shown and whenever the viewport changes.
         */
        (function () {
            function adjustIs2312Tables() {
                [
                    '#list_daily_collection_table',
                    '#pumper_excess_shortage_payments_table',
                    '#pump_operators_meters_with_payments_table'
                ].forEach(function (selector) {
                    if ($.fn.DataTable && $.fn.DataTable.isDataTable(selector)) {
                        $(selector).DataTable().columns.adjust();
                    }
                });
            }

            $(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function () {
                window.setTimeout(adjustIs2312Tables, 0);
            });

            var is2312ResizeTimer = null;
            $(window).on('resize.is2312', function () {
                window.clearTimeout(is2312ResizeTimer);
                is2312ResizeTimer = window.setTimeout(adjustIs2312Tables, 120);
            });

            $(document).ready(function () {
                window.setTimeout(adjustIs2312Tables, 0);
            });
        })();
    </script>

@endsection
