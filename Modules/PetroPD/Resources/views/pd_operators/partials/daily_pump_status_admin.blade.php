<!-- Main content -->
<style>
    footer.main-footer.no-print {
        margin-left: 0 !important;
    }

    /* Official pump status colours: Blue = before assigned/closed, Yellow = assigned/waiting to receive, Purple = received */
    .petropd-pump-status-card {
        margin: 10px 10px 10px 20px;
        color: #fff;
        padding: 16px 12px;
        min-height: 118px;
        border-radius: 4px;
        box-shadow: 0 4px 10px rgba(15, 23, 42, .12);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border: 0 !important;
    }
    .petropd-pump-status-card h3,
    .petropd-pump-status-card h4,
    .petropd-pump-status-card h5 {
        color: #fff;
        margin: 4px 0;
        padding: 0;
        line-height: 1.25;
    }
    .petropd-pump-status-card.available {
        background: #3b83b9 !important;
        cursor: default !important;
    }
    .petropd-pump-status-card.assigned {
        background: #f4b400 !important;
    }
    .petropd-pump-status-card.received {
        background: #8F3A84 !important;
    }
    .petropd-pump-status-card.closed {
        background: #3b83b9 !important;
    }
    .petropd-pump-status-card .btn { color:#fff !important; background: inherit !important; border:0 !important; box-shadow:none !important; }

    /*
    |--------------------------------------------------------------------------
    | IS2312 - Daily Pump Status compact / screen-fit table
    |--------------------------------------------------------------------------
    | Keep the requested headings on two rows and stop long labels from forcing
    | the DataTable wider than the available PD Operators content area.
    */
    #daily_pump_status .petropd-is2312-table-wrap {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
    }

    #daily_pump_status #list_daily_collection_table {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed;
        margin: 0 !important;
    }

    #daily_pump_status #list_daily_collection_table thead th {
        white-space: normal !important;
        word-break: normal;
        overflow-wrap: normal;
        vertical-align: middle !important;
        text-align: center;
        line-height: 1.08;
        padding: 7px 4px !important;
    }

    #daily_pump_status #list_daily_collection_table tbody td {
        vertical-align: middle !important;
        padding: 7px 4px !important;
        overflow-wrap: anywhere;
    }

    #daily_pump_status #list_daily_collection_table .pd-is2312-action { width: 7% !important; }
    #daily_pump_status #list_daily_collection_table .pd-is2312-date { width: 9% !important; }
    #daily_pump_status #list_daily_collection_table .pd-is2312-shift { width: 5% !important; }
    #daily_pump_status #list_daily_collection_table .pd-is2312-settlement { width: 7% !important; }
    #daily_pump_status #list_daily_collection_table .pd-is2312-operator { width: 14% !important; }
    #daily_pump_status #list_daily_collection_table .pd-is2312-pump { width: 5% !important; }
    #daily_pump_status #list_daily_collection_table .pd-is2312-starting { width: 8% !important; }
    #daily_pump_status #list_daily_collection_table .pd-is2312-closing { width: 8% !important; }
    #daily_pump_status #list_daily_collection_table .pd-is2312-sold-lts { width: 7% !important; }
    #daily_pump_status #list_daily_collection_table .pd-is2312-testing-lts { width: 7% !important; }
    #daily_pump_status #list_daily_collection_table .pd-is2312-sold-amount { width: 9% !important; }

    @media (max-width: 767px) {
        #daily_pump_status #list_daily_collection_table {
            min-width: 860px;
        }
    }
</style>
@if (auth()->user()->is_pump_operator)
    <div class="col-md-12">
        <a href="{{ action('Auth\PumpOperatorLoginController@logout') }}" class="btn btn-flat btn-lg pull-right"
            style=" background-color: orange; color: #fff; margin-left: 5px;">@lang('petropd::lang.logout')</a>
        <span><a href="{{ route('petropd.pd-operators') }}"
                class="btn btn-flat btn-lg pull-right"
                style="color: #fff; background-color:#810040;">@lang('petropd::lang.dashboard')</a></span>
    </div>
    <div class="clearfix"></div>
@endif

<section class="content" style="overflow: visible;">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="row">
                    @foreach ($pumps as $pump)
                        @php
                            $status = strtolower((string) ($pump->assignment_status ?? $pump->status ?? ''));
                            $hasOpenAssignment = $pump->pump_operator_id && (empty($status) || in_array($status, ['open', 'assigned', 'received'], true));
                            $cardClass = 'available';
                            if ($hasOpenAssignment && !empty($pump->is_confirmed)) {
                                $cardClass = 'received';
                            } elseif ($hasOpenAssignment) {
                                $cardClass = 'assigned';
                            } elseif (in_array($status, ['closed', 'close'], true)) {
                                $cardClass = 'closed';
                            }
                        @endphp

                        {{-- S360: Available pumps are display-only here. Only Assign Pumps button opens the assignment popup. --}}
                        <div class="col-md-2 text-center petropd-pump-status-card {{ $cardClass }}">
                            <h3>{{ $pump->pump_no }}</h3>
                            @if($hasOpenAssignment)
                                <h4 style="font-size: 14px;">{{ $pump->pumper_name }}</h4>
                                <h5 style="font-size: 13px;">Shift No: {{ $pump->shift_number ?? ($pump->shift_id ?? 'N/A') }}</h5>
                            @else
                                <h5 style="font-size: 13px;">Available</h5>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endcomponent
        </div>
    </div>

    @component('components.widget', [
        'class' => 'box-primary',
        'title' => __('petropd::lang.all_your_daily_collection'),
    ])

        @slot('tool')
            <div class="row">
                <div class="box-tools pull-right ">
                    @can('bulk_assign_pumps')
                        <button type="button" class="btn btn-primary btn-modal petropd-assign-pumps-button"
                            data-href="{{ route('petropd.pump-assignments.create') }}"
                            data-container=".pump_operator_modal">
                            <i class="fa fa-plus"></i> @lang('petropd::lang.assign_pumps')</button>
                    @endcan
                </div>
            </div>
        @endslot

        <div class="table-responsive petropd-is2312-table-wrap">
            <table class="table table-bordered table-striped" id="list_daily_collection_table" style="width: 100%;">
                <thead>
                    <tr>
                        <th class="notexport pd-is2312-action">@lang('messages.action')</th>
                        <th class="pd-is2312-date">@lang('petropd::lang.date')</th>
                        <th class="pd-is2312-shift"><span>Shift<br>No</span></th>
                        <th class="pd-is2312-settlement"><span>Settlement<br>No</span></th>
                        <!--<th>@lang('petropd::lang.location')</th>-->
                        <th class="pd-is2312-operator">@lang('petropd::lang.pump_operator')</th>
                        <th class="pd-is2312-pump"><span>Pump<br>No</span></th>
                        <th class="pd-is2312-starting"><span>Starting<br>Meter</span></th>
                        <th class="pd-is2312-closing"><span>Closing<br>Meter</span></th>
                        <th class="pd-is2312-sold-lts"><span>Sold<br>Lts</span></th>
                        <th class="pd-is2312-testing-lts"><span>Testing<br>Lts</span></th>
                        <th class="pd-is2312-sold-amount"><span>Sold<br>Amount</span></th>

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
