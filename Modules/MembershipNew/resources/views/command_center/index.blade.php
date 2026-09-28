@extends('membershipnew::layouts.app')
@php
    $title = 'Membership-New Command Center';
@endphp
@section('membership-content')
<div class="mn-grid">
    @foreach($summary as $label => $value)
        <div class="mn-card">
            <span>{{ ucwords(str_replace('_', ' ', $label)) }}</span>
            <strong>{{ is_numeric($value) ? number_format($value) : $value }}</strong>
        </div>
    @endforeach
</div>

<div class="mn-panel" style="margin-top:14px">
    <h3>Quick Actions</h3>
    <div class="mn-nav">
        <a href="{{ route('membership-new.central-members.index') }}">Central Members</a>
        <a href="{{ route('membership-new.business-members.index') }}">Business Members</a>
        <a href="{{ route('membership-new.points.index') }}">Points</a>
        <a href="{{ route('membership-new.dividends.index') }}">Dividends</a>
        <a href="{{ route('membership-new.cards.scan-page') }}">Card Scan</a>
        <a href="{{ route('membership-new.report-center.index') }}">Reports</a>
        <a href="{{ route('membership-new.health.index') }}">Health Check</a>
    </div>
</div>
@endsection
