@include('pumperdashboard::partials.pumper_dashboard_ui_standard')
<style>
    .is1604-shift-status-size,
    #closing_shift_id,
    #closing_shift_id option,
    #closing_shift .select2-selection__rendered {
        font-size: 120% !important;
        font-weight: 700 !important;
    }
    .is1637-shift-status-badge {
        display: inline-block;
        margin-top: 8px;
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 120% !important;
        font-weight: 800 !important;
        line-height: 1.2;
    }
    .is1637-shift-status-open { background: #fff7ed; color: #c2410c; border: 1px solid #fdba74; }
    .is1637-shift-status-closed { background: #ecfdf5; color: #047857; border: 1px solid #6ee7b7; }
    /* MA-002: the shortage / excess banner. */
    .pd-balance-banner {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 20px;
        margin-bottom: 14px;
        border-radius: 8px;
        border: 2px solid transparent;
        font-family: "Segoe UI", Arial, sans-serif;
    }
    .pd-balance-label {
        font-size: 15px;
        font-weight: 600;
        letter-spacing: .3px;
    }
    .pd-balance-value {
        font-size: 32px;
        font-weight: 700;
        margin-left: auto;
    }
    .pd-balance-note {
        font-size: 14px;
        font-weight: 600;
        min-width: 90px;
        text-align: right;
    }
    /*
        MA-002: shortage AND excess both shown in red.

        An excess was previously green. You asked for both prominent and in
        red - a difference either way is something the operator must see and
        account for, not a reward.

        Only the balanced state stays grey.
    */
    .pd-balance-neutral { background: #f1f3f5; border-color: #ced4da; color: #495057; }
    .pd-balance-short,
    .pd-balance-excess  { background: #fdecea; border-color: #d32f2f; color: #d32f2f; }
    .pd-balance-short .pd-balance-value,
    .pd-balance-excess .pd-balance-value,
    .pd-balance-short .pd-balance-note,
    .pd-balance-excess .pd-balance-note { color: #d32f2f; }

    #pump_operators_closing_shift_table tfoot .footer-total td,
    #pump_operators_closing_shift_table tfoot .footer-total td *,
    #pump_operators_closing_shift_table tfoot .footer-total strong {
        font-size: 20px !important;
        font-weight: 800 !important;
        line-height: 1.35 !important;
    }

    /* IS1804: Close Shift table professional layout.
       Scoped only to this page to avoid changing any other module table. */
    .pd-closing-shift-page {
        --pd-cs-primary: #0ea5b7;
        --pd-cs-primary-dark: #087f90;
        --pd-cs-heading: #35597d;
        --pd-cs-text: #13263f;
        --pd-cs-muted: #6b7f93;
        --pd-cs-border: #d7e3ef;
        --pd-cs-soft-border: #e8eff6;
        --pd-cs-header-bg: #f3f7fb;
        --pd-cs-hover: #f5fbff;
    }

    .pd-closing-shift-page .pd-closing-shift-table-card {
        width: 100%;
        margin-top: 8px;
        padding: 14px;
        background: #fff;
        border: 1px solid #e2ebf4;
        border-radius: 16px;
        box-shadow: 0 8px 24px rgba(15, 43, 72, .07);
    }

    .pd-closing-shift-page .pd-closing-shift-scroll {
        width: 100%;
        overflow-x: auto !important;
        overflow-y: visible !important;
        border: 1px solid var(--pd-cs-border);
        border-radius: 12px;
        background: #fff;
        -webkit-overflow-scrolling: touch;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table,
    .pd-closing-shift-page #pump_operators_closing_shift_table.dataTable {
        width: 100% !important;
        min-width: 1540px !important;
        margin: 0 !important;
        table-layout: auto !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
        background: #fff;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table thead th {
        height: 58px;
        padding: 12px 14px !important;
        vertical-align: middle !important;
        color: var(--pd-cs-heading) !important;
        background: var(--pd-cs-header-bg) !important;
        border-top: 0 !important;
        border-right: 1px solid var(--pd-cs-soft-border) !important;
        border-bottom: 1px solid var(--pd-cs-border) !important;
        font-size: 15px !important;
        font-weight: 800 !important;
        line-height: 1.25 !important;
        letter-spacing: .01em;
        text-align: center !important;
        white-space: nowrap !important;
        word-break: normal !important;
        overflow-wrap: normal !important;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table thead th:last-child {
        border-right: 0 !important;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table tbody td {
        min-height: 54px;
        padding: 13px 14px !important;
        vertical-align: middle !important;
        color: var(--pd-cs-text) !important;
        background: #fff !important;
        border-top: 0 !important;
        border-right: 1px solid var(--pd-cs-soft-border) !important;
        border-bottom: 1px solid var(--pd-cs-soft-border) !important;
        font-size: 15px !important;
        font-weight: 500 !important;
        line-height: 1.35 !important;
        white-space: nowrap !important;
        word-break: normal !important;
        overflow-wrap: normal !important;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table tbody td:last-child {
        border-right: 0 !important;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table tbody tr:last-child td {
        border-bottom: 0 !important;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table tbody tr:hover td {
        background: var(--pd-cs-hover) !important;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table tfoot td {
        padding: 13px 14px !important;
        vertical-align: middle !important;
        background: #eef4fa !important;
        border-top: 1px solid var(--pd-cs-border) !important;
        border-right: 1px solid var(--pd-cs-soft-border) !important;
        color: var(--pd-cs-text) !important;
        white-space: nowrap !important;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table tfoot td:last-child {
        border-right: 0 !important;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table .pd-cs-action {
        min-width: 125px !important;
        width: 125px !important;
        text-align: center !important;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table .pd-cs-date {
        min-width: 125px !important;
        width: 125px !important;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table .pd-cs-location {
        min-width: 175px !important;
        width: 175px !important;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table .pd-cs-time {
        min-width: 110px !important;
        width: 110px !important;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table .pd-cs-operator {
        min-width: 170px !important;
        width: 170px !important;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table .pd-cs-shift {
        min-width: 100px !important;
        width: 100px !important;
        text-align: center !important;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table .pd-cs-pump {
        min-width: 130px !important;
        width: 130px !important;
        text-align: center !important;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table .pd-cs-meter {
        min-width: 125px !important;
        width: 125px !important;
        text-align: right !important;
        font-variant-numeric: tabular-nums;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table .pd-cs-qty {
        min-width: 110px !important;
        width: 110px !important;
        text-align: right !important;
        font-variant-numeric: tabular-nums;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table .pd-cs-amount {
        min-width: 145px !important;
        width: 145px !important;
        text-align: right !important;
        font-variant-numeric: tabular-nums;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table .btn-group {
        display: inline-flex !important;
        vertical-align: middle !important;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table .btn-group > .btn {
        min-width: 104px;
        min-height: 38px;
        padding: 8px 13px !important;
        color: #fff !important;
        background: linear-gradient(180deg, var(--pd-cs-primary), var(--pd-cs-primary-dark)) !important;
        border: 0 !important;
        border-radius: 10px !important;
        box-shadow: 0 4px 12px rgba(14, 165, 183, .20) !important;
        font-size: 14px !important;
        font-weight: 800 !important;
        line-height: 1.1 !important;
        white-space: nowrap !important;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table .dropdown-menu {
        min-width: 190px;
        padding: 7px;
        border: 1px solid #d7e3ef;
        border-radius: 12px;
        box-shadow: 0 12px 28px rgba(15, 43, 72, .16);
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table .dropdown-menu > li > a {
        display: block;
        width: 100%;
        padding: 9px 11px !important;
        color: var(--pd-cs-text) !important;
        background: transparent !important;
        border: 0 !important;
        border-radius: 8px;
        text-align: left;
        font-size: 14px !important;
        font-weight: 600 !important;
        white-space: nowrap !important;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table .dropdown-menu > li > a:hover {
        background: #f1f7fc !important;
    }

    /* DataTables controls are kept compact and aligned. */
    .pd-closing-shift-page #pump_operators_closing_shift_table_wrapper .dt-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin: 0 0 14px !important;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table_wrapper .dt-buttons .btn,
    .pd-closing-shift-page #pump_operators_closing_shift_table_wrapper .dt-buttons button {
        min-height: 40px !important;
        padding: 8px 13px !important;
        border-radius: 9px !important;
        font-size: 13px !important;
        font-weight: 700 !important;
        line-height: 1.15 !important;
        white-space: nowrap !important;
        box-shadow: 0 4px 12px rgba(15, 43, 72, .10) !important;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table_wrapper .dataTables_filter,
    .pd-closing-shift-page #pump_operators_closing_shift_table_wrapper .dataTables_length {
        margin: 0 0 14px !important;
        color: var(--pd-cs-muted);
        font-size: 14px;
        font-weight: 600;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table_wrapper .dataTables_filter input {
        width: 260px !important;
        max-width: 100%;
        min-height: 40px;
        margin-left: 8px;
        padding: 8px 12px;
        color: var(--pd-cs-text);
        background: #fff;
        border: 1px solid #cbd9e7;
        border-radius: 10px;
        outline: none;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table_wrapper .dataTables_length select {
        min-width: 74px;
        min-height: 40px;
        margin: 0 6px;
        padding: 7px 28px 7px 10px;
        background: #fff;
        border: 1px solid #cbd9e7;
        border-radius: 10px;
    }

    .pd-closing-shift-page #pump_operators_closing_shift_table_wrapper .dataTables_info,
    .pd-closing-shift-page #pump_operators_closing_shift_table_wrapper .dataTables_paginate {
        margin-top: 14px !important;
        color: var(--pd-cs-muted);
        font-size: 14px;
    }

    @media (max-width: 767px) {
        .pd-closing-shift-page .pd-closing-shift-table-card {
            padding: 8px;
            border-radius: 12px;
        }

        .pd-closing-shift-page #pump_operators_closing_shift_table_wrapper .dataTables_filter,
        .pd-closing-shift-page #pump_operators_closing_shift_table_wrapper .dataTables_length {
            float: none !important;
            width: 100%;
            text-align: left !important;
        }

        .pd-closing-shift-page #pump_operators_closing_shift_table_wrapper .dataTables_filter input {
            width: calc(100% - 70px) !important;
        }
    }
</style>

                

<!-- Main content -->
<section class="content pd-closing-shift-page">
    
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
               
            <div class="row">
                 <div class="col-md-4 px-4" style="margin-left: 35px;">
                    <div class="form-group">
                       {!! Form::label('shift_id',  __('pumperdashboard::lang.shift') . ':') !!}
                        <select class="form-control select2" style="width:100%" id="closing_shift_id">
                            @foreach($shifts as $shift)
                                {{-- @php
                                    // status 0 = Open, 2 = Closed (anything else fallback "Unknown")
                                    $statusText = $shift->status == 0 ? 'Open' : ($shift->status == 2 ? 'Closed' : 'Unknown');
                                @endphp --}}
                                @php
                                    // status 2 = Closed, others = Open
                                    $isClosed = ((string) $shift->status === '2' || strtolower((string) $shift->status) === 'closed');
                                    $statusText = $isClosed ? 'Shift Closed' : 'Shift Open';
                                    $displayShiftNumber = $shift->assignment_shift_number ?? $shift->shift_number ?? $shift->id;
                                @endphp
                                <option 
                                    value="{{ $shift->id }}" 
                                    data-status="{{ $shift->status }}" 
                                    data-name="{{ $shift->name }}"
                                >
                                    {{ $shift->name }} - Shift {{ $displayShiftNumber }} ({{ $statusText }})
                                </option>
                            @endforeach
                        </select>
                        <div id="closing_shift_status_badge" class="is1637-shift-status-badge"></div>

                    </div>
                </div>
                
            </div>
                
                
            @endcomponent
        </div>
    </div>
    
    <div class="row" id="closing_shift_summary">
        
    </div>

    @if(empty(auth()->user()->pump_operator_id))
    {{-- <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('close_shift_location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('close_shift_location_id', $business_locations, null, ['class' => 'form-control
                    select2',
                    'placeholder' => __('pumperdashboard::lang.all'), 'id' => 'close_shift_location_id', 'style' =>
                    'width:100%']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('close_shift_pump_operators', __('pumperdashboard::lang.pump_operator') . ':') !!}
                    {!! Form::select('close_shift_pump_operators', $pump_operators, null, ['class' => 'form-control
                    select2', 'placeholder'
                    => __('pumperdashboard::lang.all'), 'id' => 'close_shift_pump_operators', 'style' => 'width:100%']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('pumps', __('pumperdashboard::lang.pumps') . ':') !!}
                    {!! Form::select('close_shift_pumps', $pumps->pluck('pump_name', 'id'), null, ['class' =>
                    'form-control select2', 'placeholder'
                    => __('pumperdashboard::lang.all'), 'id' => 'close_shift_pumps', 'style' => 'width:100%']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('close_shift_payment_method', __('pumperdashboard::lang.payment_method') . ':') !!}
                    {!! Form::select('close_shift_payment_method', $payment_types, null, ['class' => 'form-control
                    select2',
                    'placeholder'
                    => __('pumperdashboard::lang.all'), 'id' => 'close_shift_payment_method', 'style' => 'width:100%']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('close_shift_date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('close_shift_date_range', @format_date('first day of this month') . ' ~ ' .
                    @format_date('last
                    day of this month') , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' =>
                    'form-control', 'id' => 'close_shift_date_range', 'readonly']); !!}
                </div>
            </div>
            @endcomponent
        </div>
    </div> --}}
    @endif

    @component('components.widget', ['class' => 'box-primary', 'title' =>
    __('pumperdashboard::lang.all_your_daily_collection')])
    {{--
        MA-002: shortage or excess, shown prominently.

        The figure already existed, but only as the last column of a wide
        table and its footer total - easy to miss entirely.

        This banner sits above the table and shows the same number, taken
        from the same footer total, so there is ONE calculation and the two
        can never disagree. It fills in when the table draws.

        Colour carries the meaning at a glance: red for a shortage, green for
        an excess, grey when the shift balances.
    --}}
    <div id="cs_balance_banner" class="pd-balance-banner pd-balance-neutral">
        <span class="pd-balance-label">Shortage / Excess</span>
        <span class="pd-balance-value" id="cs_balance_value">0.00</span>
        <span class="pd-balance-note" id="cs_balance_note">Balanced</span>
    </div>

    <div class="pd-closing-shift-table-card">
        <div class="table-responsive pd-closing-shift-scroll">
        <table class="table table-bordered table-striped" id="pump_operators_closing_shift_table" style="width: 100%;">
            <thead>
                <tr>
                    <th class="notexport">@lang('messages.action')</th>
                    <th>@lang('pumperdashboard::lang.date')</th>
                    <th>@lang('pumperdashboard::lang.location')</th>
                    {{-- Time column removed: it now shows beneath the Date. --}}
                    <th>@lang('pumperdashboard::lang.pump_operator')</th>
                    <th>@lang('pumperdashboard::lang.shift_number')</th>
                    <th>@lang('pumperdashboard::lang.pump_no')</th>
                    <th>@lang('pumperdashboard::lang.starting_meter')</th>
                    <th>@lang('pumperdashboard::lang.closing_meter')</th>
                    <th>@lang('pumperdashboard::lang.test_qty')</th>
                    <th>@lang('pumperdashboard::lang.sold_ltr')</th>
                    <th>@lang('pumperdashboard::lang.amount')</th>
                    <th>@lang('pumperdashboard::lang.short_amount')</th>

                </tr>
            </thead>

            <tfoot>
                <tr class="bg-gray font-17 footer-total">
                    <td colspan="9" class="text-right"><strong>@lang('sale.total'):</strong></td>
                    <td><span class="display_currency" id="footer_cs_testing_ltr" data-currency_symbol="false"></span></td>
                    <td><span class="display_currency" id="footer_cs_sold_ltr" data-currency_symbol="false"></span></td>
                    <td><span class="display_currency" id="footer_cs_sold_amount" data-currency_symbol="true"></span></td>
                    <td><span class="display_currency" id="footer_cs_short_amount" data-currency_symbol="true"></span></td>

                </tr>
            </tfoot>
        </table>
        </div>
    </div>
    @endcomponent

</section>
<!-- /.content -->

<script>
(function($){
    function refreshClosingShiftStatusBadge(){
        var $select = $('#closing_shift_id');
        var $badge = $('#closing_shift_status_badge');
        if (!$select.length || !$badge.length) { return; }
        var $opt = $select.find('option:selected');
        var status = ($opt.data('status') || '').toString().toLowerCase();
        var isClosed = status === '2' || status === 'closed';
        $badge
            .removeClass('is1637-shift-status-open is1637-shift-status-closed')
            .addClass(isClosed ? 'is1637-shift-status-closed' : 'is1637-shift-status-open')
            .text(isClosed ? 'Shift Closed' : 'Shift Open');
    }
    $(document).ready(function(){
        refreshClosingShiftStatusBadge();
        $(document).on('change', '#closing_shift_id', refreshClosingShiftStatusBadge);
    });
})(jQuery);
</script>





