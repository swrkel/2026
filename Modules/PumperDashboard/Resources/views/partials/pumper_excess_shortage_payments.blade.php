<style>
    /* IS2306: fit the complete table to the available card width and keep it usable on small screens. */
    .pd-excess-shortage-scroll {
        width: 100%;
        max-width: 100%;
        overflow-x: auto !important;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }
    .pd-excess-shortage-scroll .dataTables_wrapper {
        width: 100%;
        max-width: 100%;
    }
    #pumper_excess_shortage_payments_table,
    #pumper_excess_shortage_payments_table.dataTable {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed !important;
        font-size: 11px;
    }
    #pumper_excess_shortage_payments_table > thead > tr > th,
    #pumper_excess_shortage_payments_table > tbody > tr > td {
        padding: 5px 3px !important;
        vertical-align: middle !important;
        line-height: 1.15;
        overflow-wrap: anywhere;
        word-break: normal;
    }
    #pumper_excess_shortage_payments_table > thead > tr > th {
        white-space: normal !important;
        text-align: center;
        font-size: 10.5px !important;
        line-height: 1.05 !important;
    }
    #pumper_excess_shortage_payments_table > thead .pd-th-line {
        display: block;
        white-space: nowrap;
    }

    @media (max-width: 1366px) {
        #pumper_excess_shortage_payments_table,
        #pumper_excess_shortage_payments_table.dataTable { font-size: 10.5px; }
        #pumper_excess_shortage_payments_table > thead > tr > th { font-size: 10px !important; }
        #pumper_excess_shortage_payments_table > thead > tr > th,
        #pumper_excess_shortage_payments_table > tbody > tr > td { padding: 4px 2px !important; }
    }

    @media (max-width: 767px) {
        #pumper_excess_shortage_payments_table,
        #pumper_excess_shortage_payments_table.dataTable {
            min-width: 720px !important;
            font-size: 10px;
        }
        #pumper_excess_shortage_payments_table > thead > tr > th { font-size: 9.5px !important; }
    }
</style>
<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('pesp_location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('pesp_location_id', $business_locations, null, ['class' => 'form-control select2',
                    'placeholder' => __('pumperdashboard::lang.all'), 'style' => 'width:100%']); !!}
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('pesp_pump_operator', __('pumperdashboard::lang.pump_operator').':') !!}
                    {!! Form::select('pesp_pump_operator', $pump_operators, null, ['class' => 'form-control select2' ,
                    'style' => 'width:100%',
                    'placeholder' => __('pumperdashboard::lang.all')]); !!}
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
                    {!! Form::label('pesp_type', __('pumperdashboard::lang.type') . ':') !!}
                    {!! Form::select('pesp_type', ['commission' => __('pumperdashboard::lang.commission'),'excess' =>
                    __('pumperdashboard::lang.excess'),'shortage' => __('pumperdashboard::lang.shortage')], null, ['class' => 'form-control
                    select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('pesp_payment_type', __('pumperdashboard::lang.payment_type') . ':') !!}
                    {!! Form::select('pesp_payment_type', $payment_types, null, ['class' => 'form-control
                    select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' =>
    __('pumperdashboard::lang.pumper_excess_shortage_payments')])
    <div class="table-responsive pd-excess-shortage-scroll">
        <table class="table table-bordered table-striped" id="pumper_excess_shortage_payments_table" style="width: 100%;">
            <colgroup>
                <col style="width:8%"><col style="width:12%"><col style="width:11%"><col style="width:11%">
                <col style="width:14%"><col style="width:11%"><col style="width:10%"><col style="width:12%"><col style="width:11%">
            </colgroup>
            <thead>
                <tr>
                    <th>@lang('pumperdashboard::lang.action')</th>
                    <th><span class="pd-th-line">Transaction</span><span class="pd-th-line">Date</span></th>
                    <th><span class="pd-th-line">Vehicle</span><span class="pd-th-line">No</span></th>
                    <th>@lang('pumperdashboard::lang.location')</th>
                    <th><span class="pd-th-line">Pump</span><span class="pd-th-line">Operator</span></th>
                    <th><span class="pd-th-line">Current</span><span class="pd-th-line">Shortage</span></th>
                    <th><span class="pd-th-line">Current</span><span class="pd-th-line">Excess</span></th>
                    <th><span class="pd-th-line">Shortage</span><span class="pd-th-line">Recovered</span></th>
                    <th><span class="pd-th-line">Excess</span><span class="pd-th-line">Paid</span></th>
                </tr>
            </thead>

            <tfoot>
            </tfoot>
        </table>
    </div>
    @endcomponent

</section>
<!-- /.content -->




