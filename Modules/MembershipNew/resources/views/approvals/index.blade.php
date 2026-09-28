@extends('membershipnew::layouts.app')
@php
    $title = 'Membership-New Approval Requests';
@endphp
@section('membership-content')
<div class="mn-panel">
    <form method="POST" action="{{ route('membership-new.approvals.store') }}">
        @csrf
        <div class="mn-form-grid">
            <label>Request Type <input name="request_type" required placeholder="merge / dividend / card / adjustment"></label>
            <label>Entity Type <input name="entity_type"></label>
            <label>Entity ID <input name="entity_id"></label>
            <label class="mn-wide">Note <textarea name="note"></textarea></label>
        </div>
        <button class="mn-btn mn-btn-primary">Create Approval Request</button>
    </form>
</div>

<div class="mn-panel" style="margin-top:14px">
    <table class="mn-table">
        <thead><tr><th>Date &amp; Time</th><th>Type</th><th>Entity</th><th>Status</th><th>Added By</th><th>Action</th></tr></thead>
        <tbody>
            @foreach($records as $record)
                <tr>
                    <td>{{ optional($record->created_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ $record->request_type }}</td>
                    <td>{{ $record->entity_type }} #{{ $record->entity_id }}</td>
                    <td>{{ $record->status }}</td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td>
                    <td>
                        @if($record->status === 'pending')
                            <form method="POST" action="{{ route('membership-new.approvals.approve', $record->id) }}" style="display:inline">@csrf<button class="mn-btn mn-btn-success">Approve</button></form>
                            <form method="POST" action="{{ route('membership-new.approvals.reject', $record->id) }}" style="display:inline">@csrf<button class="mn-btn mn-btn-danger">Reject</button></form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $records->links() }}
</div>
@endsection
