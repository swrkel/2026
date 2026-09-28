@extends('audit::layout')
@section('audit-title','Audit Reports')
@section('audit-content')

<div class="audit-cards audit-report-cards">
    <div class="audit-stat report-total"><span>Report Rows</span><strong>{{ number_format($summary['total']) }}</strong></div>
    <div class="audit-stat open"><span>Current / Open</span><strong>{{ number_format($summary['open']) }}</strong></div>
    <div class="audit-stat resolved"><span>Resolved</span><strong>{{ number_format($summary['resolved']) }}</strong></div>
    <div class="audit-stat report-false-positive"><span>False Positives</span><strong>{{ number_format($summary['false_positive']) }}</strong></div>
</div>

<form method="get" class="audit-card audit-filter-card audit-report-filter-card">
    @include('audit::partials.filters')

    <div class="audit-filter-row audit-report-secondary-filters">
        <select name="module" class="audit-input">
            <option value="">All Modules</option>
            @foreach($modules as $m)
                <option value="{{ $m }}" {{ request('module') === $m ? 'selected' : '' }}>{{ $m }}</option>
            @endforeach
        </select>

        <select name="severity" class="audit-input">
            <option value="">All Severities</option>
            @foreach(['critical','high','warning','information'] as $s)
                <option value="{{ $s }}" {{ request('severity') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>

        <select name="status" class="audit-input">
            <option value="">All Statuses</option>
            @foreach(['open','acknowledged','under_review','resolved','ignored','false_positive','reopened'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
            @endforeach
        </select>
    </div>
</form>

<div class="audit-toolbar audit-report-toolbar">
    <input class="audit-input audit-table-search" placeholder="Search visible rows" aria-label="Search visible report rows">
    <a class="audit-btn" href="{{ route('audit.reports.export',array_merge(request()->query(),['format'=>'csv'])) }}">CSV</a>
    <a class="audit-btn" href="{{ route('audit.reports.export',array_merge(request()->query(),['format'=>'excel'])) }}">Excel</a>
    <a class="audit-btn" href="{{ route('audit.reports.export',array_merge(request()->query(),['format'=>'pdf'])) }}">PDF</a>
    <a class="audit-btn" target="_blank" href="{{ route('audit.reports.export',array_merge(request()->query(),['format'=>'print'])) }}">Print</a>
    <button type="button" class="audit-btn audit-columns">Column Visibility</button>
</div>

<div class="audit-card audit-table-wrap audit-report-table-card">
    <table class="audit-table audit-report-table">
        <colgroup>
            <col class="audit-col-finding">
            <col class="audit-col-module">
            <col class="audit-col-rule">
            <col class="audit-col-severity">
            <col class="audit-col-status">
            <col class="audit-col-issue">
            <col class="audit-col-business">
            <col class="audit-col-location">
            <col class="audit-col-last-seen">
        </colgroup>
        <thead>
            <tr>
                <th>Finding No</th>
                <th>Module</th>
                <th>Rule</th>
                <th>Severity</th>
                <th>Status</th>
                <th>Issue</th>
                <th>Business</th>
                <th>Location</th>
                <th>Last Seen</th>
            </tr>
        </thead>
        <tbody>
        @forelse($rows as $r)
            <tr>
                <td>{{ $r->finding_no }}</td>
                <td>{{ $r->module }}</td>
                <td>{{ $r->rule_code }}</td>
                <td><span class="audit-badge {{ $r->severity }}">{{ ucfirst($r->severity) }}</span></td>
                <td><span class="audit-badge status-{{ $r->status }}">{{ ucwords(str_replace('_',' ',$r->status)) }}</span></td>
                <td class="audit-report-issue">{{ $r->title }}</td>
                <td>{{ $r->business_name }}</td>
                <td>{{ $r->location_name }}</td>
                <td>{{ $r->last_seen_display }}</td>
            </tr>
        @empty
            <tr><td colspan="9" class="audit-empty-cell">No audit findings match the selected filters.</td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $rows->links() }}
</div>
@endsection
