<style>
    /* IS2312 - keep the Meters with Payments table fitted to the active screen. */
    .petropd-is2312-fit-table {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
    }
    #pump_operators_meters_with_payments_table {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed;
        margin: 0 !important;
    }
    #pump_operators_meters_with_payments_table thead th {
        white-space: normal !important;
        vertical-align: middle !important;
        text-align: center;
        line-height: 1.08;
        padding: 7px 3px !important;
        overflow-wrap: anywhere;
    }
    #pump_operators_meters_with_payments_table tbody td {
        padding: 7px 3px !important;
        vertical-align: middle !important;
        overflow-wrap: anywhere;
    }
    @media (max-width: 767px) {
        #pump_operators_meters_with_payments_table {
            min-width: 900px;
        }
    }
</style>
<section class="content">
    @component('components.filters', ['title' => __('report.filters'), 'id' => 'meters_with_payment'])
        <div class="row">
            <div class="col-md-12">
                @if(empty($only_pumper))
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('meters_with_payments_pump_operators', __('petropd::lang.pump_operator') . ':') !!}
                            {!! Form::select('meters_with_payments_pump_operators', $pump_operators, null, ['class' => 'form-control
                            select2', 'placeholder'
                            => __('petropd::lang.all'), 'id' => 'meters_with_payments_pump_operators', 'style' => 'width:100%']); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('meters_with_payments_pump_no', __('petropd::lang.pumps') . ':') !!}
                            {!! Form::select('meters_with_payments_pump_no', $pumps->pluck('pump_name', 'id'), null, ['class' => 'form-control
                            select2',
                            'placeholder'
                            => __('petropd::lang.all'), 'id' => 'meters_with_payments_pump_no', 'style' => 'width:100%']); !!}
                        </div>
                    </div>
                @endif
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('meters_with_payments_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('meters_with_payments_date_range', @format_date('today') . ' ~ ' .
                        @format_date('today') , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' =>
                        'form-control', 'id' => 'meters_with_payments_date_range', 'readonly']); !!}
                    </div>
                </div>
            </div>
        </div>
    @endcomponent
    
    @component('components.widget', ['class' => 'box-primary', 'title' => __('petropd::lang.all_your_meters_with_payments')])
        <div class="table-responsive petropd-is2312-fit-table">
            <table class="table table-bordered table-striped" id="pump_operators_meters_with_payments_table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>@lang('petropd::lang.date')</th>
                        <th>@lang('petropd::lang.time')</th>
                        <th>@lang('petropd::lang.pump_operator')</th>
                        <th>@lang('petropd::lang.collection_form_no')</th>
                        <th>Pumps</th>
                        <th>Unit Price</th>
                        <th>Last Meter</th>
                        <th>New Meter</th>
                        <th>Qty Sold</th>
                        <th>Total Sold Amount</th>
                        <th>@lang('petropd::lang.payment_type')</th>
                        <th>Total Payment Entered</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent

</section>
