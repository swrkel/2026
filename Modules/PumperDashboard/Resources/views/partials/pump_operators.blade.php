<!-- Main content -->
<style>
    #list_pump_operators_table {
        width: 100% !important;
    }
</style>
<section class="content">
    <div class="text-right" style="margin-bottom:12px;">
        <a href="{{ route('pumperdashboard.pumperLoginAttemptHistory') }}" class="btn btn-info">
            <i class="fa fa-history"></i> Block / Unblock History
        </a>
    </div>
    @if (!empty($pumperLoginAttempts) && $pumperLoginAttempts->count() > 0)
        <h4 class="box-title text-center">Currently Blocked Pumper Dashboard Login Access</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>IP Address</th>
                        <th>Company Number</th>
                        <th>Last Entered Passcode</th>
                        <th>Login Attempts</th>
                        <th>Status</th>
                        <th>Last Attempted Date</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($pumperLoginAttempts as $pumperLoginAttempt)
                        <tr>
                            <td>{{ $pumperLoginAttempt->ip_address }}</td>
                            <td>{{ $pumperLoginAttempt->company_number }}</td>
                            <td>{{ $pumperLoginAttempt->last_entered_passcode }}</td>
                            <td>{{ $pumperLoginAttempt->attempt_count }}</td>
                            <td>{{ $pumperLoginAttempt->status }}</td>
                            <td>{{ \Carbon\Carbon::parse($pumperLoginAttempt->updated_at)->format('Y-m-d H:i:s') }}</td>
                            <td><a href="{{ route('pumperdashboard.unblockPumperLoginAttempt', $pumperLoginAttempt->id) }}"
                                    class="btn btn-primary">Unblock</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                       {!! Form::select('location_id', $business_locations, $default_location, [
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('pump_operator', __('pumperdashboard::lang.pump_operator') . ':') !!}
                        {!! Form::select('pump_operator', $pump_operators, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('pumperdashboard::lang.all'),
                        ]) !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('settlement_no', __('pumperdashboard::lang.settlement_no') . ':') !!}
                        {!! Form::select('settlement_no', $settlement_nos, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('pumperdashboard::lang.all'),
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('date_range', __('report.date_range') . ':') !!}
                        {!! Form::text(
                            'date_range',
                            \Carbon\Carbon::now()->format('Y-m-d') . ' ~ ' . \Carbon\Carbon::now()->format('Y-m-d'),
                            [
                                'placeholder' => __('lang_v1.select_a_date_range'),
                                'class' => 'form-control',
                                'id' => 'expense_date_range',
                                'readonly',
                            ],
                        ) !!}
                    </div>
                </div>
            @endcomponent
        </div>
    </div>


    <div class="row">
        <div class="col-md-12">
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('type', __('pumperdashboard::lang.type') . ':') !!}
                    {!! Form::select(
                        'type',
                        [
                            'commission' => __('pumperdashboard::lang.commission'),
                            'excess' => __('pumperdashboard::lang.excess'),
                            'shortage' => __('pumperdashboard::lang.shortage'),
                        ],
                        null,
                        [
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                            'placeholder' => __('lang_v1.all'),
                        ],
                    ) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('status', __('pumperdashboard::lang.status') . ':') !!}
                    {!! Form::select(
                        'status',
                        ['inactive' => __('pumperdashboard::lang.inactive'), 'active' => __('pumperdashboard::lang.active')],
                        null,
                        [
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                            'placeholder' => __('lang_v1.all'),
                        ],
                    ) !!}
                </div>
            </div>
        </div>
    </div>


    @component('components.widget', [
        'class' => 'box-primary',
        'title' => __('pumperdashboard::lang.all_your_list_pump_operators'),
    ])
        @slot('tool')
            <div class="row">
                <div class="box-tools pull-right ">
                    <button type="button" class="btn  btn-primary btn-modal"
                        data-href="{{ action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorController@create') }}"
                        data-container=".pump_operator_modal">
                        <i class="fa fa-plus"></i> @lang('messages.add')</button>
                    <a class="btn  btn-primary"
                        href="{{ action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorController@importPumps') }}">
                        <i class="fa fa-download "></i> @lang('pumperdashboard::lang.import')</a>

                </div>
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="list_pump_operators_table">
                <thead>
                    <tr>
                        <th class="notexport">@lang('messages.action')</th>
                        <th>@lang('pumperdashboard::lang.current_balance')</th>
                        <th>
                            Balance For The Period
                        </th>
                        <th>@lang('pumperdashboard::lang.pump_operator')</th>
                        <th>@lang('pumperdashboard::lang.location')</th>
                        <th>@lang('pumperdashboard::lang.sold_fuel_qty')</th>
                        <th>@lang('pumperdashboard::lang.sale_amount_fuel')</th>
                        <th>@lang('pumperdashboard::lang.commission_type')</th>
                        <th>@lang('pumperdashboard::lang.commission_rate')</th>
                        <th>@lang('pumperdashboard::lang.commission_amount')</th>
                        <th>@lang('pumperdashboard::lang.excess_amount')</th>
                        <th>@lang('pumperdashboard::lang.short_amount')</th>

                    </tr>
                </thead>

                <tfoot>
                    <tr class="bg-gray font-17 footer-total text-center">
                        <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                        <td><span class="display_currency" id="footer_sold_fuel_qty" data-currency_symbol="false"></span>
                        </td>
                        <td><span class="display_currency" id="footer_sale_amount_fuel" data-currency_symbol="true"></span>
                        </td>
                        <td></td>
                        <td></td>
                        <td><span class="display_currency" id="footer_commission_amount" data-currency_symbol="true"></span>
                        </td>
                        <td><span class="display_currency" id="footer_excess_amount" data-currency_symbol="true"></span>
                        </td>
                        <td><span class="display_currency" id="footer_short_amount" data-currency_symbol="true"></span></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endcomponent

</section>
<!-- /.content -->


