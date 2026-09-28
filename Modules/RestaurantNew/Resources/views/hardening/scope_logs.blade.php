@extends('restaurantnew::layouts.app')

@section('title', __('restaurantnew::messages.scope_logs'))

@section('content')
<div class="restnew-page">
    <div class="restnew-toolbar"><h3>{{ __('restaurantnew::messages.scope_logs') }}</h3></div>
    <div class="restnew-card">
        <table class="table table-bordered restnew-datatable">
            <thead><tr><th>Date</th><th>User</th><th>Business</th><th>Location</th><th>Operation</th><th>Status</th><th>Reason</th></tr></thead>
            <tbody>
            @foreach($logs as $log)
                <tr><td>{{ $log->created_at }}</td><td>{{ $log->user_id }}</td><td>{{ $log->business_id }}</td><td>{{ $log->location_id }}</td><td>{{ $log->operation }}</td><td>{{ $log->status }}</td><td>{{ $log->reason }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
