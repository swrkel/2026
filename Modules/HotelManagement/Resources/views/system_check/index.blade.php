@extends('hotelmanagement::layouts.app')

@section('hotel_content')
<section class="content-header">
    <h1>Hotel Management <small>System Check</small></h1>
</section>

<section class="content">
    @include('hotelmanagement::partials.nav')

    <div class="row">
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Tables</div><div class="hm-kpi-value">{{ $summary['tables_ok'] }}/{{ $summary['tables_total'] }}</div><div class="hm-kpi-sub">Required database tables</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Routes</div><div class="hm-kpi-value">{{ $summary['routes_ok'] }}/{{ $summary['routes_total'] }}</div><div class="hm-kpi-sub">Page route binding</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Scope</div><div class="hm-kpi-value">{{ $summary['scope_ok'] }}/{{ $summary['scope_total'] }}</div><div class="hm-kpi-sub">Business/location columns</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Registry</div><div class="hm-kpi-value">{{ $summary['menu_records'] }}</div><div class="hm-kpi-sub">Active menu entries / {{ $summary['permission_records'] }} permissions</div></div></div>
    </div>

    <div class="box hm-card">
        <div class="box-header with-border"><h3 class="box-title">Readiness Checklist</h3></div>
        <div class="box-body">
            <div class="hm-toolbar">
                <input type="text" class="form-control hm-search-input" placeholder="Search checks" style="max-width:260px;">
                <button type="button" class="btn hm-btn-print" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped hm-table">
                    <thead><tr><th>Type</th><th>Check</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach($tableChecks as $check)
                            <tr><td>{{ ucfirst($check['type']) }}</td><td>{{ $check['name'] }}</td><td><span class="hm-badge {{ $check['status'] ? 'active' : 'maintenance' }}">{{ $check['status'] ? 'OK' : 'Missing' }}</span></td></tr>
                        @endforeach
                        @foreach($routeChecks as $check)
                            <tr><td>{{ ucfirst($check['type']) }}</td><td>{{ $check['name'] }}</td><td><span class="hm-badge {{ $check['status'] ? 'active' : 'maintenance' }}">{{ $check['status'] ? 'OK' : 'Missing' }}</span></td></tr>
                        @endforeach
                        @foreach($scopeChecks as $check)
                            <tr><td>{{ ucfirst($check['type']) }}</td><td>{{ $check['name'] }}</td><td><span class="hm-badge {{ $check['status'] ? 'active' : 'maintenance' }}">{{ $check['status'] ? 'OK' : 'Missing' }}</span></td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
