@include('petropd::partials.pumper_dashboard_design_tweaks')
<style>
    .pd-shift-status-size-fix,
    #closing_shift_id,
    #closing_shift_id option,
    #closing_shift .select2-selection__rendered {
        font-size: 120% !important;
        font-weight: 700 !important;
    }

    #closing_shift .petropd-close-shift-filter-row {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        margin-left: -8px;
        margin-right: -8px;
    }

    #closing_shift .petropd-close-shift-filter-col {
        padding-left: 8px;
        padding-right: 8px;
    }

    #closing_shift .form-group {
        margin-bottom: 8px;
    }

    #closing_shift .select2-container,
    #closing_shift_date_range {
        width: 100% !important;
    }

    @media (max-width: 991px) {
        #closing_shift .petropd-close-shift-filter-col {
            margin-bottom: 8px;
        }
    }

    /* Petro PD Close Shift: stable class-based table layout.
       The Location column is hidden by DataTables, therefore named classes
       are used instead of nth-child selectors. */
    #close_shift .table-responsive,
    #closing_shift .table-responsive {
        display: block !important;
        width: 100% !important;
        max-width: 100% !important;
        overflow-x: auto !important;
        overflow-y: visible !important;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 8px;
    }

    #pump_operators_closing_shift_table,
    #pump_operators_closing_shift_table_wrapper .dataTables_scrollHead table,
    #pump_operators_closing_shift_table_wrapper .dataTables_scrollBody table,
    #pump_operators_closing_shift_table_wrapper .dataTables_scrollFoot table {
        width: 100% !important;
        min-width: 1130px !important;
        table-layout: fixed !important;
    }

    #pump_operators_closing_shift_table_wrapper,
    #pump_operators_closing_shift_table_wrapper .dataTables_scroll,
    #pump_operators_closing_shift_table_wrapper .dataTables_scrollHead,
    #pump_operators_closing_shift_table_wrapper .dataTables_scrollBody,
    #pump_operators_closing_shift_table_wrapper .dataTables_scrollFoot {
        width: 100% !important;
        max-width: 100% !important;
    }

    #pump_operators_closing_shift_table thead th,
    #pump_operators_closing_shift_table tbody td,
    #pump_operators_closing_shift_table tfoot td {
        vertical-align: middle !important;
        box-sizing: border-box !important;
        word-break: normal !important;
        overflow-wrap: normal !important;
    }

    #pump_operators_closing_shift_table thead th {
        padding: 10px 7px !important;
        text-align: center !important;
        line-height: 1.12 !important;
        white-space: normal !important;
    }

    #pump_operators_closing_shift_table tbody td,
    #pump_operators_closing_shift_table tfoot td {
        padding: 9px 7px !important;
        line-height: 1.25 !important;
        white-space: nowrap !important;
    }

    #pump_operators_closing_shift_table .pd-cs-action {
        width: 100px !important;
        min-width: 100px !important;
        max-width: 100px !important;
        text-align: center !important;
    }

    #pump_operators_closing_shift_table .pd-cs-date {
        width: 112px !important;
        min-width: 112px !important;
        max-width: 112px !important;
    }

    #pump_operators_closing_shift_table .pd-cs-location {
        width: 0 !important;
        min-width: 0 !important;
        max-width: 0 !important;
        padding: 0 !important;
    }

    #pump_operators_closing_shift_table .pd-cs-time {
        width: 98px !important;
        min-width: 98px !important;
        max-width: 98px !important;
    }

    #pump_operators_closing_shift_table .pd-cs-operator {
        width: 135px !important;
        min-width: 135px !important;
        max-width: 135px !important;
    }

    #pump_operators_closing_shift_table .pd-cs-shift {
        width: 70px !important;
        min-width: 70px !important;
        max-width: 70px !important;
        padding-left: 4px !important;
        padding-right: 4px !important;
        text-align: center !important;
    }

    #pump_operators_closing_shift_table .pd-cs-pump {
        width: 190px !important;
        min-width: 190px !important;
        max-width: 190px !important;
        padding-left: 8px !important;
        padding-right: 8px !important;
        overflow: hidden !important;
        text-align: center !important;
    }

    #pump_operators_closing_shift_table .pd-cs-test {
        width: 70px !important;
        min-width: 70px !important;
        max-width: 70px !important;
        padding-left: 4px !important;
        padding-right: 4px !important;
        text-align: right !important;
    }

    #pump_operators_closing_shift_table .pd-cs-sold {
        width: 85px !important;
        min-width: 85px !important;
        max-width: 85px !important;
        text-align: right !important;
    }

    #pump_operators_closing_shift_table .pd-cs-amount {
        width: 135px !important;
        min-width: 135px !important;
        max-width: 135px !important;
        text-align: right !important;
    }

    #pump_operators_closing_shift_table .pd-cs-short {
        width: 135px !important;
        min-width: 135px !important;
        max-width: 135px !important;
        text-align: right !important;
    }

    #pump_operators_closing_shift_table .pd-close-shift-two-line-heading {
        display: inline-block;
        max-width: 100%;
        text-align: center;
        line-height: 1.08;
        white-space: normal !important;
    }

    #pump_operators_closing_shift_table .pd-close-shift-pump-cell {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr);
        justify-items: center !important;
        align-items: center !important;
        width: 100%;
        max-width: 100%;
        min-width: 0;
        gap: 6px;
        overflow: hidden;
        text-align: center !important;
    }

    #pump_operators_closing_shift_table .pd-close-shift-pump-number {
        display: block;
        width: 100%;
        max-width: 100%;
        margin: 0;
        overflow: hidden;
        font-weight: 700;
        text-align: center;
        text-overflow: ellipsis;
        white-space: nowrap !important;
        word-break: normal !important;
        overflow-wrap: normal !important;
    }

    #pump_operators_closing_shift_table td.pd-cs-action .btn-group,
    #pump_operators_closing_shift_table td.pd-cs-action .dropdown-toggle {
        width: auto !important;
        min-width: 0 !important;
        max-width: 100% !important;
        padding-left: 8px !important;
        padding-right: 8px !important;
        box-sizing: border-box !important;
        white-space: nowrap !important;
    }

    #pump_operators_closing_shift_table td.pd-cs-pump .pd-meter-details-button,
    #pump_operators_closing_shift_table .pd-meter-details-button {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        justify-self: center !important;
        width: max-content !important;
        max-width: calc(100% - 4px) !important;
        min-width: 0 !important;
        min-height: 30px;
        margin-left: auto !important;
        margin-right: auto !important;
        padding: 5px 7px !important;
        overflow: hidden;
        border-radius: 15px;
        box-sizing: border-box;
        font-size: 11px;
        font-weight: 700;
        line-height: 1.2;
        text-align: center !important;
        text-overflow: ellipsis;
        white-space: nowrap !important;
    }

    #pump_operators_closing_shift_table tfoot .footer-total td {
        font-size: 14px !important;
        line-height: 1.2 !important;
        white-space: nowrap !important;
    }

    #close_shift_meter_details_modal .modal-dialog {
        max-width: 700px;
    }

    #close_shift_meter_details_modal .modal-header {
        background: #f5f8fc;
        border-bottom: 1px solid #dce5ef;
    }

    #close_shift_meter_details_modal .pd-meter-details-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 18px 36px;
        margin-bottom: 18px;
        padding: 12px 14px;
        border: 1px solid #dce5ef;
        border-radius: 7px;
        background: #f9fbfd;
        font-size: 15px;
    }

    #close_shift_meter_details_modal .pd-meter-details-meta strong {
        color: #23364d;
    }

    #close_shift_meter_details_modal .pd-meter-details-table {
        margin-bottom: 0;
    }

    #close_shift_meter_details_modal .pd-meter-details-table th,
    #close_shift_meter_details_modal .pd-meter-details-table td {
        padding: 12px;
        text-align: center;
        vertical-align: middle;
    }

    #close_shift_meter_details_modal .pd-meter-details-table th {
        background: #f5f8fc;
        font-weight: 700;
    }
</style>
@php


    $date = date('Y-m-d');
    $pump_operator_id = Auth::user()->pump_operator_id;
    $open_meters = \Modules\PetroPD\Entities\PumpOperatorAssignment::where('pump_operator_id',$pump_operator_id)
                        ->whereDate('date_and_time',$date)
                        ->where('is_manually_closed','0')
                        ->count();
@endphp
                

<!-- Main content -->
<section class="content">
    @component('components.filters', ['title' => __('report.filters'), 'id' => 'closing_shift'])
        @if(empty(auth()->user()->pump_operator_id))
            <div class="row petropd-close-shift-filter-row">
                <div class="col-md-4 col-sm-12 petropd-close-shift-filter-col">
                    <div class="form-group">
                        {!! Form::label('close_shift_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text(
                            'close_shift_date_range',
                            @format_date(date('Y-m-d')) . ' - ' . @format_date(date('Y-m-d')),
                            [
                                'class' => 'form-control',
                                'id' => 'close_shift_date_range',
                                'placeholder' => __('lang_v1.select_a_date_range'),
                                'readonly' => true,
                            ]
                        ) !!}
                    </div>
                </div>

                <div class="col-md-4 col-sm-12 petropd-close-shift-filter-col">
                    <div class="form-group">
                        {!! Form::label('close_shift_pump_operator_id', __('petropd::lang.pump_operator') . ':') !!}
                        <select class="form-control select2" id="close_shift_pump_operator_id" style="width:100%;">
                            <option value="">@lang('petropd::lang.all')</option>
                            @foreach(($close_shift_active_pump_operators ?? collect()) as $operatorId => $operatorName)
                                <option value="{{ $operatorId }}">{{ $operatorName }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-4 col-sm-12 petropd-close-shift-filter-col">
                    <div class="form-group">
                        {!! Form::label('closing_shift_id', __('petropd::lang.shift_number') . ':') !!}
                        <select class="form-control select2" id="closing_shift_id" data-pd-status-size="1" style="width:100%;">
                            <option value="">@lang('petropd::lang.all')</option>
                        </select>
                    </div>
                </div>
            </div>
        @else
            <div class="row">
                <div class="col-md-4 px-4" style="margin-left:35px;">
                    <div class="form-group">
                        {!! Form::label('closing_shift_id', __('petropd::lang.shift') . ':') !!}
                        <select class="form-control select2" style="width:100%" id="closing_shift_id" data-pd-status-size="1">
                            @foreach($shifts as $shift)
                                @php
                                    $statusValue = strtolower(trim((string) ($shift->status ?? '')));
                                    $isClosed = ((int) ($shift->status ?? 0) === 2) || in_array($statusValue, ['closed', 'close', 'shift closed'], true);
                                    $statusText = $isClosed ? 'Shift Closed' : 'Shift Open';
                                    $displayShiftNumber = $shift->assignment_shift_number
                                        ?? $shift->shift_number
                                        ?? $shift->shift_no
                                        ?? $shift->id;
                                @endphp
                                <option
                                    value="{{ $shift->id }}"
                                    data-status="{{ $shift->status }}"
                                    data-name="{{ $shift->name }}"
                                    style="font-size:120%;font-weight:700;"
                                >
                                    {{ $shift->name }} - Shift {{ $displayShiftNumber }} ({{ $statusText }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        @endif
    @endcomponent

    <div class="row" id="closing_shift_summary"></div>

    @component('components.widget', ['class' => 'box-primary', 'title' =>
    __('petropd::lang.all_your_daily_collection')])
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="pump_operators_closing_shift_table" style="width: 100%;">
            <thead>
                <tr>
                    <th class="notexport pd-cs-action">@lang('messages.action')</th>
                    <th class="pd-cs-date">@lang('petropd::lang.date')</th>
                    <th class="pd-cs-location">@lang('petropd::lang.location')</th>
                    <th class="pd-cs-time">@lang('petropd::lang.time')</th>
                    <th class="pd-cs-operator"><span class="pd-close-shift-two-line-heading">{!! str_replace(' ', '<br>', e(__('petropd::lang.pump_operator'))) !!}</span></th>
                    <th class="pd-cs-shift"><span class="pd-close-shift-two-line-heading">{!! str_replace(' ', '<br>', e(__('petropd::lang.shift_number'))) !!}</span></th>
                    <th class="pd-cs-pump"><span class="pd-close-shift-two-line-heading">@lang('petropd::lang.pump_no')</span></th>
                    <th class="pd-cs-test"><span class="pd-close-shift-two-line-heading">{!! str_replace(' ', '<br>', e(__('petropd::lang.test_qty'))) !!}</span></th>
                    <th class="pd-cs-sold"><span class="pd-close-shift-two-line-heading">@lang('petropd::lang.sold_ltr')</span></th>
                    <th class="pd-cs-amount">@lang('petropd::lang.amount')</th>
                    <th class="pd-cs-short"><span class="pd-close-shift-two-line-heading">{!! str_replace(' ', '<br>', e(__('petropd::lang.short_amount'))) !!}</span></th>

                </tr>
            </thead>

            <tfoot>
                <tr class="bg-gray font-17 footer-total">
                    <td class="pd-cs-action"></td>
                    <td class="pd-cs-date"></td>
                    <td class="pd-cs-location"></td>
                    <td class="pd-cs-time"></td>
                    <td class="pd-cs-operator text-right"><strong>@lang('sale.total'):</strong></td>
                    <td class="pd-cs-shift"></td>
                    <td class="pd-cs-pump"></td>
                    <td class="pd-cs-test"><span class="display_currency" id="footer_cs_testing_ltr" data-currency_symbol="false"></span></td>
                    <td class="pd-cs-sold"><span class="display_currency" id="footer_cs_sold_ltr" data-currency_symbol="false"></span></td>
                    <td class="pd-cs-amount"><span class="display_currency" id="footer_cs_sold_amount" data-currency_symbol="true"></span></td>
                    <td class="pd-cs-short"><span class="display_currency" id="footer_cs_short_amount" data-currency_symbol="true"></span></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endcomponent

</section>
<!-- /.content -->

<div class="modal fade" id="close_shift_meter_details_modal" tabindex="-1" role="dialog"
    aria-labelledby="close_shift_meter_details_modal_title" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="@lang('messages.close')">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="close_shift_meter_details_modal_title">Meter Details</h4>
            </div>
            <div class="modal-body">
                <div class="pd-meter-details-meta">
                    <div>
                        <strong>@lang('petropd::lang.shift_number'):</strong>
                        <span id="close_shift_meter_shift_number">—</span>
                    </div>
                    <div>
                        <strong>@lang('petropd::lang.pump_operator'):</strong>
                        <span id="close_shift_meter_operator">—</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered pd-meter-details-table">
                        <thead>
                            <tr>
                                <th><span class="pd-close-shift-two-line-heading">@lang('petropd::lang.pump_no')</span></th>
                                <th>@lang('petropd::lang.starting_meter')</th>
                                <th>@lang('petropd::lang.closing_meter')</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td id="close_shift_meter_pump">—</td>
                                <td id="close_shift_meter_starting">—</td>
                                <td id="close_shift_meter_closing">—</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>
    </div>
</div>
