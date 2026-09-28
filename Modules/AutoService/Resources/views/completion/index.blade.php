@extends('autoservice::layouts.master')
@section('title','Auto Service Completion Check')
@section('autoservice_content')
<div class="autoservice-dashboard-page">
    <div class="autoservice-dashboard-header">
        <div>
            <span class="autoservice-eyebrow">AUTO SERVICE</span>
            <h2>Completion & Readiness Check</h2>
            <p>Use this page after server upload to confirm tenant tables, route registration and business-scoped operational records.</p>
        </div>
        <div class="autoservice-header-actions">
            <a href="{{ route('autoservice.dashboard') }}" class="autoservice-primary-action"><i class="fa fa-dashboard"></i> Dashboard</a>
        </div>
    </div>

    <div class="row autoservice-kpi-row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12"><div class="autoservice-kpi-card autoservice-kpi-blue"><div class="autoservice-kpi-top"><div class="autoservice-kpi-icon"><i class="fa fa-database"></i></div><div class="autoservice-kpi-title">Required Tables</div></div><div class="autoservice-kpi-value">{{ $summary['required_tables'] }}</div><div class="autoservice-kpi-footer"><span>Tenant DB: {{ $summary['tenant_database'] ?: '-' }}</span></div></div></div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12"><div class="autoservice-kpi-card autoservice-kpi-{{ $summary['missing_tables'] ? 'orange' : 'green' }}"><div class="autoservice-kpi-top"><div class="autoservice-kpi-icon"><i class="fa fa-check-circle"></i></div><div class="autoservice-kpi-title">Missing Tables</div></div><div class="autoservice-kpi-value">{{ $summary['missing_tables'] }}</div><div class="autoservice-kpi-footer"><span>{{ $summary['missing_tables'] ? 'Run tenant SQL/migrations' : 'Table check passed' }}</span></div></div></div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12"><div class="autoservice-kpi-card autoservice-kpi-cyan"><div class="autoservice-kpi-top"><div class="autoservice-kpi-icon"><i class="fa fa-briefcase"></i></div><div class="autoservice-kpi-title">Open Jobs</div></div><div class="autoservice-kpi-value">{{ number_format($summary['open_jobs']) }}</div><div class="autoservice-kpi-footer"><span>Business: {{ $summary['business_id'] ?: '-' }}</span></div></div></div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12"><div class="autoservice-kpi-card autoservice-kpi-orange"><div class="autoservice-kpi-top"><div class="autoservice-kpi-icon"><i class="fa fa-bell"></i></div><div class="autoservice-kpi-title">Pending Alerts</div></div><div class="autoservice-kpi-value">{{ number_format($summary['pending_reminders'] + $summary['pending_notifications'] + $summary['pending_communications']) }}</div><div class="autoservice-kpi-footer"><span>Reminders / notifications / comms</span></div></div></div>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="autoservice-panel">
                <div class="autoservice-panel-header"><h3><i class="fa fa-table"></i> Tenant Table Health</h3></div>
                <div class="autoservice-panel-body table-responsive">
                    <table class="table autoservice-modern-table">
                        <thead><tr><th>Table</th><th>Status</th><th class="text-right">Rows for Current Scope</th></tr></thead>
                        <tbody>
                        @foreach($tableStatus as $row)
                            <tr>
                                <td><code>{{ $row['table'] }}</code></td>
                                <td>@if($row['exists'])<span class="autoservice-status-pill autoservice-status-green">Available</span>@else<span class="autoservice-status-pill autoservice-status-red">Missing</span>@endif</td>
                                <td class="text-right">{{ is_null($row['rows']) ? '-' : number_format($row['rows']) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="autoservice-panel">
                <div class="autoservice-panel-header"><h3><i class="fa fa-link"></i> Route Registration</h3></div>
                <div class="autoservice-panel-body table-responsive">
                    <table class="table autoservice-modern-table">
                        <thead><tr><th>Route Name</th><th>URL</th></tr></thead>
                        <tbody>
                        @foreach($routes as $name => $url)
                            <tr><td><code>{{ $name }}</code></td><td><a href="{{ $url }}">{{ $url }}</a></td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="autoservice-panel">
                <div class="autoservice-panel-header"><h3><i class="fa fa-list-check"></i> Server Acceptance Checklist</h3></div>
                <div class="autoservice-panel-body">
                    <ul class="autoservice-checklist">
                        <li>Sidebar/menu visible after permissions are assigned.</li>
                        <li>Dashboard opens without setup warning.</li>
                        <li>Vehicle, job, invoice and payment pages save inside the selected business.</li>
                        <li>Workshop, QC and delivery statuses update correctly.</li>
                        <li>Reports filter by date, business and location.</li>
                        <li>Customer portal and central vehicle portal open from public URLs.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
