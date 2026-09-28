<!-- Main content -->
<section class="content">
    @if(empty($only_pumper))
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('current_meter_location_id', __('pumperdashboard::lang.location') . ':') !!}
                    {!! Form::select('current_meter_location_id', $business_locations, null, ['class' => 'form-control
                    select2', 'placeholder'
                    => __('pumperdashboard::lang.all'), 'id' => 'current_meter_location_id', 'style' => 'width:100%']); !!}
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('current_meter_pump_operators', __('pumperdashboard::lang.pump_operator') . ':') !!}
                    {!! Form::select('current_meter_pump_operators', $pump_operators, null, ['class' => 'form-control
                    select2', 'placeholder'
                    => __('pumperdashboard::lang.all'), 'id' => 'current_meter_pump_operators', 'style' => 'width:100%']); !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('current_meter_pump_no', __('pumperdashboard::lang.pumps') . ':') !!}
                    {!! Form::select('current_meter_pump_no', $pumps->pluck('pump_name', 'id'), null, ['class' => 'form-control
                    select2',
                    'placeholder'
                    => __('pumperdashboard::lang.all'), 'id' => 'current_meter_pump_no', 'style' => 'width:100%']); !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('current_meter_date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('current_meter_date_range', @format_date('today') . ' ~ ' .
                    @format_date('today') , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' =>
                    'form-control', 'id' => 'current_meter_date_range', 'readonly']); !!}
                </div>
            </div>
            @endcomponent
        </div>
    </div>
    @endif

    @component('components.widget', ['class' => 'box-primary', 'title' =>
    __('pumperdashboard::lang.current_meter')])
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="pump_operators_current_meter_table" style="width: 100%;">
            <thead>
                <tr>
                    <th>@lang('pumperdashboard::lang.date_and_time')</th>
                    <th>@lang('pumperdashboard::lang.location')</th>
                    <th>@lang('pumperdashboard::lang.pump')</th>
                    <th>@lang('pumperdashboard::lang.pump_operator')</th>
                    <th>@lang('pumperdashboard::lang.starting_meter')</th>
                    <th>@lang('pumperdashboard::lang.last_time_meter')</th>
                    <th>@lang('pumperdashboard::lang.current_meter')</th>
                    <th>@lang('pumperdashboard::lang.new_sale_amount')</th>
                    <th>@lang('pumperdashboard::lang.total_sale_amount')</th>
                </tr>
            </thead>
        </table>
    </div>
    @endcomponent

</section>
<!-- /.content -->




