@extends('autoservice::layouts.master')

@section('title', 'Auto Service Stabilization Centre')

@section('content')
@include('autoservice::layouts.nav')

<section class="content-header">
    <h1>Auto Service Stabilization Centre <small>Stage 041</small></h1>
</section>

<section class="content">
    @if(session('status'))
        <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }}">{{ session('status.msg') }}</div>
    @endif

    <div class="row">
        <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-database"></i></span><div class="info-box-content"><span class="info-box-text">Missing Tables</span><span class="info-box-number">{{ $summary['missing_tables'] }}</span></div></div></div>
        <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-link"></i></span><div class="info-box-content"><span class="info-box-text">Missing Routes</span><span class="info-box-number">{{ $summary['missing_routes'] }}</span></div></div></div>
        <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-red"><i class="fa fa-bug"></i></span><div class="info-box-content"><span class="info-box-text">Open Issues</span><span class="info-box-number">{{ $summary['open_issues'] }}</span></div></div></div>
        <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-maroon"><i class="fa fa-warning"></i></span><div class="info-box-content"><span class="info-box-text">Critical Issues</span><span class="info-box-number">{{ $summary['critical_issues'] }}</span></div></div></div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Current Context</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <tr><th>Tenant Connection</th><td>{{ $summary['tenant_connection'] }}</td><th>Tenant Database</th><td>{{ $summary['tenant_database'] }}</td></tr>
                <tr><th>Business ID</th><td>{{ $summary['business_id'] }}</td><th>Location ID</th><td>{{ $summary['location_id'] }}</td></tr>
            </table>
        </div>
    </div>

    <div class="box box-success">
        <div class="box-header with-border"><h3 class="box-title">Capture Server Testing Issue</h3></div>
        <form method="POST" action="{{ route('autoservice.stabilization.issues.store') }}">
            @csrf
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label>Issue Title</label><input type="text" name="issue_title" class="form-control" required></div></div>
                    <div class="col-md-3"><div class="form-group"><label>Severity</label><select name="severity" class="form-control"><option value="medium">Medium</option><option value="low">Low</option><option value="high">High</option><option value="critical">Critical</option></select></div></div>
                    <div class="col-md-3"><div class="form-group"><label>Page URL</label><input type="text" name="page_url" class="form-control" value="{{ request()->fullUrl() }}"></div></div>
                </div>
                <div class="form-group"><label>Description</label><textarea name="issue_description" class="form-control" rows="3" placeholder="Paste the exact error, steps to reproduce, and expected result."></textarea></div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label>Screenshot Reference</label><input type="text" name="screenshot_reference" class="form-control" placeholder="Screenshot filename or note"></div></div>
                    <div class="col-md-6"><div class="form-group"><label>Log Reference</label><input type="text" name="log_reference" class="form-control" placeholder="Log line, file name, or timestamp"></div></div>
                </div>
            </div>
            <div class="box-footer"><button class="btn btn-primary"><i class="fa fa-save"></i> Save Issue</button></div>
        </form>
    </div>

    <div class="box box-warning">
        <div class="box-header with-border"><h3 class="box-title">Recent Server Testing Issues</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>ID</th><th>Severity</th><th>Status</th><th>Title</th><th>Page</th><th>Reported</th><th>Update</th></tr></thead>
                <tbody>
                @forelse($recentIssues as $issue)
                    <tr>
                        <td>{{ $issue->id }}</td>
                        <td><span class="label label-{{ $issue->severity == 'critical' ? 'danger' : ($issue->severity == 'high' ? 'warning' : 'info') }}">{{ ucfirst($issue->severity) }}</span></td>
                        <td>{{ ucfirst(str_replace('_', ' ', $issue->status)) }}</td>
                        <td>{{ $issue->issue_title }}<br><small>{{ $issue->issue_description }}</small></td>
                        <td><small>{{ $issue->page_url }}</small></td>
                        <td>{{ $issue->created_at }}</td>
                        <td>
                            <form method="POST" action="{{ route('autoservice.stabilization.issues.update', $issue->id) }}">
                                @csrf
                                <div class="input-group input-group-sm">
                                    <select name="status" class="form-control"><option value="open">Open</option><option value="in_progress">In Progress</option><option value="fixed">Fixed</option><option value="closed">Closed</option><option value="rejected">Rejected</option></select>
                                    <span class="input-group-btn"><button class="btn btn-default">Update</button></span>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">No server testing issues captured yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border"><h3 class="box-title">Route Validation</h3></div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-condensed">
                        <thead><tr><th>Route</th><th>Status</th><th>URL</th></tr></thead>
                        <tbody>@foreach($routeChecks as $route)<tr><td>{{ $route['name'] }}</td><td>{!! $route['exists'] ? '<span class="label label-success">OK</span>' : '<span class="label label-danger">Missing</span>' !!}</td><td><small>{{ $route['url'] }}</small></td></tr>@endforeach</tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border"><h3 class="box-title">Tenant Table Validation</h3></div>
                <div class="box-body table-responsive">
                    <table class="table table-bordered table-condensed">
                        <thead><tr><th>Table</th><th>Status</th><th>Rows</th><th>Business</th><th>Location</th></tr></thead>
                        <tbody>@foreach($tableChecks as $table)<tr><td>{{ $table['table'] }}</td><td>{!! $table['exists'] ? '<span class="label label-success">OK</span>' : '<span class="label label-danger">Missing</span>' !!}</td><td>{{ $table['rows'] }}</td><td>{{ $table['businessScoped'] ? 'Yes' : '-' }}</td><td>{{ $table['locationScoped'] ? 'Yes' : '-' }}</td></tr>@endforeach</tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
