@extends('membershipnew::layouts.app')
@php
    $title = 'Central Member Merge Requests';
@endphp
@section('membership-content')
<div class="mn-panel">
    <form method="POST" action="{{ route('membership-new.member-merge.store') }}">
        @csrf
        <div class="mn-form-grid">
            <label>Primary Central Member
                <select name="primary_central_member_id" required>
                    @foreach($centralMembers as $member)
                        <option value="{{ $member->id }}">{{ $member->central_member_code }} - {{ trim($member->first_name . ' ' . $member->last_name) }}</option>
                    @endforeach
                </select>
            </label>
            <label>Duplicate Central Member
                <select name="duplicate_central_member_id" required>
                    @foreach($centralMembers as $member)
                        <option value="{{ $member->id }}">{{ $member->central_member_code }} - {{ trim($member->first_name . ' ' . $member->last_name) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="mn-wide">Reason <textarea name="reason"></textarea></label>
        </div>
        <button class="mn-btn mn-btn-primary">Create Merge Request</button>
    </form>
</div>

<div class="mn-panel" style="margin-top:14px">
    <table class="mn-table">
        <thead><tr><th>Date &amp; Time</th><th>Primary</th><th>Duplicate</th><th>Approved</th><th>Processed Date &amp; Time</th><th>Reason</th><th>Added By</th><th>Action</th></tr></thead>
        <tbody>
            @foreach($records as $record)
                <tr>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($record->created_at) }}</td>
                    <td>{{ optional($record->primaryMember)->central_member_code }}</td>
                    <td>{{ optional($record->duplicateMember)->central_member_code }}</td>
                    <td>{{ $record->is_approved ? 'Yes' : 'No' }}</td>
                    <td>{{ $record->processed_at ? $record->processed_at->format('Y-m-d H:i') : 'No' }}</td>
                    <td>{{ $record->reason }}</td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td>
                    <td>
                        @if(!$record->is_approved)
                            <form method="POST" action="{{ route('membership-new.member-merge.approve', $record->id) }}" style="display:inline">@csrf<button class="mn-btn mn-btn-success">Approve</button></form>
                        @endif
                        @if($record->is_approved && !$record->processed_at)
                            <form method="POST" action="{{ route('membership-new.member-merge.process', $record->id) }}" style="display:inline">@csrf<button class="mn-btn mn-btn-info">Process</button></form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $records->links() }}
</div>
@endsection
