@extends('membershipnew::layouts.app')
@php
    $title = 'Central Member Registry';
@endphp
@section('membership-content')
<div class="mn-panel">
    <form method="POST" action="{{ route('membership-new.central-members.store') }}">
        @csrf
        <div class="mn-form-grid">
            <label>First Name <input name="first_name" required></label>
            <label>Last Name <input name="last_name"></label>
            <label>Mobile <input name="mobile"></label>
            <label>Email <input name="email"></label>
            <label>NIC <input name="nic"></label>
            <label>Date of Birth <input type="date" name="date_of_birth"></label>
            <label class="mn-wide">Address <textarea name="address"></textarea></label>
        </div>
        <button class="mn-btn mn-btn-success">Save Central Member</button>
    </form>
</div>

<div class="mn-panel" style="margin-top:14px">
    <form class="mn-toolbar" method="GET">
        <input name="search" value="{{ request('search') }}" placeholder="Search central member">
    </form>
    <table class="mn-table">
        <thead><tr><th>Code</th><th>Name</th><th>Mobile</th><th>NIC</th><th>Email</th><th>Date &amp; Time</th><th>Added By</th><th>Action</th></tr></thead>
        <tbody>
            @foreach($records as $record)
                <tr>
                    <td>{{ $record->central_member_code }}</td>
                    <td>{{ trim($record->first_name . ' ' . $record->last_name) }}</td>
                    <td>{{ $record->mobile }}</td>
                    <td>{{ $record->nic }}</td>
                    <td>{{ $record->email }}</td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($record->created_at) }}</td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td>
                    <td><a href="{{ route('membership-new.central-members.show', $record->id) }}">View</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $records->links() }}
</div>
@endsection
