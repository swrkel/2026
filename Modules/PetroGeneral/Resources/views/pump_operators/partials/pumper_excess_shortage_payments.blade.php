<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('pesp_location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('pesp_location_id', $business_locations, null, ['class' => 'form-control select2',
                    'placeholder' => __('petrogeneral::lang.all'), 'style' => 'width:100%']); !!}
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('pesp_pump_operator', __('petrogeneral::lang.pump_operator').':') !!}
                    {!! Form::select('pesp_pump_operator', $pump_operators, null, ['class' => 'form-control select2' ,
                    'style' => 'width:100%',
                    'placeholder' => __('petrogeneral::lang.all')]); !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('pesp_date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('pesp_date_range', @format_date('today') . ' ~ ' . @format_date('today') , [
                    'placeholder' => __('lang_v1.select_a_date_range'), 'class' =>
                    'form-control', 'id' => 'pesp_date_range', 'readonly']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('pesp_type', __('petrogeneral::lang.type') . ':') !!}
                    {!! Form::select('pesp_type', ['commission' => __('petrogeneral::lang.commission'),'excess' =>
                    __('petrogeneral::lang.excess'),'shortage' => __('petrogeneral::lang.shortage')], null, ['class' => 'form-control
                    select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('pesp_payment_type', __('petrogeneral::lang.payment_type') . ':') !!}
                    {!! Form::select('pesp_payment_type', $payment_types, null, ['class' => 'form-control
                    select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' =>
    __('petrogeneral::lang.pumper_excess_shortage_payments')])
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="pumper_excess_shortage_payments_table" style="width: 100%;">
            <thead>
                <tr>
                    <th>@lang('petrogeneral::lang.action')</th>
                    <th>@lang('petrogeneral::lang.transaction_date')</th>
                    <th>@lang('petrogeneral::lang.reference_no')</th>
                    <th>@lang('petrogeneral::lang.location')</th>
                    <th>@lang('petrogeneral::lang.pump_operator')</th>
                    <th>@lang('petrogeneral::lang.current_shortage')</th>
                    <th>@lang('petrogeneral::lang.current_excess')</th>
                    <th>@lang('petrogeneral::lang.shortage_recovered')</th>
                    <th>@lang('petrogeneral::lang.excess_paid')</th>

                </tr>
            </thead>

            <tfoot>
            </tfoot>
        </table>
    </div>
    @endcomponent

</section>
<!-- /.content -->
