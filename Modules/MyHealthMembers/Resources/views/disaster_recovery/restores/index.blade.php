@extends('layouts.app')
@section('title', 'My Health Restore Centre')
@section('content')
<section class="content-header"><h1>My Health <small>Restore Centre</small></h1></section>
<section class="content">
@include('myhealthmembers::disaster_recovery._nav')
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="box box-warning"><div class="box-header with-border"><h3 class="box-title">Restore Wizard Request</h3></div><div class="box-body">
<form method="POST" action="{{ route('myhealth.disaster_recovery.restores.store') }}">@csrf
<div class="row"><div class="col-md-4"><label>Backup</label><select name="backup_record_id" class="form-control"><option value="">Select backup</option>@foreach($backups as $backup)<option value="{{ $backup->id }}">{{ $backup->backup_no }} - {{ $backup->backup_scope }}</option>@endforeach</select></div><div class="col-md-3"><label>Restore Scope</label><select name="restore_scope" class="form-control"><option value="preview">Preview Only</option><option value="database">Database</option><option value="documents">Documents</option><option value="full">Full Restore</option></select></div><div class="col-md-3"><label>Notes</label><input type="text" name="notes" class="form-control"></div><div class="col-md-2"><label>&nbsp;</label><button class="btn btn-warning btn-block"><i class="fa fa-check"></i> Request</button></div></div>
</form></div></div>
<div class="box box-info"><div class="box-header"><h3 class="box-title">Restore History</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>No</th><th>Backup</th><th>Scope</th><th>Status</th><th>Requested</th><th>Completed</th><th>Notes</th></tr></thead><tbody>
@forelse($restores as $restore)<tr><td>{{ $restore->restore_no }}</td><td>{{ optional($restore->backup)->backup_no }}</td><td>{{ ucfirst($restore->restore_scope) }}</td><td>{{ ucfirst($restore->status) }}</td><td>{{ $restore->created_at->format('Y-m-d H:i') }}</td><td>{{ optional($restore->completed_at)->format('Y-m-d H:i') }}</td><td>{{ $restore->notes }}</td></tr>@empty<tr><td colspan="7" class="text-center text-muted">No restore records found.</td></tr>@endforelse
</tbody></table>{{ $restores->links() }}</div></div>
</section>
@endsection
