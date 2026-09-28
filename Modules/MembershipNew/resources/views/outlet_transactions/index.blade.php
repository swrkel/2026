@extends('membershipnew::layouts.app')
@php
    $title = 'Linked Outlet Membership Transactions';
@endphp
@section('membership-content')
<div class="mn-panel">
    <form method="POST" action="{{ route('membership-new.outlet-transactions.store') }}">
        @csrf
        <div class="mn-form-grid">
            <label>Outlet Business ID <input name="outlet_business_id" required></label>
            <label>Member Business Map ID <input name="member_business_map_id"></label>
            <label>Central Member ID <input name="central_member_id"></label>
            <label>Local Member ID <input name="member_id"></label>
            <label>Purchase Amount <input type="number" step="{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::moneyStep() }}" inputmode="decimal" name="purchase_amount" value="{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money(0) }}"></label>
            <label>Earn Points <input name="earn_points" value="0.0000"></label>
            <label>Redeem Points <input name="redeem_points" value="0.0000"></label>
            <label>Reference Type <input name="reference_type"></label>
            <label>Reference ID <input name="reference_id"></label>
        </div>
        <button class="mn-btn mn-btn-primary">Queue Outlet Transaction</button>
    </form>
</div>

<div class="mn-panel" style="margin-top:14px">
    <table class="mn-table">
        <thead>
            <tr>
                <th>Date &amp; Time</th>
                <th>Outlet</th>
                <th>Purchase</th>
                <th>Earn</th>
                <th>Redeem</th>
                <th>Reference</th>
                <th>Processed</th>
                <th>Added By</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $record)
                <tr>
                    <td>{{ optional($record->transaction_date)->format('Y-m-d H:i') }}</td>
                    <td>{{ $record->outlet_business_id }}</td>
                    <td class="mn-money">{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($record->purchase_amount) }}</td>
                    <td>{{ number_format($record->earn_points, 4) }}</td>
                    <td>{{ number_format($record->redeem_points, 4) }}</td>
                    <td>{{ $record->reference_type }} #{{ $record->reference_id }}</td>
                    <td>{{ $record->is_processed ? 'Yes' : 'No' }}</td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td>
                    <td>
                        @if(!$record->is_processed)
                            <form method="POST" action="{{ route('membership-new.outlet-transactions.process', $record->id) }}">
                                @csrf
                                <button class="mn-btn mn-btn-success">Process</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $records->links() }}
</div>
@endsection
