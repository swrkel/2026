@extends('pos::layouts.app', ['title' => 'POS Production Stabilization'])

@section('pos_content')
<div class="ch-kpi-grid ch-standard-grid">
    <div class="ch-kpi">
        <div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-bolt"></i></div><div class="label-text">Cashier Speed</div></div>
        <div class="value">Optimized</div>
        <div class="hint">Barcode focus + shortcuts</div><div class="spark"></div>
    </div>
    <div class="ch-kpi success">
        <div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-check-circle"></i></div><div class="label-text">Workflow Status</div></div>
        <div class="value">{{ collect($workflowChecks)->where('status', 'Ready')->count() }}/{{ count($workflowChecks) }}</div>
        <div class="hint">Production areas ready</div><div class="spark"></div>
    </div>
    <div class="ch-kpi warning">
        <div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-database"></i></div><div class="label-text">Tenant Tables</div></div>
        <div class="value">{{ collect($tableChecks)->where('ok', true)->count() }}/{{ count($tableChecks) }}</div>
        <div class="hint">Standalone POS tables</div><div class="spark"></div>
    </div>
    <div class="ch-kpi purple">
        <div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-print"></i></div><div class="label-text">Print Calibration</div></div>
        <div class="value">Ready</div>
        <div class="hint">Receipt/barcode checklist</div><div class="spark"></div>
    </div>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="ch-card">
            <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-tasks text-primary"></i> Production Workflow Checklist</h3><div class="ch-card-subtitle">Use this page during server testing to confirm the POS production readiness areas.</div></div></div>
            <div class="ch-card-body">
                <div class="table-responsive">
                    <table class="table table-bordered pos-standard-table">
                        <thead><tr><th>Area</th><th>Verification</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach($workflowChecks as $row)
                            <tr>
                                <td><strong>{{ $row['area'] }}</strong></td>
                                <td>{{ $row['check'] }}</td>
                                <td><span class="label label-success">{{ $row['status'] }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="ch-card">
            <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-print text-purple"></i> Receipt & Barcode Calibration</h3><div class="ch-card-subtitle">Final checks to run with the real printer/scanner hardware.</div></div></div>
            <div class="ch-card-body">
                <ol style="line-height:2;font-size:14px;">
                    @foreach($printerChecklist as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ol>
                <div class="alert alert-info" style="margin-top:15px;">
                    Scanner and printer behaviour depends on the installed device/driver. Use this checklist after deployment and send screenshots if alignment needs adjustment.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="ch-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-database text-success"></i> Standalone Table Readiness</h3><div class="ch-card-subtitle">Counts are read from the currently selected tenant database.</div></div></div>
    <div class="ch-card-body">
        <div class="table-responsive">
            <table class="table table-bordered pos-standard-table">
                <thead><tr><th>Table</th><th>Status</th><th class="text-right">Rows</th></tr></thead>
                <tbody>
                @foreach($tableChecks as $check)
                    <tr>
                        <td><code>{{ $check['table'] }}</code></td>
                        <td>{!! $check['ok'] ? '<span class="label label-success">Available</span>' : '<span class="label label-danger">Missing</span>' !!}</td>
                        <td class="text-right">{{ is_null($check['count']) ? '-' : number_format($check['count']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="ch-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-rocket text-primary"></i> Recommended Final Testing Order</h3><div class="ch-card-subtitle">Follow this sequence to catch business-impacting issues quickly.</div></div></div>
    <div class="ch-card-body">
        <ol style="line-height:2;font-size:14px;">
            <li>Open <strong>/pos-module/sales/workspace</strong> and scan/search a product.</li>
            <li>Complete one cash sale and one card sale.</li>
            <li>Complete one credit sale against a Customers module customer.</li>
            <li>Reprint receipt and verify thermal/A4 layout.</li>
            <li>Check product stock movement after sale.</li>
            <li>Process one partial return and one exchange.</li>
            <li>Close shift and compare cash drawer/reports.</li>
            <li>Open reports and verify daily/payment/item totals.</li>
        </ol>
    </div>
</div>
@endsection
