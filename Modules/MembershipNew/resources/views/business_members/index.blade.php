@extends('membershipnew::layouts.app')
@php
    $title = 'This Business Members / Customers';
@endphp
@section('membership-content')
<div class="mn-panel">
    <form method="POST" action="{{ route('membership-new.business-members.link') }}">
        @csrf
        <div class="mn-form-grid">
            <label>Central Member
                <select name="central_member_id" required>
                    @foreach($centralMembers as $member)
                        <option value="{{ $member->id }}">{{ $member->central_member_code }} - {{ trim($member->first_name . ' ' . $member->last_name) }}</option>
                    @endforeach
                </select>
            </label>
            <label>Local Customer ID <input name="local_customer_id"></label>
            <label>Local Member ID <input name="local_member_id"></label>
        </div>
        <button class="mn-btn mn-btn-success">Link to This Business</button>
    </form>
</div>

<div class="mn-panel" style="margin-top:14px">
    <table class="mn-table">
        <thead><tr><th>Central Member</th><th>Local Customer ID</th><th>Local Member ID</th><th>Points</th><th>Dividend</th><th>Status</th><th>Date &amp; Time</th><th>Added By</th></tr></thead>
        <tbody>
            @foreach($records as $record)
                <tr>
                    <td>{{ optional($record->centralMember)->central_member_code }} - {{ optional($record->centralMember)->first_name }}</td>
                    <td>{{ $record->local_customer_id }}</td>
                    <td>{{ $record->local_member_id }}</td>
                    <td>{{ $record->points_enabled ? 'Enabled' : 'Disabled' }}</td>
                    <td>{{ $record->dividend_enabled ? 'Enabled' : 'Disabled' }}</td>
                    <td>{{ $record->is_active ? 'Active' : 'Inactive' }}</td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($record->created_at) }}</td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $records->links() }}
</div>
@endsection
