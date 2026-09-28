@extends('audit::layout')
@section('audit-title','Audit Dashboard')
@section('audit-content')
<form method="get" class="audit-card audit-filter-card">@include('audit::partials.filters')</form>
<div class="audit-cards">
 @foreach([['Current Findings',$summary['total'],'neutral'],['Critical',$summary['critical'],'critical'],['High',$summary['high'],'high'],['Warnings',$summary['warning'],'warning'],['Latest Run',$summary['latest_run'],'latest'],['Resolved History',$summary['resolved'],'resolved']] as $c)
 <div class="audit-stat {{ $c[2] }}"><span>{{ $c[0] }}</span><strong>{{ number_format($c[1]) }}</strong></div>
 @endforeach
</div>
<div class="audit-grid-two">
 <div class="audit-card"><div class="audit-card-title">Current Findings by Module</div><table class="audit-table"><thead><tr><th>Module</th><th class="num">Findings</th></tr></thead><tbody>@forelse($byModule as $r)<tr><td>{{ $r->module }}</td><td class="num">{{ number_format($r->total) }}</td></tr>@empty<tr><td colspan="2">No current findings for the selected period.</td></tr>@endforelse</tbody></table></div>
 <div class="audit-card"><div class="audit-card-title">Recent Audit Runs</div><table class="audit-table"><thead><tr><th>Run</th><th>Status</th><th class="num">Findings</th><th>Started</th></tr></thead><tbody>@forelse($runs as $r)<tr><td>{{ $r->run_no }}</td><td><span class="audit-badge {{ $r->status }}">{{ ucfirst($r->status) }}</span></td><td class="num">{{ number_format((int)data_get($r->summary,'findings',0)) }}</td><td>{{ optional($r->started_at)->format('d M Y H:i') }}</td></tr>@empty<tr><td colspan="4">No runs yet.</td></tr>@endforelse</tbody></table></div>
</div>
<div class="audit-card"><div class="audit-card-title">Current Findings</div><table class="audit-table"><thead><tr><th>Finding</th><th>Module</th><th>Severity</th><th>Issue</th><th>Status</th><th></th></tr></thead><tbody>@forelse($recent as $f)<tr><td>{{ $f->finding_no }}</td><td>{{ $f->module }}</td><td><span class="audit-badge {{ $f->severity }}">{{ ucfirst($f->severity) }}</span></td><td>{{ $f->title }}</td><td>{{ str_replace('_',' ',ucfirst($f->status)) }}</td><td><a class="audit-btn small" href="{{ route('audit.findings.show',$f) }}">Open</a></td></tr>@empty<tr><td colspan="6">No current findings.</td></tr>@endforelse</tbody></table></div>
@endsection
