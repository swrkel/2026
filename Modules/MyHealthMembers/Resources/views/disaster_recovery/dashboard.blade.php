@extends('layouts.app')
@section('title', 'My Health Disaster Recovery')
@section('content')
<section class="content-header"><h1>My Health <small>Disaster Recovery & Business Continuity</small></h1></section>
<section class="content">
    @include('myhealthmembers::disaster_recovery._nav')
    <div class="row">
        <div class="col-md-3"><div class="small-box bg-aqua"><div class="inner"><h3>{{ $dashboard['counts']['backups'] ?? 0 }}</h3><p>Backup Records</p></div><div class="icon"><i class="fa fa-database"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-green"><div class="inner"><h3>{{ $dashboard['restore_points'] ?? 0 }}</h3><p>Restore Points</p></div><div class="icon"><i class="fa fa-history"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-yellow"><div class="inner"><h3>{{ $dashboard['readiness_score'] ?? 0 }}%</h3><p>Recovery Readiness</p></div><div class="icon"><i class="fa fa-shield"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-red"><div class="inner"><h3>{{ $dashboard['failed_backups'] ?? 0 }}</h3><p>Failed Backups</p></div><div class="icon"><i class="fa fa-warning"></i></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-6"><div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Last Backup</h3></div><div class="box-body">
            @php($backup = $dashboard['last_backup'] ?? null)
            @if($backup)
                <table class="table table-bordered">
                    <tr><th>Backup No</th><td>{{ $backup->backup_no }}</td></tr>
                    <tr><th>Type</th><td>{{ ucfirst($backup->backup_type) }}</td></tr>
                    <tr><th>Status</th><td><span class="label label-{{ $backup->status == 'completed' ? 'success' : ($backup->status == 'failed' ? 'danger' : 'warning') }}">{{ ucfirst($backup->status) }}</span></td></tr>
                    <tr><th>Completed</th><td>{{ optional($backup->completed_at)->format('Y-m-d H:i') ?: '-' }}</td></tr>
                </table>
            @else
                <p class="text-muted">No My Health backup has been recorded yet.</p>
            @endif
        </div></div></div>
        <div class="col-md-6"><div class="box box-success"><div class="box-header with-border"><h3 class="box-title">Storage Summary</h3></div><div class="box-body">
            <table class="table table-bordered">
                <tr><th>Disk</th><td>{{ $dashboard['storage']['disk'] ?? '-' }}</td></tr>
                <tr><th>Backup Path</th><td>{{ $dashboard['storage']['backup_path'] ?? '-' }}</td></tr>
                <tr><th>Status</th><td>{{ $dashboard['storage']['status'] ?? '-' }}</td></tr>
            </table>
        </div></div></div>
    </div>
    <div class="box box-info"><div class="box-header with-border"><h3 class="box-title">Latest Health Checks</h3></div><div class="box-body table-responsive">
        <table class="table table-bordered table-striped"><thead><tr><th>Check</th><th>Status</th><th>Severity</th><th>Checked At</th><th>Message</th></tr></thead><tbody>
        @forelse(($dashboard['health_checks'] ?? []) as $check)
            <tr><td>{{ $check->check_name }}</td><td>{{ ucfirst($check->status) }}</td><td>{{ ucfirst($check->severity) }}</td><td>{{ optional($check->checked_at)->format('Y-m-d H:i') }}</td><td>{{ $check->message }}</td></tr>
        @empty<tr><td colspan="5" class="text-center text-muted">No health checks yet.</td></tr>@endforelse
        </tbody></table>
    </div></div>
</section>
@endsection
