<!-- Main content -->
<style>
/* S685 table display. */

#list_pump_operators_table {
    width: 100% !important;
    table-layout: fixed;
}

/*
 | Headings wrap instead of being cut.
 |
 | DataTables sets white-space: nowrap on headers, so a two-word heading either
 | forced its column wider or was clipped - it could not wrap.
*/
#list_pump_operators_table thead th {
    white-space: normal !important;
    word-wrap: break-word;
    vertical-align: middle;
    font-size: 12px;
    line-height: 1.25;
    padding: 8px 6px;
}

#list_pump_operators_table tbody td {
    white-space: normal;
    word-wrap: break-word;
    font-size: 13px;
    padding: 6px;
}

#list_pump_operators_table td.text-right,
#list_pump_operators_table th.text-right { text-align: right; }

/*
 | The Actions menu was clipped.
 |
 | Not by table-responsive, the usual suspect: the TABLE element carries
 | overflow: hidden from the DataTables stylesheet, so rules aimed at the
 | wrapper never reach it.
*/
#list_pump_operators_table_wrapper table.dataTable,
#list_pump_operators_table_wrapper .table-responsive,
#list_pump_operators_table_wrapper .dataTables_wrapper {
    overflow: visible !important;
}

/* The button group stays relative - making it static sends the menu to a
   distant ancestor for positioning, and on the top rows it renders off-screen. */
#list_pump_operators_table .btn-group { position: relative; }

#list_pump_operators_table .dropdown-menu {
    position: absolute;
    z-index: 1051;
    top: 100%;
    bottom: auto;
}
</style>
<section class="content">
    <div class="text-right" style="margin-bottom:12px;">
        <a href="{{ route('petrogeneral.pumper_login_attempt_history') }}" class="btn btn-info">
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
                            <td><a href="{{ route('petrogeneral.unblockPumperLoginAttempt', $pumperLoginAttempt->id) }}"
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
                        {!! Form::label('pump_operator', __('petrogeneral::lang.pump_operator') . ':') !!}
                        {!! Form::select('pump_operator', $pump_operators, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('petrogeneral::lang.all'),
                        ]) !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('settlement_no', __('petrogeneral::lang.settlement_no') . ':') !!}
                        {!! Form::select('settlement_no', $settlement_nos, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('petrogeneral::lang.all'),
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
                    {!! Form::label('type', __('petrogeneral::lang.type') . ':') !!}
                    {!! Form::select(
                        'type',
                        [
                            'commission' => __('petrogeneral::lang.commission'),
                            'excess' => __('petrogeneral::lang.excess'),
                            'shortage' => __('petrogeneral::lang.shortage'),
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
                    {!! Form::label('status', __('petrogeneral::lang.status') . ':') !!}
                    {!! Form::select(
                        'status',
                        ['inactive' => __('petrogeneral::lang.inactive'), 'active' => __('petrogeneral::lang.active')],
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
        'title' => __('petrogeneral::lang.all_your_list_pump_operators'),
    ])
        @slot('tool')
            <div class="row">
                <div class="box-tools pull-right ">
                    <button type="button" class="btn  btn-primary btn-modal"
                        data-href="{{ action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorController@create') }}"
                        data-container=".pump_operator_modal">
                        <i class="fa fa-plus"></i> @lang('messages.add')</button>
                    <a class="btn  btn-primary"
                        href="{{ action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorController@importPumps') }}">
                        <i class="fa fa-download "></i> @lang('petrogeneral::lang.import')</a>

                </div>
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="list_pump_operators_table">
                <thead>
                    <tr>
                        <th class="notexport">@lang('messages.action')</th>
                        <th>@lang('petrogeneral::lang.current_balance')</th>
                        <th>
                            Balance For The Period
                        </th>
                        <th>@lang('petrogeneral::lang.pump_operator')</th>
                        <th>@lang('petrogeneral::lang.location')</th>
                        <th>@lang('petrogeneral::lang.sold_fuel_qty')</th>
                        <th>@lang('petrogeneral::lang.sale_amount_fuel')</th>
                        <th>@lang('petrogeneral::lang.commission_type')</th>
                        <th>@lang('petrogeneral::lang.commission_rate')</th>
                        <th>@lang('petrogeneral::lang.commission_amount')</th>
                        <th>@lang('petrogeneral::lang.excess_amount')</th>
                        <th>@lang('petrogeneral::lang.short_amount')</th>

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
