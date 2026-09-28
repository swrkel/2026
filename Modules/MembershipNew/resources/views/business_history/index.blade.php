@extends('membershipnew::layouts.app')
@php
    $title = 'This Business Customer History / Ledger';
@endphp
@section('membership-content')
<div class="mn-panel">
    <form method="POST" action="{{ route('membership-new.business-history.ledger-entry') }}">
        @csrf
        <div class="mn-form-grid">
            <label>Business Member
                <select name="member_business_map_id" required>
                    @foreach($maps as $map)
                        <option value="{{ $map->id }}">{{ optional($map->centralMember)->central_member_code }} - {{ optional($map->centralMember)->first_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Type <input name="transaction_type" placeholder="sale / payment / adjustment"></label>
            <label>Debit <input type="number" step="{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::moneyStep() }}" inputmode="decimal" name="debit" value="{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money(0) }}"></label>
            <label>Credit <input type="number" step="{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::moneyStep() }}" inputmode="decimal" name="credit" value="{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money(0) }}"></label>
            <label>Reference Type <input name="reference_type"></label>
            <label>Reference ID <input name="reference_id"></label>
            <label class="mn-wide">Note <textarea name="note"></textarea></label>
        </div>
        <button class="mn-btn mn-btn-primary">Add Business Ledger Entry</button>
    </form>
</div>

<div class="mn-panel" style="margin-top:14px">
    @include('membershipnew::reports.toolbar', ['tableId' => 'mn-business-history-report', 'reportTitle' => 'Business Customer History / Ledger'])
    <table class="mn-table" id="mn-business-history-report">
        <thead><tr><th>Date &amp; Time</th><th>Member</th><th>Type</th><th>Debit</th><th>Credit</th><th>Balance</th><th>Reference</th><th>Added By</th></tr></thead>
        <tbody>
            @foreach($records as $record)
                <tr>
                    <td>{{ optional($record->transaction_date)->format('Y-m-d H:i') }}</td>
                    <td>{{ optional(optional($record->businessMap)->centralMember)->central_member_code }} - {{ optional(optional($record->businessMap)->centralMember)->first_name }}</td>
                    <td>{{ $record->transaction_type }}</td>
                    <td class="mn-money">{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($record->debit) }}</td>
                    <td class="mn-money">{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($record->credit) }}</td>
                    <td class="mn-money">{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($record->balance) }}</td>
                    <td>{{ $record->reference_type }} #{{ $record->reference_id }}</td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $records->appends(request()->query())->links() }}
</div>
@endsection
