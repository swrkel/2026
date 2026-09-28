@extends('ezylaw::layouts.module')
@section('ezylaw_title','EzyLaw Dashboard')
@section('ezylaw_content')
<div class="ezylaw-grid">
<div class="law-card"><span>Active Clients</span><strong>{{ number_format($clients) }}</strong></div>
<div class="law-card"><span>Active Matters</span><strong>{{ number_format($active_matters) }}</strong></div>
<div class="law-card"><span>Hearings - Next 7 Days</span><strong>{{ number_format($hearings_7d) }}</strong></div>
<div class="law-card"><span>Outstanding Receivables</span><strong>{{ number_format($receivables,2) }}</strong></div>
<div class="law-card"><span>Client Trust Balance</span><strong>{{ number_format($trust_balance,2) }}</strong></div>
<div class="law-card"><span>Reminders Due - 24 Hours</span><strong>{{ number_format($due_reminders) }}</strong></div>
<div class="law-card"><span>Active Retainers</span><strong>{{ number_format($active_retainers) }}</strong></div>
<div class="law-card"><span>Conflict Checks - Review</span><strong>{{ number_format($conflict_reviews) }}</strong></div><div class="law-card law-kpi-danger"><span>Overdue Legal Deadlines</span><strong>{{ number_format($overdue_deadlines) }}</strong></div>
<div class="law-card law-kpi-warning"><span>Open Trust Reconciliations</span><strong>{{ number_format($trust_reconcile_open) }}</strong></div>
<div class="law-card"><span>Unbilled Time</span><strong>{{ number_format($unbilled_time,2) }}</strong></div>
<div class="law-card"><span>Unbilled Expenses</span><strong>{{ number_format($unbilled_expenses,2) }}</strong></div>
<div class="law-card"><span>Pending Court Filings</span><strong>{{ number_format($pending_filings) }}</strong></div>
<div class="law-card"><span>Active Settlements</span><strong>{{ number_format($active_settlements) }}</strong></div>
<div class="law-card"><span>Client Advances Available</span><strong>{{ number_format($client_advances,2) }}</strong></div>
<div class="law-card"><span>Pending Document Approvals</span><strong>{{ number_format($pending_approvals) }}</strong></div>
<div class="law-card"><span>Pending E-Sign Requests</span><strong>{{ number_format($pending_esign) }}</strong></div>
</div>
<div class="row"><div class="col-md-7"><div class="box box-primary"><div class="box-header"><h3 class="box-title">Upcoming Hearings</h3></div><div class="box-body table-responsive"><table class="table table-bordered law-datatable"><thead><tr><th>Date/Time</th><th>Matter</th><th>Purpose</th><th>Status</th></tr></thead><tbody>@foreach($upcoming as $h)<tr><td>{{ optional($h->hearing_at)->format('Y-m-d H:i') }}</td><td>{{ optional($h->matter)->matter_no }} - {{ optional($h->matter)->title }}</td><td>{{ $h->purpose }}</td><td>{{ ucfirst($h->status) }}</td></tr>@endforeach</tbody></table></div></div></div>
<div class="col-md-5"><div class="box box-warning"><div class="box-header"><h3 class="box-title">Upcoming Reminders</h3></div><div class="box-body table-responsive"><table class="table table-bordered"><tr><th>When</th><th>Reminder</th></tr>@forelse($reminders as $r)<tr><td>{{ optional($r->remind_at)->format('Y-m-d H:i') }}</td><td>{{ $r->title }}<br><small>{{ optional($r->matter)->matter_no }}</small></td></tr>@empty<tr><td colspan="2">No pending reminders.</td></tr>@endforelse</table></div></div></div></div>
@endsection
