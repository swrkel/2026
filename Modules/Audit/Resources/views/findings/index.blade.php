@extends('audit::layout')
@section('audit-title','Audit Findings')
@section('audit-content')
<form method="get" class="audit-card">
 <div class="audit-filter-row">
  <input name="search" value="{{ request('search') }}" class="audit-input" placeholder="Finding no / rule / issue">
  <select name="module" class="audit-input"><option value="">All Modules</option>@foreach($modules as $m)<option value="{{ $m }}" {{ request('module') === $m ? 'selected' : '' }}>{{ $m }}</option>@endforeach</select>
  <select name="severity" class="audit-input"><option value="">All Severities</option>@foreach(['critical','high','warning','information'] as $s)<option value="{{ $s }}" {{ request('severity') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>@endforeach</select>
  <select name="status" class="audit-input"><option value="">All Statuses</option>@foreach(['open','acknowledged','under_review','resolved','ignored','false_positive','reopened'] as $s)<option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>@endforeach</select>
  <button class="audit-btn primary">Filter</button>
 </div>
</form>
<div class="audit-card audit-table-wrap"><table class="audit-table"><thead><tr><th>Finding No</th><th>Module</th><th>Rule</th><th>Severity</th><th>Issue</th><th>Status</th><th>Last Seen</th><th>Action</th></tr></thead><tbody>
@forelse($findings as $f)<tr><td>{{ $f->finding_no }}</td><td>{{ $f->module }}</td><td>{{ $f->rule_code }}</td><td><span class="audit-badge {{ $f->severity }}">{{ ucfirst($f->severity) }}</span></td><td>{{ $f->title }}</td><td>{{ ucwords(str_replace('_',' ',$f->status)) }}</td><td>{{ optional($f->last_seen_at)->format('d M Y H:i') }}</td><td><a href="{{ route('audit.findings.show',$f) }}" class="audit-btn small">View</a></td></tr>@empty<tr><td colspan="8">No findings match these filters.</td></tr>@endforelse
</tbody></table><div class="audit-pagination">{{ $findings->links() }}</div></div>
@endsection
