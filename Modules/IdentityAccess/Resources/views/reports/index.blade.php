@extends('layouts.app')
@section('title', 'Identity Reports')
@section('content')
<section class="content-header"><h1>Identity Security Events</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body table-responsive">
<table class="table table-bordered table-striped">
<thead><tr><th>Date</th><th>Portal</th><th>Event</th><th>Severity</th><th>Description</th><th>IP</th></tr></thead>
<tbody>@foreach($events as $e)<tr><td>{{ $e->created_at }}</td><td>{{ $e->portal_type }}</td><td>{{ $e->event_type }}</td><td>{{ $e->severity }}</td><td>{{ $e->description }}</td><td>{{ $e->ip_address }}</td></tr>@endforeach</tbody>
</table>{{ $events->links() }}</div></div></section>
@endsection
