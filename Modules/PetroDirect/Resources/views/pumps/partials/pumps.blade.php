<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('petrodirect::lang.all_your_list_pumps')])
    @slot('tool')
    <div class="box-tools pull-right">
            <button type="button" class="btn  btn-primary btn-modal"
                data-href="{{action('\Modules\PetroDirect\Http\Controllers\PumpController@create')}}"
                data-container=".fuel_tank_modal">
                <i class="fa fa-plus"></i> @lang('messages.add')</button>
            <a class="btn  btn-danger"
               href="{{action('\Modules\PetroDirect\Http\Controllers\PumpController@importPumps')}}">
                <i class="fa fa-download "></i> @lang('petrodirect::lang.import')</a>
        
    </div>
    <div class="clearfix"></div>
    @endslot
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="list_pumps_table" width="100%">
            <thead>
                <tr>
                    <th>@lang('petrodirect::lang.date')</th>
                    <th>@lang('petrodirect::lang.location')</th>
                    <th>@lang('petrodirect::lang.transaction_date')</th>
                    <th>@lang('petrodirect::lang.pump_no')</th>
                    <th>@lang('petrodirect::lang.pump_name')</th>
                    <th>@lang('petrodirect::lang.pump_starting_meter')</th>
                    <th>@lang('petrodirect::lang.pump_current_meter')</th>
                    <th>@lang('petrodirect::lang.product_name')</th>
                    <th>@lang('petrodirect::lang.fuel_tank')</th>
                    <th class="notexport">@lang('messages.action')</th>

                </tr>
            </thead>
        </table>
    </div>
    @endcomponent

    <div class="modal fade fuel_tank_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->