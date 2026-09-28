@extends('communicationhub::layout')
@section('communicationhub_title', 'API Logs')
@section('communicationhub_content')
<div class="box"><div class="box-header"><h3 class="box-title">API Request Logs</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><tr><th>ID</th><th>Client</th><th>Endpoint</th><th>Method</th><th>Status</th><th>Date</th></tr>@forelse($logs as $row)<tr><td>{{ $row->id }}</td><td>{{ $row->api_client_id ?? '' }}</td><td>{{ $row->endpoint ?? '' }}</td><td>{{ $row->method ?? '' }}</td><td>{{ $row->response_status ?? '' }}</td><td>{{ $row->created_at ?? '' }}</td></tr>@empty<tr><td colspan="6">No API logs yet</td></tr>@endforelse</table></div></div>
@endsection
