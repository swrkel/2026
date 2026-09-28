@extends('autoservice::layouts.master')

@section('title', 'Auto Service UI Standardization & Performance')

@section('content')
@include('autoservice::layouts.nav')

<section class="content-header">
    <h1>UI Standardization & Performance <small>Stage 044</small></h1>
</section>

<section class="content autoservice-stage044">
    @if(session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif

    <div class="row">
        <div class="col-md-3"><div class="small-box bg-aqua"><div class="inner"><h3>{{ $counters['open_jobs'] }}</h3><p>Open / Active Jobs</p></div><div class="icon"><i class="fa fa-wrench"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-yellow"><div class="inner"><h3>{{ $counters['unbilled_jobs'] }}</h3><p>Unbilled Completed Jobs</p></div><div class="icon"><i class="fa fa-file-text"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-red"><div class="inner"><h3>{{ $counters['pending_customer_alerts'] }}</h3><p>Pending Customer Alerts</p></div><div class="icon"><i class="fa fa-bell"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-green"><div class="inner"><h3>{{ $counters['pending_appointments'] }}</h3><p>Pending Appointments</p></div><div class="icon"><i class="fa fa-calendar"></i></div></div></div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Route & Menu Readiness</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped table-condensed autoservice-compact-table">
                <thead><tr><th>Page</th><th>Route Name</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($routeChecks as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td><code>{{ $row['name'] }}</code></td>
                        <td>{!! $row['exists'] ? '<span class="label label-success">Ready</span>' : '<span class="label label-danger">Missing</span>' !!}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="box box-info">
        <div class="box-header with-border"><h3 class="box-title">Tenant Table Readiness</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped table-condensed autoservice-compact-table">
                <thead><tr><th>Table</th><th>Purpose</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($tableChecks as $row)
                    <tr>
                        <td><code>{{ $row['table'] }}</code></td>
                        <td>{{ $row['purpose'] }}</td>
                        <td>{!! $row['exists'] ? '<span class="label label-success">Ready</span>' : '<span class="label label-warning">Run SQL / Optional</span>' !!}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="box box-warning">
        <div class="box-header with-border"><h3 class="box-title">Performance & Index Readiness</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped table-condensed autoservice-compact-table">
                <thead><tr><th>Table</th><th>Status</th><th>Missing Recommended Columns</th></tr></thead>
                <tbody>
                @foreach($performanceChecks as $row)
                    <tr>
                        <td><code>{{ $row['table'] }}</code></td>
                        <td>{{ $row['status'] }}</td>
                        <td>{{ empty($row['missing_columns']) ? 'None' : implode(', ', $row['missing_columns']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="box box-success">
        <div class="box-header with-border"><h3 class="box-title">Manual Stage 044 Audit Log</h3></div>
        <form method="POST" action="{{ route('autoservice.ui_standardization.log_check') }}">
            @csrf
            <div class="box-body row">
                <div class="col-md-3"><label>Audit Area</label><input name="audit_area" class="form-control" value="ui_standardization"></div>
                <div class="col-md-3"><label>Status</label><select name="audit_status" class="form-control"><option value="checked">Checked</option><option value="passed">Passed</option><option value="needs_attention">Needs Attention</option></select></div>
                <div class="col-md-4"><label>Notes</label><input name="notes" class="form-control" value="Stage 044 UI/performance check completed."></div>
                <div class="col-md-2" style="padding-top:25px"><button class="btn btn-primary btn-block"><i class="fa fa-save"></i> Save</button></div>
            </div>
        </form>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped table-condensed autoservice-compact-table">
                <thead><tr><th>ID</th><th>Area</th><th>Status</th><th>Notes</th><th>Date</th></tr></thead>
                <tbody>
                @forelse($recentLogs as $log)
                    <tr><td>{{ $log->id }}</td><td>{{ $log->audit_area }}</td><td>{{ $log->audit_status }}</td><td>{{ $log->notes }}</td><td>{{ $log->created_at }}</td></tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">No Stage 044 audit logs yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
