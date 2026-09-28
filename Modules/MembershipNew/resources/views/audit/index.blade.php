@extends('membershipnew::layouts.app')
@php
    $title = 'Membership New Audit Log';
@endphp
@php
    $subtitle = 'Business-scoped audit trail for Membership New activity.';
@endphp
@section('membership-content')
<div class="mn-panel">
    <form class="mn-toolbar" method="GET"><input name="action" value="{{ request('action') }}" placeholder="Action"><input name="entity_type" value="{{ request('entity_type') }}" placeholder="Entity Type"><input type="hidden" name="per_page" value="{{ request('per_page', 50) }}"></form>
    @include('membershipnew::reports.toolbar', ['tableId' => 'mn-audit-report', 'reportTitle' => $title])
    <table class="mn-table" id="mn-audit-report"><thead><tr><th>Date &amp; Time</th><th>Added By</th><th>Action</th><th>Entity</th><th>IP</th><th>Details</th></tr></thead><tbody>@forelse($records as $record)<tr><td>{{ optional($record->created_at)->format('Y-m-d H:i') }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td><td>{{ $record->action }}</td><td>{{ $record->entity_type }} #{{ $record->entity_id }}</td><td>{{ $record->ip_address }}</td><td><pre>{{ json_encode($record->new_values ?? $record->meta, JSON_PRETTY_PRINT) }}</pre></td></tr>@empty<tr><td colspan="6" class="mn-report-empty">No audit records found.</td></tr>@endforelse</tbody></table>
    <div class="mn-pagination">{{ $records->appends(request()->query())->links() }}</div>
</div>
@endsection
