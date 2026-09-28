@extends('membershipnew::layouts.app')
@php
    $title = 'Membership New Dashboard';
@endphp
@php
    $subtitle = "Membership activity and balances for the currently selected business.";
@endphp
@section('page-actions')
<a class="mn-btn mn-btn-primary" href="{{ route('membership-new.members.index') }}"><i class="fa fa-users"></i> Members</a>
<a class="mn-btn mn-btn-success" href="{{ route('membership-new.report-center.index') }}"><i class="fa fa-bar-chart"></i> Reports</a>
@endsection
@section('membership-content')
<div class="mn-grid">
    <a class="mn-card" href="{{ route('membership-new.members.index') }}"><span class="mn-card-icon"><i class="fa fa-users"></i></span><span>Total Members</span><strong>{{ number_format($summary['members'] ?? 0) }}</strong></a>
    <a class="mn-card success" href="{{ route('membership-new.linked-businesses.index') }}"><span class="mn-card-icon"><i class="fa fa-building"></i></span><span>Linked Businesses</span><strong>{{ number_format($summary['linked_businesses'] ?? 0) }}</strong></a>
    <a class="mn-card warning" href="{{ route('membership-new.point-rules.index') }}"><span class="mn-card-icon"><i class="fa fa-sliders"></i></span><span>Point Rules</span><strong>{{ number_format($summary['point_rules'] ?? 0) }}</strong></a>
    <a class="mn-card purple" href="{{ route('membership-new.points.index') }}"><span class="mn-card-icon"><i class="fa fa-star"></i></span><span>Point Balance</span><strong>{{ number_format($summary['point_balance'] ?? 0, 4) }}</strong></a>
    <a class="mn-card cyan" href="{{ route('membership-new.shares.index') }}"><span class="mn-card-icon"><i class="fa fa-pie-chart"></i></span><span>Total Shares</span><strong>{{ number_format($summary['shares'] ?? 0, 4) }}</strong></a>
</div>
<div class="mn-panel">
    <div class="mn-report-card-header"><div><h3>Membership Workspace</h3><p>Quick access to the most frequently used Membership New functions.</p></div></div>
    <div class="mn-action-links">
        <a class="mn-btn mn-btn-primary" href="{{ route('membership-new.members.create') }}"><i class="fa fa-user-plus"></i> Add Member</a>
        <a class="mn-btn mn-btn-info" href="{{ route('membership-new.payments.index') }}"><i class="fa fa-money"></i> Payments</a>
        <a class="mn-btn mn-btn-warning" href="{{ route('membership-new.points.index') }}"><i class="fa fa-star"></i> Points</a>
        <a class="mn-btn mn-btn-purple" href="{{ route('membership-new.dividends.index') }}"><i class="fa fa-line-chart"></i> Dividends</a>
        <a class="mn-btn mn-btn-success" href="{{ route('membership-new.report-center.index') }}"><i class="fa fa-bar-chart"></i> Report Center</a>
    </div>
</div>
@endsection
