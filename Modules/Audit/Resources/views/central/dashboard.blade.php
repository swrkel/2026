@extends('audit::central.layout')
@section('audit-title','Central Audit Dashboard')
@section('audit-content')

<div class="audit-cards audit-central-cards">
    <div class="audit-stat latest"><span>Data Sources</span><strong>{{ number_format($summary['sources']) }}</strong></div>
    <div class="audit-stat"><span>Businesses</span><strong>{{ number_format($summary['businesses']) }}</strong></div>
    <div class="audit-stat"><span>Locations</span><strong>{{ number_format($summary['locations']) }}</strong></div>
    <div class="audit-stat open"><span>Current Findings</span><strong>{{ number_format($summary['current']) }}</strong></div>
    <div class="audit-stat critical"><span>Critical</span><strong>{{ number_format($summary['critical']) }}</strong></div>
    <div class="audit-stat warning"><span>Unavailable Sources</span><strong>{{ number_format($summary['unavailable']) }}</strong></div>
</div>

<form method="get" class="audit-card audit-central-filter-card">
    @include('audit::central.partials.scope_filters',['showDate'=>true,'showSearch'=>true])
</form>

@if(!empty($source_errors))
<div class="audit-card audit-source-errors">
    <div class="audit-card-title">Sources Needing Attention</div>
    @foreach($source_errors as $error)
        <div class="audit-alert danger"><strong>{{ $error['source_label'] }}</strong>: {{ $error['message'] }}</div>
    @endforeach
</div>
@endif

<div class="audit-card">
    <div class="audit-card-title">Database / Tenant Audit Status</div>
    <div class="audit-note" style="margin-bottom:10px">Central Database and every selected tenant are checked independently. A database that cannot be opened is reported here without stopping the remaining tenants.</div>
    <div class="audit-table-wrap">
        <table class="audit-table audit-central-status-table">
            <thead><tr><th>Tenant / Data Source</th><th>Database</th><th>Businesses</th><th>Locations</th><th>Latest Run</th><th>Latest Result</th><th>Current Findings</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($source_status as $row)
                <tr>
                    <td><strong>{{ $row['source_label'] }}</strong><br><span class="audit-note">{{ ucfirst($row['source_type']) }}</span></td>
                    <td>{{ $row['database'] ?: '—' }}</td>
                    <td class="num">{{ number_format($row['businesses']) }}</td>
                    <td class="num">{{ number_format($row['locations']) }}</td>
                    <td>{{ $row['latest_run'] ?: '—' }}</td>
                    <td>{{ $row['latest_status'] ? ucfirst($row['latest_status']).' / '.number_format($row['latest_findings']).' findings' : '—' }}</td>
                    <td class="num">{{ number_format($row['total']) }}</td>
                    <td>
                        @if($row['error'])<span class="audit-badge failed">Unavailable</span>
                        @elseif(!$row['audit_ready'])<span class="audit-badge warning">Audit Tables Missing</span>
                        @else<span class="audit-badge completed">Ready</span>@endif
                    </td>
                </tr>
                @if($row['error'])<tr class="audit-source-error-row"><td colspan="8">{{ $row['error'] }}</td></tr>@endif
            @empty
                <tr><td colspan="8" class="audit-empty-cell">No data sources match the selected scope.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="audit-card">
    <div class="audit-card-title">Recent Current Findings Across Selected Sources</div>
    <div class="audit-table-wrap">
        <table class="audit-table audit-report-table">
            <thead><tr><th>Tenant / Source</th><th>Finding</th><th>Module</th><th>Severity</th><th>Issue</th><th>Business</th><th>Location</th><th>Last Seen</th></tr></thead>
            <tbody>
            @forelse($recent as $row)
                <tr>
                    <td>{{ $row['source_label'] }}</td><td>{{ $row['finding_no'] }}</td><td>{{ $row['module'] }}</td>
                    <td><span class="audit-badge {{ $row['severity'] }}">{{ ucfirst($row['severity']) }}</span></td>
                    <td>{{ $row['title'] }}</td><td>{{ $row['business_name'] }}</td><td>{{ $row['location_name'] }}</td><td>{{ $row['last_seen_display'] }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="audit-empty-cell">No current findings in the selected central scope.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
