@extends('autoservice::layouts.master')

@section('title', 'Auto Service Deployment Diagnostics')

@section('content')
@include('autoservice::layouts.nav')

<section class="content-header">
    <h1>Auto Service Deployment Diagnostics <small>Stage 042</small></h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-red"><i class="fa fa-database"></i></span><div class="info-box-content"><span class="info-box-text">Missing Tables</span><span class="info-box-number">{{ $summary['missing_tables'] }}</span></div></div></div>
        <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-link"></i></span><div class="info-box-content"><span class="info-box-text">Missing Routes</span><span class="info-box-number">{{ $summary['missing_routes'] }}</span></div></div></div>
        <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-building"></i></span><div class="info-box-content"><span class="info-box-text">No Business Scope</span><span class="info-box-number">{{ $summary['tables_without_business_scope'] }}</span></div></div></div>
        <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-clock-o"></i></span><div class="info-box-content"><span class="info-box-text">No Timestamps</span><span class="info-box-number">{{ $summary['tables_without_timestamps'] }}</span></div></div></div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Current Tenant / Business Context</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <tr><th>Tenant Connection</th><td>{{ $summary['tenant_connection'] }}</td><th>Tenant Database</th><td>{{ $summary['tenant_database'] }}</td></tr>
                <tr><th>Business ID</th><td>{{ $summary['business_id'] }}</td><th>Location ID</th><td>{{ $summary['location_id'] }}</td></tr>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3"><div class="small-box bg-aqua"><div class="inner"><h3>{{ $queueChecks['pending_jobs'] }}</h3><p>Pending / Active Jobs</p></div><div class="icon"><i class="fa fa-wrench"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-green"><div class="inner"><h3>{{ $queueChecks['completed_jobs'] }}</h3><p>Completed / Delivered Jobs</p></div><div class="icon"><i class="fa fa-check"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-yellow"><div class="inner"><h3>{{ $queueChecks['unpaid_invoices'] }}</h3><p>Unpaid / Pending Invoices</p></div><div class="icon"><i class="fa fa-money"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-red"><div class="inner"><h3>{{ $queueChecks['open_issues'] }}</h3><p>Open Testing Issues</p></div><div class="icon"><i class="fa fa-bug"></i></div></div></div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border"><h3 class="box-title">Route / Menu Readiness</h3></div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-condensed table-striped">
                        <thead><tr><th>Route</th><th>Status</th><th>URL</th></tr></thead>
                        <tbody>
                        @foreach($routeChecks as $route)
                            <tr>
                                <td>{{ $route['name'] }}</td>
                                <td>{!! $route['exists'] ? '<span class="label label-success">OK</span>' : '<span class="label label-danger">Missing</span>' !!}</td>
                                <td><small>{{ $route['url'] }}</small></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border"><h3 class="box-title">Tenant Table / Scope Readiness</h3></div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-condensed table-striped">
                        <thead><tr><th>Table</th><th>Status</th><th>Rows</th><th>Business</th><th>Location</th><th>Time</th></tr></thead>
                        <tbody>
                        @foreach($tableChecks as $table)
                            <tr>
                                <td>{{ $table['table'] }}</td>
                                <td>{!! $table['exists'] ? '<span class="label label-success">OK</span>' : '<span class="label label-danger">Missing</span>' !!}</td>
                                <td>{{ $table['rows'] }}</td>
                                <td>{{ $table['businessScoped'] ? 'Yes' : '-' }}</td>
                                <td>{{ $table['locationScoped'] ? 'Yes' : '-' }}</td>
                                <td>{{ $table['hasTimestamps'] ? 'Yes' : '-' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="box box-warning">
        <div class="box-header with-border"><h3 class="box-title">Recent Diagnostic Runs</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>ID</th><th>Database</th><th>Missing Routes</th><th>Missing Tables</th><th>Open Issues</th><th>Run By</th><th>Run At</th></tr></thead>
                <tbody>
                @forelse($recentDiagnosticRuns as $run)
                    <tr><td>{{ $run->id }}</td><td>{{ $run->tenant_database }}</td><td>{{ $run->missing_routes }}</td><td>{{ $run->missing_tables }}</td><td>{{ $run->open_issues }}</td><td>{{ $run->run_by }}</td><td>{{ $run->created_at }}</td></tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">Run Stage 042 SQL to store diagnostic history. Current live checks are shown above.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
