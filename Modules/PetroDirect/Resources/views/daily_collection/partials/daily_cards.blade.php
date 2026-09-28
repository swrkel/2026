

<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang( 'petrodirect::lang.daily_cards', ['contacts' => __('petrodirect::lang.mange_daily_cards') ])</h4>
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">@lang('petrodirect::lang.daily_cards')</a></li>
                    <li><span>@lang( 'petrodirect::lang.daily_cards', ['contacts' => __('petrodirect::lang.mange_daily_cards') ])</span></li>
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
               
            <div class="row">
                 <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('dc_location_id',  __('purchase.business_location') . ':') !!}
                        {!! Form::select('dc_location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('petrodirect::lang.all'), 'style' => 'width:100%']); !!}
                    </div>
                </div>
                
                 <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('dc_pump_operator', __('petrodirect::lang.pump_operator').':') !!}<br>
                        {!! Form::select('dc_pump_operator', $assigned_operators, null, ['class' => 'form-control select2', 'placeholder' => __('petrodirect::lang.all'), 'style' => 'width:100%']); !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('dc_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('dc_date_range', @format_date('first day of this month') . ' ~ ' . @format_date('last day of this month') , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'dc_date_range', 'readonly']); !!}
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('customer_id', __('petrodirect::lang.customer').':') !!}
                        {!! Form::select('customer_id', $customers, null, ['class' => 'form-control select2', 'style' => 'width: 100%;','required','placeholder' => __('petrodirect::lang.all')]); !!}
                    </div>
                </div>
                
                <div class="clearfix"></div>
                
                <div class="col-md-3">
                        <div class="form-group">
                        {!! Form::label('card_type', __('petrodirect::lang.card_type').':') !!}
                        {!! Form::select('card_type', $card_types, null, ['class' => 'form-control card_fields
                        select2', 'style' => 'width: 100%;', 'placeholder' => __('petrodirect::lang.all' ) ,'required']); !!}
                    </div>
                </div>
            
                <div class="col-md-3">
                        <div class="form-group">
                        {!! Form::label('slip_no', __('petrodirect::lang.slip_no').':') !!}
                        {!! Form::select('slip_no', $slip_nos, null, ['class' => 'form-control card_fields
                        select2', 'style' => 'width: 100%;', 'placeholder' => __('petrodirect::lang.all' ) ,'required']); !!}
                    </div>
                </div>
                
                 <div class="col-md-3">
                        <div class="form-group">
                        {!! Form::label('card_number', __('petrodirect::lang.card_number').':') !!}
                        {!! Form::select('card_number', $card_numbers, null, ['class' => 'form-control card_fields
                        select2', 'style' => 'width: 100%;', 'placeholder' => __('petrodirect::lang.all' ) ,'required']); !!}
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('dc_settlement_id',  __('petrodirect::lang.settlement_no') . ':') !!}
                        {!! Form::select('dc_settlement_id', $daily_card_settlements, null, ['class' => 'form-control select2', 'placeholder' => __('petrodirect::lang.all'), 'style' => 'width:100%']); !!}
                    </div>
                </div>
                
                 <div class="clearfix"></div>
                
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('dc_status', __('petrodirect::lang.status').':') !!}<br>
                        {!! Form::select('dc_status', array('pending' => 'Pending', 'completed' => 'Completed'), null, ['class' => 'form-control select2', 'placeholder' => __('petrodirect::lang.all'), 'style' => 'width:100%']); !!}
                    </div>
                </div>
                
            </div>
                
                
            @endcomponent
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' => __('petrodirect::lang.all_your_daily_cards')])
    @slot('tool')
    <div class="box-tools pull-right">
            <button type="button" class="btn  btn-primary btn-modal"
                data-href="{{action('\Modules\PetroDirect\Http\Controllers\DailyCardController@create', ['type' => 'daily_collection'])}}"
                data-container=".pump_modal">
                <i class="fa fa-plus"></i> @lang('messages.add')</button>
        
    </div>
    @endslot
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="daily_card_table" width="100%">
            <thead>
                <tr>
                    <th>@lang('petrodirect::lang.date')</th>
                    <th>@lang('petrodirect::lang.location')</th>
                    <th>@lang('petrodirect::lang.pump_operator')</th>
                    <th>@lang('petrodirect::lang.shift_number')</th>
                    <th>@lang('petrodirect::lang.collection_form_no')</th>
                    <th>@lang('petrodirect::lang.cusotmer_name' )</th>
                    <th>@lang('petrodirect::lang.card_type' )</th>
                    <th>@lang('petrodirect::lang.card_number' )</th>
                    <th>@lang('petrodirect::lang.amount' )</th>
                    <th>@lang('petrodirect::lang.total_collection')</th>
                    <th>@lang('petrodirect::lang.slip_no' )</th>
                    <th>@lang('petrodirect::lang.settlement_no')</th>
                    <th>@lang('lang_v1.note') </th>
                    <th>@lang('petrodirect::lang.status')</th>
                    <th>@lang('petrodirect::lang.action' )</th>
                </tr>
            </thead>
        </table>
    </div>
    @endcomponent

    <div class="modal fade pump_operator_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    <div id="daily_card_print"></div>
   
</section>
<!-- /.content -->
