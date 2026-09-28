@extends('bankingui::layouts.master', ['title' => 'Banking Navigation Audit'])
@section('banking_content')
<div class="box box-primary"><div class="box-body table-responsive">
<table class="table table-striped table-bordered"><thead><tr><th>ID</th><th>User</th><th>Module</th><th>Route</th><th>URL</th><th>Date</th></tr></thead><tbody>
@forelse($records as $record)
<tr><td>{{ $record->id }}</td><td>{{ $record->user_id }}</td><td>{{ $record->module_key }}</td><td>{{ $record->route_name }}</td><td>{{ $record->url }}</td><td>{{ $record->created_at }}</td></tr>
@empty
<tr><td colspan="6" class="text-center">No navigation audit records yet.</td></tr>
@endforelse
</tbody></table>
</div></div>
@endsection
