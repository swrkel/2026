@extends('autoservice::layouts.master')
@section('title','Auto Service Production Audit')
@section('autoservice_content')
<div class="autoservice-dashboard-page">
    <div class="autoservice-dashboard-header">
        <div>
            <span class="autoservice-eyebrow">AUTO SERVICE STAGE 040</span>
            <h2>Final Production Audit</h2>
            <p>Read-only validation for tenant tables, business scope, route registration, operational queues and deployment sign-off.</p>
        </div>
        <div class="autoservice-header-actions">
            <a href="{{ route('autoservice.completion.index') }}" class="autoservice-secondary-action"><i class="fa fa-check-square-o"></i> Completion Check</a>
            <a href="{{ route('autoservice.dashboard') }}" class="autoservice-primary-action"><i class="fa fa-dashboard"></i> Dashboard</a>
        </div>
    </div>

    <div class="row autoservice-kpi-row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12"><div class="autoservice-kpi-card autoservice-kpi-blue"><div class="autoservice-kpi-top"><div class="autoservice-kpi-icon"><i class="fa fa-database"></i></div><div class="autoservice-kpi-title">Tenant Tables</div></div><div class="autoservice-kpi-value">{{ $summary['tables_total'] }}</div><div class="autoservice-kpi-footer"><span>{{ $summary['tenant_database'] ?: '-' }}</span></div></div></div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12"><div class="autoservice-kpi-card autoservice-kpi-{{ $summary['tables_missing'] ? 'red' : 'green' }}"><div class="autoservice-kpi-top"><div class="autoservice-kpi-icon"><i class="fa fa-warning"></i></div><div class="autoservice-kpi-title">Missing Tables</div></div><div class="autoservice-kpi-value">{{ $summary['tables_missing'] }}</div><div class="autoservice-kpi-footer"><span>{{ $summary['tables_missing'] ? 'Run latest master SQL' : 'Passed' }}</span></div></div></div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12"><div class="autoservice-kpi-card autoservice-kpi-{{ $summary['routes_missing'] ? 'orange' : 'green' }}"><div class="autoservice-kpi-top"><div class="autoservice-kpi-icon"><i class="fa fa-link"></i></div><div class="autoservice-kpi-title">Missing Routes</div></div><div class="autoservice-kpi-value">{{ $summary['routes_missing'] }}</div><div class="autoservice-kpi-footer"><span>{{ $summary['routes_total'] }} routes checked</span></div></div></div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12"><div class="autoservice-kpi-card autoservice-kpi-cyan"><div class="autoservice-kpi-top"><div class="autoservice-kpi-icon"><i class="fa fa-building"></i></div><div class="autoservice-kpi-title">Business Scope</div></div><div class="autoservice-kpi-value">{{ $summary['business_id'] ?: '-' }}</div><div class="autoservice-kpi-footer"><span>Location: {{ $summary['location_id'] ?: '-' }}</span></div></div></div>
    </div>

    <div class="row autoservice-kpi-row">
        @foreach($operational as $label => $value)
            <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
                <div class="autoservice-kpi-card autoservice-kpi-silver">
                    <div class="autoservice-kpi-top"><div class="autoservice-kpi-icon"><i class="fa fa-bar-chart"></i></div><div class="autoservice-kpi-title">{{ ucwords(str_replace('_',' ', $label)) }}</div></div>
                    <div class="autoservice-kpi-value">{{ number_format($value) }}</div>
                    <div class="autoservice-kpi-footer"><span>Current business/location scope</span></div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="autoservice-panel">
                <div class="autoservice-panel-header"><h3><i class="fa fa-table"></i> Tenant Table / Scope Audit</h3></div>
                <div class="autoservice-panel-body table-responsive">
                    <table class="table autoservice-modern-table">
                        <thead><tr><th>Table</th><th>Status</th><th>Business Scope</th><th>Location Scope</th><th class="text-right">Rows</th></tr></thead>
                        <tbody>
                        @foreach($tableChecks as $row)
                            <tr>
                                <td><code>{{ $row['table'] }}</code></td>
                                <td>@if($row['exists'])<span class="autoservice-status-pill autoservice-status-green">Available</span>@else<span class="autoservice-status-pill autoservice-status-red">Missing</span>@endif</td>
                                <td>@if($row['businessScoped'])<span class="autoservice-status-pill autoservice-status-green">Yes</span>@else<span class="autoservice-status-pill autoservice-status-orange">Review</span>@endif</td>
                                <td>@if($row['locationScoped'])<span class="autoservice-status-pill autoservice-status-green">Yes</span>@else<span class="autoservice-status-pill autoservice-status-gray">Optional</span>@endif</td>
                                <td class="text-right">{{ number_format($row['rows']) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="autoservice-panel">
                <div class="autoservice-panel-header"><h3><i class="fa fa-sitemap"></i> Route Audit</h3></div>
                <div class="autoservice-panel-body table-responsive">
                    <table class="table autoservice-modern-table">
                        <thead><tr><th>Route</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach($routeChecks as $route)
                            <tr>
                                <td><code>{{ $route['name'] }}</code><br>@if($route['url'])<small><a href="{{ $route['url'] }}">{{ $route['url'] }}</a></small>@endif</td>
                                <td>@if($route['exists'])<span class="autoservice-status-pill autoservice-status-green">OK</span>@else<span class="autoservice-status-pill autoservice-status-red">Missing</span>@endif</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="autoservice-panel">
                <div class="autoservice-panel-header"><h3><i class="fa fa-clipboard"></i> Production Sign-off Checklist</h3></div>
                <div class="autoservice-panel-body">
                    <ul class="autoservice-checklist">
                        <li>Apply <code>41_AUTOSERVICE_STAGE040_FINAL_ENTERPRISE_AUDIT.sql</code> to every tenant database.</li>
                        <li>Assign <code>autoservice.production_audit.view</code> to admin roles.</li>
                        <li>Verify menu/sidebar visibility for Auto Service and customer portal URLs.</li>
                        <li>Create one test appointment, estimate, job, invoice, payment and delivery.</li>
                        <li>Confirm customer can view status, bill, service history and parts/accessories history.</li>
                        <li>Confirm reports export and show only selected business/location data.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
