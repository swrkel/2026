@extends('membershipnew::layouts.app')
@php
    $title = 'Business Customer Statement';
@endphp
@php
    $subtitle = 'Detailed business-specific member ledger statement with date and member filters.';
@endphp
@section('membership-content')
<div class="mn-business-only-note">This statement is restricted to the currently selected business.</div>
<div class="mn-panel">
    <form class="mn-toolbar" method="GET">
        <select name="member_business_map_id"><option value="">All business members</option>@foreach($maps as $map)<option value="{{ $map->id }}" {{ request('member_business_map_id') == $map->id ? 'selected' : '' }}>{{ optional($map->centralMember)->central_member_code }} - {{ optional($map->centralMember)->first_name }}</option>@endforeach</select>
        <input type="date" name="from_date" value="{{ request('from_date') }}" title="From date">
        <input type="date" name="to_date" value="{{ request('to_date') }}" title="To date">
        <input type="hidden" name="per_page" value="{{ request('per_page', 50) }}">
    </form>
    @include('membershipnew::reports.toolbar', ['tableId' => 'mn-business-statement-report', 'reportTitle' => $title])
    <table class="mn-table" id="mn-business-statement-report">
        <thead><tr><th>Date &amp; Time</th><th>Member</th><th>Type</th><th>Debit</th><th>Credit</th><th>Balance</th><th>Reference</th><th>Note</th><th>Added By</th></tr></thead>
        <tbody>@forelse($records as $record)<tr><td>{{ optional($record->transaction_date)->format('Y-m-d H:i') }}</td><td>{{ optional(optional($record->businessMap)->centralMember)->central_member_code }} - {{ optional(optional($record->businessMap)->centralMember)->first_name }}</td><td>{{ $record->transaction_type }}</td><td class="mn-money">{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($record->debit) }}</td><td class="mn-money">{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($record->credit) }}</td><td class="mn-money">{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($record->balance) }}</td><td>{{ $record->reference_type }} #{{ $record->reference_id }}</td><td>{{ $record->note }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td></tr>@empty<tr><td colspan="9" class="mn-report-empty">No statement transactions found.</td></tr>@endforelse</tbody>
    </table>
    <div class="mn-pagination">{{ $records->appends(request()->query())->links() }}</div>
</div>
@endsection
