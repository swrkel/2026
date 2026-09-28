@extends('membershipnew::layouts.app')
@php
    $title = 'Point Ledger Report';
@endphp
@php
    $subtitle = 'Earned and redeemed point transactions for the selected business';
@endphp
@section('membership-content')
<div class="mn-panel">
    <div class="mn-report-card-header"><div><h3>Point Ledger Report</h3><p>Earned and redeemed point transactions for the selected business</p></div></div>
    @include('membershipnew::reports.toolbar', ['tableId' => 'mn-report-point-ledger', 'reportTitle' => $title])
    <table class="mn-table" id="mn-report-point-ledger">
        <thead><tr><th>ID</th><th>Date &amp; Time</th><th>Member</th><th>Type</th><th>Points</th><th>Purchase Amount</th><th>Reference</th><th>Added By</th></tr></thead>
        <tbody>
        @forelse($records as $record)
            <tr><td>{{ $record->id }}</td><td>{{ optional($record->transaction_date)->format('Y-m-d H:i') }}</td><td>{{ optional($record->member)->member_code }} - {{ trim((optional($record->member)->first_name ?? '') . ' ' . (optional($record->member)->last_name ?? '')) }}</td><td>{{ $record->type ?? '-' }}</td><td>{{ number_format($record->points ?? 0, 4) }}</td><td class="mn-money">{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($record->purchase_amount ?? 0) }}</td><td>{{ $record->reference_type ?? '-' }} #{{ $record->reference_id ?? '-' }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td></tr>
        @empty
            <tr><td colspan="8" class="mn-report-empty">No records found.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="mn-pagination">{{ $records->appends(request()->query())->links() }}</div>
</div>
@endsection
