@extends('audit::layout')
@section('audit-title',$finding->finding_no)
@section('audit-content')
<div class="audit-grid-two">
 <div class="audit-card">
  <div class="audit-card-title">Finding Details</div>
  <dl class="audit-detail"><dt>Module</dt><dd>{{ $finding->module }}</dd><dt>Rule</dt><dd>{{ $finding->rule_code }}</dd><dt>Severity</dt><dd><span class="audit-badge {{ $finding->severity }}">{{ ucfirst($finding->severity) }}</span></dd><dt>Status</dt><dd>{{ ucwords(str_replace('_',' ',$finding->status)) }}</dd><dt>Source</dt><dd>{{ $finding->source_table }} #{{ $finding->source_id }}</dd><dt>Issue</dt><dd>{{ $finding->message }}</dd><dt>Expected</dt><dd>{{ $finding->expected_value ?: '—' }}</dd><dt>Actual</dt><dd>{{ $finding->actual_value ?: '—' }}</dd></dl>
 </div>
 <div class="audit-card"><div class="audit-card-title">Resolution Centre</div><p class="audit-note">The Audit module does not silently repair live data. Record the investigation status here; any operational correction should be carried out through the owning module.</p><form method="post" action="{{ route('audit.findings.status',$finding) }}">@csrf<select class="audit-input full" name="status">@foreach(['open','acknowledged','under_review','resolved','ignored','false_positive','reopened'] as $s)<option value="{{ $s }}" {{ $finding->status === $s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>@endforeach</select><textarea class="audit-input full" name="note" rows="5" placeholder="Investigation / resolution note"></textarea><button class="audit-btn primary">Save Status</button></form></div>
</div>
<div class="audit-card"><div class="audit-card-title">History</div><table class="audit-table"><thead><tr><th>Date</th><th>From</th><th>To</th><th>Note</th><th>User</th></tr></thead><tbody>@forelse($finding->history as $h)<tr><td>{{ $h->created_at }}</td><td>{{ $h->from_status ?: '—' }}</td><td>{{ $h->to_status }}</td><td>{{ $h->note }}</td><td>{{ $h->changed_by ?: 'System' }}</td></tr>@empty<tr><td colspan="5">No history.</td></tr>@endforelse</tbody></table></div>
@endsection
