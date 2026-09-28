@extends('layouts.app')
@section('title', __('stocktransfernew::maintenance.calendar'))
@section('content')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stocktransfernew-maintenance.css') }}">
<div class="stn-page-wrap">
    <div class="stn-page-header"><div><h1>{{ __('stocktransfernew::maintenance.calendar') }}</h1><p>Maintenance schedule for {{ $month }}</p></div><a class="btn btn-default" href="{{ route('stocktransfernew.admin.maintenance.index') }}">Back</a></div>
    <div class="stn-card">
        @forelse($calendar as $date => $tasks)
            <h4>{{ $date }}</h4>
            <ul class="stn-calendar-list">
                @foreach($tasks as $task)
                    <li><strong>{{ $task['title'] }}</strong> <span class="stn-badge {{ $task['priority'] }}">{{ $task['priority'] }}</span> <em>{{ $task['status'] }}</em></li>
                @endforeach
            </ul>
        @empty
            <p>No scheduled maintenance for this month.</p>
        @endforelse
    </div>
</div>
@endsection
