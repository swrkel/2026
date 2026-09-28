@extends('layouts.app')
@section('title','Microfinance Audit Exceptions')
@section('content')<div class="container-fluid"><h3>Microfinance Audit Exceptions</h3><table class="table table-bordered"><thead><tr><th>Date</th><th>Area</th><th>Event</th><th>Severity</th><th>Record</th></tr></thead><tbody>@foreach($events as $e)<tr><td>{{ $e->created_at }}</td><td>{{ $e->module_area }}</td><td>{{ $e->event_type }}</td><td>{{ $e->severity }}</td><td>{{ $e->record_id }}</td></tr>@endforeach</tbody></table>{{ $events->links() }}</div>@endsection
