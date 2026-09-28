<!-- Main content -->
<style>
    footer.main-footer.no-print {
        margin-left: 0 !important;
    }

    /* Official pump status colours: Blue = before assigned/closed, Yellow = assigned/waiting to receive, Purple = received */
    .petropd-pumper-card {
        margin: 10px 10px 10px 20px;
        color: #fff;
        border-radius: 4px;
        box-shadow: 0 4px 10px rgba(15, 23, 42, .12);
        border: 0 !important;
        min-height: 120px;
    }
    .petropd-pumper-card.assigned { background: #f4b400 !important; }
    .petropd-pumper-card.received { background: #8F3A84 !important; }
    .petropd-pumper-card.closed { background: #3b83b9 !important; }
    .petropd-pumper-card .btn { color: #fff !important; background: inherit !important; border: 0 !important; box-shadow: none !important; }
</style>
@if(auth()->user()->is_pump_operator)
<div class="col-md-12">
    <a href="{{action('Auth\PumpOperatorLoginController@logout')}}" class="btn btn-flat btn-lg pull-right"
        style=" background-color: orange; color: #fff; margin-left: 5px;">@lang('petropd::lang.logout')</a>
    <span><a href="{{action('\Modules\PetroPD\Http\Controllers\PumpOperatorController@dashboard@dashboard')}}"
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
                @if($pump->is_confirmed == 1)
                    <div class="col-md-2 text-center petropd-pumper-card received">
                    <h3 style="padding: 0px 0px 6px 0px;"> {{$pump->pump_no}}</h3>
                    <h3 style="padding: 0px 0px 11px 0px;"> {{$pump->pumper_name}}</h3>
                </div>
                @else
                <div class="col-md-2 text-center petropd-pumper-card assigned">
                    <button type="button" class="btn  btn-primary btn-flat btn-modal"
                        style="height: 120px; width:100%; background: inherit !important; border: 0px !important; box-shadow: none !important;"
                        data-href="{{ route('petropd.receive-pump.confirm', [$pump->assignment_id]) }}"
                        data-container=".pump_operator_modal">
                        <h3 style="padding: 0px 0px 6px 0px;"> {{$pump->pump_no}}</h3>
                        <h3 style="padding: 0px 0px 11px 0px;"> {{$pump->pumper_name}}</h3>
                    </button>
                    
                </div>
                
                @endif
                @endforeach
            </div>
            @endcomponent
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' =>
    __('petropd::lang.all_your_daily_collection')])
    
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
    
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="list_daily_collection_table" style="width: 100%;">
            <thead>
                <tr>
                    <th class="notexport">@lang('messages.action')</th>
                    <th>@lang('petropd::lang.date')</th>
                    <th>@lang('petropd::lang.shift_number')</th>
                    <th>@lang('petropd::lang.settlement_no')</th>
                    <th>@lang('petropd::lang.pump_operator')</th>
                    <th>@lang('petropd::lang.pump_no')</th>
                    <th>@lang('petropd::lang.starting_meter')</th>
                    <th>@lang('petropd::lang.closing_meter')</th>
                    <th>@lang('petropd::lang.shift_closed')</th>
                    <th>@lang('petropd::lang.sold_ltr')</th>
                    <th>@lang('petropd::lang.testing_ltr')</th>
                    <th>@lang('petropd::lang.sold_amount')</th>

                </tr>
            </thead>

            <tfoot>
                <tr class="bg-gray font-17 footer-total text-center">
                     <td colspan="8"><strong>@lang('sale.total'):</strong></td>
                    <td><span class="display_currency" id="dc_footer_sold_fuel_qty" data-currency_symbol="false"></span></td>
                    <td><span class="display_currency" id="dc_footer_testing_qty" data-currency_symbol="false"></span></td>
                    <td><span class="display_currency" id="dc_footer_sold_fuel_amount" data-currency_symbol="false"></span></td>

                </tr>
            </tfoot>
        </table>
    </div>
    @endcomponent

</section>
<!-- /.content -->
