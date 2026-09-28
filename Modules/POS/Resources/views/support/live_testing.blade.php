@extends('pos::layouts.app', ['title' => 'POS Live Testing Support'])

@section('pos_content')
<div class="ch-kpi-grid ch-standard-grid">
    <div class="ch-kpi">
        <div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-link"></i></div><div class="label-text">Route Checks</div></div>
        <div class="value">{{ collect($routeChecks)->where('ok', true)->count() }}/{{ count($routeChecks) }}</div>
        <div class="hint">Registered POS routes</div><div class="spark"></div>
    </div>
    <div class="ch-kpi success">
        <div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-database"></i></div><div class="label-text">Table Checks</div></div>
        <div class="value">{{ collect($tableChecks)->where('ok', true)->count() }}/{{ count($tableChecks) }}</div>
        <div class="hint">Required POS tables</div><div class="spark"></div>
    </div>
    <div class="ch-kpi warning">
        <div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-paint-brush"></i></div><div class="label-text">UI Standard</div></div>
        <div class="value">ERP</div>
        <div class="hint">Communication Hub layout</div><div class="spark"></div>
    </div>
    <div class="ch-kpi purple">
        <div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-users"></i></div><div class="label-text">Customers</div></div>
        <div class="value">Shared</div>
        <div class="hint">Uses Customers module</div><div class="spark"></div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="ch-card">
            <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-link text-primary"></i> Route Verification</h3><div class="ch-card-subtitle">Use this page after upload to confirm URL registration.</div></div></div>
            <div class="ch-card-body">
                <div class="table-responsive">
                    <table class="table table-bordered pos-standard-table">
                        <thead><tr><th>URL</th><th>Status</th><th>Open</th></tr></thead>
                        <tbody>
                        @foreach($routeChecks as $check)
                            <tr>
                                <td><code>{{ $check['uri'] }}</code></td>
                                <td>{!! $check['ok'] ? '<span class="label label-success">Registered</span>' : '<span class="label label-danger">Missing</span>' !!}</td>
                                <td><a href="{{ url($check['uri']) }}" class="btn btn-xs btn-primary" target="_blank">Open</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="ch-card">
            <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-database text-success"></i> Database Verification</h3><div class="ch-card-subtitle">Checks required POS standalone tables in the active tenant database.</div></div></div>
            <div class="ch-card-body">
                <div class="table-responsive">
                    <table class="table table-bordered pos-standard-table">
                        <thead><tr><th>Table</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach($tableChecks as $check)
                            <tr>
                                <td><code>{{ $check['table'] }}</code></td>
                                <td>{!! $check['ok'] ? '<span class="label label-success">Available</span>' : '<span class="label label-danger">Missing</span>' !!}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="ch-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-check-circle text-primary"></i> Final Live Testing Order</h3><div class="ch-card-subtitle">Recommended server-side verification sequence.</div></div></div>
    <div class="ch-card-body">
        <ol style="line-height:2; font-size:14px;">
            <li>Open <strong>/pos-module</strong> and confirm it uses the normal ERP sidebar/header.</li>
            <li>Open Sales Workspace and complete one cash sale.</li>
            <li>Open Products and confirm stock balance reduced.</li>
            <li>Open Register/Cash Drawer and confirm sale totals are included.</li>
            <li>Process one partial return and confirm stock is restored.</li>
            <li>Open Reports and compare daily sales/payment totals.</li>
            <li>Open Settings and confirm receipt/barcode/security pages load.</li>
        </ol>
    </div>
</div>
@endsection
