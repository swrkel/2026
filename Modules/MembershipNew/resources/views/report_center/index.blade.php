@extends('membershipnew::layouts.app')
@php
    $title = 'Membership New Report Center';
@endphp
@php
    $subtitle = 'Open business-scoped membership reports with export, search, column, print and sharing tools.';
@endphp
@section('membership-content')
<div class="mn-grid mn-report-center-grid">
    @foreach($reports as $index => $report)
        <a class="mn-card {{ ['','success','warning','purple','cyan'][$index % 5] }}" href="{{ route($report['route']) }}">
            <span class="mn-card-icon"><i class="fa {{ $report['icon'] ?? 'fa-bar-chart' }}"></i></span>
            <strong>{{ $report['title'] }}</strong>
            <span>{{ $report['description'] }}</span>
        </a>
    @endforeach
</div>
@endsection
