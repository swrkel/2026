@extends('airlineticketingnew::layouts.app')
@section('atn-title','Notification Queue')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-toolbar"><form method="POST" action="{{ route('airline-ticketing-new.notifications.dispatch') }}">@csrf<button class="btn btn-primary">Prepare Queued Notifications</button></form></div>
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table">
<thead><tr><th>Event</th><th>Channel</th><th>Recipient</th><th>Subject</th><th>Status</th><th>Created</th></tr></thead>
<tbody>@forelse($records as $record)<tr><td>{{ $record->event_code }}</td><td>{{ $record->channel }}</td><td>{{ $record->recipient }}</td><td>{{ $record->subject }}</td><td>{{ $record->status }}</td><td>{{ $record->created_at }}</td></tr>@empty<tr><td colspan="6" class="text-center">No records</td></tr>@endforelse</tbody>
</table></div>{{ $records->links() }}</div>
@endsection
