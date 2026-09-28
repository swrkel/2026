@extends('managementreport::layouts.master', [
    'pageTitle' => 'Saved Management Reports',
    'pageSubtitle' => 'Immutable report snapshots kept for review, printing and delivery.'
])

@section('page_actions')
<a href="{{ route('managementreport.dashboard') }}" class="mgmt-btn mgmt-btn-default"><i class="fa fa-dashboard"></i> Dashboard</a>
<a href="{{ route('managementreport.daily.index') }}" class="mgmt-btn mgmt-btn-primary"><i class="fa fa-plus"></i> New Report</a>
<a href="{{ route('managementreport.shares.index') }}" class="mgmt-btn mgmt-btn-success"><i class="fa fa-paper-plane"></i> Delivery History</a>
@endsection

@section('managementreport_content')
<div class="mgmt-panel">
    <form class="mgmt-inline-filter" method="GET">
        <div class="mgmt-field"><label>From</label><input type="date" name="start_date" value="{{ request('start_date') }}"></div>
        <div class="mgmt-field"><label>To</label><input type="date" name="end_date" value="{{ request('end_date') }}"></div>
        <div class="mgmt-field">
            <label>Status</label>
            <select name="status" class="mgmt-native-all-filter" data-default-all="1">
                <option value="__all__" {{ in_array((string) request('status', '__all__'), ['', '__all__'], true) ? 'selected' : '' }}>All</option>
                @foreach(['generated','shared','archived'] as $status)
                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <button class="mgmt-btn mgmt-btn-primary" type="submit"><i class="fa fa-search"></i> Filter</button>
        <a class="mgmt-btn" href="{{ route('managementreport.saved.index') }}">Reset</a>
    </form>
    <div class="table-responsive">
        <table class="table mgmt-table">
            <thead><tr><th>Report</th><th>Period</th><th>Scope</th><th>Generated</th><th>Review</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            @forelse($runs as $run)
                <tr>
                    <td><strong>{{ $run->report_title }}</strong><small class="mgmt-muted">{{ $run->uuid }}</small></td>
                    <td>{{ \Carbon\Carbon::parse($run->period_start)->format('d M Y') }}@if($run->period_start !== $run->period_end) - {{ \Carbon\Carbon::parse($run->period_end)->format('d M Y') }}@endif</td>
                    <td>Location: {{ $run->location_id ?: 'All' }} / Store: {{ $run->store_id ?: 'All' }}</td>
                    <td>{{ optional($run->generated_at)->format('d M Y, h:i A') }}</td>
                    <td><span class="mgmt-status mgmt-status-{{ $run->review_status }}">{{ ucfirst($run->review_status) }}</span></td>
                    <td class="text-right">
                        <a href="{{ route('managementreport.saved.show', $run) }}" class="mgmt-btn mgmt-btn-sm">Open</a>
                        <a href="{{ route('managementreport.daily.pdf', $run) }}" class="mgmt-btn mgmt-btn-sm">PDF</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="mgmt-empty">No saved reports match this filter.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mgmt-pagination">{{ $runs->links() }}</div>
</div>
@endsection
