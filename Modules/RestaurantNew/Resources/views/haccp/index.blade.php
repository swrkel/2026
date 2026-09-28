@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::haccp.haccp_command_center'))
@section('content')
<div class="restnew-page">
    <h1>{{ __('restaurantnew::haccp.haccp_command_center') }}</h1>
    <div class="restnew-dashboard-grid">
        <div class="restnew-card"><span>Open Checks</span><strong>{{ $summary['open_checks'] }}</strong></div>
        <div class="restnew-card"><span>Failed Checks</span><strong>{{ $summary['failed_checks'] }}</strong></div>
        <div class="restnew-card"><span>Temperature Alerts</span><strong>{{ $summary['temperature_alerts'] }}</strong></div>
        <div class="restnew-card"><span>Corrective Actions</span><strong>{{ $summary['open_corrective_actions'] }}</strong></div>
    </div>
</div>
@endsection
