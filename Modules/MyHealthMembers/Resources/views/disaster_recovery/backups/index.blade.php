@extends('layouts.app')
@section('title', 'My Health Backups')
@section('content')
<section class="content-header"><h1>My Health <small>Backup Centre</small></h1></section>
<section class="content">
@include('myhealthmembers::disaster_recovery._nav')
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Create Backup Request</h3></div><div class="box-body">
<form method="POST" action="{{ route('myhealth.disaster_recovery.backups.store') }}">@csrf
<div class="row"><div class="col-md-3"><label>Backup Type</label><select name="backup_type" class="form-control"><option value="manual">Manual</option><option value="scheduled">Scheduled</option><option value="incremental">Incremental</option><option value="differential">Differential</option></select></div>
<div class="col-md-3"><label>Scope</label><select name="backup_scope" class="form-control"><option value="database_and_files">Database & Files</option><option value="database">Database Only</option><option value="documents">Documents</option><option value="radiology_images">Radiology Images</option><option value="configuration">Configuration</option></select></div>
<div class="col-md-4"><label>Notes</label><input type="text" name="notes" class="form-control"></div><div class="col-md-2"><label>&nbsp;</label><button class="btn btn-primary btn-block"><i class="fa fa-plus"></i> Queue</button></div></div>
</form></div></div>
<div class="box box-info"><div class="box-header"><h3 class="box-title">Backup History</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>No</th><th>Type</th><th>Scope</th><th>Status</th><th>Started</th><th>Completed</th><th>Path</th></tr></thead><tbody>
@forelse($backups as $backup)<tr><td>{{ $backup->backup_no }}</td><td>{{ ucfirst($backup->backup_type) }}</td><td>{{ str_replace('_',' ',ucfirst($backup->backup_scope)) }}</td><td>{{ ucfirst($backup->status) }}</td><td>{{ optional($backup->started_at)->format('Y-m-d H:i') }}</td><td>{{ optional($backup->completed_at)->format('Y-m-d H:i') }}</td><td>{{ $backup->storage_path }}</td></tr>@empty<tr><td colspan="7" class="text-center text-muted">No backup records found.</td></tr>@endforelse
</tbody></table>{{ $backups->links() }}</div></div>
</section>
@endsection
