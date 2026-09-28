@extends('membershipnew::layouts.app')
@php
    $title = 'Member Point Ledger';
@endphp
@section('membership-content')
<div class="mn-card"><span>{{ $member->member_code }} - {{ trim($member->first_name . ' ' . $member->last_name) }}</span><strong>{{ number_format($balance, 4) }}</strong></div>
<div class="mn-panel" style="margin-top:14px">@include('membershipnew::reports.toolbar', ['tableId' => 'mn-member-point-ledger-report', 'reportTitle' => 'Member Point Ledger'])<table class="mn-table" id="mn-member-point-ledger-report"><thead><tr><th>Date &amp; Time</th><th>Type</th><th>Points</th><th>Purchase Amount</th><th>Reference</th><th>Note</th><th>Added By</th></tr></thead><tbody>@foreach($records as $record)<tr><td>{{ optional($record->transaction_date)->format('Y-m-d H:i') }}</td><td>{{ $record->type }}</td><td>{{ number_format($record->points, 4) }}</td><td class="mn-money">{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($record->purchase_amount) }}</td><td>{{ $record->reference_type }} #{{ $record->reference_id }}</td><td>{{ $record->note }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td></tr>@endforeach</tbody></table>{{ $records->appends(request()->query())->links() }}</div>
@endsection
