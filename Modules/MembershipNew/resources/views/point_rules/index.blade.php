@extends('membershipnew::layouts.app')
@php
    $title = 'Point Rules';
@endphp
@section('membership-content')
<div class="mn-panel">
<form method="POST" action="{{ route('membership-new.point-rules.store') }}">@csrf
<div class="mn-form-grid">
<label>Outlet Business ID <input name="outlet_business_id"></label>
<label>Location ID <input name="location_id"></label>
<label>Product Category ID <input name="category_id"></label>
<label>Amount Step <input name="amount_step" value="1.0000"></label>
<label>Points Per Step <input name="points_per_amount" value="0.0000"></label>
<label>Max Points Per Invoice <input name="max_points_per_invoice"></label>
<label class="mn-wide">Note <textarea name="note"></textarea></label>
<label><input type="checkbox" name="is_active" value="1" checked> Active</label>
</div><button class="mn-btn mn-btn-success">Save</button></form>
</div>
<div class="mn-panel" style="margin-top:14px"><table class="mn-table"><thead><tr><th>Outlet</th><th>Location</th><th>Category</th><th>Step</th><th>Points</th><th>Status</th><th>Date &amp; Time</th><th>Added By</th></tr></thead><tbody>@foreach($records as $record)<tr><td>{{ $record->outlet_business_id ?? 'All' }}</td><td>{{ $record->location_id ?? 'All' }}</td><td>{{ $record->category_id ?? 'All' }}</td><td class="mn-money">{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($record->amount_step) }}</td><td>{{ number_format($record->points_per_amount, 4) }}</td><td>{{ $record->is_active ? 'Active' : 'Inactive' }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($record->created_at) }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td></tr>@endforeach</tbody></table>{{ $records->links() }}</div>
@endsection
