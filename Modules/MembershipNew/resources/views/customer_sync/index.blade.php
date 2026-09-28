@extends('membershipnew::layouts.app')
@php
    $title = 'Membership-New Customer Sync';
@endphp
@section('page-actions')
<form method="POST" action="{{ route('membership-new.customer-sync.prepare-all') }}" style="display:inline">@csrf<button class="mn-btn mn-btn-primary">Prepare All Active Members</button></form>
@endsection
@section('membership-content')
<div class="mn-panel">
    <h3>Prepare One Member</h3>
    <form method="POST" action="{{ route('membership-new.customer-sync.prepare-member') }}">
        @csrf
        <div class="mn-form-grid">
            <label>Member
                <select name="member_id" required>
                    @foreach($members as $member)
                        <option value="{{ $member->id }}">{{ $member->member_code }} - {{ trim($member->first_name . ' ' . $member->last_name) }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <button class="mn-btn mn-btn-info">Prepare Member Sync</button>
    </form>
</div>

<div class="mn-panel" style="margin-top:14px">
    <table class="mn-table">
        <thead>
            <tr>
                <th>Member</th>
                <th>Linked Business</th>
                <th>Linked Customer ID</th>
                <th>Synced</th>
                <th>Last Sync Date &amp; Time</th>
                <th>Added By</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $record)
                <tr>
                    <td>{{ optional($record->member)->member_code }} - {{ optional($record->member)->first_name }}</td>
                    <td>{{ $record->linked_business_id }}</td>
                    <td>{{ $record->linked_customer_id ?? '-' }}</td>
                    <td>{{ $record->is_synced ? 'Yes' : 'No' }}</td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($record->last_synced_at, $record->created_at) }}</td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td>
                    <td>
                        <form method="POST" action="{{ route('membership-new.customer-sync.mark-synced', $record->id) }}">
                            @csrf
                            <input name="linked_customer_id" placeholder="Customer ID">
                            <button class="mn-btn mn-btn-success">Mark Synced</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">No sync records prepared yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $records->links() }}
</div>
@endsection
