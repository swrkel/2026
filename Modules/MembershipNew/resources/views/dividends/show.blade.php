@extends('membershipnew::layouts.app')
@php
    $title = 'Dividend Batch Details';
@endphp
@section('page-actions')
@if(!$record->is_posted)
<form method="POST" action="{{ route('membership-new.dividends.post', $record->id) }}" style="display:inline">
    @csrf
    <button class="mn-btn mn-btn-success">Post Dividend</button>
</form>
@endif
@endsection
@section('membership-content')
<div class="mn-grid">
    <div class="mn-card"><span>Total Amount</span><strong>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($record->total_dividend_amount) }}</strong></div>
    <div class="mn-card"><span>Dividend Per Share</span><strong>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($record->dividend_per_share) }}</strong></div>
    <div class="mn-card"><span>Status</span><strong>{{ $record->is_posted ? 'Posted' : 'Draft' }}</strong></div>
    <div class="mn-card"><span>Date &amp; Time</span><strong>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($record->dividend_date, $record->created_at) }}</strong></div>
    <div class="mn-card"><span>Added By</span><strong>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</strong></div>
</div>
<div class="mn-panel" style="margin-top:14px">
    <table class="mn-table">
        <thead><tr><th>Date &amp; Time</th><th>Member</th><th>Shares</th><th>Amount</th><th>Paid</th><th>Added By</th></tr></thead>
        <tbody>
        @forelse($record->payments as $payment)
            <tr>
                <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($payment->created_at) }}</td>
                <td>{{ optional($payment->member)->member_code }} - {{ optional($payment->member)->first_name }}</td>
                <td>{{ number_format($payment->shares, 4) }}</td>
                <td class="mn-money">{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($payment->amount) }}</td>
                <td>{{ $payment->is_paid ? 'Yes' : 'No' }}</td>
                <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($payment) }}</td>
            </tr>
        @empty
            <tr><td colspan="6">No dividend payments found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
