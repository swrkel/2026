@extends('layouts.app')
@section('title', __('membership::lang.add_dividends'))

@section('content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-12">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('membership::lang.add_dividends')</h4>
            </div>
        </div>
    </div>
</div>

<section class="content">
    {!! Form::open(['url' => action('\Modules\Membership\Http\Controllers\DividendController@storeDividends'), 'method'
    => 'post', 'id' => 'dividend_form']) !!}

    <!-- Filters Section -->
    <div class="box box-solid filter-box no-print">
        <div class="box-header with-border">
            <h3 class="box-title">
                <i class="fa fa-filter"></i> @lang('report.filters')
            </h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('date', __('lang_v1.date').':') !!}
                        {!! Form::text('date', null, ['class' => 'form-control', 'id' => 'date', 'readonly']) !!}
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="form-group">
                        {!! Form::label('form_no', __('membership::lang.form_no').':') !!}
                        {!! Form::text('form_no', null, ['class' => 'form-control', 'id' => 'form_no', 'placeholder' =>
                        '1']) !!}
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('region_id', __('membership::lang.region').':') !!}
                        <select id="region_id" name="region_id" class="form-control select2" style="width: 100%;">
                            <option value="">@lang('lang_v1.please_select')</option>
                            @foreach($regions ?? [] as $id => $region)
                            <option value="{{ $id }}">{{ $region }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('member_id_filter', __('membership::lang.member_name_code').':') !!}
                        <select id="member_id_filter" name="member_id_filter" class="form-control select2"
                            style="width: 100%;">
                            <option value="">@lang('lang_v1.please_select')</option>
                            @foreach($allMembers ?? [] as $member)
                            <option value="{{ $member['id'] }}">{{ $member['text'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('membership_status_id', __('membership::lang.membership_status').':') !!}
                        <select id="membership_status_id" name="membership_status_id" class="form-control select2"
                            style="width: 100%;">
                            <option value="">@lang('lang_v1.please_select')</option>
                            @foreach($membershipStatuses ?? [] as $id => $status)
                            <option value="{{ $id }}">{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('date_joined_range', __('membership::lang.joined_date').':') !!}
                        {!! Form::text('date_joined_range', null, ['class' => 'form-control', 'id' =>
                        'date_joined_range', 'placeholder' => __('lang_v1.select_a_date_range'), 'readonly']) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export and Action Buttons -->
    <div class="box box-solid no-print">
        <div class="box-body">
            <!-- Row 1: Export buttons -->
            <div
                style="display: flex; justify-content: center; align-items: center; flex-wrap: wrap; margin-bottom: 8px;">
                <button type="button" class="btn" id="export_csv_btn"
                    style="background-color: #9c27b0; color: white; border-color: #9c27b0; border-radius: 4px 0 0 4px; padding: 8px 15px; margin: 0; border-right: none;">
                    <i class="fa fa-file-text-o"></i> @lang('membership::lang.export_to_csv')
                </button>
                <button type="button" class="btn" id="export_excel_btn"
                    style="background-color: #9c27b0; color: white; border-color: #9c27b0; border-radius: 0; padding: 8px 15px; margin: 0; border-left: 1px solid rgba(255,255,255,0.3); border-right: none;">
                    <i class="fa fa-file-excel-o"></i> @lang('membership::lang.export_to_excel')
                </button>
                <button type="button" class="btn" id="column_visibility_btn"
                    style="background-color: #9c27b0; color: white; border-color: #9c27b0; border-radius: 0; padding: 8px 15px; margin: 0; border-left: 1px solid rgba(255,255,255,0.3); border-right: none;">
                    <i class="fa fa-th"></i> @lang('membership::lang.column_visibility')
                </button>
                <button type="button" class="btn" id="export_pdf_btn"
                    style="background-color: #9c27b0; color: white; border-color: #9c27b0; border-radius: 0; padding: 8px 15px; margin: 0; border-left: 1px solid rgba(255,255,255,0.3); border-right: none;">
                    <i class="fa fa-file-pdf-o"></i> @lang('membership::lang.export_to_pdf')
                </button>
                <button type="button" class="btn" id="print_table_btn"
                    style="background-color: #9c27b0; color: white; border-color: #9c27b0; border-radius: 0 4px 4px 0; padding: 8px 15px; margin: 0; border-left: 1px solid rgba(255,255,255,0.3);">
                    <i class="fa fa-print"></i> @lang('messages.print')
                </button>
            </div>
        </div>
    </div>

    <!-- Members Table -->
    <div class="box box-solid">
        <div class="box-body">
            <!-- Business Name Header -->
            <div class="row">
                <div class="col-md-12 text-center">
                    <h2 style="font-weight: bold; margin-bottom: 20px;">{{ $business_name ?? '' }}</h2>
                </div>
            </div>
            <div class="row no-print" style="margin-top: 20px;">
                <div class="col-md-12 text-right">
                    <button type="button" class="btn btn-primary" id="print_footer_btn">
                        <i class="fa fa-print"></i> @lang('messages.print')
                    </button>
                    @can('add_dividends')
                    <button type="button" class="btn" style="background-color: #e95a0d; color: white;"
                        id="save_print_footer_btn">
                        <i class="fa fa-save"></i> @lang('membership::lang.save_print')
                    </button>
                    @endcan
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="members_dividend_table" style="width: 100%">
                    <thead>
                        <tr>
                            <th>@lang('membership::lang.index_no')</th>
                            <th>@lang('membership::lang.region')</th>
                            <th>@lang('membership::lang.member_code')</th>
                            <th>@lang('membership::lang.member_name')</th>
                            <th>@lang('membership::lang.date_joined')</th>
                            <th>@lang('membership::lang.no_of_shares')</th>
                            <th>@lang('membership::lang.total_share_value')</th>
                            <th>@lang('membership::lang.member_status')</th>
                            <th>@lang('membership::lang.dividend_amount')</th>
                            <th>{{ __('membership::lang.checked_by') }}</th>
                            <th>{{ __('membership::lang.approved_by') }}</th>
                        </tr>
                    </thead>
                    <tbody id="members_dividend_tbody">
                        <tr>
                            <td colspan="11" class="text-center">
                                <p>@lang('membership::lang.use_filters_to_load_members')</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Row 2: Print / Save & Print at top (b) -->
            <div class="no-print" style="display: flex; justify-content: flex-end; align-items: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-primary" id="print_top_btn" style="margin-right: 5px;">
                    <i class="fa fa-print"></i> @lang('messages.print')
                </button>
                @can('add_dividends')
                <button type="button" class="btn" style="background-color: #e95a0d; color: white;"
                    id="save_print_top_btn">
                    <i class="fa fa-save"></i> @lang('membership::lang.save_print')
                </button>
                @endcan
            </div>
        </div>
    </div>

    <!-- Footer Section -->
    <div class="box box-solid footer-section">
        <div class="box-body">
            <div class="row" style="margin-top: 30px;">
                <div class="col-md-4">
                    <div class="form-group">
                        <div class="input-group-append">
                            <br> <br>
                        </div>
                        {!! Form::label('prepared_by', __('membership::lang.prepared_by').':', ['style' =>
                        'margin-bottom: 5px;']) !!}
                        {!! Form::text('prepared_by',$business_user, ['class' => 'form-control signature-line', 'id' =>
                        'prepared_by', 'style' => 'border: none; border-bottom: 2px solid #000; border-radius: 0;
                        background: transparent; padding: 5px 0;']) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {{-- (e) Only users with checked_by permission see the Check button --}}
                        @if(auth()->user()->can('checked_by'))
                        <div class="input-group-append" id="checked_btn_wrapper">
                            <button type="button" class="btn btn-warning" id="mark_as_checked_btn">
                                <i class="fa fa-check-circle"></i> {{ __('Click to mark as Checked') }}
                            </button>
                        </div>
                        @endif

                        {!! Form::label('checked_by', __('membership::lang.checked_by').':', ['style' => 'margin-bottom:
                        5px;']) !!}
                        <div class="input-group">
                            {!! Form::text('checked_by', null, ['class' => 'form-control signature-line', 'id' =>
                            'checked_by', 'style' => 'border: none; border-bottom: 2px solid #000; border-radius: 0;
                            background: transparent; padding: 5px 0;', 'readonly' => 'readonly', 'placeholder' => 'Click
                            button to mark as Checked']) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {{-- (f) Only users with approved_by permission see the Approve button --}}
                        @if(auth()->user()->can('approved_by'))
                        <div class="input-group-append" id="approved_btn_wrapper">
                            <button type="button" class="btn btn-success" id="mark_as_approved_btn">
                                <i class="fa fa-check-double"></i> {{ __('Click to mark as Approved') }}
                            </button>
                        </div>
                        @endif

                        {!! Form::label('approved_by', __('membership::lang.approved_by').':', ['style' =>
                        'margin-bottom: 5px;']) !!}
                        {!! Form::text('approved_by', null, ['class' => 'form-control signature-line', 'id' =>
                        'approved_by', 'style' => 'border: none; border-bottom: 2px solid #000; border-radius: 0;
                        background: transparent; padding: 5px 0;', 'readonly' => 'readonly', 'placeholder' => 'Click
                        button to mark as Approved']) !!}
                    </div>
                </div>
            </div>
            <div class="row footer-text">
                <div class="col-md-6">
                    @php
                    $reports_footer = \App\System::where('key', 'admin_reports_footer')->first();
                    @endphp
                    @if (!empty($reports_footer))
                    <div style="margin-top: 20px;">
                        {{ $reports_footer->value }}
                    </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    <!-- Hidden fields for form submission -->
    {!! Form::hidden('dividend_date', null, ['id' => 'dividend_date_hidden']) !!}
    {!! Form::hidden('reference_number', null, ['id' => 'reference_number_hidden']) !!}
    {!! Form::hidden('notes', null, ['id' => 'notes_hidden']) !!}

    {!! Form::close() !!}
</section>
@endsection

@push('css')
<style>
    /* Signature line styling for normal view */
    .signature-line {
        border: none !important;
        border-bottom: 2px solid #000 !important;
        border-radius: 0 !important;
        background: transparent !important;
        padding: 5px 0 !important;
        box-shadow: none !important;
    }

    .signature-line:focus {
        outline: none !important;
        border-bottom: 2px solid #007bff !important;
    }

    @media print {

        /* Hide unnecessary elements when printing */
        .page-title-area,
        .breadcrumbs-area,
        .box-header,
        #print_btn,
        #save_print_btn,
        #export_csv_btn,
        #export_excel_btn,
        #column_visibility_btn,
        #export_pdf_btn,
        #print_table_btn,
        #print_footer_btn,
        #save_print_footer_btn,
        .dataTables_length,
        .dataTables_filter,
        .dataTables_info,
        .dataTables_paginate,
        .box-tools,
        .btn,
        .no-print {
            display: none !important;
        }

        /* Hide filter section and all of its descendants */
        .filter-box,
        .filter-box *,
        .filter-box .box-body,
        .filter-box .form-group,
        .filter-box .select2,
        .filter-box .select2-container,
        .filter-box input,
        .filter-box select,
        .filter-box label {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            min-height: 0 !important;
            max-height: 0 !important;
            overflow: hidden !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
        }

        /* Show only print content */
        body {
            background: white !important;
            font-size: 12px;
            padding: 0 !important;
            margin: 0 !important;
        }

        .content {
            padding: 0 !important;
        }

        .box {
            border: none !important;
            box-shadow: none !important;
            page-break-inside: avoid;
            margin: 0 !important;
        }

        .box-body {
            padding: 15px !important;
        }

        /* Format table for printing */
        #members_dividend_table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 11px;
            margin: 10px 0;
        }

        #members_dividend_table thead {
            background-color: #f5f5f5 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        #members_dividend_table th,
        #members_dividend_table td {
            border: 1px solid #333 !important;
            padding: 6px 8px !important;
            text-align: left;
        }

        #members_dividend_table th {
            font-weight: bold;
            background-color: #f5f5f5 !important;
        }

        #members_dividend_table .dividend_amount {
            border: none !important;
            background: transparent !important;
            padding: 0 !important;
            width: 100% !important;
            box-shadow: none !important;
        }

        /* Hide rows without dividend amounts when printing */
        #members_dividend_table tbody tr.no-print-row {
            display: none !important;
        }

        /* Business name header */
        h2 {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 15px 0 25px 0;
            page-break-after: avoid;
        }

        /* Footer section */
        .footer-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .footer-section .form-group {
            margin-bottom: 20px;
            display: inline-block;
            width: 30%;
            vertical-align: top;
            margin-right: 3%;
        }

        .footer-section label {
            font-weight: bold;
            margin-bottom: 8px;
            display: block;
            font-size: 12px;
        }

        .footer-section .signature-line,
        .footer-section input.signature-line {
            border-bottom: 2px solid #000 !important;
            border-top: none !important;
            border-left: none !important;
            border-right: none !important;
            background: transparent !important;
            padding: 8px 0 !important;
            width: 100% !important;
            font-size: 12px;
            box-shadow: none !important;
        }

        .footer-section input {
            border-bottom: 2px solid #000 !important;
            border-top: none !important;
            border-left: none !important;
            border-right: none !important;
            background: transparent !important;
            padding: 8px 0 !important;
            width: 100% !important;
            font-size: 12px;
        }

        /* Footer text and page number */
        .footer-text {
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .footer-text .col-md-6:first-child {
            text-align: left;
        }

        .footer-text .col-md-6:last-child {
            text-align: right;
        }

        /* Remove DataTables styling for print */
        .dataTables_wrapper {
            overflow: visible !important;
        }

        /* Ensure table fits on page */
        table {
            page-break-inside: auto;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
    }
</style>
@endpush

@push('javascript')
<script type="text/javascript">
    $(document).ready(function () {
        var membersData = [];
        var storageKey = 'dividend_form_data_{{ request()->session()->get("user.business_id") }}_{{ auth()->id() }}';
        var table;
        var authUserName = "{{ $auth_user_name ?? '' }}";
        var initialMemberCount = {{ !empty($allMembers) ? count($allMembers) : 0 }};
        var canApprove = {{ auth() -> user() -> can('approved_by') ? 'true' : 'false'
    }};
        var autoSaveTimer = null;

    function safeLocalStorageGetItem(key) {
        try {
            if (!window.localStorage) return null;
            return window.localStorage.getItem(key);
        } catch (e) {
            return null;
        }
    }

    function safeLocalStorageSetItem(key, value) {
        try {
            if (!window.localStorage) return;
            window.localStorage.setItem(key, value);
        } catch (e) {
        }
    }

    function safeLocalStorageRemoveItem(key) {
        try {
            if (!window.localStorage) return;
            window.localStorage.removeItem(key);
        } catch (e) {
        }
    }

    // Initialize date pickers
    var dateFormat = typeof moment_date_format !== 'undefined' ? moment_date_format : 'YYYY-MM-DD';
    var defaultDate = moment();

    if (!$('#date').val()) {
        $('#date').val(defaultDate.format(dateFormat));
    }

    $('#date').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        startDate: defaultDate,
        autoUpdateInput: true,
        locale: {
            format: dateFormat,
            cancelLabel: 'Clear'
        }
    }, function (start, end, label) {
        $('#date').val(start.format(dateFormat));
        saveFormData();
    });

    var startOfMonth = moment().startOf('month');
    var endOfMonth = moment().endOf('month');

    $('#date_joined_range').daterangepicker({
        startDate: startOfMonth,
        endDate: endOfMonth,
        ranges: dateRangeSettings.ranges || {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            'Custom Date Range': [moment().startOf('month'), moment().endOf('month')]
        },
        alwaysShowCalendars: true,
        showDropdowns: true,
        autoApply: true,
        opens: 'center',
        locale: {
            format: dateFormat,
            cancelLabel: 'Clear',
            applyLabel: 'Apply',
            fromLabel: 'From',
            toLabel: 'To',
            customRangeLabel: 'Custom Range',
            daysOfWeek: moment.weekdaysMin(),
            monthNames: moment.monthsShort(),
            firstDay: moment.localeData().firstDayOfWeek()
        }
    }, function (start, end, label) {
        if (label === 'Custom Date Range') {
            $('.custom_date_typing_modal').modal('show');
        } else {
            $('#date_joined_range').val(
                start.format(dateFormat) + ' ~ ' + end.format(dateFormat)
            );
            saveFormData();
            filterMembers();
        }
    });

    if (!$('#date_joined_range').val()) {
        $('#date_joined_range').val(
            startOfMonth.format(dateFormat) + ' ~ ' + endOfMonth.format(dateFormat)
        );
    }

    $('#region_id, #membership_status_id').select2({
        placeholder: '@lang("messages.please_select")',
        allowClear: false
    });

    $('#member_id_filter').select2({
        placeholder: '@lang("messages.please_select")',
        allowClear: true,
        minimumInputLength: 0
    });

    function initializeDataTable() {
        if ($.fn.DataTable.isDataTable('#members_dividend_table')) {
            $('#members_dividend_table').DataTable().destroy();
        }

        if (membersData.length === 0) {
            var emptyMessage = initialMemberCount === 0
                ? 'No membership members are configured for this business yet.'
                : '@lang("membership::lang.use_filters_to_load_members")';
            $('#members_dividend_tbody').html('<tr><td colspan="11" class="text-center"><p>' + emptyMessage + '</p></td></tr>');
            return;
        }

        table = $('#members_dividend_table').DataTable({
            data: membersData,
            columns: [
                {
                    data: null,
                    render: function (data, type, row, meta) {
                        return meta.row + 1;
                    }
                },
                { data: 'region', defaultContent: '-' },
                { data: 'member_number', defaultContent: '-' },
                { data: 'member_name', defaultContent: '-' },
                { data: 'date_joined', defaultContent: '-' },
                { data: 'no_of_shares', defaultContent: '0' },
                {
                    data: 'total_share_value',
                    render: function (data) {
                        return parseFloat(data || 0).toFixed(2);
                    },
                    defaultContent: '0.00'
                },
                { data: 'membership_status', defaultContent: '-' },
                {
                    data: null,
                    render: function (data, type, row, meta) {
                        var dataIndex = meta.row;
                        // Find the actual index in membersData if possible
                        var realIndex = membersData.findIndex(function(m) { return m.id === row.id; });
                        if (realIndex !== -1) dataIndex = realIndex;

                        // Internal Logic for inline locking - never locked
                        var isLocked = false;

                        return '<input type="hidden" name="dividends[' + dataIndex + '][member_id]" value="' + (row.id || '') + '" data-member-id="' + (row.id || '') + '">' +
                            '<input type="number" name="dividends[' + dataIndex + '][amount]" class="form-control dividend_amount" ' +
                            'value="' + (row.dividend_amount || '') + '" step="0.01" min="0" placeholder="0.00" style="width: 100%;" data-member-id="' + (row.id || '') + '" ' + (isLocked ? 'disabled' : '') + '>';
                    },
                    orderable: false
                },
                { data: 'checked_by_name', defaultContent: '' },
                { data: 'approved_by_name', defaultContent: '' }
            ],
            order: [[0, 'asc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            dom: 'fBlrtip',
            buttons: [],
            language: {
                emptyTable: initialMemberCount === 0
                    ? 'No membership members are configured for this business yet.'
                    : '@lang("membership::lang.use_filters_to_load_members")'
            }
        });

        $('#members_dividend_table tbody').on('input', '.dividend_amount', function () {
            var memberId = $(this).data('member-id');
            var amount = $(this).val();
            var memberIndex = membersData.findIndex(function (m) { return m.id == memberId; });
            if (memberIndex !== -1) {
                membersData[memberIndex].dividend_amount = amount;
                saveFormData();
            }
        });
    }

    // Form Locking Logic (e.iii & f.ii)
    function applyFormLock() {
        var isChecked = $('#checked_by').val().length > 0;
        var isApproved = $('#approved_by').val().length > 0;

        // Keep form inputs enabled under all conditions
        $('#date, #form_no, #region_id, #member_id_filter, #membership_status_id, #date_joined_range, #prepared_by').prop('disabled', false);
        $('.dividend_amount').prop('disabled', false);
        $('#save_print_footer_btn, #save_print_top_btn, #save_print_btn').prop('disabled', false);

        if (isApproved) {
            $('#checked_btn_wrapper').hide();
            $('#approved_btn_wrapper').hide();
        } else if (isChecked) {
            if (canApprove) {
                $('#checked_btn_wrapper').hide();
                $('#approved_btn_wrapper').show();
            } else {
                $('#checked_btn_wrapper').hide();
                $('#approved_btn_wrapper').hide();
            }
        } else {
            $('#checked_btn_wrapper').show();
            $('#approved_btn_wrapper').hide();
        }
    }

    function validateBatchSelection() {
        if (!$('#date').val()) {
            toastr.error('Please select a Date first.');
            return false;
        }
        return true;
    }

    function checkBatchLockStatus(reinitTable) {
        var dividendDate = $('#date').val();
        var refNo = $('#form_no').val();

        if (!dividendDate) return;

        $.ajax({
            url: '{{ action("\Modules\Membership\Http\Controllers\DividendController@checkLockStatus") }}',
            method: 'GET',
            data: {
                dividend_date: dividendDate,
                reference_number: refNo
            },
            success: function (response) {
                if (response.success) {
                    $('#checked_by').val(response.is_checked ? response.checked_by : '');
                    $('#approved_by').val(response.is_approved ? response.approved_by : '');
                    applyFormLock();
                    if (reinitTable) {
                        initializeDataTable();
                    }
                }
            }
        });
    }

    function loadSavedFormData() {
        var savedData = safeLocalStorageGetItem(storageKey);
        if (savedData) {
            try {
                var data = JSON.parse(savedData);
                if (data.date) $('#date').val(data.date);
                if (data.form_no) $('#form_no').val(data.form_no);
                if (data.date_joined_range) {
                    $('#date_joined_range').val(data.date_joined_range);
                    var dates = data.date_joined_range.split(' ~ ');
                    if (dates.length === 2) {
                        $('#date_joined_range').data('daterangepicker').setStartDate(dates[0]);
                        $('#date_joined_range').data('daterangepicker').setEndDate(dates[1]);
                    }
                }
                if (data.region_id) $('#region_id').val(data.region_id).trigger('change.select2');
                if (data.member_id_filter) $('#member_id_filter').val(data.member_id_filter).trigger('change.select2');
                if (data.membership_status_id) $('#membership_status_id').val(data.membership_status_id).trigger('change.select2');
                if (data.prepared_by) $('#prepared_by').val(data.prepared_by);
                // Do NOT restore checked_by / approved_by from localStorage;
                // server is the single source of truth (checkBatchLockStatus).
                // if (data.checked_by) $('#checked_by').val(data.checked_by);
                // if (data.approved_by) $('#approved_by').val(data.approved_by);
                if (data.membersData && data.membersData.length > 0) {
                    membersData = data.membersData;
                    initializeDataTable();
                    applyFormLock();
                } else {
                    filterMembers();
                }
            } catch (e) {
                console.error('Error loading saved form data:', e);
                filterMembers();
            }
        } else {
            filterMembers();
        }
    }

    function saveFormData() {
        var formData = {
            date: $('#date').val(),
            form_no: $('#form_no').val(),
            date_joined_range: $('#date_joined_range').val(),
            region_id: $('#region_id').val(),
            member_id_filter: $('#member_id_filter').val(),
            membership_status_id: $('#membership_status_id').val(),
            prepared_by: $('#prepared_by').val(),
            checked_by: $('#checked_by').val(),
            approved_by: $('#approved_by').val(),
            membersData: membersData
        };
        safeLocalStorageSetItem(storageKey, JSON.stringify(formData));
    }

    function resetDividendFormState() {
        membersData = [];
        $('#form_no').val('');
        $('#region_id').val('').trigger('change.select2');
        $('#member_id_filter').val('').trigger('change.select2');
        $('#membership_status_id').val('').trigger('change.select2');
        $('#date_joined_range').val('');
        $('#prepared_by').val(@json($business_user ?? ''));
        $('#checked_by').val('');
        $('#approved_by').val('');
        $('#reference_number_hidden, #notes_hidden').val('');
        $('#members_dividend_tbody').html('<tr><td colspan="11" class="text-center"><p>@lang("membership::lang.use_filters_to_load_members")</p></td></tr>');
        if ($.fn.DataTable.isDataTable('#members_dividend_table')) {
            table.clear().draw();
        }
        applyFormLock();
        safeLocalStorageRemoveItem(storageKey);
    }

    function queueSaveFormData() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(saveFormData, 150);
    }

    $('#date, #form_no, #region_id, #member_id_filter, #membership_status_id, #prepared_by').on('change input blur', function () {
        queueSaveFormData();
    });

    $('#date, #form_no').on('change blur', function () {
        checkBatchLockStatus(true);
    });

    $('#checked_by, #approved_by').on('change input', function () {
        saveFormData();
    });

    $('#region_id, #member_id_filter, #membership_status_id').on('change', function () {
        filterMembers();
    });

    $(window).on('beforeunload pagehide', function () {
        saveFormData();
    });

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            saveFormData();
        }
    });

    function filterMembers() {
        // Lock guard is now handled exclusively by checkBatchLockStatus;
        // no early-return here so that data always reloads from server.
        // var isChecked = $('#checked_by').val();
        // var isApproved = $('#approved_by').val();
        // if (isApproved || (isChecked && !canApprove)) return;

        var dateRange = $('#date_joined_range').val();
        var regionId = $('#region_id').val();
        var memberId = $('#member_id_filter').val();
        var membershipStatusId = $('#membership_status_id').val();

        var dateStart = null, dateEnd = null;
        if (dateRange) {
            var dates = dateRange.split(' ~ ');
            if (dates.length === 2) {
                dateStart = dates[0].trim();
                dateEnd = dates[1].trim();
            }
        }

        $.ajax({
            url: '{{ action("\Modules\Membership\Http\Controllers\DividendController@getFilteredMembers") }}',
            method: 'GET',
            data: {
                date_joined_start: dateStart,
                date_joined_end: dateEnd,
                region_id: regionId ? regionId : null,
                member_id: memberId ? memberId : null,
                membership_status_id: membershipStatusId ? membershipStatusId : null,
                dividend_date: $('#date').val(),
                reference_number: $('#form_no').val()
            },
            success: function (response) {
                if (response.success) {
                    var existingDividends = {};
                    membersData.forEach(function (member) {
                        if (member.dividend_amount) {
                            existingDividends[member.id] = member.dividend_amount;
                        }
                    });

                    membersData = response.members.map(function (member) {
                        return {
                            ...member,
                            dividend_amount: existingDividends[member.id] !== undefined ? existingDividends[member.id] : (member.dividend_amount || '')
                        };
                    });

                    initializeDataTable();
                    saveFormData();
                }
            }
        });
    }

    $('#export_csv_btn, #export_excel_btn').on('click', function () {
        var headers = ['Index No', 'Region', 'Member Code', 'Member Name', 'Date Joined', 'No of Shares', 'Total Share Value', 'Member Status', 'Dividend Amount'];
        var csv = headers.join(',') + '\n';
        table.rows().every(function () {
            var row = this.data();
            csv += [this.index() + 1, row.region, row.member_number, row.member_name, row.date_joined, row.no_of_shares, row.total_share_value, row.membership_status, row.dividend_amount].join(',') + '\n';
        });
        var link = document.createElement("a");
        link.setAttribute("href", "data:text/csv;charset=utf-8," + encodeURIComponent(csv));
        link.setAttribute("download", "dividends.csv");
        link.click();
    });

    function printFormattedPage() {
        $('.filter-box').addClass('no-print');
        $('#members_dividend_table tbody tr').each(function () {
            var amount = parseFloat($(this).find('.dividend_amount').val());
            if (!amount || amount <= 0) $(this).addClass('no-print-row');
        });
        window.print();
        setTimeout(function () {
            $('.no-print-row').removeClass('no-print-row');
            $('.filter-box').addClass('no-print');
        }, 500);
    }

    $('#export_pdf_btn, #print_table_btn, #print_footer_btn, #print_top_btn').on('click', function () {
        printFormattedPage();
    });

    var isSubmitting = false;
    $('#save_print_footer_btn, #save_print_top_btn, #save_print_btn').on('click', function (e) {
        e.preventDefault();
        if (!isSubmitting) $('#dividend_form').submit();
    });

    $('#dividend_form').on('submit', function (e) {
        e.preventDefault();
        if (isSubmitting) return;

        var dividends = [];
        membersData.forEach(function (m) {
            if (parseFloat(m.dividend_amount) > 0) {
                dividends.push({ member_id: m.id, amount: m.dividend_amount });
            }
        });
        var savedMemberIds = dividends.map(function (d) {
            return String(d.member_id);
        });

        if (dividends.length === 0) {
            toastr.error('@lang("membership::lang.please_enter_at_least_one_dividend_amount")');
            return;
        }

        isSubmitting = true;
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: {
                dividend_date: $('#date').val(),
                reference_number: $('#form_no').val(),
                dividends: dividends,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.msg);
                    printFormattedPage();
                    setTimeout(function () {
                        resetDividendFormState();
                        isSubmitting = false;
                    }, 400);
                } else {
                    toastr.error(response.msg);
                    isSubmitting = false;
                }
            },
            error: function (xhr) { 
                isSubmitting = false; 
                var msg = '@lang("messages.something_went_wrong")';
                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    msg = xhr.responseJSON.msg;
                }
                toastr.error(msg); 
            }
        });
    });

    $('#mark_as_checked_btn').on('click', function () {
        if (!validateBatchSelection()) {
            return;
        }

        var dividends = [];
        membersData.forEach(function (m) {
            if (parseFloat(m.dividend_amount) > 0) {
                dividends.push({ member_id: m.id, amount: m.dividend_amount });
            }
        });

        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: '{{ action("\\Modules\\Membership\\Http\\Controllers\\DividendController@markAsChecked") }}',
            method: 'POST',
            data: {
                dividend_date: $('#date').val(),
                reference_number: $('#form_no').val(),
                dividends: dividends,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                $btn.prop('disabled', false);
                if (response.success) {
                    var checkedByName = response.checked_by || response.auth_user_name || '';
                    $('#checked_by').val(checkedByName);

                    // Update the checked_by_name in membersData
                    membersData.forEach(function(member) {
                        member.checked_by_name = checkedByName;
                    });

                    applyFormLock();
                    initializeDataTable();
                    saveFormData();
                    toastr.success(response.msg);
                } else {
                    toastr.error(response.msg);
                }
            },
            error: function (xhr) {
                $btn.prop('disabled', false);
                var msg = 'Something went wrong';
                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    msg = xhr.responseJSON.msg;
                }
                toastr.error(msg);
            }
        });
    });

    $('#mark_as_approved_btn').on('click', function () {
        if (!validateBatchSelection()) {
            return;
        }
        if (!$('#checked_by').val()) {
            toastr.error('Please mark this batch as Checked before approval.');
            return;
        }
        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: '{{ action("\\Modules\\Membership\\Http\\Controllers\\DividendController@markAsApproved") }}',
            method: 'POST',
            data: {
                dividend_date: $('#date').val(),
                reference_number: $('#form_no').val(),
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                $btn.prop('disabled', false);
                if (response.success) {
                    var approvedByName = response.approved_by || response.auth_user_name || '';
                    $('#approved_by').val(approvedByName);

                    // Update the approved_by_name in membersData and re-init table
                    membersData.forEach(function(member) {
                        member.approved_by_name = approvedByName;
                    });

                    applyFormLock();
                    initializeDataTable();
                    saveFormData();
                    toastr.success(response.msg);
                } else {
                    toastr.error(response.msg);
                }
            },
            error: function (xhr) {
                $btn.prop('disabled', false);
                var msg = 'Something went wrong';
                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    msg = xhr.responseJSON.msg;
                }
                toastr.error(msg);
            }
        });
    });

    loadSavedFormData();
    checkBatchLockStatus(true);
        });
</script>
@endpush
