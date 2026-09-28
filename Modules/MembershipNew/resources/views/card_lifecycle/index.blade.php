@extends('membershipnew::layouts.app')
@php
    $title = 'Membership Card Lifecycle';
@endphp
@section('membership-content')
<div class="mn-panel">
    <table class="mn-table">
        <thead><tr><th>Card No</th><th>Member</th><th>Issued Date &amp; Time</th><th>Expires Date &amp; Time</th><th>Last Scan Date &amp; Time</th><th>Status</th><th>Added By</th><th>Action</th></tr></thead>
        <tbody>
            @foreach($records as $record)
                <tr>
                    <td>{{ $record->card_no }}</td>
                    <td>{{ optional($record->member)->member_code }} - {{ optional($record->member)->first_name }}</td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($record->issued_on, $record->created_at) }}</td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($record->expires_on) }}</td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($record->last_scanned_at) }}</td>
                    <td>{{ $record->is_active ? 'Active' : 'Inactive' }}</td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td>
                    <td>
                        @if($record->is_active)
                            <form method="POST" action="{{ route('membership-new.card-lifecycle.block', $record->id) }}" style="display:inline">
                                @csrf
                                <input name="blocked_reason" placeholder="Reason">
                                <button class="mn-btn mn-btn-danger">Block</button>
                            </form>
                            <form method="POST" action="{{ route('membership-new.card-lifecycle.replace', $record->id) }}" style="display:inline">
                                @csrf
                                <button class="mn-btn mn-btn-warning">Replace</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{ $records->links() }}
</div>
@endsection
