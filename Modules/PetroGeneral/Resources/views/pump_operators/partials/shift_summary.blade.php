
<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('shift_summary_location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('shift_summary_location_id', $business_locations, null, ['class' => 'form-control
                    select2',
                    'placeholder' => __('petrogeneral::lang.all'), 'id' => 'shift_summary_location_id', 'style' => 'width:100%']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('shift_summary_pump_operators', __('petrogeneral::lang.pump_operator') . ':') !!}
                    {!! Form::select('shift_summary_pump_operators', $pump_operators, null, ['class' => 'form-control select2', 'placeholder'
                    => __('petrogeneral::lang.all'), 'id' => 'shift_summary_pump_operators', 'style' => 'width:100%']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('pumps', __('petrogeneral::lang.pumps') . ':') !!}
                    {!! Form::select('shift_summary_pumps', $pumps->pluck('pump_name', 'id'), null, ['class' => 'form-control select2', 'placeholder'
                    => __('petrogeneral::lang.all'), 'id' => 'shift_summary_pumps', 'style' => 'width:100%']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('shift_summary_payment_method', __('petrogeneral::lang.payment_method') . ':') !!}
                    {!! Form::select('shift_summary_payment_method', $payment_types, null, ['class' => 'form-control select2',
                    'placeholder'
                    => __('petrogeneral::lang.all'), 'id' => 'shift_summary_payment_method', 'style' => 'width:100%']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('shift_summary_difference', __('petrogeneral::lang.difference') . ':') !!}
                    {!! Form::select('shift_summary_difference', ['positive' => 'Positive', 'negative' => 'Negative'], null, ['class' => 'form-control select2',
                    'placeholder'
                    => __('petrogeneral::lang.all'), 'id' => 'shift_summary_difference', 'style' => 'width:100%']); !!}
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
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget')
            <div class="col-md-5 col-md-offset-2">
                <div class="row">
                    <div class="col-md-6 text-red">
                        <h3>@lang('petrogeneral::lang.no_of_pumps_today'):</h3>
                    </div>
                    <div class="col-md-6">
                        <h3><span id="shift_summary_pumps_today_val">0</span></h3>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 text-red">
                        <h3>@lang('petrogeneral::lang.total_sale_today'):</h3>
                    </div>
                    <div class="col-md-6">
                        <h3><span class="display_currency" id="shift_summary_total_sale_val" data-currency_symbol="true">0.00</span></h3>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 text-red">
                        <h3>@lang('petrogeneral::lang.total_payments'):</h3>
                    </div>
                    <div class="col-md-6">
                        <h3><span class="display_currency" id="shift_summary_total_payments_val" data-currency_symbol="true">0.00</span></h3>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 text-red">
                        <h3>@lang('petrogeneral::lang.balance_to_settle'):</h3>
                    </div>
                    <div class="col-md-6">
                        <h3><span class="display_currency" id="shift_summary_balance_to_settle_val" data-currency_symbol="true">0.00</span></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="row">
                    <div class="col-md-6 text-red">
                        <h3>@lang('petrogeneral::lang.cash'):</h3>
                    </div>
                    <div class="col-md-6">
                        <h3><span class="display_currency" id="shift_summary_cash_val" data-currency_symbol="true">0.00</span></h3>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 text-red">
                        <h3>@lang('petrogeneral::lang.credit_sales'):</h3>
                    </div>
                    <div class="col-md-6">
                        <h3><span class="display_currency" id="shift_summary_credit_sales_val" data-currency_symbol="true">0.00</span></h3>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 text-red">
                        <h3>@lang('petrogeneral::lang.credit_cards'):</h3>
                    </div>
                    <div class="col-md-6">
                        <h3><span class="display_currency" id="shift_summary_credit_cards_val" data-currency_symbol="true">0.00</span></h3>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 text-red">
                        <h3>@lang('petrogeneral::lang.cheque_sales'):</h3>
                    </div>
                    <div class="col-md-6">
                        <h3><span class="display_currency" id="shift_summary_cheque_sales_val" data-currency_symbol="true">0.00</span></h3>
                    </div>
                </div>
            </div>
          
            @endcomponent
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' =>
    __('petrogeneral::lang.all_your_daily_collection')])
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="pump_operators_shift_summary_table" style="width: 100%;">
            <thead>
                <tr>
                    <th class="notexport">@lang('messages.action')</th>
                    <th>@lang('petrogeneral::lang.date')</th>
                    <th>@lang('petrogeneral::lang.pump_operator')</th>
                    <th>@lang('petrogeneral::lang.pump_no')</th>
                    <th>@lang('petrogeneral::lang.starting_meter')</th>
                    <th>@lang('petrogeneral::lang.closing_meter')</th>
                    <th>@lang('petrogeneral::lang.test_qty')</th>
                    <th>@lang('petrogeneral::lang.sold_ltr')</th>
                    <th>@lang('petrogeneral::lang.sold_amount')</th>
                    <th>@lang('petrogeneral::lang.credit_sale')</th>
                    <th>@lang('petrogeneral::lang.cards')</th>
                    <th>@lang('petrogeneral::lang.cash')</th>
                    <th>@lang('petrogeneral::lang.cheque')</th>
                    <th>@lang('petrogeneral::lang.total_amount')</th>
                    <th>@lang('petrogeneral::lang.difference')</th>

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
                    <td><span class="display_currency" id="footer_shift_summary_credit_sale" data-currency_symbol="true"></span></td>
                    <td><span class="display_currency" id="footer_shift_summary_cards" data-currency_symbol="true"></span></td>
                    <td><span class="display_currency" id="footer_shift_summary_cash" data-currency_symbol="true"></span></td>
                    <td><span class="display_currency" id="footer_shift_summary_cheque" data-currency_symbol="true"></span></td>
                    <td><span class="display_currency" id="footer_shift_summary_total_amount" data-currency_symbol="true"></span></td>
                    <td><span class="display_currency" id="footer_shift_summary_difference" data-currency_symbol="true"></span></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endcomponent

</section>
<!-- /.content -->
