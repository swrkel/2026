@extends('audit::central.layout')
@section('audit-title','Central Multi-Tenant Audit Reports')
@section('audit-content')
<div class="audit-cards audit-report-cards">
    <div class="audit-stat report-total"><span>Report Rows</span><strong>{{ number_format($summary['total']) }}</strong></div>
    <div class="audit-stat open"><span>Current / Open</span><strong>{{ number_format($summary['open']) }}</strong></div>
    <div class="audit-stat resolved"><span>Resolved</span><strong>{{ number_format($summary['resolved']) }}</strong></div>
    <div class="audit-stat report-false-positive"><span>False Positives</span><strong>{{ number_format($summary['false_positive']) }}</strong></div>
</div>

<form method="get" class="audit-card audit-central-filter-card">
    @include('audit::central.partials.scope_filters',['showDate'=>true,'showSearch'=>true])
    <div class="audit-central-secondary-filters">
        <select name="module" class="audit-input"><option value="">All Modules</option>@foreach($modules as $m)<option value="{{ $m }}" {{ request('module')===$m?'selected':'' }}>{{ $m }}</option>@endforeach</select>
        <select name="severity" class="audit-input"><option value="">All Severities</option>@foreach(['critical','high','warning','information'] as $s)<option value="{{ $s }}" {{ request('severity')===$s?'selected':'' }}>{{ ucfirst($s) }}</option>@endforeach</select>
        <select name="status" class="audit-input"><option value="">All Statuses</option>@foreach(['open','acknowledged','under_review','resolved','ignored','false_positive','reopened'] as $s)<option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>@endforeach</select>
        <button class="audit-btn primary" type="submit">Apply</button>
    </div>
</form>

@if(!empty($source_errors))@foreach($source_errors as $error)<div class="audit-alert danger"><strong>{{ $error['source_label'] }}</strong>: {{ $error['message'] }}</div>@endforeach@endif

<div class="audit-toolbar audit-report-toolbar">
    <input class="audit-input audit-table-search" placeholder="Search visible rows" aria-label="Search visible report rows">
    <a class="audit-btn" href="{{ url('/audit/reports/export/csv').'?'.http_build_query(request()->query()) }}">CSV</a>
    <a class="audit-btn" href="{{ url('/audit/reports/export/excel').'?'.http_build_query(request()->query()) }}">Excel</a>
    <a class="audit-btn" href="{{ url('/audit/reports/export/pdf').'?'.http_build_query(request()->query()) }}">PDF</a>
    <a class="audit-btn" target="_blank" href="{{ url('/audit/reports/export/print').'?'.http_build_query(request()->query()) }}">Print</a>
    <button type="button" class="audit-btn audit-columns">Column Visibility</button>
</div>

<div class="audit-card audit-table-wrap audit-report-table-card"><table class="audit-table audit-report-table audit-central-report-table"><thead><tr><th>Tenant / Data Source</th><th>Database</th><th>Finding No</th><th>Module</th><th>Rule</th><th>Severity</th><th>Status</th><th>Issue</th><th>Business</th><th>Location</th><th>Last Seen</th></tr></thead><tbody>
@forelse($rows as $r)<tr><td>{{ $r['source_label'] }}</td><td>{{ $r['database'] }}</td><td>{{ $r['finding_no'] }}</td><td>{{ $r['module'] }}</td><td>{{ $r['rule_code'] }}</td><td><span class="audit-badge {{ $r['severity'] }}">{{ ucfirst($r['severity']) }}</span></td><td><span class="audit-badge status-{{ $r['status'] }}">{{ ucwords(str_replace('_',' ',$r['status'])) }}</span></td><td class="audit-report-issue">{{ $r['title'] }}</td><td>{{ $r['business_name'] }}</td><td>{{ $r['location_name'] }}</td><td>{{ $r['last_seen_display'] }}</td></tr>@empty<tr><td colspan="11" class="audit-empty-cell">No audit findings match the selected central scope.</td></tr>@endforelse
</tbody></table>{{ $rows->links() }}</div>
@endsection
