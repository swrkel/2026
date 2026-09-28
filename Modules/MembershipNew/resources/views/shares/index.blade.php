@extends('membershipnew::layouts.app')
@php
    $title = 'Member Share Holdings';
@endphp
@section('membership-content')
<div class="mn-panel">
<form method="POST" action="{{ route('membership-new.shares.store') }}">@csrf
<div class="mn-form-grid">
<label>Member ID <input name="member_id" required></label>
<label>No. of Shares <input name="shares" required></label>
<label>Share Value <input type="number" step="{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::moneyStep() }}" inputmode="decimal" name="share_value" value="{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money(0) }}"></label>
<label class="mn-wide">Note <textarea name="note"></textarea></label>
</div><button class="mn-btn mn-btn-success">Save / Update</button></form>
</div>
<div class="mn-panel" style="margin-top:14px"><table class="mn-table"><thead><tr><th>Member</th><th>Shares</th><th>Share Value</th><th>Note</th><th>Date &amp; Time</th><th>Added By</th></tr></thead><tbody>@foreach($records as $record)<tr><td>{{ optional($record->member)->member_code }} - {{ optional($record->member)->first_name }}</td><td>{{ number_format($record->shares, 4) }}</td><td class="mn-money">{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($record->share_value) }}</td><td>{{ $record->note }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($record->created_at) }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td></tr>@endforeach</tbody></table>{{ $records->links() }}</div>
@endsection
