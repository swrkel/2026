


<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">@lang('petrogeneral::lang.daily_cash')</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content" style="padding:0px">
    <div class="row" style="padding:20px">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                            {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('petrogeneral::lang.all'), 'style' => 'width:100%']) !!}
                        </div>
                    </div>
                    {{-- 
                        * @ChangedBy Afes
                        * @Date 26-05-2021
                        * @Task 1526 
                    --}}
                    <div class="col-md-2">
                        <div class="form-group">
                            {!! Form::label('pump_operator', __('petrogeneral::lang.pump_operator').':') !!}
                            {!! Form::select('pump_operator', $assigned_operators, null, ['class' => 'form-control select2', 'placeholder' => __('petrogeneral::lang.all')]) !!}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('date_range', __('report.date_range') . ':') !!}
                            {!! Form::text('date_range', @format_date('first day of this month') . ' ~ ' . @format_date('last day of this month') , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'expense_date_range', 'readonly']) !!}
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <div class="form-group">
                            {!! Form::label('dcollection_settlement_id',  __('petrogeneral::lang.settlement_no') . ':') !!}
                            {!! Form::select('dcollection_settlement_id', $daily_collection_settlements, null, ['class' => 'form-control select2', 'placeholder' => __('petrogeneral::lang.all'), 'style' => 'width:100%']) !!}
                        </div>
                    </div>
                    
                    
                    <div class="col-md-2">
                        <div class="form-group">
                            {!! Form::label('dcollection_status', __('petrogeneral::lang.status').':') !!}<br>
                            {!! Form::select('dcollection_status', array('pending' => 'Pending', 'completed' => 'Completed'), null, ['class' => 'form-control select2', 'placeholder' => __('petrogeneral::lang.all'), 'style' => 'width:100%']) !!}
                        </div>
                    </div>
                </div>

                
            @endcomponent
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' => __('petrogeneral::lang.all_your_daily_cash')])
    @slot('tool')
    <div class="box-tools pull-right ">
            <button type="button" class="btn  btn-primary btn-modal"
                data-href="{{action('\Modules\PetroGeneral\Http\Controllers\DailyCollectionController@create')}}"
                data-container=".pump_modal">
                <i class="fa fa-plus"></i> @lang('messages.add')</button>
        
    </div>
    @endslot
    <div class="table-responsive" style="padding:5px">
        <table class="table table-bordered table-striped" id="daily_collection_table" width="100%">
            <thead>
                <tr>
                    <th>@lang('petrogeneral::lang.date_and_time')</th>
                    <th>@lang('petrogeneral::lang.location')</th>
                    <th>@lang('petrogeneral::lang.pump_operator')</th>
                    <th>@lang('petrogeneral::lang.shift_number')</th>
                    <th>@lang('petrogeneral::lang.collection_form_no')</th>
                    <th>@lang('petrogeneral::lang.current_amount')</th>
                    <th>@lang('petrogeneral::lang.total_collection')</th>
                    <th>@lang('petrogeneral::lang.created_by')</th>
                    <th>@lang('petrogeneral::lang.settlement_no')</th>
                    <th>@lang('petrogeneral::lang.settlement_date')</th>
                    <th>@lang('petrogeneral::lang.status')</th>
                    <th>@lang('messages.action')</th>

                </tr>
            </thead>
        </table>
    </div>
    @endcomponent

    <div class="modal fade pump_operator_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    <div id="daily_collection_print"></div>

</section>
<!-- /.content -->
