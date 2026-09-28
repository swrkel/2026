


<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">@lang('petrodirect::lang.daily_cheques')</a></li>
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
                        {!! Form::label('daily_cheque_location_id',  __('purchase.business_location') . ':') !!}
                        {!! Form::select('daily_cheque_location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('petrodirect::lang.all'), 'style' => 'width:100%']); !!}
                    </div>
                </div>
                
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('daily_cheque_pump_operator', __('petrodirect::lang.pump_operator').':') !!}
                        {!! Form::select('daily_cheque_pump_operator', $pump_operators, null, ['class' => 'form-control select2', 'placeholder' => __('petrodirect::lang.all')]); !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('daily_cheque_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('daily_cheque_date_range', @format_date('first day of this month') . ' ~ ' . @format_date('last day of this month') , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('daily_cheque_settlement_id',  __('petrodirect::lang.settlement_no') . ':') !!}
                        {!! Form::select('daily_cheque_settlement_id', $cheques_settlements, null, ['class' => 'form-control select2', 'placeholder' => __('petrodirect::lang.all'), 'style' => 'width:100%']); !!}
                    </div>
                </div>
                
                <div class="clearfix"></div>
                
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('daily_cheque_status', __('petrodirect::lang.status').':') !!}<br>
                        {!! Form::select('daily_cheque_status', array('pending' => 'Pending', 'completed' => 'Completed'), null, ['class' => 'form-control select2', 'placeholder' => __('petrodirect::lang.all'), 'style' => 'width:100%']); !!}
                    </div>
                </div>
                
            @endcomponent
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' => __('petrodirect::lang.daily_cheques')])
    
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="daily_cheques_table" width="100%">
            <thead>
                <tr>
                    <th>@lang('petrodirect::lang.date_and_time')</th>
                    <th>@lang('petrodirect::lang.pump_operator')</th>
                    <th>@lang('petrodirect::lang.current_amount')</th>
                    <th>@lang('petrodirect::lang.total_collection')</th>
                    <th>@lang('petrodirect::lang.created_by')</th>
                    <th>@lang('petrodirect::lang.settlement_no')</th>
                    <th>@lang('petrodirect::lang.settlement_date')</th>
                    <th>@lang('petrodirect::lang.status')</th>
                    
                    <th>@lang('petrodirect::lang.customer')</th>
                    <th>@lang('petrodirect::lang.cheque_date')</th>
                    <th>@lang('petrodirect::lang.cheque_number')</th>
                    <th>@lang('petrodirect::lang.bank_name')</th>
                    
                    <th>@lang('messages.action')</th>

                </tr>
            </thead>
        </table>
    </div>
    @endcomponent

    <div class="modal fade pump_operator_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    <div id="daily_cheques_print"></div>

</section>
<!-- /.content -->
