

<style>
/* PDB-004 / IS1461: Payment Summary alignment final fix.
   Scoped only to Pumper Dashboard payment summary to avoid affecting other pages. */
#payment_summarys,
#payment_summarys .box,
#payment_summarys .box-body,
#payment_summarys .box-body > .row,
#payment_summarys .filter-box,
#payment_summarys .well {
    overflow: visible !important;
}

#payment_summarys .box-body {
    padding: 18px 20px !important;
}

#payment_summarys .box-body > .row {
    display: flex !important;
    flex-wrap: wrap !important;
    align-items: flex-end !important;
    gap: 0 !important;
    margin-left: -8px !important;
    margin-right: -8px !important;
}

#payment_summarys .box-body > .row > [class*="col-"],
#payment_summarys .box-body > [class*="col-"] {
    float: none !important;
    padding-left: 8px !important;
    padding-right: 8px !important;
    margin-bottom: 12px !important;
}

#payment_summarys .form-group {
    margin-bottom: 0 !important;
    min-height: 74px !important;
}

#payment_summarys label,
#payment_summarys .control-label {
    display: block !important;
    min-height: 20px !important;
    margin-bottom: 6px !important;
    color: #334155 !important;
    font-weight: 600 !important;
    font-size: 13px !important;
    line-height: 1.2 !important;
}

#payment_summarys .form-control,
#payment_summarys .select2-container,
#payment_summarys .select2-selection,
#payment_summarys .select2-selection--single {
    width: 100% !important;
    height: 38px !important;
    min-height: 38px !important;
    border-radius: 8px !important;
}

#payment_summarys .select2-selection__rendered {
    line-height: 36px !important;
}

#payment_summarys .select2-selection__arrow {
    height: 36px !important;
}

#pump_operators_payment_summary_table_wrapper,
#pump_operators_payment_summary_table_wrapper .row,
#pump_operators_payment_summary_table_wrapper .col-sm-12,
#pump_operators_payment_summary_table_wrapper .dataTables_scroll,
#pump_operators_payment_summary_table_wrapper .dataTables_scrollHead,
#pump_operators_payment_summary_table_wrapper .dataTables_scrollBody {
    width: 100% !important;
}

#pump_operators_payment_summary_table {
    width: 100% !important;
    table-layout: auto !important;
}

#pump_operators_payment_summary_table th,
#pump_operators_payment_summary_table td {
    white-space: nowrap !important;
    vertical-align: middle !important;
    padding: 8px 10px !important;
    line-height: 1.35 !important;
}

#pump_operators_payment_summary_table th {
    font-size: 12px !important;
    font-weight: 700 !important;
}

#pump_operators_payment_summary_table td {
    font-size: 13px !important;
}

#pump_operators_payment_summary_table tfoot td {
    font-weight: 700 !important;
    background: #f8fafc !important;
}

#pump_operators_payment_summary_table .btn[disabled],
#pump_operators_payment_summary_table .disabled {
    opacity: .55 !important;
    cursor: not-allowed !important;
    pointer-events: none !important;
}

@media (min-width: 1200px) {
    #payment_summarys .col-md-2 {
        width: 14.285714% !important;
        flex: 0 0 14.285714% !important;
        max-width: 14.285714% !important;
    }

    #payment_summarys .col-md-3 {
        width: 21.428571% !important;
        flex: 0 0 21.428571% !important;
        max-width: 21.428571% !important;
    }
}

@media (min-width: 992px) and (max-width: 1199px) {
    #payment_summarys .col-md-2,
    #payment_summarys .col-md-3 {
        width: 25% !important;
        flex: 0 0 25% !important;
        max-width: 25% !important;
    }
}

@media (min-width: 768px) and (max-width: 991px) {
    #payment_summarys .col-md-2,
    #payment_summarys .col-md-3 {
        width: 33.333333% !important;
        flex: 0 0 33.333333% !important;
        max-width: 33.333333% !important;
    }
}

@media (max-width: 767px) {
    #payment_summarys .box-body {
        padding: 12px !important;
    }

    #payment_summarys .box-body > .row > [class*="col-"],
    #payment_summarys .box-body > [class*="col-"],
    #payment_summarys .col-md-2,
    #payment_summarys .col-md-3 {
        width: 100% !important;
        flex: 0 0 100% !important;
        max-width: 100% !important;
        float: none !important;
    }

    #payment_summarys .form-group {
        min-height: auto !important;
    }

    #pump_operators_payment_summary_table th,
    #pump_operators_payment_summary_table td {
        white-space: normal !important;
    }
}

/* IS1471-001: compact payment summary columns and stable total row */
#pump_operators_payment_summary_table{
    table-layout: fixed !important;
    width: 100% !important;
}
#pump_operators_payment_summary_table th:nth-child(1),
#pump_operators_payment_summary_table td:nth-child(1){width:78px !important; max-width:78px !important;}
#pump_operators_payment_summary_table th:nth-child(5),
#pump_operators_payment_summary_table td:nth-child(5){width:105px !important; max-width:105px !important;}
#pump_operators_payment_summary_table th:nth-child(6),
#pump_operators_payment_summary_table td:nth-child(6){width:70px !important; max-width:70px !important;}
#pump_operators_payment_summary_table th:nth-child(7),
#pump_operators_payment_summary_table td:nth-child(7){width:92px !important; max-width:92px !important;}
#pump_operators_payment_summary_table th:nth-child(8),
#pump_operators_payment_summary_table td:nth-child(8){width:90px !important; max-width:90px !important;}
#pump_operators_payment_summary_table th:nth-child(12),
#pump_operators_payment_summary_table td:nth-child(12){width:110px !important; max-width:110px !important; text-align:right !important;}
#pump_operators_payment_summary_table th,
#pump_operators_payment_summary_table td{
    overflow:hidden !important;
    text-overflow:ellipsis !important;
}
#pump_operators_payment_summary_table tfoot tr.footer-total td{
    background:#fff7ed !important;
    border-top:2px solid #fb923c !important;
    font-weight:900 !important;
}

#pump_operators_payment_summary_table tfoot tr.footer-breakdown td{
    background:#fffaf5 !important;
    border-bottom:2px solid #fed7aa !important;
    font-size:13px !important;
    color:#0369a1 !important;
}
.payment-summary-breakdown-wrap{
    display:inline-block;
    min-width:220px;
    text-align:left;
    line-height:1.8;
}
.payment-summary-breakdown-line{
    display:flex;
    justify-content:space-between;
    gap:18px;
}
.payment-summary-breakdown-line strong{
    text-align:right;
    min-width:105px;
}

.payment-summary-horizontal-scroll{overflow-x:auto !important; -webkit-overflow-scrolling:touch;}

</style>


<!-- Main content -->
<section class="content" id="payment_summarys">

    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                @if (empty($only_pumper))
                    <div class="col-md-2">
                        <div class="form-group">
                            {!! Form::label('payment_summary_location_id', __('purchase.business_location') . ':') !!}
                            {!! Form::select('payment_summary_location_id', $business_locations, null, [
                                'class' => 'form-control select2',
                                'placeholder' => __('pumperdashboard::lang.all'),
                                'id' => 'payment_summary_location_id',
                                'style' => 'width:100%',
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('payment_summary_date_range', __('report.date_range') . ':') !!}
                            {!! Form::text('payment_summary_date_range', @format_date('today') . ' ~ ' . @format_date('today'), [
                                'class' => 'form-control',
                                'id' => 'payment_summary_date_range',
                                'readonly',
                            ]) !!}
                        </div>
                    </div>
                @endif

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('payment_summary_pump_operators', __('pumperdashboard::lang.pump_operator') . ':') !!}
                        {!! Form::select('payment_summary_pump_operators', $pump_operators, $selected_pump_operator_id ?? null, [
                            'class' => 'form-control select2',
                            'placeholder' => !empty($only_pumper) ? null : __('pumperdashboard::lang.all'),
                            'id' => 'payment_summary_pump_operators',
                            'style' => 'width:100%',
                            'disabled' => !empty($only_pumper) ? true : false,
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('payment_summary_shift_id', __('pumperdashboard::lang.shift_number') . ':') !!}
                        <select class="form-control select2" style='width:100%' id="payment_summary_shift_id">
                            @if (empty($only_pumper))
                                <option value="">@lang('lang_v1.all')</option>
                            @endif

                            @php
                                $latest_shift_id = optional($shifts->sortByDesc('id')->first())->id;
                            @endphp

                            @foreach ($shifts as $shift)
                                <option value="{{ $shift->id }}" {{ $shift->id == $latest_shift_id ? 'selected' : '' }}>
                                    {{ $shift->assignment_shift_number ?? $shift->shift_number ?? $shift->id }} -
                                    ({{ @format_date($shift->assignment_date ?? $shift->shift_date ?? $shift->created_at) }} to
                                    {{ !empty($shift->closed_time) ? @format_datetime($shift->closed_time) : 'Open' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>






                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('payment_summary_payment_method', __('pumperdashboard::lang.payment_method') . ':') !!}
                        {!! Form::select('payment_summary_payment_method', $payment_types, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('pumperdashboard::lang.all'),
                            'id' => 'payment_summary_payment_method',
                            'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('payment_summary_customer', __('pumperdashboard::lang.customer') . ':') !!}
                        {!! Form::select('payment_summary_customer', $customers, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('pumperdashboard::lang.all'),
                            'id' => 'payment_summary_customer',
                            'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('payment_summary_slip_no', __('pumperdashboard::lang.slip_no') . ':') !!}
                        {!! Form::text('payment_summary_slip_no', null, [
                            'class' => 'form-control',
                            'placeholder' => 'Enter Slip No',
                            'id' => 'payment_summary_slip_no',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('payment_summary_order_no', __('pumperdashboard::lang.order_no') . ':') !!}
                        {!! Form::text('payment_summary_order_no', null, [
                            'class' => 'form-control',
                            'placeholder' => 'Enter order no',
                            'id' => 'payment_summary_order_no',
                        ]) !!}
                    </div>
                </div>
            @endcomponent

        </div>
    </div>


    @component('components.widget', ['class' => 'box-primary', 'title' => __('pumperdashboard::lang.all_your_payments')])
        <div class="table-responsive payment-summary-horizontal-scroll">
            <table class="table table-bordered table-striped" id="pump_operators_payment_summary_table"
                style="width: 100%;">
                <thead>
                    <tr>
                        <th>@lang('pumperdashboard::lang.action')</th>
                        <th>@lang('pumperdashboard::lang.date')</th>
                        <th>@lang('pumperdashboard::lang.location')</th>
                        <th>@lang('pumperdashboard::lang.time')</th>
                        <th>@lang('pumperdashboard::lang.pump_operator')</th>
                        <th>@lang('pumperdashboard::lang.shift_number')</th>
                        <th>@lang('pumperdashboard::lang.collection_form_no')</th>
                        <th>@lang('pumperdashboard::lang.payment_type')</th>
                        <th>Customer</th>
                        <th>@lang('pumperdashboard::lang.slip_no')</th>
                        <th>@lang('pumperdashboard::lang.order_no')</th>
                        <th>@lang('pumperdashboard::lang.amount')</th>
                        @if (empty($only_pumper))
                            <th>@lang('pumperdashboard::lang.note')</th>
                            <th>@lang('pumperdashboard::lang.edited_by')</th>
                        @endif
                    </tr>
                </thead>

                <tfoot>
                    <tr class="bg-gray font-17 footer-total">
                        <td colspan="11" class="text-right" style="color:brown">
                            <strong>@lang('sale.total'):</strong>
                        </td>
                        <td style="color:brown" class="text-right"><span class="display_currency" id="footer_payment_summary_amount"
                                data-currency_symbol="true">0.00</span></td>
                            @if (empty($only_pumper))
                        <td></td>
                        <td></td>
                        @endif
                    </tr>
                    <tr class="footer-breakdown">
                        <td colspan="11" class="text-right">
                            <strong>Payment Type Breakdown:</strong>
                        </td>
                        <td class="text-right">
                            <div id="payment_summary_breakdown_container" class="payment-summary-breakdown-wrap">
                                <span class="text-muted">-</span>
                            </div>
                        </td>
                        @if (empty($only_pumper))
                        <td></td>
                        <td></td>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>
    @endcomponent

</section>
<!-- /.content -->




