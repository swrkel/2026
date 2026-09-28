@extends('communicationhub::layout')

@section('communicationhub_title', 'Provider Monitor')
@section('communicationhub_content')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Live Provider Monitor</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Name</th><th>Channel</th><th>Status</th><th>Priority</th><th>Response Time</th><th>Today</th><th>Month</th><th>Last Success</th><th>Last Failure</th><th>Last Error</th></tr></thead><tbody>@foreach($providers as $provider)<tr><td>{{ $provider['name'] }}</td><td>{{ strtoupper($provider['channel']) }}</td><td><span class="label label-{{ ($provider['status'] == 'online' || $provider['is_active']) ? 'success' : 'danger' }}">{{ $provider['status'] }}</span></td><td>{{ $provider['priority'] }}</td><td>{{ $provider['response_time_ms'] ? $provider['response_time_ms'].' ms' : '-' }}</td><td>{{ $provider['daily_usage'] }}</td><td>{{ $provider['monthly_usage'] }}</td><td>{{ $provider['last_success_at'] }}</td><td>{{ $provider['last_failure_at'] }}</td><td>{{ $provider['last_error'] }}</td></tr>@endforeach</tbody></table></div></div>
@endsection
