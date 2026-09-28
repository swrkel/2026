@extends('membershipnew::layouts.app')
@php
    $title = 'Share Register';
@endphp
@php
    $subtitle = 'Member shareholding register for the selected business';
@endphp
@section('membership-content')
<div class="mn-panel">
    <div class="mn-report-card-header"><div><h3>Share Register</h3><p>Member shareholding register for the selected business</p></div></div>
    @include('membershipnew::reports.toolbar', ['tableId' => 'mn-report-share-register', 'reportTitle' => $title])
    <table class="mn-table" id="mn-report-share-register">
        <thead><tr><th>ID</th><th>Member</th><th>Shares</th><th>Value</th><th>Date &amp; Time</th><th>Added By</th></tr></thead>
        <tbody>
        @forelse($records as $record)
            <tr><td>{{ $record->id }}</td><td>{{ optional($record->member)->member_code }} - {{ optional($record->member)->first_name }}</td><td>{{ number_format($record->shares ?? 0, 4) }}</td><td class="mn-money">{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($record->amount ?? $record->share_value ?? 0) }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($record->created_at) }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td></tr>
        @empty
            <tr><td colspan="6" class="mn-report-empty">No records found.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="mn-pagination">{{ $records->appends(request()->query())->links() }}</div>
</div>
@endsection
