<!-- Main content -->
<style>
    footer.main-footer.no-print {
        margin-left: 0 !important;
    }

    /* IS2306: fluid table sizing on desktop with a safe horizontal fallback on small screens. */
    .pd-daily-status-scroll {
        width: 100%;
        max-width: 100%;
        overflow-x: auto !important;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }
    .pd-daily-status-scroll .dataTables_wrapper {
        width: 100%;
        max-width: 100%;
    }
    #list_daily_collection_table,
    #list_daily_collection_table.dataTable {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed !important;
        font-size: 11px;
    }
    #list_daily_collection_table > thead > tr > th,
    #list_daily_collection_table > tbody > tr > td {
        padding: 5px 3px !important;
        vertical-align: middle !important;
        line-height: 1.15;
        overflow-wrap: anywhere;
        word-break: normal;
    }
    #list_daily_collection_table > thead > tr > th {
        white-space: normal !important;
        text-align: center;
        font-size: 10.5px !important;
        line-height: 1.05 !important;
    }
    #list_daily_collection_table > thead .pd-th-line {
        display: block;
        white-space: nowrap;
    }
    #list_daily_collection_table > tbody > tr > td {
        white-space: normal;
    }

    @media (max-width: 1366px) {
        #list_daily_collection_table,
        #list_daily_collection_table.dataTable { font-size: 10.5px; }
        #list_daily_collection_table > thead > tr > th { font-size: 10px !important; }
        #list_daily_collection_table > thead > tr > th,
        #list_daily_collection_table > tbody > tr > td { padding: 4px 2px !important; }
    }

    @media (max-width: 767px) {
        #list_daily_collection_table,
        #list_daily_collection_table.dataTable {
            min-width: 760px !important;
            font-size: 10px;
        }
        #list_daily_collection_table > thead > tr > th { font-size: 9.5px !important; }
    }
</style>
@if (auth()->user()->is_pump_operator)
    <div class="col-md-12">
        <a href="{{ action('Auth\PumpOperatorLoginController@logout') }}" class="btn btn-flat btn-lg pull-right"
            style=" background-color: orange; color: #fff; margin-left: 5px;">@lang('pumperdashboard::lang.logout')</a>
        <span><a href="{{ action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorController@dashboard') }}"
                class="btn btn-flat btn-lg pull-right"
                style="color: #fff; background-color:#810040;">@lang('pumperdashboard::lang.dashboard')</a></span>
    </div>
    <div class="clearfix"></div>
@endif

<section class="content" style="overflow: visible;">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="row">
                    @foreach ($pumps as $pump)
                        @if ($pump->pump_operator_id && (empty($pump->assignment_status ?? $pump->status) || ($pump->assignment_status ?? $pump->status) == 'open'))
                            @php
                                $cardColor = '#f4b400'; // yellow: assigned, open shift
                                if (!empty($pump->is_confirmed)) {
                                    $cardColor = '#8F3A84'; // purple: received by pumper
                                }
                            @endphp
                            <div class="col-md-2 text-center"
                                style="background: {{ $cardColor }}; margin: 10px 10px 10px 20px; color: #fff; padding: 15px;">
                                <h3 style="padding: 0px 0px 6px 0px; margin: 10px 0;"> {{ $pump->pump_no }}</h3>
                                <h4 style="padding: 0px 0px 6px 0px; margin: 5px 0; font-size: 14px;">
                                    {{ $pump->pumper_name }}</h4>
                                <h5 style="padding: 0px 0px 6px 0px; margin: 5px 0; font-size: 13px;"> Shift No:
                                    {{ $pump->shift_number ?? ($pump->shift_id ?? 'N/A') }}</h5>
                            </div>
                        @elseif (!empty($pump->is_settled))
                            {{-- Blue: settlement completed --}}
                            <div class="col-md-2 text-center"
                                style="background: #3b83b9; margin: 10px 10px 10px 20px; color: #fff; padding: 15px;">
                                <h3 style="padding: 0px 0px 6px 0px; margin: 10px 0;"> {{ $pump->pump_no }}</h3>
                                <h4 style="padding: 0px 0px 6px 0px; margin: 5px 0; font-size: 14px;">
                                    {{ $pump->pumper_name }}</h4>
                                <h5 style="padding: 0px 0px 6px 0px; margin: 5px 0; font-size: 13px;">
                                    {{ $pump->settlement_no }}</h5>
                            </div>
                        @else
                            {{-- Blue: unassigned --}}
                            <div class="col-md-2 text-center bg-primary"
                                style="margin: 10px 10px 10px 20px; color: #fff; padding: 0;">
                                <button type="button" class="btn  btn-primary btn-flat btn-modal"
                                    style="height: 140px; width:100%; background: inherit !important; border: 0px !important; box-shadow: none !important;"
                                    {{-- data-href="{{action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorAssignmentController@getPumperAssignment', ['pump_id' => $pump->id, 'pump_operator_id' => \Auth::user()->pump_operator_id])}}"
                        data-container=".pump_operator_modal" --}}>
                                    <h3> {{ $pump->pump_no }}</h3>
                                </button>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endcomponent
        </div>
    </div>

    @component('components.widget', [
        'class' => 'box-primary',
        'title' => __('pumperdashboard::lang.all_your_daily_collection'),
    ])

        @slot('tool')
            <div class="row">
                <div class="box-tools pull-right ">
                    @can('bulk_assign_pumps')
                        <button type="button" class="btn  btn-primary btn-modal"
                            data-href="{{ action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorAssignmentController@create') }}"
                            data-container=".pump_operator_modal">
                            <i class="fa fa-plus"></i> @lang('pumperdashboard::lang.assign_pumps')</button>
                    @endcan
                </div>
            </div>
        @endslot

        <div class="table-responsive pd-daily-status-scroll">
            <table class="table table-bordered table-striped" id="list_daily_collection_table" style="width: 100%;">
                <colgroup>
                    <col style="width:7%"><col style="width:9%"><col style="width:6%"><col style="width:9%">
                    <col style="width:12%"><col style="width:6%"><col style="width:10%"><col style="width:10%">
                    <col style="width:8%"><col style="width:8%"><col style="width:15%">
                </colgroup>
                <thead>
                    <tr>
                        <th class="notexport">@lang('messages.action')</th>
                        <th>@lang('pumperdashboard::lang.date')</th>
                        <th><span class="pd-th-line">Shift</span><span class="pd-th-line">No</span></th>
                        <th><span class="pd-th-line">Settlement</span><span class="pd-th-line">No</span></th>
                        <!--<th>@lang('pumperdashboard::lang.location')</th>-->
                        <th><span class="pd-th-line">Pump</span><span class="pd-th-line">Operator</span></th>
                        <th><span class="pd-th-line">Pump</span><span class="pd-th-line">No</span></th>
                        <th><span class="pd-th-line">Starting</span><span class="pd-th-line">Meter</span></th>
                        <th><span class="pd-th-line">Closing</span><span class="pd-th-line">Meter</span></th>
                        <th><span class="pd-th-line">Sold</span><span class="pd-th-line">Ltr</span></th>
                        <th><span class="pd-th-line">Testing</span><span class="pd-th-line">Ltr</span></th>
                        <th><span class="pd-th-line">Sold</span><span class="pd-th-line">Amount</span></th>
                    </tr>
                </thead>

                <tfoot>
                    <tr class="bg-gray font-17 footer-total text-center">
                        <!--<td colspan="7"><strong>@lang('sale.total'):</strong></td>-->
                        <!--<td><span class="display_currency" id="dc_footer_sold_fuel_qty" data-currency_symbol="false"></span></td>-->
                        <!--<td><span class="display_currency" id="dc_footer_testing_qty" data-currency_symbol="false"></span></td>-->
                        <!--<td><span class="display_currency" id="dc_footer_sold_fuel_amount" data-currency_symbol="false"></span></td>-->

                    </tr>
                </tfoot>
            </table>
        </div>
    @endcomponent

</section>
<!-- /.content -->




