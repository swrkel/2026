@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::haccp.temperature_logs'))
@section('content')
<div class="restnew-page">
    <h1>{{ __('restaurantnew::haccp.temperature_logs') }}</h1>
    @include('restaurantnew::components.list-toolbar')
    <table class="table table-bordered table-striped">
        <thead><tr><th>Checked At</th><th>Asset</th><th>Type</th><th>Temperature</th><th>Status</th><th>Remarks</th></tr></thead>
        <tbody>
        @foreach($logs as $log)
            <tr><td>{{ $log->checked_at }}</td><td>{{ $log->asset_name }}</td><td>{{ $log->check_type }}</td><td>{{ $log->temperature }}</td><td>{{ $log->status }}</td><td>{{ $log->remarks }}</td></tr>
        @endforeach
        </tbody>
    </table>
    {{ $logs->links() }}
</div>
@endsection
