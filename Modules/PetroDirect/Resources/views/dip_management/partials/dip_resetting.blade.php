<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('resetting_location_id', $business_locations, null, ['class' => 'form-control
                    select2',
                    'placeholder' => __('petrodirect::lang.all'), 'id' => 'resetting_location_id', 'style' => 'width:100%']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('tank_id', __('petrodirect::lang.tanks') . ':') !!}
                    {!! Form::select('resetting_tank_id', $tanks, null, ['class' => 'form-control select2', 'placeholder'
                    => __('petrodirect::lang.all'), 'id' => 'resetting_tank_id', 'style' => 'width:100%']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('products', __('petrodirect::lang.products') . ':') !!}
                    {!! Form::select('resetting_product_id', $products, null, ['class' => 'form-control select2',
                    'placeholder'
                    => __('petrodirect::lang.all'), 'id' => 'resetting_product_id', 'style' => 'width:100%']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('resetting_date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('resetting_date_range', @format_date('first day of this month') . ' ~ ' .
                    @format_date('last
                    day of this month') , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' =>
                    'form-control', 'id' => 'resetting_date_range', 'readonly']); !!}
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' => __('petrodirect::lang.dip_report')])
    @can('add_dip_resetting')
    @slot('tool')
    <button type="button" class="btn  btn-primary btn-modal pull-right"
                data-href="{{action('\Modules\PetroDirect\Http\Controllers\DipManagementController@addResettingDip')}}"
                data-container=".dip_modal">
                <i class="fa fa-balance-scale"></i> @lang('petrodirect::lang.add_resetting')</button>
  
    @endslot
    @endcan
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-5 text-red" style="margin-top: 14px;">
                <b>@lang('petrodirect::lang.date_range'): <span class="resetting_from_date"></span> @lang('petrodirect::lang.to') <span
                        class="resetting_to_date"></span> </b>
            </div>
            <div class="col-md-7">
                <div class="text-center pull-left">
                    <h5 style="font-weight: bold;">{{request()->session()->get('business.name')}} <br>
                        <span class="resetting_location_name">@lang('petrodirect::lang.all')</span></h5>
                </div>
            </div>
        </div>
        <div class="row" style="margin-top: 15px;">
            <div class="col-md-3"><b>@lang('petrodirect::lang.tank'): <span class="resetting_tank"></span></b></div>
            <div class="col-md-3"><b>@lang('petrodirect::lang.product'): <span class="resetting_product"></span></b></div>
            <div class="col-md-2"><b>@lang('petrodirect::lang.total_loss'): <span class="resetting_total_loss"></span></b></div>
            <div class="col-md-2"><b>@lang('petrodirect::lang.total_excess'): <span class="resetting_total_excess"></span></b></div>
            <div class="col-md-2"><b>@lang('petrodirect::lang.net_difference'): <span class="resetting_net_difference"></span></b></div>
        </div>
        <div class="row" style="margin-top: 20px;">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="dip_resetting_table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>@lang('petrodirect::lang.add_dip_no')</th>
                            <th>@lang('petrodirect::lang.date')</th>
                            <th>@lang('petrodirect::lang.location')</th>
                            <th>@lang('petrodirect::lang.tank')</th>
                            <th>@lang('petrodirect::lang.product')</th>
                            <th>@lang('petrodirect::lang.current_qty')</th>
                            <th>@lang('petrodirect::lang.qty_difference')</th>
                            <th>@lang('petrodirect::lang.new_qty')</th>
                            <th>@lang('petrodirect::lang.reason')</th>

                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    @endcomponent

    <div class="modal fade settlement_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
</section>
<!-- /.content -->