


<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">@lang('petrogeneral::lang.daily_shortage_excess')</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('se_location_id',  __('purchase.business_location') . ':') !!}
                        {!! Form::select('se_location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('petrogeneral::lang.all'), 'style' => 'width:100%']); !!}
                    </div>
                </div>
               
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('se_pump_operator', __('petrogeneral::lang.pump_operator').':') !!}
                        {!! Form::select('se_pump_operator', $pump_operators, null, ['class' => 'form-control select2', 'placeholder' => __('petrogeneral::lang.all')]); !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('se_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('se_date_range', @format_date('first day of this month') . ' ~ ' . @format_date('last day of this month') , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'se_date_range', 'readonly']); !!}
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('se_settlement_id',  __('petrogeneral::lang.settlement_no') . ':') !!}
                        {!! Form::select('se_settlement_id', $shortage_settlements, null, ['class' => 'form-control select2', 'placeholder' => __('petrogeneral::lang.all'), 'style' => 'width:100%']); !!}
                    </div>
                </div>
                
                <div class="clearfix"></div>
                
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('se_status', __('petrogeneral::lang.status').':') !!}<br>
                        {!! Form::select('se_status', array('pending' => 'Pending', 'completed' => 'Completed'), null, ['class' => 'form-control select2', 'placeholder' => __('petrogeneral::lang.all'), 'style' => 'width:100%']); !!}
                    </div>
                </div>
                
            @endcomponent
        </div>
    </div>

    
    @component('components.widget', ['class' => 'box-primary', 'title' => __('petrogeneral::lang.daily_shortage_excess')])
    
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="daily_shortage_excess_table" width="100%">
            <thead>
                <tr>
                    <th>@lang('petrogeneral::lang.date_and_time')</th>
                    <th>@lang('petrogeneral::lang.location')</th>
                    <th>@lang('petrogeneral::lang.pump_operator')</th>
                    <th>@lang('petrogeneral::lang.shortage_amount')</th>
                    <th>@lang('petrogeneral::lang.excess_amount')</th>
                    <th>@lang('petrogeneral::lang.total_collection')</th>
                    <th>@lang('petrogeneral::lang.settlement_no')</th>
                    <th>@lang('petrogeneral::lang.settlement_date')</th>
                    <th>@lang('petrogeneral::lang.status')</th>
                    <th>@lang('messages.action')</th>

                </tr>
            </thead>
        </table>
    </div>
    @endcomponent

</section>
<!-- /.content -->
