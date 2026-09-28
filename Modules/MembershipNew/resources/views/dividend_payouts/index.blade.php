@extends('membershipnew::layouts.app')
@php
    $title = 'Dividend Payouts';
@endphp
@php
    $subtitle = 'Pending dividend payments and payout/reversal history for the selected business.';
@endphp
@section('membership-content')
<div class="mn-panel">
    <div class="mn-report-card-header"><div><h3>Pending Dividend Payments</h3><p>Outstanding dividend payments awaiting settlement.</p></div></div>
    @include('membershipnew::reports.toolbar', ['tableId' => 'mn-dividend-pending-report', 'reportTitle' => 'Pending Dividend Payments'])
    <table class="mn-table" id="mn-dividend-pending-report"><thead><tr><th>ID</th><th>Member ID</th><th>Shares</th><th>Amount</th><th>Date &amp; Time</th><th>Added By</th><th>Action</th></tr></thead><tbody>@forelse($pending as $payment)<tr><td>{{ $payment->id }}</td><td>{{ $payment->member_id }}</td><td>{{ number_format($payment->shares, 4) }}</td><td class="mn-money">{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($payment->amount) }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($payment->created_at) }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($payment) }}</td><td><form method="POST" action="{{ route('membership-new.dividend-payouts.pay', $payment->id) }}">@csrf<div class="mn-action-links"><input name="payment_method" placeholder="Method"><input name="payment_ref_no" placeholder="Ref No"><button class="mn-btn mn-btn-success mn-btn-sm">Pay</button></div></form></td></tr>@empty<tr><td colspan="7" class="mn-report-empty">No pending dividend payments.</td></tr>@endforelse</tbody></table>
    <div class="mn-pagination">{{ $pending->appends(request()->query())->links() }}</div>
</div>
<div class="mn-panel">
    <div class="mn-report-card-header"><div><h3>Payout History</h3><p>Paid and reversed dividend transactions.</p></div></div>
    @include('membershipnew::reports.toolbar', ['tableId' => 'mn-dividend-payout-report', 'reportTitle' => 'Dividend Payout History'])
    <table class="mn-table" id="mn-dividend-payout-report"><thead><tr><th>Date &amp; Time</th><th>Member ID</th><th>Amount</th><th>Method</th><th>Ref</th><th>Reversed</th><th>Added By</th><th>Action</th></tr></thead><tbody>@forelse($payouts as $payout)<tr><td>{{ optional($payout->paid_at)->format('Y-m-d H:i') }}</td><td>{{ $payout->member_id }}</td><td class="mn-money">{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($payout->amount) }}</td><td>{{ $payout->payment_method }}</td><td>{{ $payout->payment_ref_no }}</td><td>{{ $payout->is_reversed ? 'Yes' : 'No' }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($payout) }}</td><td>@if(!$payout->is_reversed)<form method="POST" action="{{ route('membership-new.dividend-payouts.reverse', $payout->id) }}">@csrf<div class="mn-action-links"><input name="reversal_note" placeholder="Reason"><button class="mn-btn mn-btn-danger mn-btn-sm">Reverse</button></div></form>@endif</td></tr>@empty<tr><td colspan="8" class="mn-report-empty">No payout history found.</td></tr>@endforelse</tbody></table>
    <div class="mn-pagination">{{ $payouts->appends(request()->query())->links() }}</div>
</div>
@endsection
