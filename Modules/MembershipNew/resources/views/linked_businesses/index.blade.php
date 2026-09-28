@extends('membershipnew::layouts.app')
@php
    $title = 'Linked Businesses / Outlets';
@endphp
@section('membership-content')
<div class="mn-panel">
<form method="POST" action="{{ route('membership-new.linked-businesses.store') }}">@csrf
<div class="mn-form-grid">
<label>Linked Business ID <input name="linked_business_id" required></label>
<label>Name <input name="name"></label>
<label class="mn-wide">Note <textarea name="note"></textarea></label>
<label><input type="checkbox" name="is_active" value="1" checked> Active</label>
</div><button class="mn-btn mn-btn-success">Save</button></form>
</div>
<div class="mn-panel" style="margin-top:14px"><table class="mn-table"><thead><tr><th>ID</th><th>Linked Business ID</th><th>Name</th><th>Status</th><th>Date &amp; Time</th><th>Added By</th></tr></thead><tbody>@foreach($records as $record)<tr><td>{{ $record->id }}</td><td>{{ $record->linked_business_id }}</td><td>{{ $record->name }}</td><td>{{ $record->is_active ? 'Active' : 'Inactive' }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($record->created_at) }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td></tr>@endforeach</tbody></table>{{ $records->links() }}</div>
@endsection
