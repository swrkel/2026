@extends('membershipnew::layouts.app')
@php
    $title = 'Central Member Details';
@endphp
@section('membership-content')
<div class="mn-grid">
    <div class="mn-card"><span>Central Code</span><strong>{{ $record->central_member_code }}</strong></div>
    <div class="mn-card"><span>Name</span><strong>{{ trim($record->first_name . ' ' . $record->last_name) }}</strong></div>
    <div class="mn-card"><span>Mobile</span><strong>{{ $record->mobile }}</strong></div>
    <div class="mn-card"><span>NIC</span><strong>{{ $record->nic }}</strong></div>
    <div class="mn-card"><span>Date &amp; Time</span><strong>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($record->created_at) }}</strong></div>
    <div class="mn-card"><span>Added By</span><strong>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</strong></div>
</div>
<div class="mn-panel" style="margin-top:14px">
    <h3>Linked Businesses</h3>
    <table class="mn-table">
        <thead><tr><th>Date &amp; Time</th><th>Business ID</th><th>Local Customer ID</th><th>Local Member ID</th><th>Status</th><th>Last Activity Date &amp; Time</th><th>Added By</th></tr></thead>
        <tbody>
            @forelse($record->businessMaps as $map)
                <tr>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($map->created_at) }}</td>
                    <td>{{ $map->business_id }}</td>
                    <td>{{ $map->local_customer_id }}</td>
                    <td>{{ $map->local_member_id }}</td>
                    <td>{{ $map->is_active ? 'Active' : 'Inactive' }}</td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($map->last_activity_at) }}</td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($map) }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No linked businesses found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
