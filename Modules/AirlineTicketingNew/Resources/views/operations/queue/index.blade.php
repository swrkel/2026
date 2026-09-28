@extends('airlineticketingnew::layouts.app')
@section('atn-title','Operational Queue')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-toolbar"><form method="POST" action="{{ route('airline-ticketing-new.operations.queue.rebuild') }}">@csrf<button class="btn btn-primary">Refresh Queue</button></form></div>
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table"><thead><tr><th>Task No</th><th>Type</th><th>Title</th><th>Priority</th><th>Due</th><th>Status</th></tr></thead><tbody>
@forelse($records as $record)<tr><td>{{ $record->task_no }}</td><td>{{ $record->task_type }}</td><td>{{ $record->title }}</td><td>{{ $record->priority }}</td><td>{{ $record->due_at }}</td><td>{{ $record->status }}</td></tr>@empty<tr><td colspan="6" class="text-center">No records</td></tr>@endforelse
</tbody></table></div><div class="atn-pagination">{{ $records->links() }}</div></div>
@endsection
