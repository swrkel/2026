@extends('membershipnew::layouts.app')
@php
    $title = 'Member Point Balances';
@endphp
@php
    $subtitle = 'Current point balance by member for the selected business';
@endphp
@section('membership-content')
<div class="mn-panel">
    <div class="mn-report-card-header"><div><h3>Member Point Balances</h3><p>Current point balance by member for the selected business</p></div></div>
    @include('membershipnew::reports.toolbar', ['tableId' => 'mn-report-member-balances', 'reportTitle' => $title])
    <table class="mn-table" id="mn-report-member-balances">
        <thead><tr><th>ID</th><th>Member</th><th>Mobile</th><th>Point Balance</th><th>Date &amp; Time</th><th>Added By</th></tr></thead>
        <tbody>
        @forelse($records as $record)
            <tr><td>{{ $record->id }}</td><td>{{ $record->member_code }} - {{ trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? '')) }}</td><td>{{ $record->mobile ?? '-' }}</td><td>{{ number_format($record->point_balance ?? 0, 4) }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($record->joined_on, $record->created_at) }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td></tr>
        @empty
            <tr><td colspan="6" class="mn-report-empty">No records found.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="mn-pagination">{{ $records->appends(request()->query())->links() }}</div>
</div>
@endsection
