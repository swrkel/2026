@extends('membershipnew::layouts.app')
@php
    $title = 'View Member';
@endphp
@section('page-actions')
<form method="POST" action="{{ route('membership-new.cards.issue', $member->id) }}" style="display:inline">@csrf<button class="mn-btn mn-btn-success">Issue Card</button></form>
<a class="mn-btn mn-btn-info" href="{{ route('membership-new.cards.print', $member->id) }}">Print Card</a>
@endsection
@section('membership-content')
<div class="mn-grid">
    <div class="mn-card"><span>Member Code</span><strong>{{ $member->member_code }}</strong></div>
    <div class="mn-card"><span>Point Balance</span><strong>{{ number_format($pointBalance, 4) }}</strong></div>
    <div class="mn-card"><span>Shares</span><strong>{{ number_format(optional($member->shareHolding)->shares ?? 0, 4) }}</strong></div>
    <div class="mn-card"><span>Card</span><strong>{{ optional($member->activeCard)->card_no ?? '-' }}</strong></div>
    <div class="mn-card"><span>Joined Date &amp; Time</span><strong>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($member->joined_on, $member->created_at) }}</strong></div>
    <div class="mn-card"><span>Added By</span><strong>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($member) }}</strong></div>
    <div class="mn-card"><span>Status</span><strong>{{ $member->is_active ? 'Active' : 'Inactive' }}</strong></div>
</div>
<div class="mn-panel" style="margin-top:14px">
    <h3>{{ $member->full_name ?: trim($member->first_name . ' ' . $member->last_name) }}</h3>
    @if($member->full_name_second_language)
        <p><strong>Full Name in Second Language:</strong> {{ $member->full_name_second_language }}</p>
    @endif
    <p><strong>Title:</strong> {{ $member->title ?: '-' }} | <strong>Gender:</strong> {{ $member->gender ?: '-' }}</p>
    <p><strong>Region:</strong> {{ optional($member->region)->region ?: '-' }} | <strong>Membership Type:</strong> {{ optional($member->membershipType)->setting_value ?: '-' }}</p>
    <p><strong>Mobile:</strong> {{ $member->mobile ?: '-' }} | <strong>Other Mobile Nos:</strong> {{ $member->other_mobile_nos ?: '-' }}</p>
    <p><strong>Business Name:</strong> {{ $member->business_name ?: '-' }} | <strong>Email:</strong> {{ $member->email ?: '-' }} | <strong>NIC:</strong> {{ $member->nic ?: '-' }}</p>
    <p><strong>No of Shares:</strong> {{ $member->no_of_shares !== null ? number_format((float) $member->no_of_shares, 4) : '-' }} | <strong>Total Share Value:</strong> {{ $member->total_share_value !== null ? \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($member->total_share_value) : '-' }}</p>
    <p>{{ $member->address }}</p>
</div>
@endsection
