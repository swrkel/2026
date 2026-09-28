<style>
    /* IS2312 - keep the PD Operators payment table inside the available screen width. */
    .petropd-is2312-fit-table {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
    }
    #pumper_excess_shortage_payments_table {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed;
        margin: 0 !important;
    }
    #pumper_excess_shortage_payments_table thead th {
        white-space: normal !important;
        vertical-align: middle !important;
        text-align: center;
        line-height: 1.08;
        padding: 7px 4px !important;
        overflow-wrap: anywhere;
    }
    #pumper_excess_shortage_payments_table tbody td {
        padding: 7px 4px !important;
        vertical-align: middle !important;
        overflow-wrap: anywhere;
    }
    @media (max-width: 767px) {
        #pumper_excess_shortage_payments_table {
            min-width: 820px;
        }
    }
</style>
<!-- Main content -->
<section class="content">
    @component('components.filters', ['title' => __('report.filters'), 'id' => 'pumper_excess'])
        <div class="row">
            <div class="col-md-12">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('pesp_location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('pesp_location_id', $business_locations, null, ['class' => 'form-control select2',
                        'placeholder' => __('petropd::lang.all'), 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('pesp_pump_operator', __('petropd::lang.pump_operator').':') !!}
                        {!! Form::select('pesp_pump_operator', $pump_operators, null, ['class' => 'form-control select2' ,
                        'style' => 'width:100%',
                        'placeholder' => __('petropd::lang.all')]); !!}
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
                        {!! Form::label('pesp_type', __('petropd::lang.type') . ':') !!}
                        {!! Form::select('pesp_type', ['commission' => __('petropd::lang.commission'),'excess' =>
                        __('petropd::lang.excess'),'shortage' => __('petropd::lang.shortage')], null, ['class' => 'form-control
                        select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('pesp_payment_type', __('petropd::lang.payment_type') . ':') !!}
                        {!! Form::select('pesp_payment_type', $payment_types, null, ['class' => 'form-control
                        select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                    </div>
                </div>
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' =>
    __('petropd::lang.pumper_excess_shortage_payments')])
    <div class="table-responsive petropd-is2312-fit-table">
        <table class="table table-bordered table-striped" id="pumper_excess_shortage_payments_table" style="width: 100%;">
            <thead>
                <tr>
                    <th>@lang('petropd::lang.action')</th>
                    <th>@lang('petropd::lang.transaction_date')</th>
                    <th>@lang('petropd::lang.reference_no')</th>
                    <th>@lang('petropd::lang.location')</th>
                    <th>@lang('petropd::lang.pump_operator')</th>
                    <th>@lang('petropd::lang.current_shortage')</th>
                    <th>@lang('petropd::lang.current_excess')</th>
                    <th>@lang('petropd::lang.shortage_recovered')</th>
                    <th>@lang('petropd::lang.excess_paid')</th>

                </tr>
            </thead>

            <tfoot>
            </tfoot>
        </table>
    </div>
    @endcomponent

</section>
<!-- /.content -->
