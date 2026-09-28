@extends('membershipnew::layouts.app')
@php
    $title = 'Membership New Error Logs';
@endphp
@php
    $subtitle = 'Captured Membership New errors for the currently selected business.';
@endphp
@section('membership-content')
<div class="mn-panel">
    <form class="mn-toolbar" method="GET"><input name="search" value="{{ request('search') }}" placeholder="Search error/message"><input type="hidden" name="per_page" value="{{ request('per_page', 50) }}"></form>
    @include('membershipnew::reports.toolbar', ['tableId' => 'mn-error-log-report', 'reportTitle' => $title])
    <table class="mn-table" id="mn-error-log-report"><thead><tr><th>Date &amp; Time</th><th>Added By</th><th>Error</th><th>Message</th><th>File</th><th>Line</th></tr></thead><tbody>@forelse($records as $record)<tr><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($record->created_at) }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td><td>{{ $record->error_class }}</td><td>{{ $record->message }}</td><td>{{ $record->file }}</td><td>{{ $record->line }}</td></tr>@empty<tr><td colspan="6" class="mn-report-empty">No error records found.</td></tr>@endforelse</tbody></table>
    <div class="mn-pagination">{{ $records->appends(request()->query())->links() }}</div>
</div>
@endsection
