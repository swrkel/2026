
<!-- Main content -->
<section class="content">
<style>
.petropd-shift-status-select,
.select2-container--default .select2-selection--single .select2-selection__rendered { font-size:120% !important; font-weight:700 !important; }
.select2-results__option { font-size:120% !important; font-weight:700 !important; }
.petropd-bottom-scroll-fix{overflow-x:auto !important; -webkit-overflow-scrolling:touch !important; padding-bottom:12px !important;}
.petropd-bottom-scroll-fix table{min-width:1250px !important;}
.petropd-bottom-scroll-fix th{white-space:normal !important; line-height:1.15 !important; text-align:center !important;}
.petropd-bottom-scroll-fix td{white-space:nowrap !important;}
/* IS1752-4: remove the empty widget header band and make the live summary readable. */
/*
 | S661: the summary panel is restyled to match Pumper Dashboard > Close Shift.
 |
 | The palette, card treatment, section headers and figure styling below are
 | taken from pumper_dashboard/close_shift_summary.blade.php so the two screens
 | read as one product. Variables are namespaced --ss- to avoid clashing with
 | the --csr- set on the Close Shift page itself.
 |
 | The markup keeps every id and class the JS writes into - the twelve
 | shift_summary_*_val spans and their display_currency class. Only the
 | surrounding presentation changed, so the existing update logic is untouched.
 */
:root{
    --ss-primary:#0868c9;
    --ss-primary-dark:#074b91;
    --ss-heading:#063e7d;
    --ss-text:#10233e;
    --ss-muted:#556983;
    --ss-border:#bdd8f4;
    --ss-border-soft:#d8e7f7;
    --ss-surface-soft:#f5f9fe;
    --ss-shadow:0 12px 36px rgba(27,62,104,.14);
}
.petropd-shift-summary-totals{
    background:#fff;
    border:1px solid var(--ss-border);
    border-radius:18px;
    padding:0;
    box-shadow:var(--ss-shadow);
    overflow:hidden;
    font-family:"Segoe UI", Arial, Helvetica, sans-serif;
}
.petropd-ss-head{
    background:linear-gradient(180deg,#1078d9,#0863b9);
    padding:14px 22px;
    color:#fff;
}
.petropd-ss-head h4{
    margin:0;
    font-size:19px;
    font-weight:700;
    letter-spacing:.3px;
    color:#fff;
}
.petropd-ss-head small{
    display:block;
    margin-top:3px;
    font-size:13px;
    color:rgba(255,255,255,.85);
}
.petropd-ss-body{padding:18px 22px 6px;}
.petropd-ss-col-title{
    font-size:13px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.7px;
    color:var(--ss-primary);
    padding-bottom:8px;
    margin-bottom:10px;
    border-bottom:2px solid var(--ss-border-soft);
}
.petropd-ss-row{
    display:flex;
    align-items:baseline;
    justify-content:space-between;
    gap:14px;
    padding:9px 12px;
    border-radius:8px;
}
.petropd-ss-row:nth-child(even){background:var(--ss-surface-soft);}
.petropd-ss-label{
    font-size:15px;
    font-weight:500;
    color:var(--ss-muted);
}
.petropd-ss-value{
    font-size:19px;
    font-weight:700;
    color:var(--ss-text);
    font-variant-numeric:tabular-nums;
    white-space:nowrap;
}
.petropd-ss-row.is-total{
    background:linear-gradient(145deg,#eef6ff,#dbeafb);
    border:1px solid var(--ss-border);
    margin-top:6px;
}
.petropd-ss-row.is-total .petropd-ss-label{color:var(--ss-heading); font-weight:700;}
.petropd-ss-row.is-total .petropd-ss-value{color:var(--ss-primary-dark); font-size:21px;}
.petropd-ss-row.is-negative .petropd-ss-value{color:#b91c1c;}
.petropd-ss-row.is-positive .petropd-ss-value{color:#15803d;}
@media (max-width:767px){
    .petropd-ss-body{padding:14px 14px 4px;}
    .petropd-ss-value{font-size:17px;}
}
</style>
    @component('components.filters', ['title' => __('report.filters'), 'id' => 'shift_summarys'])
        <div class="row">
            <div class="col-md-12">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('shift_summary_location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('shift_summary_location_id', $business_locations, null, ['class' => 'form-control
                        select2',
                        'placeholder' => __('petropd::lang.all'), 'id' => 'shift_summary_location_id', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('shift_summary_shift_id', __('petropd::lang.shift') . ':') !!}
                        <select class="form-control select2 petropd-shift-status-select" style="width:100%; font-size:120%; font-weight:700;" id="shift_summary_shift_id" placeholder="{{ __('petropd::lang.all') }}">
                            <option value="">{{ __('petropd::lang.all') }}</option>
                            @foreach($shifts as $shift)
                                @php
                                    $statusValue = strtolower((string) ($shift->status ?? ''));
                                    $isClosed = in_array($statusValue, ['2', 'closed', 'close', 'shift closed'], true);
                                    $statusText = $isClosed ? 'Shift Closed' : 'Shift Open';
                                    $displayShiftDate = $shift->transaction_date ?? $shift->shift_date ?? $shift->date ?? $shift->start_date ?? $shift->start_date_and_time ?? $shift->date_and_time ?? $shift->created_at ?? null;
                                    $displayShiftNo = $shift->shift_number ?? $shift->shift_no ?? $shift->id;
                                @endphp
                                <option value="{{ $shift->id }}" style="font-size:120%; font-weight:700;">
                                    {{ $shift->name }} - Shift {{ $displayShiftNo }} - {{ !empty($displayShiftDate) ? @format_date($displayShiftDate) : '-' }} ({{ $statusText }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('shift_summary_pump_operators', __('petropd::lang.pump_operator') . ':') !!}
                        {!! Form::select('shift_summary_pump_operators', $pump_operators, null, ['class' => 'form-control select2', 'placeholder'
                        => __('petropd::lang.all'), 'id' => 'shift_summary_pump_operators', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('pumps', __('petropd::lang.pumps') . ':') !!}
                        {!! Form::select('shift_summary_pumps', $pumps->pluck('pump_name', 'id'), null, ['class' => 'form-control select2', 'placeholder'
                        => __('petropd::lang.all'), 'id' => 'shift_summary_pumps', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('shift_summary_payment_method', __('petropd::lang.payment_method') . ':') !!}
                        {!! Form::select('shift_summary_payment_method', $payment_types, null, ['class' => 'form-control select2',
                        'placeholder'
                        => __('petropd::lang.all'), 'id' => 'shift_summary_payment_method', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('shift_summary_difference', __('petropd::lang.difference') . ':') !!}
                        {!! Form::select('shift_summary_difference', ['positive' => 'Positive', 'negative' => 'Negative'], null, ['class' => 'form-control select2',
                        'placeholder'
                        => __('petropd::lang.all'), 'id' => 'shift_summary_difference', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('shift_summary_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('shift_summary_date_range', @format_date('first day of this month') . ' ~ ' .
                        @format_date('last
                        day of this month') , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' =>
                        'form-control', 'id' => 'shift_summary_date_range', 'readonly']); !!}
                    </div>
                </div>
            </div>
        </div>
    @endcomponent

    <div class="row" style="margin-bottom: 2%;">
        <div class="col-md-10 col-md-offset-1">
            <div class="petropd-shift-summary-totals">
                <div class="petropd-ss-head">
                    <h4>@lang('petropd::lang.shift_summary')</h4>
                    <small>@lang('petropd::lang.all_your_daily_collection')</small>
                </div>
                <div class="petropd-ss-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="petropd-ss-col-title">@lang('petropd::lang.total_sale_today')</div>

                            <div class="petropd-ss-row">
                                <span class="petropd-ss-label">@lang('petropd::lang.no_of_pumps_today')</span>
                                <span class="petropd-ss-value" id="shift_summary_pumps_today_val">0</span>
                            </div>
                            <div class="petropd-ss-row">
                                <span class="petropd-ss-label">@lang('petropd::lang.total_sale_today')</span>
                                <span class="petropd-ss-value display_currency" id="shift_summary_total_sale_val" data-currency_symbol="true">0.00</span>
                            </div>
                            <div class="petropd-ss-row">
                                <span class="petropd-ss-label">@lang('petropd::lang.total_other_sales')</span>
                                <span class="petropd-ss-value display_currency" id="shift_summary_total_other_sales_val" data-currency_symbol="true">0.00</span>
                            </div>
                            <div class="petropd-ss-row">
                                <span class="petropd-ss-label">@lang('petropd::lang.total_payments')</span>
                                <span class="petropd-ss-value display_currency" id="shift_summary_total_payments_val" data-currency_symbol="true">0.00</span>
                            </div>
                            <div class="petropd-ss-row is-total">
                                <span class="petropd-ss-label">@lang('petropd::lang.balance_to_settle')</span>
                                <span class="petropd-ss-value display_currency" id="shift_summary_balance_to_settle_val" data-currency_symbol="true">0.00</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="petropd-ss-col-title">@lang('petropd::lang.payment_method')</div>

                            <div class="petropd-ss-row">
                                <span class="petropd-ss-label">@lang('petropd::lang.cash')</span>
                                <span class="petropd-ss-value display_currency" id="shift_summary_cash_val" data-currency_symbol="true">0.00</span>
                            </div>
                            <div class="petropd-ss-row">
                                <span class="petropd-ss-label">@lang('petropd::lang.credit_sales')</span>
                                <span class="petropd-ss-value display_currency" id="shift_summary_credit_sales_val" data-currency_symbol="true">0.00</span>
                            </div>
                            <div class="petropd-ss-row">
                                <span class="petropd-ss-label">@lang('petropd::lang.credit_cards')</span>
                                <span class="petropd-ss-value display_currency" id="shift_summary_credit_cards_val" data-currency_symbol="true">0.00</span>
                            </div>
                            <div class="petropd-ss-row">
                                <span class="petropd-ss-label">@lang('petropd::lang.cheque_sales')</span>
                                <span class="petropd-ss-value display_currency" id="shift_summary_cheque_sales_val" data-currency_symbol="true">0.00</span>
                            </div>
                            <div class="petropd-ss-row">
                                <span class="petropd-ss-label">@lang('petropd::lang.other')</span>
                                <span class="petropd-ss-value display_currency" id="shift_summary_other_val" data-currency_symbol="true">0.00</span>
                            </div>
                            <div class="petropd-ss-row is-negative">
                                <span class="petropd-ss-label">@lang('petropd::lang.total_short')</span>
                                <span class="petropd-ss-value display_currency" id="shift_summary_shortage_val" data-currency_symbol="true">0.00</span>
                            </div>
                            <div class="petropd-ss-row is-positive">
                                <span class="petropd-ss-label">@lang('petropd::lang.total_excess')</span>
                                <span class="petropd-ss-value display_currency" id="shift_summary_excess_val" data-currency_symbol="true">0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' =>
    __('petropd::lang.all_your_daily_collection')])
    <div class="table-responsive petropd-bottom-scroll-fix">
        <table class="table table-bordered table-striped" id="pump_operators_shift_summary_table" style="width: 100%;">
            <thead>
                <tr>
                    <th class="notexport">@lang('messages.action')</th>
                    <th>@lang('petropd::lang.date')</th>
                    <th>@lang('petropd::lang.pump_operator')</th>
                    <th>@lang('petropd::lang.pump_no')</th>
                    <th>@lang('petropd::lang.starting_meter')</th>
                    <th>@lang('petropd::lang.closing_meter')</th>
                    <th>@lang('petropd::lang.test_qty')</th>
                    <th>@lang('petropd::lang.sold_ltr')</th>
                    <th>@lang('petropd::lang.sold_amount')</th>
                    <th>@lang('petropd::lang.other_sales')</th>
                    <th>@lang('petropd::lang.credit_sale')</th>
                    <th>@lang('petropd::lang.cards')</th>
                    <th>@lang('petropd::lang.cash')</th>
                    <th>@lang('petropd::lang.cheque')</th>
                    <th>@lang('petropd::lang.other')</th>
                    <th>@lang('petropd::lang.shortage')</th>
                    <th>@lang('petropd::lang.excess')</th>
                    <th>@lang('petropd::lang.total_amount')</th>
                    <th>@lang('petropd::lang.difference')</th>

                </tr>
            </thead>

            <tfoot>
                <tr class="bg-gray font-17 footer-total">
                    <td colspan="4" class="text-right"><strong>@lang('sale.total'):</strong></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td><span class="display_currency" id="footer_shift_summary_sold_ltr" data-currency_symbol="false"></span></td>
                    <td><span class="display_currency" id="footer_shift_summary_sold_amount" data-currency_symbol="true"></span></td>
                    <td><span class="display_currency" id="footer_shift_summary_other_sales" data-currency_symbol="true"></span></td>
                    <td><span class="display_currency" id="footer_shift_summary_credit_sale" data-currency_symbol="true"></span></td>
                    <td><span class="display_currency" id="footer_shift_summary_cards" data-currency_symbol="true"></span></td>
                    <td><span class="display_currency" id="footer_shift_summary_cash" data-currency_symbol="true"></span></td>
                    <td><span class="display_currency" id="footer_shift_summary_cheque" data-currency_symbol="true"></span></td>
                    <td><span class="display_currency" id="footer_shift_summary_other" data-currency_symbol="true"></span></td>
                    <td><span class="display_currency" id="footer_shift_summary_shortage" data-currency_symbol="true"></span></td>
                    <td><span class="display_currency" id="footer_shift_summary_excess" data-currency_symbol="true"></span></td>
                    <td><span class="display_currency" id="footer_shift_summary_total_amount" data-currency_symbol="true"></span></td>
                    <td><span class="display_currency" id="footer_shift_summary_difference" data-currency_symbol="true"></span></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endcomponent

</section>
<!-- /.content -->
