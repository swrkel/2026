<!-- Main content -->
<style>
    footer.main-footer.no-print {
        margin-left: 0 !important;
    }
</style>
@if(auth()->user()->is_pump_operator)
<div class="col-md-12">
    <a href="{{action('Auth\PumpOperatorLoginController@logout')}}" class="btn btn-flat btn-lg pull-right"
        style=" background-color: orange; color: #fff; margin-left: 5px;">@lang('pumperdashboard::lang.logout')</a>
    <span><a href="{{action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorController@dashboard')}}"
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
                @if($pump->is_confirmed == 1)
                    <div class="col-md-2 text-center" style="background: #8F3A84; margin: 10px 10px 10px 20px; color: #fff ">
                    <h3 style="padding: 0px 0px 6px 0px;"> {{$pump->pump_no}}</h3>
                    <h3 style="padding: 0px 0px 11px 0px;"> {{$pump->pumper_name}}</h3>
                </div>
                @else
                <div class="col-md-2 text-center" style="background: #f4b400; margin: 10px 10px 10px 20px; color: #fff ">
                    <button type="button" class="btn  btn-primary btn-flat btn-modal"
                        style="height: 120px; width:100%; background: inherit !important; border: 0px !important; box-shadow: none !important;"
                        data-href="{{action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorAssignmentController@confirmAssignment', [$pump->assignment_id])}}"
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
    __('pumperdashboard::lang.all_your_daily_collection')])
    
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="list_daily_collection_table" style="width: 100%;">
            <thead>
                <tr>
                    <th>@lang('pumperdashboard::lang.date')</th>
                    <th>@lang('pumperdashboard::lang.location')</th>
                    <th>@lang('pumperdashboard::lang.pump_operator')</th>
                    <th>@lang('pumperdashboard::lang.shift_number')</th>
                    <th>@lang('pumperdashboard::lang.pump_no')</th>
                    <th>@lang('pumperdashboard::lang.starting_meter')</th>
                    <th>@lang('pumperdashboard::lang.closing_meter')</th>
                    <th>@lang('pumperdashboard::lang.shift_closed')</th>
                    <th>@lang('pumperdashboard::lang.sold_ltr')</th>
                    <th>@lang('pumperdashboard::lang.testing_ltr')</th>
                    <th>@lang('pumperdashboard::lang.sold_amount')</th>

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



