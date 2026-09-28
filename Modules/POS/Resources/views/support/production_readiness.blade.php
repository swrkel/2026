@extends('pos::layouts.app', ['title' => 'POS Production Readiness'])

@section('pos_content')
<div class="ch-kpi-grid ch-standard-grid">
    <div class="ch-kpi"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-link"></i></div><div class="label-text">Routes</div></div><div class="value">{{ collect($routeChecks)->where('ok', true)->count() }}/{{ count($routeChecks) }}</div><div class="hint">Required POS URLs</div><div class="spark"></div></div>
    <div class="ch-kpi success"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-database"></i></div><div class="label-text">Tables</div></div><div class="value">{{ collect($tableChecks)->where('ok', true)->count() }}/{{ count($tableChecks) }}</div><div class="hint">Tenant DB readiness</div><div class="spark"></div></div>
    <div class="ch-kpi warning"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-paint-brush"></i></div><div class="label-text">UI Standard</div></div><div class="value">ERP</div><div class="hint">Communication Hub style</div><div class="spark"></div></div>
    <div class="ch-kpi purple"><div class="ch-kpi-top"><div class="ch-icon"><i class="fa fa-check-square-o"></i></div><div class="label-text">Testing</div></div><div class="value">Ready</div><div class="hint">Business workflow checks</div><div class="spark"></div></div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="ch-card">
            <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-link text-primary"></i> Route Readiness</h3><div class="ch-card-subtitle">These are the main URLs to test after deployment.</div></div></div>
            <div class="ch-card-body table-responsive">
                <table class="table table-bordered pos-standard-table"><thead><tr><th>Route</th><th>URL</th><th>Status</th></tr></thead><tbody>
                @foreach($routeChecks as $r)
                    <tr><td><code>{{ $r['name'] }}</code></td><td>{{ $r['url'] }}</td><td>{!! $r['ok'] ? '<span class="label label-success">Available</span>' : '<span class="label label-danger">Missing</span>' !!}</td></tr>
                @endforeach
                </tbody></table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="ch-card">
            <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-database text-success"></i> Table Readiness</h3><div class="ch-card-subtitle">Checks the active tenant database.</div></div></div>
            <div class="ch-card-body table-responsive">
                <table class="table table-bordered pos-standard-table"><thead><tr><th>Table</th><th>Status</th></tr></thead><tbody>
                @foreach($tableChecks as $t)
                    <tr><td><code>{{ $t['table'] }}</code></td><td>{!! $t['ok'] ? '<span class="label label-success">Available</span>' : '<span class="label label-danger">Missing</span>' !!}</td></tr>
                @endforeach
                </tbody></table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6"><div class="ch-card"><div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-paint-brush text-purple"></i> UI Verification</h3><div class="ch-card-subtitle">Use this against the live Communication Hub standard.</div></div></div><div class="ch-card-body"><ol style="line-height:2;font-size:14px;">@foreach($uiChecks as $item)<li>{{ $item }}</li>@endforeach</ol></div></div></div>
    <div class="col-md-6"><div class="ch-card"><div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-briefcase text-warning"></i> Business Workflow Verification</h3><div class="ch-card-subtitle">Recommended final test before POS sign-off.</div></div></div><div class="ch-card-body"><ol style="line-height:2;font-size:14px;">@foreach($businessChecks as $item)<li>{{ $item }}</li>@endforeach</ol></div></div></div>
</div>
@endsection
