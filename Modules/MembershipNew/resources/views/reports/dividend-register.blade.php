@extends('membershipnew::layouts.app')
@php
    $title = 'Dividend Register';
@endphp
@php
    $subtitle = 'Dividend payment register for the selected business';
@endphp
@section('membership-content')
<div class="mn-panel">
    <div class="mn-report-card-header"><div><h3>Dividend Register</h3><p>Dividend payment register for the selected business</p></div></div>
    @include('membershipnew::reports.toolbar', ['tableId' => 'mn-report-dividend-register', 'reportTitle' => $title])
    <table class="mn-table" id="mn-report-dividend-register">
        <thead><tr><th>ID</th><th>Member</th><th>Shares</th><th>Amount</th><th>Paid</th><th>Date &amp; Time</th><th>Added By</th></tr></thead>
        <tbody>
        @forelse($records as $record)
            <tr><td>{{ $record->id }}</td><td>{{ optional($record->member)->member_code }} - {{ optional($record->member)->first_name }}</td><td>{{ number_format($record->shares ?? 0, 4) }}</td><td class="mn-money">{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($record->amount ?? 0) }}</td><td>{{ !empty($record->is_paid) ? 'Yes' : 'No' }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($record->created_at) }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td></tr>
        @empty
            <tr><td colspan="7" class="mn-report-empty">No records found.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="mn-pagination">{{ $records->appends(request()->query())->links() }}</div>
</div>
@endsection
