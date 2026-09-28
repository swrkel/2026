@extends('audit::central.layout')
@section('audit-title','Central Audit Findings')
@section('audit-content')

@if(!empty($central_run_result))
<div class="audit-card audit-central-run-result">
    <div class="audit-card-title">Last Central Audit Execution</div>
    <div class="audit-cards audit-central-result-cards">
        <div class="audit-stat"><span>Jobs</span><strong>{{ number_format($central_run_result['jobs']) }}</strong></div>
        <div class="audit-stat resolved"><span>Completed</span><strong>{{ number_format($central_run_result['completed']) }}</strong></div>
        <div class="audit-stat warning"><span>Skipped</span><strong>{{ number_format($central_run_result['skipped']) }}</strong></div>
        <div class="audit-stat critical"><span>Failed Safely</span><strong>{{ number_format($central_run_result['failed']) }}</strong></div>
        <div class="audit-stat open"><span>Findings</span><strong>{{ number_format($central_run_result['findings']) }}</strong></div>
    </div>
    <details><summary>Show run-by-run result</summary>
        <div class="audit-table-wrap"><table class="audit-table"><thead><tr><th>Source</th><th>Scope</th><th>Run</th><th>Status</th><th>Findings</th><th>Message</th></tr></thead><tbody>
        @foreach($central_run_result['results'] as $r)<tr><td>{{ $r['source_label'] }}</td><td>{{ $r['scope_label'] }}</td><td>{{ $r['run_no'] ?: '—' }}</td><td>{{ $r['status'] }}</td><td>{{ $r['findings'] }}</td><td>{{ $r['message'] ?: '—' }}</td></tr>@endforeach
        </tbody></table></div>
    </details>
</div>
@endif

<div class="audit-cards audit-report-cards">
    <div class="audit-stat open"><span>Current / Open</span><strong>{{ number_format($summary['open']) }}</strong></div>
    <div class="audit-stat critical"><span>Critical</span><strong>{{ number_format($summary['critical']) }}</strong></div>
    <div class="audit-stat high"><span>High</span><strong>{{ number_format($summary['high']) }}</strong></div>
    <div class="audit-stat warning"><span>Warnings</span><strong>{{ number_format($summary['warning']) }}</strong></div>
</div>

<form method="get" class="audit-card audit-central-filter-card">
    @include('audit::central.partials.scope_filters',['showDate'=>true,'showSearch'=>true])
    <div class="audit-central-secondary-filters">
        <select name="module" class="audit-input"><option value="">All Modules</option>@foreach($modules as $m)<option value="{{ $m }}" {{ request('module')===$m?'selected':'' }}>{{ $m }}</option>@endforeach</select>
        <select name="severity" class="audit-input"><option value="">All Severities</option>@foreach(['critical','high','warning','information'] as $s)<option value="{{ $s }}" {{ request('severity')===$s?'selected':'' }}>{{ ucfirst($s) }}</option>@endforeach</select>
        <button class="audit-btn primary" type="submit">Apply</button>
    </div>
</form>

@if(!empty($source_errors))
    @foreach($source_errors as $error)<div class="audit-alert danger"><strong>{{ $error['source_label'] }}</strong>: {{ $error['message'] }}</div>@endforeach
@endif

<div class="audit-card audit-table-wrap">
<table class="audit-table audit-report-table audit-central-findings-table">
<thead><tr><th>Tenant / Source</th><th>Finding</th><th>Module</th><th>Rule</th><th>Severity</th><th>Status</th><th>Issue</th><th>Business</th><th>Location</th><th>Last Seen</th></tr></thead>
<tbody>
@forelse($rows as $r)
<tr><td>{{ $r['source_label'] }}</td><td>{{ $r['finding_no'] }}</td><td>{{ $r['module'] }}</td><td>{{ $r['rule_code'] }}</td><td><span class="audit-badge {{ $r['severity'] }}">{{ ucfirst($r['severity']) }}</span></td><td><span class="audit-badge status-{{ $r['status'] }}">{{ ucwords(str_replace('_',' ',$r['status'])) }}</span></td><td>{{ $r['title'] }}</td><td>{{ $r['business_name'] }}</td><td>{{ $r['location_name'] }}</td><td>{{ $r['last_seen_display'] }}</td></tr>
@empty<tr><td colspan="10" class="audit-empty-cell">No current audit findings match the selected central scope.</td></tr>@endforelse
</tbody></table>
{{ $rows->links() }}
</div>
@endsection
