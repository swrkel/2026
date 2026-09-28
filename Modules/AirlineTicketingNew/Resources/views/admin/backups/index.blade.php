@extends('airlineticketingnew::layouts.app')
@section('atn-title','Backups')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-toolbar">
<form method="POST" action="{{ route('airline-ticketing-new.admin.backups.store') }}">@csrf
<button class="btn btn-primary">Create Backup</button>
</form>
</div>
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table">
<thead><tr><th>ID</th><th>Type</th><th>Status</th><th>File</th><th>Size</th><th>Started</th><th>Completed</th></tr></thead>
<tbody>@forelse($records as $record)<tr><td>{{ $record->id }}</td><td>{{ $record->backup_type }}</td><td>{{ $record->status }}</td><td>{{ $record->file_path }}</td><td>{{ $record->file_size }}</td><td>{{ $record->started_at }}</td><td>{{ $record->completed_at }}</td></tr>@empty<tr><td colspan="7" class="text-center">No records</td></tr>@endforelse</tbody>
</table></div>{{ $records->links() }}</div>
@endsection
