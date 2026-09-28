@extends('membershipnew::layouts.app')
@php
    $title = 'Membership New Payments Report';
@endphp
@section('membership-content')
<div class="mn-panel">@include('membershipnew::reports.toolbar', ['tableId' => 'mn-payments-report', 'reportTitle' => $title])<table class="mn-table" id="mn-payments-report"><thead><tr><th>ID</th><th>Member / Reference</th><th>Date &amp; Time</th><th>Amount</th><th>Added By</th></tr></thead><tbody>@forelse($records as $record)<tr><td>{{ $record->id }}</td><td>{{ $record->payment_ref_no ?? optional($record->member)->member_code ?? '-' }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($record->payment_date, $record->created_at) }}</td><td class="mn-money">{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($record->amount ?? 0) }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td></tr>@empty<tr><td colspan="5" class="mn-report-empty">No records found.</td></tr>@endforelse</tbody></table><div class="mn-pagination">{{ $records->appends(request()->query())->links() }}</div></div>
@endsection
