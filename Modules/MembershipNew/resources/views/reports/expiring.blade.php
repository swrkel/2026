@extends('membershipnew::layouts.app')
@php
    $title = 'Membership New Expiring Report';
@endphp
@section('membership-content')
<div class="mn-panel">@include('membershipnew::reports.toolbar', ['tableId' => 'mn-expiring-report', 'reportTitle' => $title])<table class="mn-table" id="mn-expiring-report"><thead><tr><th>ID</th><th>Member</th><th>Joined / Expiry Date &amp; Time</th><th>Status</th><th>Added By</th></tr></thead><tbody>@forelse($records as $record)<tr><td>{{ $record->id }}</td><td>{{ trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? '')) }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($record->joined_on, $record->created_at) }}</td><td>{{ !empty($record->is_active) ? 'Active' : 'Inactive' }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td></tr>@empty<tr><td colspan="5" class="mn-report-empty">No records found.</td></tr>@endforelse</tbody></table><div class="mn-pagination">{{ $records->appends(request()->query())->links() }}</div></div>
@endsection
