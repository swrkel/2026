<!-- Main content -->
<section class="content">
    @if(empty($only_pumper))
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('unload_stock_location_id', __('pumperdashboard::lang.location') . ':') !!}
                    {!! Form::select('unload_stock_location_id', $business_locations, null, ['class' => 'form-control
                    select2', 'placeholder'
                    => __('pumperdashboard::lang.all'), 'id' => 'unload_stock_location_id', 'style' => 'width:100%']); !!}
                </div>
            </div>
            
            
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('unload_stock_tank_id', __('pumperdashboard::lang.tank') . ':') !!}
                    {!! Form::select('unload_stock_tank_id', $tanks, null, ['class' => 'form-control
                    select2', 'placeholder'
                    => __('pumperdashboard::lang.all'), 'id' => 'unload_stock_tank_id', 'style' => 'width:100%']); !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('unload_stock_product_id', __('pumperdashboard::lang.product') . ':') !!}
                    {!! Form::select('unload_stock_product_id', $products, null, ['class' => 'form-control
                    select2',
                    'placeholder'
                    => __('pumperdashboard::lang.all'), 'id' => 'unload_stock_product_id', 'style' => 'width:100%']); !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('unload_stock_date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('unload_stock_date_range', @format_date('today') . ' ~ ' .
                    @format_date('today') , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' =>
                    'form-control', 'id' => 'unload_stock_date_range', 'readonly']); !!}
                </div>
            </div>
            @endcomponent
        </div>
    </div>
    @endif

    @component('components.widget', ['class' => 'box-primary', 'title' =>
    __('pumperdashboard::lang.unloaded_stocks')])
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="pump_operators_unload_stock_table" style="width: 100%;">
            <thead>
                <tr>
                    <th>@lang('pumperdashboard::lang.date_and_time')</th>
                    <th>@lang('pumperdashboard::lang.location')</th>
                    <th>@lang('pumperdashboard::lang.tank')</th>
                    <th>@lang('pumperdashboard::lang.product')</th>
                    <th>@lang('pumperdashboard::lang.current_dip')</th>
                    <th>@lang('pumperdashboard::lang.current_stock')</th>
                    <th>@lang('pumperdashboard::lang.unloaded_qty')</th>
                    <th>@lang('pumperdashboard::lang.total_qty')</th>
                    <th>@lang('pumperdashboard::lang.added_by')</th>
                </tr>
            </thead>
        </table>
    </div>
    @endcomponent

</section>
<!-- /.content -->




