<!-- Main content -->
<section class="content">
    
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('fueltanks_tank_number', __('petrogeneral::lang.fuel_tank_number') . ':') !!}
                        {!! Form::select('fueltanks_tank_number', $tank_numbers, null, ['class' => 'form-control
                        select2 daily_report_change',
                        'placeholder' => __('petrogeneral::lang.all'), 'id' => 'fueltanks_tank_number', 'style' =>
                        'width:100%']); !!}
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        {{--
                            IS1959 #1: this filter needs an "All" placeholder.

                            Without one, Form::select renders the first business
                            location as the selected option, and the fuel tank table
                            posts it on every load:
                                d.location_id = $('#fueltanks_location_id').val();
                            The controller then applies
                                where('fuel_tanks.location_id', request()->location_id)
                            so the list was silently filtered to one branch from the
                            moment the page opened. Tanks belonging to any other
                            location simply never appeared - the table drew its
                            headers and no rows.

                            The Fuel Tank Number filter directly above already has
                            'placeholder' => lang.all for this exact reason; the
                            location filter was missing it. With the placeholder the
                            select starts empty, no location_id is sent, and every
                            permitted tank is listed until the user chooses a branch.
                        --}}
                        {!! Form::label('fueltanks_tank_location_id', __('petrogeneral::lang.location') . ':') !!}
                        {!! Form::select('fueltanks_tank_location_id', $business_locations, null, ['class' => 'form-control
                        select2 ', 'placeholder' => __('petrogeneral::lang.all'), 'id' => 'fueltanks_location_id', 'style' =>
                        'width:100%']); !!}
                    </div>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    
    
    @component('components.widget', ['class' => 'box-primary', 'title' => __('petrogeneral::lang.all_your_fuel_tanks')])
    @slot('tool')
    <div class="box-tools pull-right">
        <button type="button" class="btn btn-primary pull-right btn-modal add_fuel_tank"
            data-href="{{action('\Modules\PetroGeneral\Http\Controllers\FuelTankController@create')}}"
            data-container=".fuel_tank_modal">
            <i class="fa fa-plus"></i> @lang('messages.add')</button>
        
        <a class="btn  btn-danger"
                href="{{action('\Modules\PetroGeneral\Http\Controllers\FuelTankController@import')}}">
                <i class="fa fa-download "></i> @lang('petrogeneral::lang.import')</a> &nbsp;
    </div>
    <hr>
    @endslot
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="fuel_tanks_table" width="100%">
            <thead>
                <tr>
                    <th>@lang('petrogeneral::lang.date')</th>
                    <th>@lang('petrogeneral::lang.location')</th>
                    <th>@lang('petrogeneral::lang.fuel_tank_number')</th>
                    <th>@lang('petrogeneral::lang.product_name')</th>
                    <th>@lang('petrogeneral::lang.storage_volume')</th>
                    <th>@lang('petrogeneral::lang.current_balance')</th>
                    <th>@lang('petrogeneral::lang.bulk_tank')</th>
                    <th class="notexport">@lang('messages.action')</th>

                </tr>
            </thead>
        </table>
    </div>
    @endcomponent

</section>
<!-- /.content -->