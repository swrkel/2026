@extends('membershipnew::layouts.app')
@php
    $title = 'This Business Customer Balances';
@endphp
@php
    $subtitle = 'Business-scoped customer ledger and point balances. Central identity remains shared while balances remain isolated by business.';
@endphp
@section('membership-content')
<div class="mn-business-only-note">These balances are for the currently selected business only. Central identity is shared, but ledger and points remain business-specific.</div>
<div class="mn-panel">
    <div class="mn-report-card-header"><div><h3>Business Customer Balances</h3><p>Debit, credit, ledger balance and point balance by member.</p></div></div>
    @include('membershipnew::reports.toolbar', ['tableId' => 'mn-business-balances-report', 'reportTitle' => $title])
    <table class="mn-table" id="mn-business-balances-report">
        <thead><tr><th>Central Code</th><th>Name</th><th>Mobile</th><th>Total Debit</th><th>Total Credit</th><th>Ledger Balance</th><th>Point Balance</th><th>Date &amp; Time</th><th>Added By</th></tr></thead>
        <tbody>
        @forelse($records as $record)
            <tr><td>{{ $record->central_member_code }}</td><td>{{ trim($record->first_name . ' ' . $record->last_name) }}</td><td>{{ $record->mobile }}</td><td class="mn-money">{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($record->total_debit) }}</td><td class="mn-money">{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($record->total_credit) }}</td><td class="mn-money">{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($record->ledger_balance) }}</td><td>{{ number_format($record->point_balance, 4) }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($record->created_at) }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::userNameById($record->created_by ?? null) }}</td></tr>
        @empty<tr><td colspan="9" class="mn-report-empty">No balance records found.</td></tr>@endforelse
        </tbody>
    </table>
    <div class="mn-pagination">{{ $records->appends(request()->query())->links() }}</div>
</div>
@endsection
