@extends('membershipnew::layouts.app')
@php
    $title = 'Central Member Duplicate Candidates';
@endphp
@section('page-actions')
<form method="POST" action="{{ route('membership-new.central-members.duplicates.scan') }}" style="display:inline">
    @csrf
    <button class="mn-btn mn-btn-warning">Scan Duplicates</button>
</form>
@endsection
@section('membership-content')
<div class="mn-panel">
    <table class="mn-table">
        <thead>
            <tr>
                <th>Date &amp; Time</th>
                <th>Primary Central ID</th>
                <th>Duplicate Central ID</th>
                <th>Matched Fields</th>
                <th>Score</th>
                <th>Resolved</th>
                <th>Added By</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $record)
                <tr>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($record->created_at) }}</td>
                    <td>{{ $record->primary_central_member_id }}</td>
                    <td>{{ $record->duplicate_central_member_id }}</td>
                    <td>{{ implode(', ', $record->match_fields ?? []) }}</td>
                    <td>{{ number_format($record->confidence_score, 4) }}</td>
                    <td>{{ $record->is_resolved ? 'Yes' : 'No' }}</td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td>
                    <td>
                        @if(!$record->is_resolved)
                            <form method="POST" action="{{ route('membership-new.central-members.duplicates.resolve', $record->id) }}">
                                @csrf
                                <input name="resolution_note" placeholder="Resolution note">
                                <button class="mn-btn mn-btn-success">Resolve</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">No duplicate candidates found.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $records->links() }}
</div>
@endsection
