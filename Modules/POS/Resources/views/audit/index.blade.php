@extends('pos::layouts.app')

@section('pos_content')
<div class="syzygy-dashboard-grid">
    <div class="syzygy-metric-card syzygy-card-blue"><div class="syzygy-metric-top"><span class="syzygy-metric-icon"><i class="fa fa-link"></i></span><div><div class="syzygy-metric-title">Route Check</div><div class="syzygy-metric-value">{{ collect($routeChecks)->where('status','Ready')->count() }}/{{ count($routeChecks) }}</div></div></div><div class="syzygy-metric-footer"><span>POS URLs</span><strong>Live</strong></div></div>
    <div class="syzygy-metric-card syzygy-card-green"><div class="syzygy-metric-top"><span class="syzygy-metric-icon"><i class="fa fa-database"></i></span><div><div class="syzygy-metric-title">Table Check</div><div class="syzygy-metric-value">{{ collect($tableChecks)->where('status','Ready')->count() }}/{{ count($tableChecks) }}</div></div></div><div class="syzygy-metric-footer"><span>POS SQL</span><strong>Tenant DB</strong></div></div>
    <div class="syzygy-metric-card syzygy-card-amber"><div class="syzygy-metric-top"><span class="syzygy-metric-icon"><i class="fa fa-shield"></i></span><div><div class="syzygy-metric-title">Dependency Scan</div><div class="syzygy-metric-value">{{ count($findings) }}</div></div></div><div class="syzygy-metric-footer"><span>Review items</span><strong>Audit</strong></div></div>
    <div class="syzygy-metric-card syzygy-card-purple"><div class="syzygy-metric-top"><span class="syzygy-metric-icon"><i class="fa fa-paint-brush"></i></span><div><div class="syzygy-metric-title">UI Standard</div><div class="syzygy-metric-value">S361</div></div></div><div class="syzygy-metric-footer"><span>Communication Hub</span><strong>Applied</strong></div></div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="syzygy-panel"><div class="syzygy-panel-header"><h3 class="box-title"><span class="syzygy-title-icon"><i class="fa fa-link"></i></span>POS Route Health</h3></div><div class="box-body"><table class="table syzygy-table"><thead><tr><th>Route</th><th>URL</th><th>Status</th></tr></thead><tbody>@foreach($routeChecks as $row)<tr><td>{{ $row['name'] }}</td><td>{{ $row['url'] }}</td><td><span class="pos-badge {{ $row['status'] == 'Ready' ? 'pos-badge-success' : 'pos-badge-danger' }}">{{ $row['status'] }}</span></td></tr>@endforeach</tbody></table></div></div>
    </div>
    <div class="col-md-6">
        <div class="syzygy-panel"><div class="syzygy-panel-header"><h3 class="box-title"><span class="syzygy-title-icon"><i class="fa fa-database"></i></span>POS Table Health</h3></div><div class="box-body"><table class="table syzygy-table"><thead><tr><th>Table</th><th>Status</th></tr></thead><tbody>@foreach($tableChecks as $row)<tr><td>{{ $row['table'] }}</td><td><span class="pos-badge {{ $row['status'] == 'Ready' ? 'pos-badge-success' : 'pos-badge-danger' }}">{{ $row['status'] }}</span></td></tr>@endforeach</tbody></table></div></div>
    </div>
</div>

<div class="syzygy-panel"><div class="syzygy-panel-header"><h3 class="box-title"><span class="syzygy-title-icon"><i class="fa fa-search"></i></span>Standalone Dependency Review</h3></div><div class="box-body"><table class="table syzygy-table"><thead><tr><th>File</th><th>Detected Reference</th></tr></thead><tbody>@forelse($findings as $row)<tr><td>{{ $row['file'] }}</td><td>{{ $row['needle'] }}</td></tr>@empty<tr><td colspan="2" class="text-center">No obvious main-system dependency references detected.</td></tr>@endforelse</tbody></table></div></div>
@endsection
