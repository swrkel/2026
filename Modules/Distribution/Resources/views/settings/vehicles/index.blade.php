<section class="content">

    {{-- <div class="row">
        <div class="col-md-12">
            @component('distribution::components.filters', ['title' => __('report.filters')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('vehicle_type_filter', 'Vehicle Type' . ':') !!}
                        {!! Form::select('vehicle_type_filter', $vehicle_types ?? [], null, [
                            'class' => 'form-control select2 input-sm',
                            'placeholder' => 'All',
                            'id' => 'vehicle_type_filter'
                        ]) !!}
                    </div>
                </div>
            @endcomponent
        </div>
    </div> --}}

    @component('distribution::components.widget', ['class' => 'box-primary', 'title' => 'Vehicles'])
        @slot('tool')
            <div class="box-tools">
                <button type="button"
                    class="btn btn-primary btn-modal pull-right"
                    data-href="{{ action('\Modules\Distribution\Http\Controllers\DistributionVehiclesController@create') }}"
                    data-container="#vehicleModal" onclick="if(window.openDistributionSettingsModal){return window.openDistributionSettingsModal(this,event);}">
                    <i class="fa fa-plus"></i> @lang('messages.add')
                </button>
            </div>
        @endslot

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="vehicles_table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Vehicle No</th>
                        <th>Type</th>
                        <th>Brand</th>
                        <th>Model</th>
                        <th>Revenue License Renewal</th>
                        <th>Starting Meter</th>
                        <th>Added By</th>
                        <th class="notexport">@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>

    @endcomponent

    {{-- Modal --}}
    <div class="modal fade" id="vehicleModal" tabindex="-1" role="dialog"></div>

</section>
