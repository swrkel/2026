<style>
    /* IS2306: fluid fit on normal screens; compact responsive fallback on phones. */
    .pd-meters-payments-scroll {
        width: 100%;
        max-width: 100%;
        overflow-x: auto !important;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }
    .pd-meters-payments-scroll .dataTables_wrapper {
        width: 100%;
        max-width: 100%;
    }
    #pump_operators_meters_with_payments_table,
    #pump_operators_meters_with_payments_table.dataTable {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed !important;
        font-size: 10.5px;
    }
    #pump_operators_meters_with_payments_table > thead > tr > th,
    #pump_operators_meters_with_payments_table > tbody > tr > td {
        padding: 5px 2px !important;
        vertical-align: middle !important;
        line-height: 1.12;
        overflow-wrap: anywhere;
        word-break: normal;
    }
    #pump_operators_meters_with_payments_table > thead > tr > th {
        white-space: normal !important;
        text-align: center;
        font-size: 10px !important;
        line-height: 1.05 !important;
    }
    #pump_operators_meters_with_payments_table > thead .pd-th-line {
        display: block;
        white-space: nowrap;
    }

    @media (max-width: 1366px) {
        #pump_operators_meters_with_payments_table,
        #pump_operators_meters_with_payments_table.dataTable { font-size: 10px; }
        #pump_operators_meters_with_payments_table > thead > tr > th { font-size: 9.5px !important; }
        #pump_operators_meters_with_payments_table > thead > tr > th,
        #pump_operators_meters_with_payments_table > tbody > tr > td { padding: 4px 2px !important; }
    }

    @media (max-width: 767px) {
        #pump_operators_meters_with_payments_table,
        #pump_operators_meters_with_payments_table.dataTable {
            min-width: 840px !important;
            font-size: 9.5px;
        }
        #pump_operators_meters_with_payments_table > thead > tr > th { font-size: 9px !important; }
    }
</style>
<section class="content">
    
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                @if(empty($only_pumper))
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('meters_with_payments_pump_operators', __('pumperdashboard::lang.pump_operator') . ':') !!}
                            {!! Form::select('meters_with_payments_pump_operators', $pump_operators, null, ['class' => 'form-control
                            select2', 'placeholder'
                            => __('pumperdashboard::lang.all'), 'id' => 'meters_with_payments_pump_operators', 'style' => 'width:100%']); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('meters_with_payments_pump_no', __('pumperdashboard::lang.pumps') . ':') !!}
                            {!! Form::select('meters_with_payments_pump_no', $pumps->pluck('pump_name', 'id'), null, ['class' => 'form-control
                            select2',
                            'placeholder'
                            => __('pumperdashboard::lang.all'), 'id' => 'meters_with_payments_pump_no', 'style' => 'width:100%']); !!}
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
            @endcomponent
        </div>
    </div>
    
    @component('components.widget', ['class' => 'box-primary', 'title' => __('pumperdashboard::lang.all_your_meters_with_payments')])
        <div class="table-responsive pd-meters-payments-scroll">
            <table class="table table-bordered table-striped" id="pump_operators_meters_with_payments_table" style="width: 100%;">
                <colgroup>
                    <col style="width:8%"><col style="width:6%"><col style="width:10%"><col style="width:9%">
                    <col style="width:6%"><col style="width:8%"><col style="width:8%"><col style="width:8%">
                    <col style="width:7%"><col style="width:11%"><col style="width:8%"><col style="width:11%">
                </colgroup>
                <thead>
                    <tr>
                        <th>@lang('pumperdashboard::lang.date')</th>
                        <th>@lang('pumperdashboard::lang.time')</th>
                        <th><span class="pd-th-line">Pump</span><span class="pd-th-line">Operator</span></th>
                        <th><span class="pd-th-line">Collection</span><span class="pd-th-line">Form No</span></th>
                        <th>Pumps</th>
                        <th><span class="pd-th-line">Unit</span><span class="pd-th-line">Price</span></th>
                        <th><span class="pd-th-line">Last</span><span class="pd-th-line">Meter</span></th>
                        <th><span class="pd-th-line">New</span><span class="pd-th-line">Meter</span></th>
                        <th><span class="pd-th-line">Qty</span><span class="pd-th-line">Sold</span></th>
                        <th><span class="pd-th-line">Total Sold</span><span class="pd-th-line">Amount</span></th>
                        <th><span class="pd-th-line">Payment</span><span class="pd-th-line">Types</span></th>
                        <th><span class="pd-th-line">Total Payment</span><span class="pd-th-line">Entered</span></th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent

</section>




