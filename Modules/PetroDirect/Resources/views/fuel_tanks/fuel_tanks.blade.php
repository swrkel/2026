<!-- Main content -->
<section class="content">
    
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('fueltanks_tank_number', __('petrodirect::lang.fuel_tank_number') . ':') !!}
                        {!! Form::select('fueltanks_tank_number', $tank_numbers, null, ['class' => 'form-control
                        select2 daily_report_change',
                        'placeholder' => __('petrodirect::lang.all'), 'id' => 'fueltanks_tank_number', 'style' =>
                        'width:100%']); !!}
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('fueltanks_tank_location_id', __('petrodirect::lang.location') . ':') !!}
                        {!! Form::select('fueltanks_tank_location_id', $business_locations, null, ['class' => 'form-control
                        select2 ', 'id' => 'fueltanks_location_id', 'style' =>
                        'width:100%']); !!}
                    </div>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    
    
    @component('components.widget', ['class' => 'box-primary', 'title' => __('petrodirect::lang.all_your_fuel_tanks')])
    @slot('tool')
    <div class="box-tools pull-right">
        <button type="button" class="btn btn-primary pull-right btn-modal add_fuel_tank"
            data-href="{{action('\Modules\PetroDirect\Http\Controllers\FuelTankController@create')}}"
            data-container=".fuel_tank_modal">
            <i class="fa fa-plus"></i> @lang('messages.add')</button>
        
        <a class="btn  btn-danger"
                href="{{action('\Modules\PetroDirect\Http\Controllers\FuelTankController@import')}}">
                <i class="fa fa-download "></i> @lang('petrodirect::lang.import')</a> &nbsp;
    </div>
    <hr>
    @endslot
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="fuel_tanks_table" width="100%">
            <thead>
                <tr>
                    <th>@lang('petrodirect::lang.date')</th>
                    <th>@lang('petrodirect::lang.location')</th>
                    <th>@lang('petrodirect::lang.fuel_tank_number')</th>
                    <th>@lang('petrodirect::lang.product_name')</th>
                    <th>@lang('petrodirect::lang.storage_volume')</th>
                    <th>@lang('petrodirect::lang.current_balance')</th>
                    <th>@lang('petrodirect::lang.bulk_tank')</th>
                    <th class="notexport">@lang('messages.action')</th>

                </tr>
            </thead>
        </table>
    </div>
    @endcomponent

</section>
<!-- /.content -->