@extends('autoservice::layouts.master')

@section('title', 'Auto Service Enterprise Integration')

@section('content')
@include('autoservice::layouts.nav')

<section class="content-header">
    <h1>Auto Service Enterprise Integration <small>Stage 043</small></h1>
</section>

<section class="content">
    @if(session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif

    <div class="row">
        <div class="col-md-3"><div class="small-box bg-aqua"><div class="inner"><h3>{{ $postingSummary['jobs_ready_for_invoice'] }}</h3><p>Jobs Ready For Invoice</p></div><div class="icon"><i class="fa fa-wrench"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-yellow"><div class="inner"><h3>{{ $postingSummary['unposted_invoices'] }}</h3><p>Unposted / Draft Invoices</p></div><div class="icon"><i class="fa fa-file-text"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-red"><div class="inner"><h3>{{ $postingSummary['unlinked_parts'] }}</h3><p>Unlinked Parts Lines</p></div><div class="icon"><i class="fa fa-cogs"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-green"><div class="inner"><h3>{{ $postingSummary['pending_notifications'] }}</h3><p>Pending Notifications</p></div><div class="icon"><i class="fa fa-bell"></i></div></div></div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">ERP Integration Readiness</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped table-condensed">
                <thead><tr><th>ERP Area</th><th>Required Table / Bridge</th><th>Purpose</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($integrationChecks as $check)
                    <tr>
                        <td>{{ $check['module'] }}</td>
                        <td><code>{{ $check['table'] }}</code></td>
                        <td>{{ $check['purpose'] }}</td>
                        <td>{!! $check['available'] ? '<span class="label label-success">Ready</span>' : '<span class="label label-warning">Optional / Not Found</span>' !!}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="box box-info">
        <div class="box-header with-border"><h3 class="box-title">Manual Bridge Check</h3></div>
        <form method="POST" action="{{ route('autoservice.enterprise_integration.log_check') }}">
            @csrf
            <div class="box-body row">
                <div class="col-md-4"><label>Target Module</label><input name="target_module" class="form-control" value="CommunicationHub"></div>
                <div class="col-md-4"><label>Bridge Type</label><input name="bridge_type" class="form-control" value="readiness_check"></div>
                <div class="col-md-4" style="padding-top:25px"><button class="btn btn-primary"><i class="fa fa-check"></i> Log Integration Check</button></div>
            </div>
        </form>
    </div>

    <div class="box box-warning">
        <div class="box-header with-border"><h3 class="box-title">Recent Integration Bridge Logs</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped table-condensed">
                <thead><tr><th>ID</th><th>Target</th><th>Reference</th><th>Status</th><th>Message</th><th>Created At</th></tr></thead>
                <tbody>
                @forelse($recentBridges as $log)
                    <tr><td>{{ $log->id }}</td><td>{{ $log->target_module }}</td><td>{{ $log->reference_type }} #{{ $log->reference_id }}</td><td>{{ $log->status }}</td><td>{{ $log->message }}</td><td>{{ $log->created_at }}</td></tr>
                @empty
                    <tr><td colspan="6" class="text-muted text-center">No integration bridge logs yet. Run Stage 043 SQL and use the manual bridge check above.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
