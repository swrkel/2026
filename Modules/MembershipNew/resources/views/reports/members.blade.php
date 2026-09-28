@extends('membershipnew::layouts.app')
@php
    $title = 'Membership New Members Report';
@endphp
@section('membership-content')
<div class="mn-panel">@include('membershipnew::reports.toolbar', ['tableId' => 'mn-members-report', 'reportTitle' => $title])<table class="mn-table" id="mn-members-report"><thead><tr><th>ID</th><th>Member</th><th>Mobile</th><th>Email</th><th>Joined Date &amp; Time</th><th>Added By</th></tr></thead><tbody>@forelse($records as $record)<tr><td>{{ $record->id }}</td><td>{{ trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? '')) }}</td><td>{{ $record->mobile ?? '-' }}</td><td>{{ $record->email ?? '-' }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($record->joined_on, $record->created_at) }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td></tr>@empty<tr><td colspan="6" class="mn-report-empty">No records found.</td></tr>@endforelse</tbody></table><div class="mn-pagination">{{ $records->appends(request()->query())->links() }}</div></div>
@endsection
