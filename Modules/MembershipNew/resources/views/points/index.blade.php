@extends('membershipnew::layouts.app')
@php
    $title = 'Point Transactions';
@endphp
@section('membership-content')
<div class="mn-grid">
<div class="mn-panel">
<h3>Earn Points</h3>
<form method="POST" action="{{ route('membership-new.points.earn') }}">@csrf
<label>Member <select name="member_id">@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->member_code }} - {{ $member->first_name }}</option>@endforeach</select></label>
<label>Points <input name="points" required></label>
<label>Purchase Amount <input type="number" step="{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::moneyStep() }}" inputmode="decimal" name="purchase_amount" value="{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money(0) }}"></label>
<button class="mn-btn mn-btn-success">Earn</button>
</form>
</div>
<div class="mn-panel">
<h3>Redeem Points</h3>
<form method="POST" action="{{ route('membership-new.points.redeem') }}">@csrf
<label>Member <select name="member_id">@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->member_code }} - {{ $member->first_name }}</option>@endforeach</select></label>
<label>Points <input name="points" required></label>
<label>Purchase Amount <input type="number" step="{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::moneyStep() }}" inputmode="decimal" name="purchase_amount" value="{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money(0) }}"></label>
<button class="mn-btn mn-btn-warning">Redeem</button>
</form>
</div>
</div>
<div class="mn-panel" style="margin-top:14px">
@include('membershipnew::reports.toolbar', ['tableId' => 'mn-point-transactions-report', 'reportTitle' => 'Point Transactions'])
<table class="mn-table" id="mn-point-transactions-report"><thead><tr><th>Date &amp; Time</th><th>Member</th><th>Type</th><th>Points</th><th>Purchase Amount</th><th>Reference</th><th>Added By</th></tr></thead><tbody>@foreach($records as $record)<tr><td>{{ optional($record->transaction_date)->format('Y-m-d H:i') }}</td><td>{{ optional($record->member)->member_code }} - {{ optional($record->member)->first_name }}</td><td>{{ $record->type }}</td><td>{{ number_format($record->points, 4) }}</td><td class="mn-money">{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($record->purchase_amount) }}</td><td>{{ $record->reference_type }} #{{ $record->reference_id }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td></tr>@endforeach</tbody></table>{{ $records->appends(request()->query())->links() }}
</div>
@endsection
