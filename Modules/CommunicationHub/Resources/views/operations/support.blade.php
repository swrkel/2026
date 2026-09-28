@extends('communicationhub::layout')

@section('title', 'Communication Hub Operations Support')

@section('content')
<div class="communication-hub-page">
    <div class="ch-page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h3 class="mb-1">Operations Support Centre</h3>
            <p class="text-muted mb-0">Final production support checklist for tenant rollout, table checks, queue status and provider readiness.</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('communicationhub.quality.index') }}" class="btn btn-outline-primary btn-sm">Production QA</a>
            <a href="{{ route('communicationhub.diagnostics.index') }}" class="btn btn-outline-secondary btn-sm">Diagnostics</a>
            <a href="{{ route('communicationhub.readiness.index') }}" class="btn btn-outline-success btn-sm">Readiness</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card shadow-sm border-0"><div class="card-body"><div class="text-muted small">Tables Checked</div><h4>{{ $tableStatus->count() }}</h4></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm border-0"><div class="card-body"><div class="text-muted small">Missing Tables</div><h4>{{ $tableStatus->where('exists', false)->count() }}</h4></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm border-0"><div class="card-body"><div class="text-muted small">Queue Groups</div><h4>{{ collect($queueSummary)->count() }}</h4></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm border-0"><div class="card-body"><div class="text-muted small">Provider Groups</div><h4>{{ collect($providerSummary)->count() }}</h4></div></div></div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white"><strong>Tenant Rollout Order</strong></div>
        <div class="card-body">
            <ol class="mb-0">
                <li>Back up the tenant database before running Communication Hub SQL.</li>
                <li>Run the latest master SQL in each tenant database, or run only <code>20_OPERATIONS_SUPPORT_AND_FINAL_SIGNOFF.sql</code> if previous stages are already applied.</li>
                <li>Clear route/config/view cache after upload.</li>
                <li>Open Readiness Check, Diagnostics Centre, Production QA, then this Operations Support Centre.</li>
                <li>Send one test SMS/Email/WhatsApp/Push/In-App message from one selected business and verify reports.</li>
                <li>Enable automation/workflow rules only after manual sending and queue processing are confirmed.</li>
            </ol>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white"><strong>Required Table Status</strong></div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>Table</th><th class="text-end">Status</th></tr></thead>
                        <tbody>
                        @foreach($tableStatus as $row)
                            <tr>
                                <td><code>{{ $row['table'] }}</code></td>
                                <td class="text-end">
                                    @if($row['exists']) <span class="badge bg-success">OK</span> @else <span class="badge bg-danger">Missing</span> @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white"><strong>Operational Sign-Off Checklist</strong></div>
                <div class="card-body">
                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" disabled> <label class="form-check-label">Menu visible for permitted users</label></div>
                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" disabled> <label class="form-check-label">Business filter correctly limits records</label></div>
                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" disabled> <label class="form-check-label">Provider credentials saved per business</label></div>
                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" disabled> <label class="form-check-label">Queue processes pending and failed messages</label></div>
                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" disabled> <label class="form-check-label">Delivery reports and audit logs update</label></div>
                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" disabled> <label class="form-check-label">Automation rules are disabled by default until tested</label></div>
                    <div class="form-check mb-0"><input class="form-check-input" type="checkbox" disabled> <label class="form-check-label">No cross-business data visible from another business login</label></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
